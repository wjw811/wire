<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename AuthRole.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class AuthRole extends \Next\Core\Model {
    protected $table = 's_user_role';

/*{{{ isAdmin */
    public function isAdmin($code) {
        if ($code == 'super') {
            return true;
        }
        $role = $this->select('role_code', ['user_code' => $code]);
        if ($role && in_array('admin', $role)) {
            return true;
        }
        return false;
    }
/*}}}*/
}
