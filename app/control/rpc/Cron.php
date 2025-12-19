<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Cron.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\rpc;

class Cron extends \Next\Core\Control {

/*{{{ construct *
    public function __construct() {
        parent::__construct();
    }
/*}}}*/

/*{{{ index */
    /*
     * crontab: 1 minute
     **/
    public function index() {
        $this->resp(0, 'cron.index > success');
    }
/*}}}*/
/*{{{ bin */
    /*
     * crontab: 1 minute
     **/
    public function bin() {
        $data = [];

        $m = new \app\model\Gateway();
        if ($tmp = $m->select(
            ['id[Int]', 'serial', 'fid[Int]', 'upgrade[Int]', 'updated(time)'],
            ['fid[!]' => 0, 'upgrade[!]' => -1, 'status[!]' => 9]
        )) {
            $fids = [];
            foreach ($tmp as $row) {
                $fids[] = $row['fid'];
            }
            $fireware = [];
            $mf = new \app\model\Fireware();
            if ($x = $mf->select(['id[Int]', 'ver[Int]', 'file', 'size[Int]'], ['id' => $fids])) {
                foreach ($x as $row) {
                    $fireware[$row['id']] = $row;
                }
            }

            $time = time() - 600;
            foreach ($tmp as $row) {
                $fid = $row['fid'];
                $idx = $row['upgrade'];
                $ut = strtotime($row['time']);
                if ($idx != 0 || $ut < $time) {
                    if (isset($fireware[$fid])) {
                        $bin = $fireware[$fid];
                        $ver = $bin['ver'];
                        $max = $mf->num($bin['size']);
                        if ($hex = $mf->slice($bin['file'], $idx)) {
                            $str = sprintf(
                                '0000%02x%02x%02x%02x%02x%02x%s', 
                                $ver >> 8, $ver & 0xff, 
                                $max >> 8, $max & 0xff, 
                                $idx >> 8, $idx & 0xff, 
                                bin2hex($hex)
                            );
                            $d = [
                                'sn'   => $row['serial'],
                                'chan' => 0x01,
                                'data' => $str,
                            ];
                            $m->invoke('/bin', $d);
                            usleep(10000);
                        }
                    }
                }

            }
        }

        $this->json(0, 'cron.bin > success');
    }
/*}}}*/

/*{{{ calc */
    public function calc() {
        if ($day = $this->params('day')) {
            if (!$dt = strtotime($day)) {
                $this->json(401, "传入的参数day格式有误(格式: Ymd)");
            }
            $day = date('Ymd', $dt);
        } else {
            $day = date('Ymd', strtotime('-1 day'));
        }
        $m = new \app\model\CalcDay();
        if (!$m->make($day)) {
            $this->json(404, "定时计划执行失败");
        }

        $this->json(0, "定时计划执行成功");
    }
/*}}}*/

}
