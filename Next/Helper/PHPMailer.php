<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename PHPMailer.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class PHPMailer {

/*{{{ variable*/
    private $mail;
    private $app;
/*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct($path = null) {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR .'PHPMailerAutoload.php';
 
        $this->app = \Slim\Slim::getInstance();
         
        $config = $this->app->config("mail");
        $mail = new \PHPMailer();

        $mail->isSMTP();
        $mail->Host = $config["host"];
        $mail->SMTPAuth = $config["smtp_auth"];
        $mail->Username = $config["username"];
        $mail->Password = $config["pwd"];
        $mail->SMTPSecure = $config["smtp_secure"];
        $mail->Port = $config["port"];
        $mail->setFrom($config["from"], $config["from_name"]);

        $this->mail = $mail;
    }
/*}}}*/

/*{{{ __get */
    public function __get($name) {
        return property_exists($this->mail, $name)? $this->mail->$name: null;
    }
/*}}}*/
/*{{{ __set */
    public function __set($name, $val) {
        $this->mail->$name = $val;
    }
/*}}}*/
/*{{{ __call */
    public function __call($method, $args) {
        if (method_exists($this->mail, $method)) {
            return call_user_func_array(array($this->mail, $method), $args);
        }

        return false;
    }
/*}}}*/
/*{{{ destruct */
    public function __destruct() {
        if ($this->mail) {
            $this->mail = null;
        }
    }
/*}}}*/

}
