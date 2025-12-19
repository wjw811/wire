<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Sync.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\rpc;

class Sync extends \Next\Core\Control {

/*{{{ construct */
    public function __construct() {
        parent::__construct();
    }
/*}}}*/

/*{{{ index */
    public function index() {
        $this->resp(0, 'sync.index > invoke success');
    }
/*}}}*/

}
