<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Redis.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Redis {

/*{{{ variable*/
    private $connect;
    private $redis;
    private $app;
    /*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct() {
        if (!extension_loaded('redis')) {
            // Fallback: provide a minimal null-impl to keep app running without redis
            $this->redis = new class {
                public function get($k) { return null; }
                public function set($k, $v) { return true; }
                public function expire($k, $s) { return true; }
                public function hGetAll($k) { return []; }
                public function hGet($k, $f) { return null; }
                public function hMSet($k, $arr = []) { return true; }
                public function setTimeout($k, $s) { return true; }
                public function keys($p) { return []; }
                public function close() { return true; }
            };
            return;
        }

        $this->app = \Slim\Slim::getInstance();
        $config = $this->app->config('redis');

        $redis = new \Redis();
        if (!$config['pconnected']) {
            if (!$redis->connect($config['host'], $config['port'], $config['timeout'], $config['reserved'])) {
                throw new \Exception('Could not connect to Redis at ' . $config['host'] . ':' . $config['port']);
            }
            $this->connect = true;
        } else {
            if (!$redis->pconnect($config['host'], $config['port'], $config['timeout'], $config['reserved'])) {
                throw new \Exception('Could not pconnect to Redis at ' . $config['host'] . ':' . $config['port']);
            }
        }

        if ($config['password']) {
            if (!$redis->auth($config['password'])) {
                throw new \Exception('Could not connect to Redis, invalid password');
            }
        }
        $this->redis = $redis;
    }
/*}}}*/
/*{{{ __call */
    public function __call($method, $args) {
        if (method_exists($this->redis, $method)) {
            return call_user_func_array(array($this->redis, $method), $args);
        }

        return false;
    }
/*}}}*/
/*{{{ destruct */
    public function __destruct() {
        if ($this->connect) {
            $this->redis->close();
        }
    }
/*}}}*/

}
