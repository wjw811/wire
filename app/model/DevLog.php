<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename DevLog.php
* @touch date Tue 16 Apr 2019 09:06:39 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class DevLog extends \Next\Core\Model {
    protected $table = 'b_dev_log';
    private $tdw = 'b_dev_warn';
    private $td = 'b_dev';
    private $tu = 's_user';

/*{{{ pomo */
    public function pomo($gid, $d, $pack, $devSerial = null) {
        return $this->db->action(function($db) use($gid, $d, $pack, $devSerial) {
            $e = json_encode($d, JSON_UNESCAPED_UNICODE);
            $serial = $devSerial ?: $d['sn'];
            if (!$dev = $db->get($this->td, ['id', 'uid', 'name'], ['gid' => $gid, 'serial' => $serial, 'status[!]' => 9])) {
                $this->app->log->error('devlog.pomo > dev not found');
                $this->app->log->error(sprintf('gid: %s, serial: %s, d_sn: %s, d: %s', $gid, $serial, $d['sn'], $e));
                return false;
            }
            $did = $dev['id'];
            // dev log
            $a = [
                'did'     => $did,
                'raw'     => $pack,
                'content' => $e,
                'created' => $db->raw('now()'),
                'updated' => $db->raw('now()'),
            ];
            if (!$db->insert($this->table, $a)->rowCount()) {
                $this->app->log->error('devlog.pomo > insert dev log table fail');
                $this->app->log->error(sprintf('gid: %s, d: %s', $gid, $e));
                return false;
            }

            // warn
            $w = [];
            $arr = ['f48', 'f49', 'f50', 'f51'];
            foreach ($arr as $k) {
                // ✨ 修复：根据协议，位为0表示有故障，255 (0xFF) 表示无故障。
                // 如果值存在且不是 255，则认为存在故障位。
                if (isset($d[$k]) && $d[$k] !== 255 && $d[$k] !== "255" && $d[$k] !== "") {
                    $w[$k] = $d[$k];
                }
            }
            $key = sprintf('d:%s:%s', $gid, $serial);
            if ($w) {
                $a = [
                    'did'     => $did,
                    'day'     => date('Ymd'),
                    'content' => json_encode($w, JSON_UNESCAPED_UNICODE),
                    'status'  => 0,
                    'created' => $db->raw('now()'),
                    'updated' => $db->raw('now()'),
                ];
                if (!$db->insert($this->tdw, $a)->rowCount()) {
                    $this->app->log->error('devlog.pomo > insert dev warn table fail');
                    $this->app->log->error(sprintf('gid: %s, d: %s', $gid, $e));
                    return false;
                }
                $this->app->redis->hSet($key, 's', 1);

                // mail
                $str = implode(', ', $w);
                $to = $db->get($this->tu, 'email', ['id' => $dev['uid']]);
                $mail = new \Next\Helper\PHPMailer();
                if ($to) {
                    $mail->addAddress($to);
                } else {
                    $mail->addAddress($mail->From);
                }
                $mail->Subject = 'Device Warning';
                $mail->Body =  sprintf('Device Serial: %s, msg: %s', $d['sn'], $str);
                try {
                    if (!$mail->send()) {
                        $this->app->log->error('devlog.pomo > mail send fail');
                    }
                } catch (\Exception $e) {
                    $this->app->log->error('devlog.pomo > mail send exception');
                    $this->app->log->error($e->getMessage());
                }
            } else {
                // ✨ 修复：当没有故障时（所有故障字段为255），清除 Redis 中的报警状态
                $this->app->redis->hSet($key, 's', 0);
            }

            return true;
        });
    }
/*}}}*/

/*{{{ load4chart */
    public function load4chart($did, $start, $end) {
        $out = [];

        $q = sprintf('SELECT id, content, created FROM %s WHERE did=:did AND (created BETWEEN :start AND :end);', $this->table);
        $w = [
            ':did'   => $did,
            ':start' => $start,
            ':end'   => $end,
        ];
        if (!$query = $this->db->query($q, $w)) {
            return $out;
        }

        // ---------------------
		// build struct
        // ---------------------
        // 1 day / minutes
        // 30 day / hours
        // >30 day / day
        $data = [];
        $days = ceil((strtotime($end) - strtotime($start)) / 86400);
        if ($days == 1) {
            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < 60; $j++) {
                    $t = sprintf('%02s:%02s', $i, $j);
                    $data[$t] = [];
                }
            }
            while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
                $t = date('H:i', strtotime($row['created']));
                if (!$data[$t]) {
                    $data[$t] = json_decode($row['content'], true);
                }
            }
        } else if ($days <= 30) {
            for ($i = 0; $i < $days; $i++) {
                $d = date('Ymd', strtotime(sprintf('%s +%s day', $start, $i)));
                for ($j = 0; $j < 24; $j++) {
                    $t = sprintf('%s/%02s', $d, $j);
                    $data[$t] = [];
                }
            }
            while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
                $t = date('Ymd/H', strtotime($row['created']));
                if (!$data[$t]) {
                    $data[$t] = json_decode($row['content'], true);
                }
            }
        } else {
            for ($i = 0; $i < $days; $i++) {
                $t = date('Ymd', strtotime(sprintf('%s +%s day', $start, $i)));
                $data[$t] = [];
            }
            while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
                $t = date('Ymd', strtotime($row['created']));
                if (!$data[$t]) {
                    $data[$t] = json_decode($row['content'], true);
                }
            }
        }

        // format json
        foreach ($data as $k => $v) {
            $out['x'][] = $k;
            for ($i = 1; $i <= 60; $i++) {
                $key = sprintf('f%02s', $i);
                $out[$key][] = @$v[$key];
            }
        }

        return $out;
    }
/*}}}*/

}
