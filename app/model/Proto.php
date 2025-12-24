<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Proto.php
* @touch date Sun 21 Feb 2016 21:47:05 PM CST 
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Proto extends \Next\Core\Model {
    protected $table = 'b_dev';
    private $tg = 'b_gateway';

/*{{{ make */
    public function make() {
        $dev = [];
        $proto = [];

        // load all dev with proto v2
        if ($tmp = $this->db->select($this->table, ['serial', 'feature[JSON]', 'proto', 'gid[Int]'], ['status' => 1])) {
            $gids = array_map(function($row) {
                return $row['gid'];
            }, $tmp);

            // load gateway sn
            $gateway = [];
            if ($t = $this->db->select($this->tg, ['id[Int]', 'serial'], ['id' => $gids, 'status[!]' => 9])) {
                foreach ($t as $row) {
                    $gateway[$row['id']] = $row['serial'];
                }
            }

            // -------------------------------
            // make proto struct, sample below
            // -------------------------------
            // dev:
            //   k1: {proto: ['c1', 'c2', 'c3', 'c4']}
            //   k2: {proto: ['c11', 'c12']}
            // proto:
            //   c1: '010300000002c40b'
            //   c2: '020300000002c438'
            //   c3: '0303000d0001142b'
            //   c4: '040300000002c45e'
            //   c11: '0B0300000002C4A1'
            //   c12: '0C0300000002C516'
            // -------------------------------

            foreach ($tmp as $row) {
                if (!isset($gateway[$row['gid']])) {
                    continue;
                }
                // v2.0's feature
                if ($row['proto'] == '2') {
                    // set dev
                    $key = sprintf('k%s', $gateway[$row['gid']]);
                    if (!isset($dev[$key])) {
                        $dev[$key]['proto'] = [];
                    }

                    // set proto
                    $cmd = sprintf('c1_%s', $row['serial']);
                    if (!isset($proto[$cmd])) {
                        // system state cmd
                        try {
                            $proto[$cmd] = $this->app->common->crc16xmodem(sprintf('aa%02s0541564b', dechex($row['serial'])), true);
                        } catch (Exception $e) {
                            // 如果crc16xmodem失败，使用默认值
                            $proto[$cmd] = 'aa' . sprintf('%02s', dechex($row['serial'])) . '0541564b';
                        }
                    }

                    $dev[$key]['proto'][] = $cmd; 
                }
            }
        }

        $data = [
            'dev'   => $dev,
            'proto' => $proto,
        ];
        $yml = new \Next\Helper\Yaml();
        $content = $yml->dump($data);
        
        // 确保目录存在
        $dir = './cmd/pomo/bin';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (!@file_put_contents('./cmd/pomo/bin/data.yml', $content)) {
            $this->app->log->error('proto.make > save yml file fail');
            $this->app->log->error($data);
            $this->app->log->error($content);
            // 不返回false，继续执行
        }

        return true;
    }
/*}}}*/
/*{{{ decode */
    public function decode($pack) {
        //$pack = '550178434b00000000005900000000005800000000006300000000ff9f00000000ff9d00000000ff9e232bd7001b670003000200031b7a0615000003e70000000003e7000400020002000000000003000200030000000000001999199919990012000c0012ffffffffe7ff0000000000000000000000000010ff7b';
        $x = [];
        if (is_string($pack) && strlen($pack) % 2 == 0) {
            for($i = 0; $i < strlen($pack); $i = $i+2) {
                $x[] = hexdec(substr($pack, $i, 2));
            }
        }

        $d = [];
        $size  = count($x);
        if ($size < 4) {
            return $d;
        }

        // P: 55 01 07 50 02 {addr_hi} {addr_lo} {val_hi} {val_lo} ...
        if ($x[3] == 80) { // 'P'
            // 长度与类型校验：x[2]==0x07 且 至少到 val_lo
            if ($x[2] != 7 || $size < 10) {
                return $d;
            }

            $d['sn'] = $x[1];
            $d['mode'] = 'P';
            // 与设备当前帧对齐：addr 位于 [4,5]，val 位于 [6,7]（大端序）
            $d['addr'] = ($x[4] << 8) | $x[5];
            // 设备返回大端序数据：val 位于 [6,7]（高字节在前）
            $rawVal = ($x[6] << 8) | $x[7];
            
            // 特殊处理：地址513且低位是0x8X时，将低位从0x8X改为0x0X
            if ($d['addr'] == 513 && ($x[7] & 0xF0) == 0x80) {
                $d['val'] = ($x[6] << 8) | ($x[7] & 0x0F);
            } else {
                $d['val'] = $rawVal;
            }
            
            // Debug: 记录解析过程
            error_log(sprintf('[Proto.P] 解析 > addr_raw=[%d,%d] val_raw=[%d,%d] addr=%d val=%d', 
                $x[4], $x[5], $x[6], $x[7], $d['addr'], $d['val']));
            return $d;
        }

        // CK
        if ($x[3] == 67 && $x[4] == 75) {
            if ($x[2] != 120 || $size < 120) {
                return $d;
            }
            $d['sn'] = $x[1];
            $d['mode'] = 'CK';
            // sendbuff[port][2] =120;//回复数据长度为120
            // sendbuff[port][3] ='C';
            // sendbuff[port][4] ='K';

            $d['f01'] = $this->b($x[5]<<8|$x[6]); //系统A相有功 单位0.01kW
            $d['f02'] = $this->b($x[7]<<8|$x[8]); //系统A相无功 单位0.01kvar
            $d['f03'] = $this->b($x[9]<<8|$x[10]); //系统A相功率因数 0.01

            $d['f04'] = $this->b($x[11]<<8|$x[12]); //系统B相有功
            $d['f05'] = $this->b($x[13]<<8|$x[14]); //系统B相无功
            $d['f06'] = $this->b($x[15]<<8|$x[16]); //系统B相功率因数

            $d['f07'] = $this->b($x[17]<<8|$x[18]); //系统C相有功
            $d['f08'] = $this->b($x[19]<<8|$x[20]); //系统C相无功
            $d['f09'] = $this->b($x[21]<<8|$x[22]); //系统C相功率因数

            $d['f10'] = $this->b($x[23]<<8|$x[24]); //输出A相有功   单位0.01kW
            $d['f11'] = $this->b($x[25]<<8|$x[26]); //输出A相无功   单位0.01kvar
            $d['f12'] = $this->b($x[27]<<8|$x[28]); //输出A相功率因数   0.01

            $d['f13'] = $this->b($x[29]<<8|$x[30]); //输出B相有功
            $d['f14'] = $this->b($x[31]<<8|$x[32]); //输出B相无功
            $d['f15'] = $this->b($x[33]<<8|$x[34]); //输出B相功率因数

            $d['f16'] = $this->b($x[35]<<8|$x[36]); //输出C相有功
            $d['f17'] = $this->b($x[37]<<8|$x[38]); //输出C相无功
            $d['f18'] = $this->b($x[39]<<8|$x[40]); //输出C相功率因数

            $d['f19'] = $x[41]<<8|$x[42]; //FPGA版本号

            $d['f20'] = $x[43]; //状态
            $d['f21'] = $x[44]; //预留

            $d['f22'] = $x[45]<<8|$x[46]; //DSPB版本号

            $d['f23'] = ($x[47]<<8|$x[48]) / 10; //A相电网电压 0.1V
            $d['f24'] = ($x[49]<<8|$x[50]) / 10; //B相电网电压
            $d['f25'] = ($x[51]<<8|$x[52]) / 10; //C相电网电压

            $d['f26'] = $x[53]<<8|$x[54]; //DSPA版本号
            $d['f27'] = ($x[59]<<8|$x[60]) / 10; //电网电流谐波畸变率最大值？ 0.01 预留

            $d['f28'] = ($x[61]<<8|$x[62]) / 100; //电网频率  0.01Hz
            $d['f29'] = $this->b($x[63]<<8|$x[64], 10); //A相温度
            $d['f30'] = ($x[65]<<8|$x[66]) / 10; //电网电压谐波畸变率最大值？ 预留
            $d['f31'] = $x[67]<<8|$x[68]; //直流母线电压  1V
            $d['f32'] = $x[69]<<8|$x[70]; //上分裂电容电压 1V
            $d['f33'] = $x[71]<<8|$x[72]; //下分裂电容电压 1V
            $d['f34'] = $this->b($x[73]<<8|$x[74], 10); //B相温度 0.1℃
            $d['f35'] = $this->b($x[75]<<8|$x[76], 10); //C相温度
            $d['f36'] = ($x[77]<<8|$x[78]) / 10; //装置A相电流 0.1A
            $d['f37'] = ($x[79]<<8|$x[80]) / 10; //装置B相电流
            $d['f38'] = ($x[81]<<8|$x[82]) / 10; //装置C相电流

            $d['f39'] = $x[83]<<8|$x[84]; //A相负载电流 0.1A
            $d['f40'] = $x[85]<<8|$x[86]; //B相负载电流
            $d['f41'] = $x[87]<<8|$x[88]; //C相负载电流

            $d['f42'] = $x[89]<<8|$x[90]; //A相网侧电流  0.1A
            $d['f43'] = $x[91]<<8|$x[92]; //B相网侧电流
            $d['f44'] = $x[93]<<8|$x[94]; //C相网侧电流

            $d['f45'] = ($x[95]<<8|$x[96]) / 10; //A相设备电流  0.1A 
            $d['f46'] = ($x[97]<<8|$x[98]) / 10; //B相设备电流
            $d['f47'] = ($x[99]<<8|$x[100]) / 10; //C相设备电流

            $d['f48'] = $x[101]; //故障及状态信息5
            $d['f49'] = $x[102]; //故障及状态信息4
            $d['f50'] = $x[103]; //故障及状态信息3
            $d['f51'] = $x[104]; //故障及状态信息2
            $d['f52'] = $this->e($x[105], 0, [5, 6]); //EXTSTATE0
            $d['f53'] = $this->e($x[106], 2, [3, 4, 5, 6]); //EXTSTATE2

            $d['f54'] = $this->b($x[107]<<8|$x[108]); //负载A相有功，上送给后台的功率信息用电网和这个负载的？是的
            $d['f55'] = $this->b($x[109]<<8|$x[110]); //负载A相无功 0.01kvar
            $d['f56'] = $this->b($x[111]<<8|$x[112]); //负载B相有功
            $d['f57'] = $this->b($x[113]<<8|$x[114]); //负载B相无功
            $d['f58'] = $this->b($x[115]<<8|$x[116]); //负载C相有功
            $d['f59'] = $this->b($x[117]<<8|$x[118]); //负载C相无功
            $d['f60'] = $this->m($x[119]<<8|$x[120]); //工作模式

            // action control: run, stp, rst
            // 根据用户反馈：逻辑再次反转回来
            // b1=0 表示运行中，禁用启动按钮 (run)
            // b1=1 表示已停止，禁用停机按钮 (stp)
            $b1 = ($x[43] & 0x02) == 0? 0: 1;
            if ($b1 == 0) {
                $d['limit'] = ['run'];
            } else {
                $d['limit'] = ['stp'];
            }
            return $d;
        }

        // ACK (55 [dev] [len] 41 43 4B ...)
        if ($size >= 6 && $x[3] == 65 && $x[4] == 67 && $x[5] == 75) {
            $d['sn'] = $x[1];
            $d['mode'] = 'LIFE';
            return $d;
        }

        return $d;
    } 
/*}}}*/

/*{{{ b */
    private function b($v, $percent = 100) {
        if (($v & 0x8000) !== 0) {
            return -((~$v + 1) & 0xffff) / $percent;
        }
        return $v / $percent;
    }
/*}}}*/
/*{{{ e */
    private function e($v, $k, $pos) {
        $e = [];
        $i18n = $this->app->i18n;
        foreach ($pos as $i) {
            if (
                ($i == 0 && ($v & 0x01) == 0) || 
                ($i == 1 && ($v & 0x02) == 0) || 
                ($i == 2 && ($v & 0x04) == 0) || 
                ($i == 3 && ($v & 0x08) == 0) || 
                ($i == 4 && ($v & 0x10) == 0) || 
                ($i == 5 && ($v & 0x20) == 0) || 
                ($i == 6 && ($v & 0x40) == 0) || 
                ($i == 7 && ($v & 0x80) == 0)
            ) {
                $x = sprintf('err %s%s', $k, $i);
                $e[] = isset($i18n[$x])? $i18n[$x]: '-';
            }
        }

        return implode(', ', $e);
    }
/*}}}*/
/*{{{ m */
    private function m($v) {
        $i18n = $this->app->i18n;
        // ✨ 修复：255 (0xFF) 通常表示无效值或未初始化，应返回"未知模式"
        if ($v == 255 || $v == 0xFF) {
            return isset($i18n['mod 0'])? $i18n['mod 0']: '未知模式';
        }
        
        $m = [
            1,   2,   4,   6, 
            13,  14,  15,  16,
            35,  39,  129, 130, 
            132, 134, 141, 142,
            143, 144, 163, 167
        ];
        $x = in_array($v, $m)? sprintf('mod %s', $v): 'mod 0';

        return isset($i18n[$x])? $i18n[$x]: '-';
    }
/*}}}*/

}
