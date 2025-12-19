<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Home.php
* @touch date Tue 16 Sep 2025 00:00:00 AM CST
* @author: System
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Home extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        // Redirect to frontend SPA entry under static files
        header('Location: /static/admin/');
        exit;
    }
/*}}}*/

}


