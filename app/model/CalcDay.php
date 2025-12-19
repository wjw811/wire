<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename CalcDay.php
* @touch date Wed 20 Nov 2024 05:54:07 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class CalcDay extends \Next\Core\Model {
    protected $table = 'b_calc_day';
    private $tc = 'b_com';
    private $td = 'b_dev';
    private $tg = 'b_gateway';
    private $tdw = 'b_dev_warn';
    private $tu = 's_user';

/*{{{ make */
    public function make($day) {
        // data struct: {com:{g:[100,100], spd:[20,100]...}}
        $d = [];
        // feature list
        $map = [];

        // init data
        $fe = ['g'];
        $m = new \app\model\Option();
        if ($tmp = $m->get('value[JSON]', ['code' => 'feature'])) {
            foreach ($tmp as $row) {
                $k = $row['key'];
                if (isset($map[$k])) {
                    $k = $map[$k];
                }
                if (!in_array($k, $fe)) {
                    $fe[] = $k;
                }
            }
        }
        $com = ['0'];
        if ($tmp = $this->db->select($this->tu, 'id', ['status[!]' => 9])) {
            $com = array_merge($com, $tmp);
        }
        foreach ($com as $k1) {
            $k1 = 'k'.$k1;
            $d[$k1] = [];
            foreach ($fe as $k2) {
                $d[$k1][$k2] = [0, 0];
            }
        }

        // map dev vs com
        $dvc = [];
        if ($tmp = $this->db->select($this->td, ['id', 'uid'], ['status[!]' => 9])) {
            foreach ($tmp as $row) {
                if ($uid = $row['uid']) {
                    $kk = 'k'.$uid;
                    $dvc[$row['id']] = $kk;
                    if (isset($d[$kk])) {
                        foreach ($d[$kk] as $k => $v) {
                            $d[$kk][$k][0] += 1;
                        }
                    }
                }
                // calc all for admin
                $kk = 'k0';
                if (isset($d[$kk])) {
                    foreach ($d[$kk] as $k => $v) {
                        $d[$kk][$k][0] += 1;
                    }
                }
            }
        }

        // warn dev
        $wm = [];
        $wa = [];
        if ($tmp = $this->db->select($this->tdw, ['did', 'content[JSON]'], ['day' => $day])) {
            foreach ($tmp as $row) {
                $did = $row['did'];
                if (isset($dvc[$did])) {
                    $kk = $dvc[$did];
                    if (!isset($wm[$did])) {
                        $wm[$did] = [];
                        $d[$kk]['g'][0] = max($d[$kk]['g'][0] - 1, 0);
                        $d[$kk]['g'][1] += 1;
                    }

                    foreach ($row['content'] as $k => $v) {
                        if (in_array($k, $wm[$did])) {
                            continue;
                        }
                        // merge same item
                        if (isset($map[$k])) {
                            $k = $map[$k];
                        }

                        $wm[$did][] = $k;
                        if (isset($d[$kk][$k])) {
                            $d[$kk][$k][0] = max($d[$kk][$k][0] - 1, 0);
                            $d[$kk][$k][1] += 1;
                        }
                    }
                }
                // calc all for admin
                $kk = 'k0';
                if (!isset($wa[$did])) {
                    $wa[$did] = [];
                    $d[$kk]['g'][0] = max($d[$kk]['g'][0] - 1, 0);
                    $d[$kk]['g'][1] += 1;
                }
                if (is_array($row['content'])) {
                    foreach ($row['content'] as $k => $v) {
                        if (in_array($k, $wa[$did])) {
                            continue;
                        }
                        // merge same item
                        if (isset($map[$k])) {
                            $k = $map[$k];
                        }

                        $wa[$did][] = $k;
                        if (isset($d[$kk][$k])) {
                            $d[$kk][$k][0] = max($d[$kk][$k][0] - 1, 0);
                            $d[$kk][$k][1] += 1;
                        }
                    }
                }
            }
        }

        // save into calc table
        if ($id = $this->get('id', ['day' => $day])) {
            $u = [
                'snap'    => json_encode($d),
                'updated' => $this->db->raw('now()'),
            ];
            $w = [
                'id' => $id,
            ];
            if (!$this->update($u, $w)->rowCount()) {
                $this->app->log->error('calcday.make > update calc table error');
                $this->app->log->error($d);
                return false;
            }

            return true;
        }

        $a = [
            'day'     => $day,
            'snap'    => json_encode($d),
            'created' => $this->db->raw('now()'),
            'updated' => $this->db->raw('now()'),
        ];
        if (!$this->insert($a)->rowCount()) {
            $this->app->log->error('calcday.make > insert calc table error');
            $this->app->log->error($d);
            return false;
        }

        return true;
    }
/*}}}*/

}

