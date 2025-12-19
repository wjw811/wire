<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename User.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class User extends \Next\Core\Control {

    private $u;
    private $t;

/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->u = $this->app->user;
        $this->t = $this->app->token;
    }
/*}}}*/

/*{{{ index */
    public function index() {
        $m = new \app\model\User();

        $name = $this->params('name');
        $phone = $this->params('phone');
        $status = $this->params('status');
        $page = $this->params('page', 1);
        $size = $this->params('size', 12);

        $data = [];
        $w = [];
        if ($name) {
            $w['OR'] = [
                'name[~]'  => $name,
                'nick[~]'  => $name,
            ];
        }
        if ($phone) {
            $w['OR'] = [
                'id'    => $phone,
                'phone' => $phone,
            ];
        }
        if ($tmp = $this->params('time')) {
            if (is_array($tmp) && count($tmp) > 1) {
                $start = date('Y-m-d 00:00:00', strtotime($tmp[0]));
                $end = date('Y-m-d 23:59:59', strtotime($tmp[1]));
                $w['created[<>]'] = [$start, $end];
            }
        }
        if ($status) {
            $w['status'] = $status;
        } else {
            $w['status[!]'] = 9;
        }

        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            if ($data = $m->select(['id', 'name', 'nick', 'phone', 'sex', 'avatar', 'area', 'status', 'recharge', 'refund', 'wallet','created'], $w)) {
                foreach ($data as &$row) {
                    $row['avatar'] = $this->app->common->buildImg($row['id'], $row['avatar']);
                    $row['phone'] = $this->app->common->phone($row['phone']);
                }
            }
        }

        $this->json(0, '', ['total' => $total, 'items' => $data]);
    }
/*}}}*/

}
