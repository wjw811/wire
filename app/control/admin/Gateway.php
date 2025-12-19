<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Gateway.php
* @touch date Sat 18 Mar 2017 12:25:53 PM CST
* @author: Fred<fred@api4.me>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\admin;

class Gateway extends \Next\Core\Control {

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
        $m = new \app\model\Gateway();

        $name  = $this->params('name');
        $fid   = $this->params('fid');
        $status= $this->params('status');
        $page  = $this->params('page', 1);
        $size  = $this->params('size', 12);

        $data = [];
        $w = [];

        // role filter
        $ma = new \app\model\AuthRole();
        $isAdmin = $ma->isAdmin($this->u['code']);
        // 对于demo-token用户，也允许访问所有网关
        if (!$isAdmin && (!isset($this->t) || $this->t !== 'demo-token')) {
            $w['uid'] = $this->u['id'];
        }

        if ($name) {
            $w['OR'] = [
                'name[~]'   => $name,
                'serial[~]' => $name,
            ];
        }
        if ($fid) {
            $w['fid'] = $fid;
        }
        if ($status !== '' && $status !== null) {
            $w['status'] = $status;
        } else {
            $w['status[!]'] = 9;
        }

        $total = 0;
        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            if ($tmp = $m->select(['id', 'name', 'serial', 'uid', 'addr', 'fid', 'upgrade', 'status', 'created'], $w)) {
                foreach ($tmp as &$row) {
                    $row['fid'] = $row['fid'] == 0 ? null : (int)$row['fid'];
                    $row['upgrade'] = $row['upgrade'] === null ? null : (int)$row['upgrade'];
                    
                    // 将网关序列号从HEX转换为ASCII显示（安全版本）
                    if ($row['serial'] && strlen($row['serial']) % 2 == 0 && ctype_xdigit($row['serial'])) {
                        try {
                            $ascii = '';
                            for ($i = 0; $i < strlen($row['serial']); $i += 2) {
                                $hexByte = substr($row['serial'], $i, 2);
                                $decValue = hexdec($hexByte);
                                // 只转换可打印字符
                                if ($decValue >= 32 && $decValue <= 126) {
                                    $ascii .= chr($decValue);
                                } else {
                                    // 如果包含非可打印字符，保持原样
                                    $ascii = $row['serial'];
                                    break;
                                }
                            }
                            if ($ascii !== $row['serial']) {
                                $row['serial'] = $ascii;
                            }
                        } catch (\Throwable $e) {
                            // 转换失败时保持原样
                            error_log("ASCII conversion error: " . $e->getMessage());
                        }
                    }
                    
                    try { $row['dev'] = $this->dev($row['id']); } catch (\Throwable $e) { $row['dev'] = []; }
                    try { $row['state'] = $this->app->redis->exists(sprintf('gw:%s', $row['id'])) ? 'on' : 'off'; } catch (\Throwable $e) { $row['state'] = 'off'; }
                    try { $row['fireware'] = $this->fireware($row['fid']); } catch (\Throwable $e) { $row['fireware'] = '-'; }
                }
                $data = $tmp;
            }
        }

        $this->json(0, '', ['total' => (int)$total, 'items' => $data]);
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
        $m = new \app\model\Gateway();

        $id     = $this->params('id');
        $name   = $this->params('name');
        $serial = $m->hex($this->params('serial'));
        $addr   = $this->params('addr');
        $fid    = $this->params('fid');
        $status = $this->params('status', 1);

        // 如果发现编号被已删除的网关占用，先彻底删除旧记录腾出位置
        if (!$id) {
            $m->delete(['serial' => $serial, 'status' => 9]);
        }

        if ($m->has(['serial' => $serial, 'id[!]' => $id, 'status[!]' => 9])) {
            $this->json(4001, '编号已经存在');
        }

        if (!$id) {
            $a = [
                'name'    => $name,
                'serial'  => $serial,
                'addr'    => $addr,
                'uid'     => $this->u['id'],
                'fid'     => $fid,
                'upgrade' => $fid? 0: null,
                'status'  => $status == 1? 1: 0,
                'created' => $m->raw('now()'),
                'updated' => $m->raw('now()'),
            ];
            if (!$m->insert($a)->rowCount()) {
                $this->json(4001, '添加失败');
            }

            // make proto for dev service
            $m = new \app\model\Proto();
            $m->make();
            // clear cache
            $key = 'g.gateway';
            $this->app->redis->del($key);

            $this->json(0, '添加成功');
        }

        $u = [
            'name'    => $name,
            'serial'  => $serial,
            'addr'    => $addr,
            'fid'     => $fid,
            'status'  => $status == 1? 1: 0,
            'updated' => $m->raw('now()'),
        ];
        $ofid = $m->get('fid[Int]', ['id' => $id]);
        if ($fid != $ofid) {
            $u['upgrade'] = $fid? 0: null;
        }
        $w = [
            'id' => $id,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '保存失败');
        }

        // make proto for dev service
        $m = new \app\model\Proto();
        $m->make();
        // clear cache
        $key = 'g.gateway';
        $this->app->redis->del($key);

        $this->json(0, '保存成功');
    }
/*}}}*/
/*{{{ delete */
    public function delete() {
        $id = $this->params('id');

        $m = new \app\model\Dev();
        if ($m->get('id', ['gid' => $id, 'status[!]' => 9])) {
            $this->json(4001, '网关下有设备，请先去除关联');
        }

        $m = new \app\model\Gateway();
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
/*{{{ down */
    public function down() {
        $id = $this->params('id');
        $key = sprintf('gw:%s', $id);
        if (!$this->app->redis->exists($key)) {
            $this->json(4001, '网关离线中, 无法完成下发任务');
        }

        $m = new \app\model\Gateway();
        if (!$gw = $m->get(['id[Int]', 'serial', 'fid[Int]', 'upgrade[Int]'], ['id' => $id, 'status[!]' => 9])) {
            $this->json(4002, '网关不存在或已删除');
        }

        $mf = new \app\model\Fireware();
        if (!$bin = $mf->get(['id[Int]', 'ver[Int]', 'file', 'size[Int]'], ['id' => $gw['fid']])) {
            $this->json(4003, '固件不存在或已删除');
        }
        $fid = $gw['fid'];
        $idx = $gw['upgrade'];

        // 当 upgrade 为 null（未开始）或为负数（已完成/需重置）时，统一置为 0 开始下发
        if ($idx === null || $idx < 0) {
            $u = [
                'upgrade' => 0,
                'updated' => $m->raw('now()')
            ];
            $w = ['id' => $id];
            if (!$m->update($u, $w)->rowCount()) {
                $this->json(4004, '固件下发失败, 请重试');
            }
            $idx = 0;
        }

        if (!$hex = $mf->slice($bin['file'], $idx)) {
            $this->json(4005, '固件文件获取失败');
        }

        $ver = $bin['ver'];
        $max = $mf->num($bin['size']);
        $str = sprintf(
            '0000%02x%02x%02x%02x%02x%02x%s', 
            $ver >> 8, $ver & 0xff, 
            $max >> 8, $max & 0xff, 
            $idx >> 8, $idx & 0xff, 
            bin2hex($hex)
        );
        $d = [
            'sn'   => $gw['serial'],
            'chan' => 0x01,
            'data' => $str,
        ];
        $m->invoke('/bin', $d);
        $this->json(0, '固件下发指令发送成功');
    }
/*}}}*/
/*{{{ upgrade */
    public function upgrade() {
        $id = $this->params('id');
        $key = sprintf('gw:%s', $id);
        if (!$this->app->redis->exists($key)) {
            $this->json(4001, '网关离线中, 无法完成更新任务');
        }

        $m = new \app\model\Gateway();
        if (!$gw = $m->get(['id[Int]', 'serial', 'fid[Int]', 'upgrade[Int]'], ['id' => $id, 'status[!]' => 9])) {
            $this->json(4001, '网关不存在或已删除');
        }

        $idx = $gw['upgrade'];
        if ($idx != -1) {
            $this->json(4002, '固件未下发完成, 请稍后再试');
        }

        // upgrade
        $d = [
           'sn'   => $gw['serial'],
           'chan' => 0x01,
           'data' => '',
        ];
        $m->invoke('/upgrade', $d);
        $this->json(0, '固件更新指令发送成功');
    }
/*}}}*/

/*{{{ dev */
    private function dev($gid) {
        static $data;
        if (!isset($data)) {
            $m = new \app\model\Dev();
            if ($tmp = $m->select(['id', 'gid'], ['status[!]' => 9])) {
                foreach ($tmp as $row) {
                    if (!isset($data[$row['gid']])) {
                        $data[$row['gid']] = [];
                    }
                    $data[$row['gid']][] = $row['id'];
                }
            }
        }

        return isset($data[$gid])? $data[$gid]: [];

    }
/*}}}*/

/*{{{ fireware */
    private function fireware($fid) {
        static $data;
        if (!isset($data)) {
            $m = new \app\model\Fireware();
            if ($tmp = $m->select(['id[Int]', 'name', 'ver'], ['status[!]' => 9])) {
                foreach ($tmp as $row) {
                    $data[$row['id']] = sprintf('%s(V%s)', $row['name'], $row['ver']);
                }
            }
        }

        return isset($data[$fid])? $data[$fid]: '-';

    }
/*}}}*/

/*{{{ option */
    public function option() {
        $data = [];

        $w = ['status[!]' => 9];
        $ma = new \app\model\AuthRole();
        if (!$ma->isAdmin($this->u['code'])) {
            $m = new \app\model\Gateway();
            $ownedGids = $m->select('id', ['uid' => $this->u['id'], 'status[!]' => 9]);

            $md = new \app\model\Dev();
            $deviceGids = $md->select('gid', ['uid' => $this->u['id'], 'status[!]' => 9]);

            $gids = array_unique(array_merge($ownedGids ?: [], $deviceGids ?: []));
            if ($gids) {
                $w['id'] = $gids;
            } else {
                $w['id'] = 0;
            }
        }

        $m = new \app\model\Gateway();
        if ($tmp = $m->select(['id[Int]', 'name'], $w)) {
            $data = $tmp;
        }

        $this->json(0, '', $data);
    }
/*}}}*/

}
