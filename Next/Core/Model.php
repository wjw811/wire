<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Model.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Core;

abstract class Model {

/*{{{ variable*/
    protected $app;
    protected $db;
    protected $table;
/*}}}*/
/*{{{ construct */
    public function __construct() {
        $this->app = \Slim\Slim::getInstance();

        if (!isset($this->app->db)) {
           throw new \Exception('Please Load db before.');
        }
        $this->db = $this->app->db;
    }
/*}}}*/

/*{{{ __call */
    public function __call($method, $args) {
        if (!$this->table) {
           throw new \Exception('Please set protected property "table" in model class.');
        }

        $map = [
            'add' => 'insert',
            'up' => 'update',
            'del' => 'delete',
        ];
        if (isset($map[$method])) {
            $method = $map[$method];
        }

        $arr = ['select', 'insert', 'update', 'delete', 'replace', 'get', 'has', 'avg', 'count', 'max', 'min', 'sum'];
        if (in_array($method, $arr)) {
            array_unshift($args, $this->table);
            return call_user_func_array([$this->db, $method], $args);
        }

        $arr = ['raw', 'log', 'error', 'debug', 'id', 'query'];
        if (in_array($method, $arr)) {
            return call_user_func_array([$this->db, $method], $args);
        }

        return false;
    }
/*}}}*/

}
