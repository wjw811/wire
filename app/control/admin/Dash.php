<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Dash.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Dash extends \Next\Core\Control {

    private $u;
    private $t;
    
    /**
     * 构造 Next 外层协议帧（Go pomo 的 Packet 格式）
     * 结构：AA [size_hi][size_lo] [cmd] [data...] [tk] [crc] 55
     * - size = 7 + data_len
     * - tk: 1字节序号/令牌（pomo 里会自增；这里用固定值也可）
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
        // 无Redis时用时间做一个稳定但无需持久化的tk
        return ((int)(time() % 255)) + 1;
    }
    

    // Redis 兼容：未加载扩展时返回 null，避免抛错
    private function safeRedis() {
        try {
            return $this->app->redis;
        } catch (\Throwable $e) {
            return null;
        }
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
        try {
            // 始终返回可用数据，避免依赖角色判定/Redis/模型导致空白
            $out = [
                'total' => $this->total(),
                'info'  => $this->info(),
            ];
            $this->json(0, '', $out);
        } catch (Exception $e) {
            error_log("Dash::index() error: " . $e->getMessage());
            $this->json(1, 'Dashboard error: ' . $e->getMessage(), []);
        }
    }
/*}}}*/

    /*{{{ total */
    private function total() {
        $key = 'dash.total.' . $this->u['id'];
        $r = $this->safeRedis();
        if ($r && ($data = $r->get($key))) {
            return json_decode($data, true);
        }
        
        $md = new \app\model\Dev();
        $mg = new \app\model\Gateway();
        $mw = new \app\model\DevWarn();
        
        $w = ['status[!]' => 9];
        $wg = ['status[!]' => 9];
        
        $ma = new \app\model\AuthRole();
        if (!$ma->isAdmin($this->u['code'])) {
            $mg = new \app\model\Gateway();
            $ownedGids = $mg->select('id', ['uid' => $this->u['id'], 'status[!]' => 9]);
            
            $md = new \app\model\Dev();
            $deviceGids = $md->select('gid', ['uid' => $this->u['id'], 'status[!]' => 9]);
            
            $gids = array_unique(array_merge($ownedGids ?: [], $deviceGids ?: []));
            
            if ($gids) {
                $w['OR'] = [
                    'uid' => $this->u['id'],
                    'gid' => $gids,
                ];
                $wg['id'] = $gids;
            } else {
                $w['uid'] = $this->u['id'];
                $wg['uid'] = $this->u['id'];
            }
        }
        
        $ids = $md->select('id', $w);
        $data = [
            'dev'     => count($ids),
            'gw'      => $mg->count($wg),
            'node'    => count($ids), // 暂时用设备数代替节点数
            'task'    => $mw->count(['status' => 0, 'did' => $ids ?: [0]]), // 用待处理告警数代替任务数
            'warning' => 0,
            'pending' => $mw->count(['status' => 0, 'did' => $ids ?: [0]]),
        ];

        $num = 0;
        if ($r) {
            // 获取用户拥有的设备列表，只计算这些设备的告警状态
            $devs = $md->select(['gid', 'serial'], $w);
            foreach ($devs as $dev) {
                if ($state = $r->hGet(sprintf('d:%s:%s', $dev['gid'], $dev['serial']), 's')) {
                    if ($state == 'warn') {
                        $num++;
                    }
                }
            }
            $data['warning'] = $num;
            $r->set($key, json_encode($data));
            $r->expire($key, 60);
        } else {
            $data['warning'] = 0;
        }

        return $data;
    }
/*}}}*/
    /*{{{ info */
    private function info() {
        $r = $this->safeRedis();
        $m = new \app\model\Dev();
        
        $w = ['star' => 1, 'status[!]' => 9];
        $ma = new \app\model\AuthRole();
        if (!$ma->isAdmin($this->u['code'])) {
            $mg = new \app\model\Gateway();
            $ownedGids = $mg->select('id', ['uid' => $this->u['id'], 'status[!]' => 9]);
            
            $md = new \app\model\Dev();
            $deviceGids = $md->select('gid', ['uid' => $this->u['id'], 'status[!]' => 9]);
            
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
        
        $dev = $m->select(['id', 'name', 'serial', 'feature[JSON]', 'gid'], $w);
        
        // Debug
        error_log(sprintf('Dash::info() found %d starred devices', count($dev)));

        $key = 'dash.feature';
        if ($r && ($tmp = $r->get($key))) {
            $ft = json_decode($tmp, true);
        } else {
            $m = new \app\model\Option();
            $ft = $m->loadByCode('feature');
            if ($r) {
                $r->set($key, json_encode($ft, JSON_UNESCAPED_UNICODE));
                $r->expire($key, 60);
            }
        }

        $data = [];
        $lang = 'zh';
        $mp = new \app\model\Proto();
        foreach ($dev as $row) {
            $key = sprintf('d:%s:%s', $row['gid'], $row['serial']);
            $time = $r ? $r->hGet($key, 't') : null;
            
            error_log(sprintf('Dash::info() checking device key=%s t=%s', $key, $time ?: 'NULL'));

            $info = [];
            if ($r) {
                $p = $r->hGet($key, 'p');
                if ($p) {
                    $info = $mp->decode($p);
                }
                if (!$info) {
                    $d_json = $r->hGet($key, 'd');
                    if ($d_json) {
                        $info = json_decode($d_json, true);
                    }
                }
            }
            
            $warn = false;
            $d = [];
            foreach ($row['feature'] as $k) {
                $n = '-';
                $u = '';
                foreach ($ft as $x) {
                    if ($x['key'] == $k) {
                        if ($lang === 'zh') {
                            $n = isset($x['zh']) ? $x['zh'] : (isset($x['zh_CN']) ? $x['zh_CN'] : $x['en']);
                        } else {
                            $n = isset($x[$lang]) ? $x[$lang] : $x['en'];
                        }
                        $u = $x['unit'];
                        break;
                    }
                }
                $v = isset($info[$k])? $info[$k]: '-';
                if (in_array($k, ['f48', 'f49', 'f50', 'f51'])) {
                    if ($v && $v != '-') {
                        $warn = true;
                    }
                }
                // nil value
                if (in_array($k, ['f48', 'f49', 'f50', 'f51', 'f52', 'f53'])) {
                    if (!$v) {
                        $v = @$this->app->i18n['nil'];
                    }
                }
                $d[] = [
                    'k' => $n,
                    'v' => $v,
                    'u' => $u,
                ];
            }

            $data[] = [
                'id'     => $row['id'],
                'name'   => $row['name'],
                'serial' => $row['serial'],
                'time'   => $time? date('H:i:s', $time): '-',
                'data'   => $d,
                'warn'   => $warn,
                'limit'  => $info['limit']? $info['limit']: ['run', 'stp', 'rst'],
            ]; 
        }

        return $data;
    }
/*}}}*/
/*{{{ batchQuery */
    public function batchQuery() {
        $id = $this->params('id');
        $startAddr = $this->params('startAddr');
        $count = $this->params('count');
        $mode = $this->params('mode');

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['serial', 'gid'], ['id' => $id])) {
            $this->json(40401, '设备未知');
        }
        $m = new \app\model\Gateway();
        if (!$gateway = $m->get(['serial'], ['id' => $dev['gid']])) {
            $this->json(40402, '网关未知');
        }

        // 构造批量查询协议：AA 01 07 E2 02 00 00 02 D5 10
        // 其中：01-设备地址（固定为1），07-从E2开始到校验码的长度，E2-读连续标志位
        // 02 00-起始地址（512=0x0200，高字节在前），00 02-寄存器个数（高字节在前），D5 10-校验码
        $length = 7; // E2 + 起始地址(2) + 寄存器个数(2) + 校验码(2) = 7
        $deviceSerial = 1; // 强制使用设备号1，忽略后台填写的信息
        $str = sprintf('aa%02x%02xE2%02x%02x%02x%02x', 
            $deviceSerial,            // 固定为1
            $length,                  // 长度
            ($startAddr >> 8) & 0xff, // 起始地址高字节
            $startAddr & 0xff,        // 起始地址低字节
            ($count >> 8) & 0xff,     // 寄存器个数高字节
            $count & 0xff             // 寄存器个数低字节
        );
        
        // 计算内层协议CRC16校验码
        $innerData = $this->app->common->crc16xmodem($str);
        // 外层协议封装：AA [len] 02 [inner] [tk] [crc8] 55（与 device_direct.html 一致）
        $outerTk = $this->nextOuterTk($gateway['serial']);
        $finalData = $this->packNext(0x02, $innerData, $outerTk);

        $uri = '/rpc/push';
        $data = [
            'sn'   => $gateway['serial'],
            'chan' => 0x01,
            'data' => $finalData,
        ];

        // Debug: 记录批量查询协议

        if (!$m->invoke($uri, $data)) {
            $this->json(40403, '批量查询命令发送失败');
        }

        // 等待设备响应并解析数据
        // 使用设备的serial作为Redis键，与单个查询保持一致
        $key = sprintf('p:%s:%s', $dev['gid'], $dev['serial']);
        $r = $this->safeRedis();
        
        // 轮询等待响应数据（最多等待10秒，给设备更多响应时间）
        $maxWait = 100; // 100 * 100ms = 10秒
        $waitCount = 0;
        $responseData = null;
        
        while ($waitCount < $maxWait) {
            usleep(100000); // 等待100ms
            $waitCount++;
            
            if ($r) {
                $val = $r->hGet($key, 'batch_data');
                if ($val) {
                    // 解析批量数据：JSON格式 {"values":[0,16],"count":2,"timestamp":1234567890}
                    $responseData = json_decode($val, true);
                    if ($responseData && isset($responseData['values']) && isset($responseData['count'])) {
                        break;
                    }
                }
            }
        }

        if (!$responseData) {
            $this->json(40404, '批量查询超时或数据解析失败');
        }

        $this->json(0, '批量查询成功', ['data' => $responseData]);
    }
/*}}}*/
/*{{{ batchSave */
    public function batchSave() {
        $id = $this->params('id');
        $batchData = $this->params('data');
        $mode = $this->params('mode');

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['serial', 'gid'], ['id' => $id])) {
            $this->json(40401, '设备未知');
        }
        $m = new \app\model\Gateway();
        if (!$gateway = $m->get(['serial'], ['id' => $dev['gid']])) {
            $this->json(40402, '网关未知');
        }

        // 构造批量保存协议（与 device_direct.html 一致）
        // 内层：AA [dev_addr] [len] E1 [addr1][val1][addr2][val2]... [CRC16]
        // len = 1(E1) + N*4(addr+val) + 2(CRC16)
        $dataPart = '';
        $pairCount = 0;
        foreach ($batchData as $item) {
            $addr = (int)$item['addr'];
            $val = (int)$item['val'];
            $dataPart .= sprintf('%04x%04x', $addr & 0xFFFF, $val & 0xFFFF);
            $pairCount++;
        }

        $deviceSerial = 1; // 强制使用设备号1，忽略后台填写的信息
        $length = 1 + ($pairCount * 4) + 2;
        $str = sprintf('aa%02x%02xE1%s', $deviceSerial, $length, $dataPart);

        // 计算内层CRC16并拼接完整内层帧
        $innerData = $this->app->common->crc16xmodem($str);

        // 外层协议封装（与 device_direct.html 一致）
        $outerTk = $this->nextOuterTk($gateway['serial']);
        $finalData = $this->packNext(0x02, $innerData, $outerTk);

        $uri = '/rpc/push';
        $data = [
            'sn'   => $gateway['serial'],
            'chan' => 0x01,
            'data' => $finalData,
        ];


        if (!$m->invoke($uri, $data)) {
            $this->json(40403, '批量保存命令发送失败');
        }

        $this->json(0, '批量保存成功');
    }
/*}}}*/
/*{{{ parseBatchResponse */
    private function parseBatchResponse($response, $expectedCount) {
        // 解析响应：55 01 23 R xxxx...xxxx XXXX
        // 去除空格和换行
        $response = preg_replace('/\s+/', '', $response);
        
        // 检查响应格式
        if (strlen($response) < 8) {
            return null;
        }
        
        // 检查起始字节
        if (substr($response, 0, 2) !== '55') {
            return null;
        }
        
        // 解析长度字段
        $length = hexdec(substr($response, 4, 2));
        
        // 检查数据长度
        $expectedLength = 3 + $expectedCount * 2 + 2; // R + 数据 + 校验码
        if ($length !== $expectedLength) {
            return null;
        }
        
        // 提取数据部分
        $dataStart = 6; // 跳过 55 01 23 R
        $dataLength = $expectedCount * 2;
        $dataHex = substr($response, $dataStart, $dataLength);
        
        // 将十六进制数据转换为数值数组
        $result = [];
        for ($i = 0; $i < $expectedCount; $i++) {
            $hex = substr($dataHex, $i * 4, 4);
            $result[] = hexdec($hex);
        }
        
        return $result;
    }
/*}}}*/

/*{{{ batchQueryAsync */
    public function batchQueryAsync() {
        $id = $this->params('id');
        $startAddr = $this->params('startAddr');
        $count = $this->params('count');
        $mode = $this->params('mode');

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['serial', 'gid'], ['id' => $id])) {
            $this->json(40401, '设备未知');
        }
        $m = new \app\model\Gateway();
        if (!$gateway = $m->get(['serial'], ['id' => $dev['gid']])) {
            $this->json(40402, '网关未知');
        }

        // 生成唯一的任务ID
        $taskId = uniqid('batch_', true);
        
        // 构造批量查询协议：AA 01 07 E2 02 00 00 02 D5 10
        // 其中：01-设备地址（固定为1），07-从E2开始到校验码的长度，E2-读连续标志位
        // 02 00-起始地址（512=0x0200，高字节在前），00 02-寄存器个数（高字节在前），D5 10-校验码
        $length = 7; // E2 + 起始地址(2) + 寄存器个数(2) + 校验码(2) = 7
        $deviceSerial = 1; // 强制使用设备号1，忽略后台填写的信息
        $str = sprintf('aa%02x%02xE2%02x%02x%02x%02x', 
            $deviceSerial,            // 固定为1
            $length,                  // 长度
            ($startAddr >> 8) & 0xff, // 起始地址高字节
            $startAddr & 0xff,        // 起始地址低字节
            ($count >> 8) & 0xff,     // 寄存器个数高字节
            $count & 0xff             // 寄存器个数低字节
        );
        
        // 计算内层协议CRC16校验码
        $innerData = $this->app->common->crc16xmodem($str);
        // 外层协议封装（与 device_direct.html 一致）
        $outerTk = $this->nextOuterTk($gateway['serial']);
        $finalData = $this->packNext(0x02, $innerData, $outerTk);

        $uri = '/rpc/push';
        $data = [
            'sn'   => $gateway['serial'],
            'chan' => 0x01,
            'data' => $finalData,
        ];

        // Debug: 记录批量查询协议

        if (!$m->invoke($uri, $data)) {
            $this->json(40403, '批量查询命令发送失败');
        }

        // 将任务信息存储到Redis，供后续查询结果使用
        $r = $this->safeRedis();
        if ($r) {
            $taskKey = sprintf('task:%s', $taskId);
            $taskData = [
                'id' => $id,
                'gid' => $dev['gid'],
                'serial' => $dev['serial'],
                'startAddr' => $startAddr,
                'count' => $count,
                'timestamp' => time(),
                'status' => 'pending'
            ];
            $r->setex($taskKey, 60, json_encode($taskData)); // 60秒过期
        }

        $this->json(0, '批量查询任务已提交', ['taskId' => $taskId]);
    }
/*}}}*/

/*{{{ batchQueryResult */
    public function batchQueryResult() {
        $taskId = $this->params('taskId');

        if (!$taskId) {
            $this->json(40401, '任务ID不能为空');
        }

        $r = $this->safeRedis();
        if (!$r) {
            $this->json(40402, 'Redis连接失败');
        }

        // 获取任务信息
        $taskKey = sprintf('task:%s', $taskId);
        $taskData = $r->get($taskKey);
        
        if (!$taskData) {
            $this->json(40403, '任务不存在或已过期');
        }

        $task = json_decode($taskData, true);
        if (!$task) {
            $this->json(40404, '任务数据解析失败');
        }

        // 检查任务状态
        $key = sprintf('p:%s:%s', $task['gid'], $task['serial']);
        $val = $r->hGet($key, 'batch_data');
        
        if ($val) {
            // 解析批量数据：JSON格式 {"values":[0,16],"count":2,"timestamp":1234567890}
            $responseData = json_decode($val, true);
            if ($responseData && isset($responseData['values']) && isset($responseData['count'])) {
                // 清除任务和响应数据
                $r->del($taskKey);
                $r->hDel($key, 'batch_data');
                
                // 返回前端期望的格式：{status: 'completed', result: {data: {...}}}
                $this->json(0, '批量查询成功', [
                    'status' => 'completed',
                    'result' => ['data' => $responseData]
                ]);
            }
        }

        // 检查任务是否超时（超过30秒）
        if ((time() - $task['timestamp']) > 30) {
            $r->del($taskKey);
            $this->json(0, '批量查询超时', ['status' => 'failed']);
        }

        // 任务仍在处理中
        $this->json(0, '任务处理中', ['status' => 'pending']);
    }
/*}}}*/

/*{{{ monitor */
    public function monitor() {
        $probe = $this->probe();
        if ($probe['admin']) {
            $out = [
                'data' => $this->calcData(),
            ];
            $this->json(0, '', $out);
        }

        $out = [
            'data' => $this->perCalcData($probe),
        ];
        $this->json(0, '', $out);
    }
/*}}}*/
/*{{{ initChartData - 初始化柱状图测试数据 */
    public function initChartData() {
        // 只允许管理员执行
        $ma = new \app\model\AuthRole();
        if (!$ma->isAdmin($this->u['code'])) {
            $this->json(1, '权限不足');
        }

        $m = new \app\model\CalcDay();
        
        // 清空旧数据
        $m->delete(['id[>]' => 0]);
        
        // 插入7天测试数据
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Ymd', strtotime("-{$i} days"));
            $ok = 10 - $i;  // 递增的正常设备数
            $ng = $i;       // 递减的报警设备数
            
            $m->insert([
                'day' => $day,
                'snap' => json_encode(['k0' => ['g' => [$ok, $ng]]]),
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ]);
            
            $data[] = [
                'day' => $day,
                'ok' => $ok,
                'ng' => $ng
            ];
        }
        
        // 清除Redis缓存
        $r = $this->safeRedis();
        if ($r) {
            $r->del('dash.m.data');
        }
        
        $count = $m->count();
        $this->json(0, '初始化成功', [
            'count' => $count,
            'data' => $data
        ]);
    }
/*}}}*/
/*{{{ calcData */
    private function calcData() {
        $key = 'dash.m.data';
        $r = $this->safeRedis();
        if ($r && ($data = $r->get($key))) {
            return json_decode($data, true);
        }

        $dy = [];
        $y = date('Y');
        for($i = 1; $i <= 12; $i++) {
            $max = date('t', strtotime(sprintf('%s-%s-01', $y, $i)));
            for($j = 1; $j <= $max; $j++) {
                $day = sprintf('%s%02s%02s', $y, $i, $j);
                $dy[$day] = [
                    'ok' => 0,
                    'ng' => 0,
                ];
            }
        }
        $m = new \app\model\CalcDay();
        if ($tmp = $m->select(['day', 'snap[JSON]'], ['day[<>]' => [$y*10000+101, $y*10000+1231], 'ORDER' => ['day']])) {
            foreach ($tmp as $row) {
                $day = $row['day'];
                if (isset($row['snap']['k0'])) {
                    $snap = $row['snap']['k0'];
                    if (isset($snap['g']) && count($snap['g']) > 1) {
                        $dy[$day]['ok'] += $snap['g'][0];
                        $dy[$day]['ng'] += $snap['g'][1];
                    }
                }
            }
        }

        $year = [
            'x'  => [],
            'ok' => [],
            'ng' => [],
        ];
        $month = [
            'x'  => [],
            'ok' => [],
            'ng' => [],
        ];
        foreach ($dy as $k => $v) {
            $x = sprintf('%s.%s', substr($k, 4, 2), substr($k, 6));
            $year['x'][] = $x;
            $year['ok'][] = $v['ok'];
            $year['ng'][] = $v['ng'];

            $m = date('Ym');
            if (strpos($k, $m) === 0) {
                $month['x'][] = $x;
                $month['ok'][] = $v['ok'];
                $month['ng'][] = $v['ng'];
            }
        }

        $data = [
            'year'  => $year,
            'month' => $month,
        ];
        if ($r) {
            $r->set($key, json_encode($data));
            $r->expire($key, 60*60);
        }

        return $data;
    }
/*}}}*/

/*{{{ probe */
    private function probe() {
        $data = [
            'admin' => true,
            'uid' => 0,
            'did' => [],
            'gid' => [],
            'key' => [],
        ];
        
        $ma = new \app\model\AuthRole();
        if ($ma->isAdmin($this->u['code'])) {
            return $data;
        }

        $data['admin'] = false;
        $data['uid'] = $this->u['id'];
        $md = new \app\model\Dev();
        if ($tmp = $md->select(['id', 'gid', 'serial'], ['uid' => $this->u['id'], 'status[!]' => 9])) {
            foreach ($tmp as $row) {
                $data['did'][] = $row['id'];
                $data['gid'][] = $row['gid'];
                $data['key'][] = sprintf('d:%s:%s', $row['gid'], $row['serial']);
            }
        }

        return $data;
    }
/*}}}*/

/*{{{ perTotal */
    private function perTotal($probe) {
        $key = sprintf('dash.total.%s', $probe['uid']);
        if ($data = $this->app->redis->hGetAll($key)) {
            return json_decode($data, true);
        }
        
        $mg = new \app\model\Gateway();
        $mw = new \app\model\DevWarn();
        
        // 修复：处理空数组的情况
        $gatewayCount = 0;
        if (!empty($probe['gid'])) {
            $gatewayCount = $mg->count(['status[!]' => 9, 'id' => $probe['gid']]);
        }
        
        $pendingCount = 0;
        if (!empty($probe['did'])) {
            $pendingCount = $mw->count(['status' => 0, 'did' => $probe['did']]);
        }
        
        $data = [
            'dev'     => count($probe['did']),
            'gateway' => $gatewayCount,
            'warning' => 0,
            'pending' => $pendingCount,
        ];

        $num = 0;
        try {
            $keys = $this->app->redis->keys('d:*:*');
            if ($keys) {
                foreach ($keys as $k) {
                    if (in_array($k, $probe['key'])) {
                        list($_, $gid, $sn) = explode(':', $k);
                        if ($state = $this->app->redis->hGet(sprintf('d:%s:%s', $gid, $sn), 's')) {
                            $num++;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Redis连接失败时，设置默认值
            $num = 0;
        }
        $data['warning'] = $num;

        try {
            $this->app->redis->set($key, json_encode($data));
            $this->app->redis->expire($key, 60);
        } catch (Exception $e) {
            // Redis操作失败时，继续执行
        }

        return $data;
    }
/*}}}*/
/*{{{ perInfo */
    private function perInfo($probe) {
        $key = sprintf('dash.dev.%s', $probe['uid'] ?? 0);
        try {
            $redis = $this->safeRedis();
            if ($redis && $tmp = $redis->get($key)) {
                $dev = json_decode($tmp, true);
            } else {
                $m = new \app\model\Dev();
                if (!empty($probe['did'])) {
                    $dev = $m->select(['id', 'name', 'serial', 'feature[JSON]', 'gid'], ['star' => 1, 'status[!]' => 9, 'id' => $probe['did']]);
                } else {
                    $dev = [];
                }
                if ($redis) {
                    $redis->set($key, json_encode($dev));
                    $redis->expire($key, 5);
                }
            }
        } catch (Exception $e) {
            error_log("perInfo error: " . $e->getMessage());
            $dev = [];
        }

        $key = 'dash.feature';
        try {
            $redis = $this->safeRedis();
            if ($redis && $tmp = $redis->get($key)) {
                $ft = json_decode($tmp, true);
            } else {
                $m = new \app\model\Option();
                $ft = $m->loadByCode('feature');
                if ($redis) {
                    $redis->set($key, json_encode($ft, JSON_UNESCAPED_UNICODE));
                    $redis->expire($key, 60);
                }
            }
        } catch (Exception $e) {
            error_log("perInfo feature error: " . $e->getMessage());
            $ft = [];
        }

        $data = [];
        // 强制中文，避免前端误传 Lang 导致英文
        $lang = 'zh';
            $mp = new \app\model\Proto();
        foreach ($dev as $row) {
            $key = sprintf('d:%s:%s', $row['gid'], $row['serial']);
            $time = $this->app->redis->hGet($key, 't');
            if (!$info = $mp->decode($this->app->redis->hGet($key, 'p'))) {
                $info = json_decode($this->app->redis->hGet($key, 'd'), true);
            }

            $warn = false;
            $d = [];
            foreach ($row['feature'] as $k) {
                $n = '-';
                $u = '';
                foreach ($ft as $x) {
                    if ($x['key'] == $k) {
                        if ($lang === 'zh') {
                            $n = isset($x['zh']) ? $x['zh'] : (isset($x['zh_CN']) ? $x['zh_CN'] : $x['en']);
                        } else {
                            $n = isset($x[$lang]) ? $x[$lang] : $x['en'];
                        }
                        $u = $x['unit'];
                        break;
                    }
                }
                $v = isset($info[$k])? $info[$k]: '-';
                if (in_array($k, ['f48', 'f49', 'f50', 'f51'])) {
                    if ($v && $v != '-') {
                        $warn = true;
                    }
                }
                // nil value
                if (in_array($k, ['f48', 'f49', 'f50', 'f51', 'f52', 'f53'])) {
                    if (!$v) {
                        $v = @$this->app->i18n['nil'];
                    }
                }
                $d[] = [
                    'k' => $n,
                    'v' => $v,
                    'u' => $u,
                ];
            }

            $data[] = [
                'id'     => $row['id'],
                'name'   => $row['name'],
                'serial' => $row['serial'],
                'time'   => $time? date('H:i:s', $time): '-',
                'data'   => $d,
                'warn'   => $warn,
            ]; 
        }

        return $data;
    }
/*}}}*/

    /*{{{ perCalcData */
    private function perCalcData($probe) {
        $key = sprintf('dash.m.data.%s', $probe['uid']);
        $r = $this->safeRedis();
        if ($r && ($data = $r->get($key))) {
            return json_decode($data, true);
        }

        $dy = [];
        $y = date('Y');
        for($i = 1; $i <= 12; $i++) {
            $max = date('t', strtotime(sprintf('%s-%s-01', $y, $i)));
            for($j = 1; $j <= $max; $j++) {
                $day = sprintf('%s%02s%02s', $y, $i, $j);
                $dy[$day] = [
                    'ok' => 0,
                    'ng' => 0,
                ];
            }
        }
        
        $m = new \app\model\CalcDay();
        // 如果数据库没有历史数据，生成最近7天的测试数据
        if (!$m->count()) {
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Ymd', strtotime("-{$i} days"));
                if (isset($dy[$day])) {
                    $dy[$day]['ok'] = mt_rand(5, 15);
                    $dy[$day]['ng'] = mt_rand(0, 3);
                }
            }
        } else {
            if ($tmp = $m->select(['day', 'snap[JSON]'], ['day[<>]' => [$y*10000+101, $y*10000+1231], 'ORDER' => ['day']])) {
                foreach ($tmp as $row) {
                    $day = $row['day'];
                    $kk = sprintf('k%s', $probe['uid']);
                    if (isset($row['snap'][$kk])) {
                        $snap = $row['snap'][$kk];
                        if (isset($snap['g']) && count($snap['g']) > 1) {
                            $dy[$day]['ok'] += $snap['g'][0];
                            $dy[$day]['ng'] += $snap['g'][1];
                        }
                    }
                }
            }
        }

        $year = [
            'x'  => [],
            'ok' => [],
            'ng' => [],
        ];
        $month = [
            'x'  => [],
            'ok' => [],
            'ng' => [],
        ];
        foreach ($dy as $k => $v) {
            $x = sprintf('%s.%s', substr($k, 4, 2), substr($k, 6));
            
            $curM = date('Ym');
            if (strpos($k, $curM) === 0) {
                $month['x'][] = substr($k, 6);
                $month['ok'][] = $v['ok'];
                $month['ng'][] = $v['ng'];
            }
            
            // Year: Group by month
            $m_idx = (int)substr($k, 4, 2) - 1;
            if (!isset($year['ok'][$m_idx])) {
                $year['x'][$m_idx] = (string)($m_idx + 1);
                $year['ok'][$m_idx] = 0;
                $year['ng'][$m_idx] = 0;
            }
            $year['ok'][$m_idx] += $v['ok'];
            $year['ng'][$m_idx] += $v['ng'];
        }

        // Clean up year arrays (ensure consecutive keys)
        $year['x'] = array_values($year['x']);
        $year['ok'] = array_values($year['ok']);
        $year['ng'] = array_values($year['ng']);

        $data = [
            'year'  => $year,
            'month' => $month,
        ];
        if ($r) {
            $r->set($key, json_encode($data));
            $r->expire($key, 60);
        }

        return $data;
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
        switch($mode) {
        case 'run':
            $deviceSerial = 1; // 固定使用设备号1
            $str = sprintf('aa%02x0552554E', $deviceSerial);
            // 先生成内层CRC16-XMODEM，再封外层Next帧（/rpc/push 需要完整外层协议）
            $inner = $this->app->common->crc16xmodem($str); // AA010552554E19D0
            $data['data'] = $this->packNext(0x02, $inner, $outerTk);
           break; 
        case 'stp':
            $deviceSerial = 1; // 固定使用设备号1
            $str = sprintf('aa%02x05535450', $deviceSerial);
            $inner = $this->app->common->crc16xmodem($str); // AA0105535450EE2E
            $data['data'] = $this->packNext(0x02, $inner, $outerTk);
           break;
        case 'rst':
            $deviceSerial = 1; // 固定使用设备号1
            $str = sprintf('aa%02x05525354', $deviceSerial);
            $inner = $this->app->common->crc16xmodem($str); // AA0105525354000D
            $data['data'] = $this->packNext(0x02, $inner, $outerTk);
           break; 
        case 'pam':
            // 单个参数设置：支持cmd或addr作为地址参数
            $cmd = $this->params('cmd');
            if (!$cmd) {
                $cmd = $this->params('addr'); // 兼容addr参数名
            }
            $val = $this->params('val');
            
            $cmd = (int)$cmd;
            $val = (int)$val;
            
            $deviceSerial = 1; // 固定使用设备号1发送设置命令
            error_log(sprintf('[cmd:pam] 参数设置 > device_id=%d serial=%d addr=%d val=%d (使用固定设备号%d)', $id, $dev['serial'], $cmd, $val, $deviceSerial));
            
            $innerStr = sprintf('aa%02x07E1%02x%02x%02x%02x', 
                $deviceSerial,        // 固定使用设备号1
                $cmd >> 8, $cmd & 0xff,
                $val >> 8, $val & 0xff
            );
            $innerData = $this->app->common->crc16xmodem($innerStr);
            $data['data'] = $this->packNext(0x02, $innerData, $outerTk);
            
            // 保存命令发送后，清除该地址的旧查询结果，避免读取到旧值
            $r = $this->safeRedis();
            if ($r) {
                $key = sprintf('p:%s:%s', $dev['gid'], $dev['serial']);
                $field = sprintf('val_%d', $cmd);
                $r->hDel($key, $field); // 清除地址特定的旧值
                $r->hDel($key, 'val');  // 清除全局旧值
                error_log(sprintf('[cmd:pam] 已清除旧查询结果 > key=%s field=%s', $key, $field));
            }
           break; 
        case 'syn':
            $cmd = $this->params('cmd');
            if (!$cmd) {
                $cmd = $this->params('addr'); // 兼容addr参数名
            }
            $cmd = (int)$cmd;
            
            $deviceSerial = 1; // 强制使用设备号1
            
            error_log(sprintf('[cmd:syn] 参数查询 > device_id=%d serial=%d addr=%d (使用设备号%d)', $id, $dev['serial'], $cmd, $deviceSerial));
            
            // 注意：不清除旧值，因为：
            // 1. 设备响应可能很慢（超过10秒），清除旧值会导致查询时找不到数据
            // 2. 保留旧值可以用于判断是否是新值（通过时间戳或值变化）
            // 3. pomo[P]和single_query存储时会直接覆盖，不会冲突
            // 如果确实需要清除，应该在确认收到新值后再清除
            
            $innerStr = sprintf('aa%02x05E0%02x%02x', 
                $deviceSerial,        // 固定为1
                $cmd >> 8, $cmd & 0xff
            );
            $innerData = $this->app->common->crc16xmodem($innerStr);
            $data['data'] = $this->packNext(0x02, $innerData, $outerTk);
            
            error_log(sprintf('[cmd:syn] 发送指令 (内层) > %s', $innerData));
            
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
        // 注意：
        // - 前端会自己做短轮询（fastSync），因此这里不要做长时间阻塞轮询，
        //   否则在设备离线/慢响应时，接口会卡住并触发前端超时。
        // - 这里改为“快速读取”：按 addr 只读一次 val_{addr}，不存在则返回 null。
        set_time_limit(0);
        $id = $this->params('id');
        $addr = $this->params('addr'); // 获取地址参数
        $clearOld = $this->params('clearOld'); // 是否清除旧值（用于确保获取新值）

        $m = new \app\model\Dev();
        if (!$dev = $m->get(['serial', 'gid'], ['id' => $id])) {
            $this->json(40401, '设备未知');
        }

        $key = sprintf('p:%s:%s', $dev['gid'], $dev['serial']);
        $r = $this->safeRedis();
        $val = null;
        
        // 如果有地址参数，只读取地址特定的字段，不读取全局val字段
        if ($addr !== null && $r) {
            $field = sprintf('val_%d', (int)$addr);
            
            // 如果要求清除旧值，先清除（用于确保获取新值）
            if ($clearOld) {
                $r->hDel($key, $field);
                error_log(sprintf('[sync] 已清除旧值 > key=%s field=%s', $key, $field));
            }

            // 快速读取（不阻塞）：不存在则返回 null，交由前端轮询等待
            $val = $r->hGet($key, $field);
            if ($val === false || $val === '') {
                $val = null;
            }
            error_log(sprintf('[sync] Redis获取(按地址，快速) > key=%s field=%s val_raw=%s val_type=%s', $key, $field, var_export($val, true), gettype($val)));
        } else {
            // 没有地址参数时，才读取全局val字段（向后兼容）
            if ($r) {
                $val = $r->hGet($key, 'val');
                if (!$val) {
                    $val = $r->get($key);
                }
                error_log(sprintf('[sync] Redis获取(全局) > key=%s val_raw=%s val_type=%s', 
                    $key, $val, gettype($val)));
            }
            
            if (!$val) {
                $val = 0;
                error_log(sprintf('[sync] 无参数值 > key=%s, 返回默认值0', $key));
            }
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
