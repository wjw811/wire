<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Verify.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Verify {

/*{{{ variable*/
    private $verify;
    private $app;
/*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct($path = null) {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Verify' . DIRECTORY_SEPARATOR .'Verify.php';
 
        $this->app = \Slim\Slim::getInstance();
         
        $this->verify = new \Next\Helper\Verify\Verify();
    }
/*}}}*/
/*{{{ __call */
    public function __call($method, $args) {
        if (method_exists($this->jwt, $method)) {
            return call_user_func_array(array($this->jwt, $method), $args);
        }

        return false;
    }
/*}}}*/

}
