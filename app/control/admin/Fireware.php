<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Fireware.php
* @touch date Sat 18 Mar 2017 12:25:53 PM CST
* @author: Fred<fred@api4.me>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\admin;

class Fireware extends \Next\Core\Control {

    private $u;
    private $t;

/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->u = $this->app->user;
        $this->t = $this->app->token;

        // TODO
        // $this->u['id'] = 1;
        // $this->u['role'] = 'agency';
    }
/*}}}*/

/*{{{ index */
    public function index() {
        $m = new \app\model\Fireware();

        $name = $this->params('name');
        $ver = $this->params('ver');
        $status = $this->params('status');
        $page = $this->params('page', 1);
        $size = $this->params('size', 12);

        $data = [];
        $w = [];
        if ($name) {
            $w['name[~]'] = $name;
        }
        if ($ver) {
            $w['ver[~]'] = $ver;
        }
        if ($status !== null) {
            $w['status'] = $status;
        } else {
            $w['status[!]'] = 9;
        }

        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            $data = $m->select(['id[Int]', 'name', 'ver', 'file', 'size', 'status[Int]', 'checksum', 'created'], $w);
        }

        $this->json(0, '', ['total' => $total, 'items' => $data]);
    }
/*}}}*/
/*{{{ add */
    public function add() {
        $this->save();
    }
/*}}}*/
/*{{{ edit */
    public function edit() {
        $this->save();
    }
/*}}}*/
/*{{{ save */
    public function save() {
        $m = new \app\model\Fireware();

        $id = $this->params('id');
        $name = $this->params('name');
        $ver = $this->params('ver');
        $file = $this->params('file');
        $size = $this->params('size');
        $status = $this->params('status');


        if (!$id) {
            if (!$file) {
                $this->json(4001, '请上传固件');
            }

            $config = $this->app->config('upload');
            $checksum = md5_file(sprintf('%shex/%s', $config['save_path'],  $file));
            if ($m->has(['checksum' => $checksum, 'id[!]' => $id, 'status[!]' => 9])) {
                $this->json(4001, '固件已经存在');
            }
            $a = [
                'name'     => $name,
                'ver'      => $ver,
                'file'     => $file,
                'size'     => $size,
                'checksum' => $checksum,
                'status'   => $status == 1? 1: 0,
                'created'  => $m->raw('now()'),
                'updated'  => $m->raw('now()'),
            ];
            if (!$m->insert($a)->rowCount()) {
                $this->json(4001, '添加失败');
            }

            $this->json(0, '添加成功');
        }

        $u = [
            'name'     => $name,
            'ver'      => $ver,
            'status'   => $status == 1? 1: 0,
            'updated'  => $m->raw('now()'),
        ];
        $w = [
            'id' => $id,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '保存失败');
        }

        $this->json(0, '保存成功');
    }
/*}}}*/
/*{{{ delete */
    public function delete() {
        $id = $this->params('id');

        $m = new \app\model\Fireware();
        $u = [
            'status'  => 9,
            'updated' => $m->raw('now()'),
        ];
        $w = [
            'id' => $id,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '删除失败，请重新查询页面再试');
        }
        $this->json(0, '删除成功');
    }
/*}}}*/

/*{{{ option */
    public function option() {
        $data = [];

        $m = new \app\model\Fireware();
        if ($tmp = $m->select(['id[Int]', 'name', 'ver'], ['status[!]' => 9, 'ORDER' => ['ver' => 'DESC']])) {
            foreach ($tmp as $row) {
                $data[] = [
                    'id'   => $row['id'],
                    'name' => sprintf('%s(V%s)', $row['name'], $row['ver']),
                ];
            }
        }

        $this->json(0, '', $data);
    }
/*}}}*/

}
