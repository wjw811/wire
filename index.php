<?php
define('IN_NEXT', 1);
ini_set('date.timezone','Asia/Shanghai');

// Suppress deprecated/notice warnings for legacy Slim on PHP 8.1+
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
// But log all errors including deprecations to log file
ini_set('log_errors', '1');
ini_set('display_errors', '0'); // Don't display errors in output
// PHP 8+ removed get_magic_quotes_gpc, Slim 2 still calls it
if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() { return 0; }
}
// Built-in server: serve static files and directory index.html directly
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $full = __DIR__ . $path;
    if (is_file($full)) {
        return false;
    }
    if (is_dir($full)) {
        $index = rtrim($full, "/\\") . DIRECTORY_SEPARATOR . 'index.html';
        if (is_file($index)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($index);
            exit;
        }
    }
}
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
if (!file_exists('config.php')) {
    die('no config file.');
}

require 'config.php';
$app = new \Slim\Slim($setting);

// Set config into app container
foreach ($config as $key => $val) {
    $app->config($key, $val);
}
// Autorun middleware or
if (isset($config['auto'])) {
    if (isset($config['auto']['helper'])) {
        foreach ($config['auto']['helper'] as $val) {
            $helper = '\\Next\Helper\\' . ucfirst($val);
            $app->container->singleton($val, function() use ($app, $helper) {
                return new $helper();
            });
        }
    }
    if (isset($config['auto']['middleware'])) {
        foreach ($config['auto']['middleware'] as $val) {
            $mid = '\\Next\Middleware\\' . ucfirst($val);
            $app->add(new $mid()); 
        }
    }
    if (isset($config['auto']['hook'])) {
        foreach ($config['auto']['hook'] as $val) {
            $name = 'Next' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . strtolower($val) . '.php';
            require($name);
        }
    }
}

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
$route = function($app) {
    $url = trim(urldecode($app->request->getResourceUri()), " \t\n\r\0\x0B//");
    
    // 调试信息
    error_log("URL: $url");
    error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    
    $uris = explode('/', $url);
    $dir = array_shift($uris);
    
    // 调试信息
    error_log("Dir: $dir");
    if ($dir == '~') {
        $name = array_shift($uris);
        $name = sprintf('app/control/%s.php', strtolower($name));
        if (file_exists($name)) {
            require($name);
        }

        return;
    }

    if ($dir == '') {
        // Site default
        $r = $app->config("route");
        if ($r["module"]) {
            $dir = $r["module"];
            $patten = '/';
        }
    }
    

    $dir = strtolower(preg_replace('/[^a-zA-Z]/', '', $dir));
    
    // 调试信息
    error_log("Processing route: dir=$dir, url=$url");
    
    if (is_dir(sprintf('app/control/%s', $dir))) {
        if (!isset($patten)) {
            // Match one or more segments for routes like /admin/auth/login
            $patten = sprintf('/%s/(:name+)', $dir);
        }
        
        // 调试信息
        error_log("Route pattern: $patten");

        // Set auth - 恢复正常鉴权
        require('app/control/auth.php');
        if (isset($auth[$dir])) {
            $mw = $auth[$dir];
        } else {
            $mw = function() {};
        }

        $app->map($patten, $mw, function($name = array()) use($app, $dir) {
            $tmp = implode('/', $name);
            $pos = strpos($tmp, '?');
            if ($pos !== false) {
                $tmp = substr($tmp, 0, $pos);
            }
            $name = explode('/', $tmp);

            $control = (count($name) > 0 && $name[0])? $name[0]: 'home';
            $action = (count($name) > 1 && $name[1])? $name[1]: 'index';
            
            // 调试信息
            error_log("Route matched: dir=$dir, control=$control, action=$action");

            $arr = explode('-', $control);
            $arr = array_map(function($row) {
                return ucfirst(strtolower($row));
            }, $arr);
            $control = implode('', $arr);
            if (file_exists(sprintf('./app/control/%s/%s.php', $dir, $control))) {
                // Ensure controller file is loaded before instantiation on non-composer autoload
                $controllerFile = sprintf('./app/control/%s/%s.php', $dir, $control);
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                }
                $class = sprintf('\\app\control\\%s\\%s', $dir, $control);
                $obj = new $class($app);

                $action = str_replace('-', '', $action);
                if (!method_exists($obj, '__call') && !method_exists($obj, $action)) {
                    if ($app->config('debug')) {
                        throw new RuntimeException(sprintf('There is not %s method in app/control/%s/%s.php file.', $action, $dir, $control));
                    } else {
                        $app->notFound();
                    }
                }

                $obj->$action();
                return;
            }

            if ($app->config('debug')) {
                throw new RuntimeException(sprintf('There is not app/control/%s/%s.php file.', $dir, $control));
            } else {
                $app->notFound();
            }

        })->via('GET', 'POST');

        // Also handle path without trailing slash, e.g., /admin
        $app->map(sprintf('/%s', $dir), $mw, function() use($app, $dir) {
            $control = 'home';
            $action = 'index';
            $arr = explode('-', $control);
            $arr = array_map(function($row) {
                return ucfirst(strtolower($row));
            }, $arr);
            $control = implode('', $arr);
            
            // 调试信息
            error_log("Controller: $control, Action: $action");
            
            if (file_exists(sprintf('./app/control/%s/%s.php', $dir, $control))) {
                $controllerFile = sprintf('./app/control/%s/%s.php', $dir, $control);
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                }
                $class = sprintf('\\app\\control\\%s\\%s', $dir, $control);
                
                // 调试信息
                error_log("Creating class: $class");
                
                $obj = new $class($app);
                $action = str_replace('-', '', $action);
                if (!method_exists($obj, '__call') && !method_exists($obj, $action)) {
                    if ($app->config('debug')) {
                        throw new RuntimeException(sprintf('There is not %s method in app/control/%s/%s.php file.', $action, $dir, $control));
                    } else {
                        $app->notFound();
                    }
                }
                
                // 调试信息
                error_log("Calling method: $action");
                
                $obj->$action();
                return;
            }
            if ($app->config('debug')) {
                throw new RuntimeException(sprintf('There is not app/control/%s/%s.php file.', $dir, $control));
            } else {
                $app->notFound();
            }
        })->via('GET', 'POST');
    }
};
$route($app);

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
