<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Dev.php
* @touch date Sat 25 May 2024 08:40:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\rpc;

class Dev extends \Next\Core\Control {

/*{{{ auth */
    public function auth() {
        if (!$sn = $this->params('sn')) {
            $this->json(40401, 'param sn not found');
        }
        if (!$gid = $this->kv($sn)) {
            $this->json(40402, 'serial not in db');
        }

        $key = sprintf('gw:%s', $gid);
        $this->app->redis->hSet($key, 't', time());
        $this->app->redis->expire($key, 300);

        // 记录设备局域网IP（用于直连）
        $clientIP = $this->app->request->headers('X-Real-IP') 
                 ?: $this->app->request->headers('X-Forwarded-For') 
                 ?: $_SERVER['REMOTE_ADDR'] 
                 ?? '';
        if ($clientIP) {
            $ipKey = sprintf('gw:ip:%s', $gid);
            $this->app->redis->set($ipKey, $clientIP, 3600); // 缓存1小时
            error_log(sprintf('Gateway %s IP recorded: %s', $gid, $clientIP));
        }

        $this->json(0, sprintf('sn: %s auth success', $sn));
    }
/*}}}*/
/*{{{ pomo */
    public function pomo() {
        if (!$sn = $this->params('sn')) {
            $this->json(40401, 'param sn not found');
        }
        if (!$gid = $this->kv($sn)) {
            $this->json(40402, 'gatewary not in db');
        }
        $pack = $this->params('data');

        // debug trace
        $plen = is_string($pack)? strlen($pack): 0;
        $ppfx = is_string($pack)? substr($pack, 0, 20): '';
        error_log(sprintf('rpc/dev/pomo sn=%s gid=%s len=%s pfx=%s', $sn, $gid, $plen, $ppfx));

        $m = new \app\model\Proto();
        if (!$d = $m->decode($pack)) {
            error_log('rpc/dev/pomo decode empty');
            $this->json(40401, 'data error');
        }

        // P模式：单个参数查询/设置响应
        if ($d['mode'] == 'P') {
            // 强制固定地址为1
            $d['sn'] = 1; 
            
            // 查找数据库中该网关下的设备，将地址1映射到该网关下的第一个设备
            $devSerial = '1'; 
            $mDev = new \app\model\Dev();
            try {
                $devices = $mDev->select(['serial'], ['gid' => $gid, 'status[!]' => 9]);
                if (count($devices) >= 1) {
                    $devSerial = $devices[0]['serial'];
                    error_log(sprintf('rpc/dev/pomo [P] 固定地址1映射到数据库serial=%s (gid=%s)', $devSerial, $gid));
                }
            } catch (\Exception $e) {
                error_log(sprintf('rpc/dev/pomo [P] 查询设备失败: %s', $e->getMessage()));
            }
            
            $key = sprintf('p:%s:%s', $gid, $devSerial);
            $addr = isset($d['addr']) ? (int)$d['addr'] : null;
            $val = isset($d['val']) ? $d['val'] : null;
            
            if ($addr !== null && $val !== null) {
                $field = sprintf('val_%d', $addr);
                $this->app->redis->hSet($key, $field, $val);
                $this->app->redis->hSet($key, 'val', $val); // 向后兼容
                $this->app->redis->expire($key, 300);
            }
        }

        // LIFE模式：心跳或确认帧，仅更新在线状态
        if ($d['mode'] == 'LIFE') {
            $time = time();
            $mDev = new \app\model\Dev();
            $resolvedDevSerial = '1'; 
            try {
                $devices = $mDev->select(['serial'], ['gid' => $gid, 'status[!]' => 9]);
                if (count($devices) >= 1) {
                    $resolvedDevSerial = $devices[0]['serial'];
                }
            } catch (\Exception $e) {}

            $key = sprintf('d:%s:%s', $gid, $resolvedDevSerial);
            $this->app->redis->hSet($key, 't', $time);
            $this->app->redis->expire($key, 300);
        }

        // CK
        if ($d['mode'] == 'CK') {
            $time = time();
            // 强制固定地址为1
                $devSerial = '1';
            
            // 查找数据库中该网关下的设备，将地址1映射到该网关下的第一个设备
            $mDev = new \app\model\Dev();
            $resolvedDevSerial = '1'; 
            try {
                $devices = $mDev->select(['serial'], ['gid' => $gid, 'status[!]' => 9]);
                if (count($devices) >= 1) {
                    $resolvedDevSerial = $devices[0]['serial'];
                    error_log(sprintf('rpc/dev/pomo [CK] 固定地址1映射到数据库serial=%s (gid=%s)', $resolvedDevSerial, $gid));
                }
            } catch (\Exception $e) {
                error_log(sprintf('rpc/dev/pomo [CK] 查询设备失败: %s', $e->getMessage()));
            }

            $key = sprintf('d:%s:%s', $gid, $resolvedDevSerial);
            $this->app->redis->hSet($key, 't', $time);
            $this->app->redis->hSet($key, 'd_raw', json_encode($d));
            $this->app->redis->hSet($key, 'p', $pack);
            $this->app->redis->expire($key, 300);

            // 映射 feature 到 d 字段
            try {
                if ($row = $mDev->get(['feature[JSON]'], ['gid' => $gid, 'serial' => $resolvedDevSerial])) {
                    $feat = isset($row['feature']) ? (array)$row['feature'] : [];
                    $map  = [];
                    $vals = isset($d['val']) ? (array)$d['val'] : [];
                    $flat = [];
                    foreach ($vals as $vv) {
                        if (is_array($vv) && array_key_exists('v', $vv)) {
                            $flat[] = $vv['v'];
                        } else {
                            $flat[] = $vv;
                        }
                    }
                    $i = 0;
                    foreach ($feat as $fk) {
                        if (isset($flat[$i])) {
                            $map[$fk] = is_numeric($flat[$i]) ? 0 + $flat[$i] : $flat[$i];
                        }
                        $i++;
                    }
                    if ($map) {
                        $this->app->redis->hSet($key, 'd', json_encode($map));
                    }
                }
            } catch (\Throwable $e) {
                error_log('pomo map feature error: '.$e->getMessage());
            }

            // 日志入库
            $ekey = sprintf('e:%s:%s', $gid, $resolvedDevSerial);
                if (!$this->app->redis->exists($ekey)) {
                    $this->app->redis->set($ekey, $time);
                    $this->app->redis->expire($ekey, 60);
                $mLog = new \app\model\DevLog();
                $mLog->pomo($gid, $d, $pack, $resolvedDevSerial);
            }
        }

        $this->json(0, sprintf('sn: %s pomo success', $sn));
    }
/*}}}*/
/*{{{ mock */
    public function mock() {
        if (!$sn = $this->params('sn')) {
            $this->json(40401, 'param sn not found');
        }
        if (!$gid = $this->kv($sn)) {
            $this->json(40402, 'gatewary not in db');
        }
            $dev = '1';

        $key = sprintf('d:%s:%s', $gid, $dev);
        $m = new \app\model\Dev();
        $row = $m->get(['feature[JSON]'], ['gid' => $gid, 'serial' => $dev]);
        $feat = $row && isset($row['feature']) ? $row['feature'] : [];
        $map = [];
        $i = 0;
        foreach ($feat as $k) {
            if (stripos($k, 'pf') !== false || stripos($k, 'factor') !== false) {
                $map[$k] = 0.96 + (mt_rand(0, 3) / 100);
            } else if ($i % 7 == 0) {
                $map[$k] = mt_rand(220, 235);
            } else if ($i % 7 == 1) {
                $map[$k] = mt_rand(8, 15) + mt_rand(0, 9) / 10;
            } else if ($i % 7 == 2) {
                $map[$k] = mt_rand(2, 5) + mt_rand(0, 99) / 100;
            } else if ($i % 7 == 3) {
                $map[$k] = mt_rand(0, 2) + mt_rand(0, 99) / 100;
            } else if ($i % 7 == 4) {
                $map[$k] = mt_rand(40, 60) + mt_rand(0, 9) / 10;
            } else {
                $map[$k] = mt_rand(0, 1) ? 0 : 1;
            }
            $i++;
        }
        $this->app->redis->hSet($key, 't', time());
        $this->app->redis->hSet($key, 'd', json_encode($map));
        $this->app->redis->expire($key, 300);

        $this->json(0, 'mock success', ['key' => $key]);
    }
/*}}}*/
/*{{{ kv */
    private function kv($sn) {
        $key = 'g.gateway';
        if (!$this->app->redis->exists($key)) {
            $m = new \app\model\Gateway();
            if ($tmp = $m->select(['id', 'serial'], ['status[!]' => 9])) {
                foreach ($tmp as $row) {
                    $this->app->redis->hSet($key, $row['serial'], $row['id']);
                } 
                $this->app->redis->expire($key, 600);
            }
        }
        return $this->app->redis->hGet($key, $sn);
    }
/*}}}*/

/*{{{ batch_query */
    public function batch_query() {
        if (!$sn = $this->params('sn')) {
            $this->json(40401, 'param sn not found');
        }
        if (!$gid = $this->kv($sn)) {
            $this->json(40402, 'gateway not found');
        }
        
        $values = $this->params('values');
        $count = $this->params('count');
        $deviceSerialFromGo = $this->params('deviceSerial');
        
        // 强制地址1映射到第一个设备
        $resolvedDevSerial = '1'; 
        $mDev = new \app\model\Dev();
        try {
            $devices = $mDev->select(['serial'], ['gid' => $gid, 'status[!]' => 9]);
            if (count($devices) >= 1) {
                $resolvedDevSerial = $devices[0]['serial'];
            }
        } catch (\Exception $e) {
            error_log('rpc/dev/batch_query fail: ' . $e->getMessage());
        }

        $key = sprintf('p:%s:%s', $gid, $resolvedDevSerial);
        error_log(sprintf('[rpc/batch_query] 收到通知 > sn=%s gid=%s dev=%s resolved=%s key=%s count=%d', $sn, $gid, $deviceSerialFromGo, $resolvedDevSerial, $key, $count));
        
        $responseData = [
            'values' => $values,
            'count' => $count,
            'timestamp' => time()
        ];
        
        $this->app->redis->hSet($key, 'batch_data', json_encode($responseData));
        $this->app->redis->expire($key, 60);
        $this->json(0, 'success');
    }
/*}}}*/
/*{{{ single_query */
    public function single_query() {
        if (!$sn = $this->params('sn')) {
            $this->json(40401, 'param sn not found');
        }
        if (!$gid = $this->kv($sn)) {
            $this->json(40402, 'gateway not found');
        }
        
        $addr = $this->params('addr');
        $val = $this->params('val');
        
        // 强制地址1映射到第一个设备
        $resolvedDevSerial = '1';
        $mDev = new \app\model\Dev();
        try {
            $devices = $mDev->select(['serial'], ['gid' => $gid, 'status[!]' => 9]);
            if (count($devices) >= 1) {
                $resolvedDevSerial = $devices[0]['serial'];
            }
        } catch (\Exception $e) {
            error_log('rpc/dev/single_query fail: ' . $e->getMessage());
        }
        
        $key = sprintf('p:%s:%s', $gid, $resolvedDevSerial);
        if ($addr !== null && $addr !== '') {
            $field = sprintf('val_%d', (int)$addr);
            $this->app->redis->hSet($key, $field, $val);
            $this->app->redis->hSet($key, 'val', $val); // 向后兼容
            $this->app->redis->expire($key, 300);
        }
        $this->json(0, 'success');
    }
/*}}}*/

}
