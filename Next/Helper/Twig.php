<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Twig.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Twig {

/*{{{ variable*/
    private $twig;
    private $app;
/*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct($path = null) {
        $this->app = \Slim\Slim::getInstance();
         
        if (!class_exists('\Twig_Autoloader')) {
            require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Twig' . DIRECTORY_SEPARATOR .'Autoloader.php';
        }
        \Twig_Autoloader::register();
 
        if (!$path) {
            $path = $this->app->view->getTemplatesDirectory();
        }
        $loader = new \Twig_Loader_Filesystem($path);
        $config = $this->app->config('twig');
        $this->twig = new \Twig_Environment($loader, array(
            'cache' => $config['cache'],
            'debug' => $this->app->config('debug'),
        ));
        
        foreach(get_defined_functions() as $functions) {
            foreach($functions as $function) {
                $this->twig->addFunction($function, new \Twig_Function_Function($function));
            }
        }
    }
/*}}}*/
/*{{{ render */
    public function render($template, $data) {
        ob_clean();
        $template = $this->twig->loadTemplate($template);
        return $template->render($data);
    }
/*}}}*/
/*{{{ display */
    public function display($template, $data) {
        ob_clean();
        $template = $this->twig->loadTemplate($template);
        return $template->display($data);
    }
/*}}}*/

}
