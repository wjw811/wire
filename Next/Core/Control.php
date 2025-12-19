<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Control.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Core;

abstract class Control{

/*{{{ variable*/
    protected $app;
    protected $common;
    protected $view;
/*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct() {
        $this->app = \Slim\Slim::getInstance();
        $this->common = array(
            "config" => $this->app->config('common'),
            "user" => $this->app->session->get("user"),
            "uri" => $this->app->request->getPathInfo(),
        );    

        $this->view = new \Next\Helper\Twig();
    }
/*}}}*/
/*{{{ params */
    public function params($key = null, $default = null) {
        $get = ($tmp = $this->app->request->get())? $tmp: [];
        $post = ($tmp = $this->app->request->post())? $tmp: [];
        $body = ($tmp = json_decode($this->app->request->getBody(), true)) ? $tmp: [];

        $union = array_merge($get, $post, $body);
        if ($key) {
            return isset($union[$key]) ? $union[$key] : $default;
        }
        return $union;
    }
/*}}}*/
/*{{{ render */
    public function render($template, $data = array()) {
        if (!isset($data['common'])) {
            $data['common'] = $this->common;
        }
        return $this->view->render($template, $data);
    }
/*}}}*/
/*{{{ display */
    public function display($template, $data = array()) {
        if (!isset($data['common'])) {
            $data['common'] = $this->common;
        }
        $this->view->display($template, $data);
    }
/*}}}*/
/*{{{ rendJSON */
    public function rendJSON($data) {
        // 统一直接输出并退出，规避某些环境下响应体为空的问题
        // fix json_encode float issue
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', 14);
        }
        // 清空可能残留的输出缓冲
        while (ob_get_level() > 0) { @ob_end_clean(); }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: close');
        header('Content-Length: ' . strlen($json));
        echo $json;
        flush();
        exit;
    }
/*}}}*/
/*{{{ json */
    public function json($code, $msg, $data = null) {
        $i18n = $this->app->i18n;
        if (isset($i18n[$msg])) {
            $msg = $i18n[$msg];
        }
        $out = [
            'code'    => $code,
            'type'    => $code == 0? 'success': 'error',
            'message' => $msg,
        ];
        if (isset($data)) {
            $out['result'] = $data;
        }
        $this->rendJSON($out);
    }
/*}}}*/

}
