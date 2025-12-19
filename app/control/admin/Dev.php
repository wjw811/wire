<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Dev.php
* @touch date Sat 18 Mar 2017 12:25:53 PM CST
* @author: Fred<fred@api4.me>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\admin;

class Dev extends \Next\Core\Control {

    /**
     * 构造 Next 外层协议帧（Go pomo 的 Packet 格式）
     * 结构：AA [size_hi][size_lo] [cmd] [data...] [tk] [crc] 55
     * - crc: CRC8-Dallas/Maxim（与 standalone 的 device_direct.html 一致）
     */
    private function packNext($cmdByte, $dataHex, $tk = 0x00) {
        $dataHex = preg_replace('/\s+/', '', (string)$dataHex);
        if ($dataHex === '' || (strlen($dataHex) % 2) !== 0) {
            return '';
        }
        $dataLen = (int)(strlen($dataHex) / 2);
        $size = 7 + $dataLen;
        $sizeHi = ($size >> 8) & 0xFF;
        $sizeLo = $size & 0xFF;
        $cmd = ((int)$cmdByte) & 0xFF;
        $tk = ((int)$tk) & 0xFF;

        // 计算CRC8：按 device_direct.html 的实现（Dallas/Maxim，poly=0x31 reflected => 0x8C）
        $crcData = [$sizeHi, $sizeLo, $cmd];
        for ($i = 0; $i < strlen($dataHex); $i += 2) {
            $crcData[] = hexdec(substr($dataHex, $i, 2));
        }
        $crcData[] = $tk;
        $crc = $this->crc8Dallas($crcData);

        return strtoupper(sprintf('AA%02X%02X%02X%s%02X%02X55', $sizeHi, $sizeLo, $cmd, $dataHex, $tk, $crc));
    }

    // CRC8-Dallas/Maxim（与 standalone 的 device_direct.html 一致）
    private function crc8Dallas($bytes) {
        $crc = 0x00;
        foreach ($bytes as $b) {
            $crc ^= ($b & 0xFF);
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x01) {
                    $crc = (($crc >> 1) ^ 0x8C) & 0xFF;
                } else {
                    $crc = ($crc >> 1) & 0xFF;
                }
            }
        }
        return $crc & 0xFF;
    }

    private $u;
    private $t;

    // Redis 兼容：未加载扩展时返回 null，避免抛错
    private function safeRedis() {
        try {
            return $this->app->redis;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // 外层协议 tk（1-255循环），按网关SN持久化到Redis，便于与 device_direct.html 对齐
    private function nextOuterTk($gwSn) {
        $r = $this->safeRedis();
        if ($r && $gwSn) {
            $key = sprintf('pomo:outer_tk:%s', $gwSn);
            $cur = (int)$r->get($key);
            $next = $cur + 1;
            if ($next < 1 || $next > 255) $next = 1;
            $r->set($key, (string)$next);
            $r->expire($key, 3600);
            return $next;
        }
        return ((int)(time() % 255)) + 1;
    }

/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->u = $this->app->user;
        $this->t = $this->app->token;
    }
/*}}}*/

    /*{{{ index */
    public function index() {
        $m = new \app\model\Dev();

        $name = $this->params('name');
        $gid = $this->params('gid');
        $status = $this->params('status');
        $state = $this->params('state');
        $page = $this->params('page', 1);
        $size = $this->params('size', 12);

        $data = [];
        $w = [];

        // role filter
        $ma = new \app\model\AuthRole();
        if (!$ma->isAdmin($this->u['code'])) {
            $mg = new \app\model\Gateway();
            $ownedGids = $mg->select('id', ['uid' => $this->u['id'], 'status[!]' => 9]);

            $deviceGids = $m->select('gid', ['uid' => $this->u['id'], 'status[!]' => 9]);

            $gids = array_unique(array_merge($ownedGids ?: [], $deviceGids ?: []));
            if ($gids) {
                $w['OR'] = [
                    'uid' => $this->u['id'],
                    'gid' => $gids,
                ];
            } else {
                $w['uid'] = $this->u['id'];
            }
        }

        if ($name) {
            $w['OR'] = [
                'name[~]'   => $name,
                'serial[~]' => $name,
            ];
        }
        if ($gid) {
            $w['gid'] = $gid;
        }
        if ($status != '') {
            $w['status'] = $status;
        } else {
            $w['status[!]'] = 9;
        }
        if ($state) {
            // laod all dev ids
            $map = [];
            if ($x = $m->select(['id', 'gid', 'serial'], $w)) {
                foreach ($x as $row) {
                    $map[sprintf('d:%s:%s', $row['gid'], $row['serial'])] = $row['id'];
                }
            }

            $ids = [0];
            $r = $this->safeRedis();
            $keys = $r ? $r->keys('d:*:*') : [];
            foreach ($keys as $key) {
                list($_, $gid, $sn) = explode(':', $key);
                $k = sprintf('d:%s:%s', $gid, $sn);
                $x = $this->state($gid, $sn);
                if ($state == 'warn') {
                    if ($x == 'warn' && isset($map[$k])) {
                        $ids[] = $map[$k];
                    }
                }
                if ($state == 'on') {
                    if ($x == 'on' && isset($map[$k])) {
                        $ids[] = $map[$k];
                    }
                }
                if ($state == 'off') {
                    if ($x != 'off' && isset($map[$k])) {
                        $ids[] = $map[$k];
                    }
                }
            }

            if ($state == 'off') {
                $w['id[!]'] = $ids;
            } else {
                $w['id'] = $ids;
            }
        }

        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            if ($data = $m->select(['id[Int]', 'name', 'serial', 'feature[JSON]', 'gid[Int]', 'addr', 'proto[Int]', 'star[Int]', 'status[Int]', 'created'], $w)) {
                foreach ($data as &$row) {
                    if (!$row['feature']) {
                        $row['feature'] = [];
                    }
                    $row['gateway'] = $this->gateway($row['gid']);
                    $row['protoName'] = $this->proto($row['proto']);
                    $row['state'] = $this->state($row['gid'], $row['serial']);
                }
            }
        }

        $this->json(0, '', ['total' => $total, 'items' => $data]);
    }
/*}}}*/
/*{{{ info */
    public function info() {
        if (!$id = $this->params('id')) {
            $this->json(4001, '参数有误');
        }

        $out = [
            'base'    => [],
            'history' => [],
            'warn'    => [],
        ];

        $m = new \app\model\Dev();
        $w = ['id' => $id, 'status[!]' => 9];
        $ma = new \app\model\AuthRole();
        if (!$ma->isAdmin($this->u['code'])) {
            $mg = new \app\model\Gateway();
            $ownedGids = $mg->select('id', ['uid' => $this->u['id'], 'status[!]' => 9]);

            $deviceGids = $m->select('gid', ['uid' => $this->u['id'], 'status[!]' => 9]);

            $gids = array_unique(array_merge($ownedGids ?: [], $deviceGids ?: []));
            if ($gids) {
                $w['OR'] = [
                    'uid' => $this->u['id'],
                    'gid' => $gids,
                ];
            } else {
                $w['uid'] = $this->u['id'];
            }
        }
        
        if ($base = $m->get(['id', 'name', 'serial', 'addr', 'gid', 'feature[JSON]', 'created'], $w)) {
            $base['state'] = $this->state($base['gid'], $base['serial']);
            $feature = [];
            if (is_array($base['feature'])) {
                $mo = new \app\model\Option();
                foreach ($base['feature'] as $key) {
                    $feature[] = $mo->featureName($key, true);
                }
            }
            $base['feature'] = $feature;
            // base info
            $out['base'] = $base;
            $did = $base['id'];

            // history
            $ml = new \app\model\DevLog();
            if ($tmp = $ml->select(['id', 'raw', 'content[JSON]', 'created'], ['did' => $did, 'ORDER' => ['id' => 'DESC'], 'LIMIT' => 100])) {
                $mp = new \app\model\Proto();
                foreach ($tmp as &$row) {
                    if ($ctx = $mp->decode($row['raw'])) {
                        $row['content'] = $ctx;
                    }
                }
                $out['history'] = $tmp;
                $out['base']['history'] = $tmp[0];
            }
            // warn
            $mw = new \app\model\DevWarn();
            if ($tmp = $mw->select(['id', 'content[JSON]', 'created'], ['did' => $did, 'ORDER' => ['id' => 'DESC'], 'LIMIT' => 100])) {
                foreach ($tmp as &$row) {
                    if (is_array($row['content'])) {
                        $row['content'] = implode(', ', $row['content']);
                    }
                }
                $out['warn'] = $tmp;
                $out['base']['warn'] = $tmp[0];
            }
            
            $this->json(0, '', $out);
        } else {
            $this->json(404, '设备未找到或无权访问');
        }
    }
/*}}}*/
/*{{{ localInfo */
    public function localInfo() {
        if (!$id = $this->params('id')) {
            $this->json(4001, '参数有误');
        }

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['id', 'gid', 'name', 'serial'], ['id' => $id, 'status[!]' => 9])) {
            $this->json(4004, '设备不存在');
        }

        // 从Redis获取网关的局域网IP
        $r = $this->safeRedis();
        $localIP = null;
        $directPort = 18899; // WiFi232-B2模块的Socket B端口

        if ($r) {
            $ipKey = sprintf('gw:ip:%s', $dev['gid']);
            $localIP = $r->get($ipKey);
        }

        $this->json(0, '', [
            'id' => $dev['id'],
            'name' => $dev['name'],
            'serial' => $dev['serial'],
            'gid' => $dev['gid'],
            'localIP' => $localIP,
            'directPort' => $directPort,
            'canDirectConnect' => !empty($localIP),
        ]);
    }
/*}}}*/
/*{{{ history */
    public function history() {
        if (!$id = $this->params('id')) {
            $this->json(4001, '参数有误');
        }
        if (!$date = $this->params('date')) {
            $this->json(4002, '参数有误');
        }
        if (count($date) != 2) {
            $this->json(4003, '参数有误');
        }
        $start = date('Y-m-d 00:00:00', strtotime($date[0]));
        $end = date('Y-m-d 23:59:59', strtotime($date[1]));

        $page = $this->params('page', 1);
        $size = $this->params('size', 12);

        $data = [];
        $m = new \app\model\DevLog();
        $w = [
            'did' => $id,
            'created[<>]' => [$start, $end],
        ];
        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            if ($tmp = $m->select(['id', 'raw', 'content[JSON]', 'created'], $w)) {
                $mp = new \app\model\Proto();
                foreach ($tmp as &$row) {
                    if ($ctx = $mp->decode($row['raw'])) {
                        $row['content'] = $ctx;
                    }
                }
                $data = $tmp;
            }
        }

        $this->json(0, '', ['total' => $total, 'items' => $data]);
    }
/*}}}*/
/*{{{ chart */
    public function chart() {
        if (!$id = $this->params('id')) {
            $this->json(4001, '参数有误');
        }
        if (!$date = $this->params('date')) {
            $this->json(4002, '参数有误');
        }
        if (count($date) != 2) {
            $this->json(4003, '参数有误');
        }
        $start = date('Y-m-d 00:00:00', strtotime($date[0]));
        $end = date('Y-m-d 23:59:59', strtotime($date[1]));

        // get cache data
        $key = sprintf('c:%s-%s', $id, md5($start.$end));
        $r = $this->safeRedis();
        if ($r && ($data = $r->get($key))) {
            $this->json(0, '', json_decode($data, true));
        }

        // get data from db
        $m = new \app\model\DevLog();
        $out = $m->load4chart($id, $start, $end);

        // cached data
        if ($r) {
            $r->set($key, json_encode($out), 900);
        }

        $this->json(0, '', $out);
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
        $m = new \app\model\Dev();

        $id      = $this->params('id');
        $name    = $this->params('name');
        $serial  = intval($this->params('serial'));
        $feature = json_encode($this->params('feature'));
        $addr    = $this->params('addr');
        $gid     = $this->params('gid');
        $proto   = $this->params('proto');
        $star    = $this->params('star');
        $status  = $this->params('status');

        if (!$name) {
            $this->json(4001, '请输入设备名称');
        }
        if (!$serial || $serial < 1 || $serial > 65535) {
            $this->json(4001, '请输入有效的设备编号(1-65535)');
        }
        if (!$gid) {
            $this->json(4001, '请选择所属网关');
        }
        if ($proto === null || $proto === '') {
            $this->json(4001, '请选择通讯协议');
        }
        if ($status === null || $status === '') {
            $this->json(4001, '请选择启用状态');
        }
        if (!$this->params('feature') || !is_array($this->params('feature'))) {
            $this->json(4001, '请选择设备功能');
        }

        // 如果发现编号被已删除的设备占用，先彻底删除旧记录腾出位置
        if (!$id) {
            $m->delete(['serial' => $serial, 'gid' => $gid, 'status' => 9]);
        }

        if ($m->has(['serial' => $serial, 'gid' => $gid, 'id[!]' => $id, 'status[!]' => 9])) {
            $this->json(4001, '该网关下已存在相同编号的设备');
        }

        try {
            if (!$id) {
                $a = [
                    'name'    => $name,
                    'serial'  => $serial,
                    'addr'    => $addr,
                    'gid'     => $gid,
                    'proto'   => $proto,
                    'feature' => $feature,
                    'status'  => $status == 1? 1: 0,
                    'star'    => $star == 1? 1: 0,
                    'uid'     => $this->u['id'],
                    'created' => $m->raw('now()'),
                    'updated' => $m->raw('now()'),
                ];
                if (!$m->insert($a)->rowCount()) {
                    $this->json(4001, 'add fail');
                }

                // make proto for dev service
                $m = new \app\model\Proto();
                $m->make();

                $this->json(0, 'add success');
            }

            $u = [
                'name'    => $name,
                'serial'  => $serial,
                'addr'    => $addr,
                'gid'     => $gid,
                'proto'   => $proto,
                'feature' => $feature,
                'star'    => $star == 1? 1: 0,
                'status'  => $status == 1? 1: 0,
                'updated' => $m->raw('now()'),
            ];
            $w = [
                'id' => $id,
            ];
            if (!$m->update($u, $w)->rowCount()) {
                $this->json(4001, 'save fail');
            }

            // make proto for dev service
            $m = new \app\model\Proto();
            $m->make();

            $this->json(0, 'save success');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate entry') !== false) {
                $this->json(4001, '该编号已被占用，请更换（即使已删除的设备也会占用编号）');
            }
            error_log("Dev::save() error: " . $msg);
            $this->json(500, '服务器错误: ' . $msg);
        }
    }
/*}}}*/
/*{{{ saveSetting */
    public function saveSetting() {
        $m = new \app\model\Option();
        list($code, $d) = $m->pareseWarn($this->params('setting'));
        if ($code != 0) {
            $this->json($code, $d);
        }

        $id = $this->params('id');
        $m = new \app\model\Dev();
        $u = [
            'setting' => json_encode($d),
            'updated' => $m->raw('now()'),
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
        $m = new \app\model\Dev();
        $id = $this->params('id');

        $u = [
            'status' => 9,
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

/*{{{ proto */
    private function proto($id) {
        static $data;
        if (!isset($data)) {
            $m = new \app\model\Option();
            if ($tmp = $m->get('value[JSON]', ['code' => 'proto'])) {
                foreach ($tmp as $row) {
                    $data[$row['key']] = $row['val'];
                }
            }
        }

        return isset($data[$id])? $data[$id]: '-';
    }
/*}}}*/
/*{{{ gateway */
    public function gateway($id) {
        static $data;
        if (!isset($data)) {
            $m = new \app\model\Gateway();
            if ($tmp = $m->select(['id[Int]', 'name'], ['status[!]' => 9])) {
                foreach ($tmp as $row) {
                    $data[$row['id']] = $row['name'];
                }
            }
        }

        return isset($data[$id])? $data[$id]: '-';
    }
/*}}}*/
/*{{{ state */
    private function state($gid, $sn) {
        $state = 'off';
        $now = time() - 60*5; // offset 5 mins

        // d: data json
        // t: timestamp
        // s: 1: warn, 0: ok
        $key = sprintf('d:%s:%s', $gid, $sn);
        $r = $this->safeRedis();
        $arr = $r ? $r->hGetAll($key) : [];
        if ($arr) {
            if (isset($arr['s']) && $arr['s']) {
                $state = 'warn';
            } else if (isset($arr['t']) && $arr['t'] > $now) {
                $state = 'on';
            }
        }

        return $state;
    }
/*}}}*/
/*{{{ cmd */
    public function cmd() {
        $id = $this->params('id');
        $mode = $this->params('mode');
        
        error_log(sprintf('[cmd] 开始 > id=%s mode=%s', $id, $mode));

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['serial', 'gid'], ['id' => $id])) {
            error_log(sprintf('[cmd] 错误 > 设备未找到 id=%s', $id));
            $this->json(40401, '设备未知');
        }
        error_log(sprintf('[cmd] 设备信息 > serial=%s gid=%s', $dev['serial'], $dev['gid']));
        $m = new \app\model\Gateway();
        if (!$gateway = $m->get(['serial'], ['id' => $dev['gid']])) {
            $this->json(40402, '网关未知');
        }

        $uri = '/rpc/push';
        $data = [
            'sn'   => $gateway['serial'],
            'chan' => 0x01,
            'data' => '',
        ];
        $outerTk = $this->nextOuterTk($gateway['serial']);
        $deviceSerial = 1; // 强制使用设备号1，忽略数据库中的 serial
        switch($mode) {
        case 'run':
            $str = sprintf('aa%02x0552554E', $deviceSerial);
            $inner = $this->app->common->crc16xmodem($str);
            $data['data'] = $this->packNext(0x02, $inner, $outerTk);
           break; 
        case 'stp':
            $str = sprintf('aa%02x05535450', $deviceSerial);
            $inner = $this->app->common->crc16xmodem($str);
            $data['data'] = $this->packNext(0x02, $inner, $outerTk);
           break;
        case 'rst':
            $str = sprintf('aa%02x05525354', $deviceSerial);
            $inner = $this->app->common->crc16xmodem($str);
            $data['data'] = $this->packNext(0x02, $inner, $outerTk);
           break; 
        case 'pam':
            $cmd = $this->params('cmd');
            $val = $this->params('val');
            
            $cmd = (int)$cmd;
            $val = (int)$val;
            
            $innerStr = sprintf('aa%02x07E1%02x%02x%02x%02x', 
                $deviceSerial,
                $cmd >> 8, $cmd & 0xff,
                $val >> 8, $val & 0xff
            );
            $innerData = $this->app->common->crc16xmodem($innerStr);
            // 外层协议封装（与 device_direct.html 一致）
            $data['data'] = $this->packNext(0x02, $innerData, $outerTk);
           break; 
        case 'syn':
            $cmd = $this->params('cmd');
            $innerStr = sprintf('aa%02x05E0%02x%02x', 
                $deviceSerial,
                $cmd >> 8, $cmd & 0xff
            );
            $innerData = $this->app->common->crc16xmodem($innerStr);
            // 外层协议封装（与 device_direct.html 一致）
            $data['data'] = $this->packNext(0x02, $innerData, $outerTk);
            
           break; 
        default:
            $baud = $this->params('baud');
            $parity = $this->params('parity');
            // Go pomo 的配置接口为 /rpc/set（不是 /set）
            $uri = '/rpc/set';
            $data['data'] = sprintf('%02x%02x%02x', $baud >> 8, $baud & 0xff, $parity);
            break;
        }

        if (!$m->invoke($uri, $data)) {
            $this->json(40403, 'cmd fail');
        }
        $this->json(0, 'cmd success');
    }
/*}}}*/
/*{{{ sync */
    public function sync() {
        $id = $this->params('id');

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['serial', 'gid'], ['id' => $id])) {
            $this->json(40401, '设备未知');
        }

        $key = sprintf('p:%s:%s', $dev['gid'], $dev['serial']);
        $r = $this->safeRedis();
        
        $val = null;
        if ($r) {
            $val = $r->hGet($key, 'val');
            if (!$val) {
                $val = $r->get($key);
            }
            error_log(sprintf('[sync] Redis获取 > key=%s val_raw=%s val_type=%s', 
                $key, $val, gettype($val)));
        }
        
        if (!$val) {
            $val = 0;
            error_log(sprintf('[sync] 无参数值 > key=%s, 返回默认值0', $key));
        }

        if (is_string($val)) {
            $trim = trim($val);
            if (is_numeric($trim)) {
                $val = strpos($trim, '.') !== false ? (float)$trim : (int)$trim;
            } else if ((strlen($trim) > 1) && (($trim[0] === '{' && substr($trim, -1) === '}') || ($trim[0] === '[' && substr($trim, -1) === ']'))) {
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $decoded;
                }
            }
        }

        $data = [
            'val' => $val,
        ];
        $this->json(0, '', $data); 
    }
/*}}}*/

}
