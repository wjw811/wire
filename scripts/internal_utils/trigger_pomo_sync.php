<?php
namespace {
    class MockApp {
        public $db;
        public $common;
        public function __construct() {
            $this->db = new class {
                public function select($table, $fields, $where) {
                    $db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
                    $fieldStr = implode(',', array_map(function($f) {
                        return str_replace('[Int]', '', str_replace('[JSON]', '', $f));
                    }, $fields));
                    $whereStr = '1=1';
                    $params = [];
                    foreach ($where as $k => $v) {
                        if (strpos($k, '[!]') !== false) {
                            $k = str_replace('[!]', '', $k);
                            $whereStr .= " AND $k != ?";
                        } else if (is_array($v)) {
                            $whereStr .= " AND $k IN (".implode(',', array_fill(0, count($v), '?')).")";
                            $params = array_merge($params, $v);
                            continue;
                        } else {
                            $whereStr .= " AND $k = ?";
                        }
                        $params[] = $v;
                    }
                    $stmt = $db->prepare("SELECT $fieldStr FROM $table WHERE $whereStr");
                    $stmt->execute($params);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            };
            $this->common = new class {
                public function crc16xmodem($hex, $upper = false) {
                    $data = pack('H*', $hex);
                    $crc = 0;
                    for ($i = 0; $i < strlen($data); $i++) {
                        $crc ^= (ord($data[$i]) << 8);
                        for ($j = 0; $j < 8; $j++) {
                            if ($crc & 0x8000) {
                                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                            } else {
                                $crc = ($crc << 1) & 0xFFFF;
                            }
                        }
                    }
                    $res = $hex . sprintf('%04x', $crc);
                    return $upper ? strtoupper($res) : strtolower($res);
                }
            };
        }
    }
}

namespace app\model {
    class Proto {
        public $app;
        protected $db;
        protected $table = 'b_dev';
        private $tg = 'b_gateway';
        public function __construct($app) {
            $this->app = $app;
            $this->db = $app->db;
        }
        public function make() {
            $dev = [];
            $proto = [];
            if ($tmp = $this->db->select($this->table, ['serial', 'feature[JSON]', 'proto', 'gid[Int]'], ['status' => 1])) {
                $gids = array_map(function($row) { return $row['gid']; }, $tmp);
                $gateway = [];
                if ($t = $this->db->select($this->tg, ['id[Int]', 'serial'], ['id' => array_unique($gids), 'status[!]' => 9])) {
                    foreach ($t as $row) { $gateway[$row['id']] = $row['serial']; }
                }
                foreach ($tmp as $row) {
                    if (!isset($gateway[$row['gid']])) continue;
                    if ($row['proto'] == '2') {
                        $key = sprintf('k%s', $gateway[$row['gid']]);
                        if (!isset($dev[$key])) $dev[$key]['proto'] = [];
                        $cmd = sprintf('c1_%s', $row['serial']);
                        if (!isset($proto[$cmd])) {
                            $proto[$cmd] = $this->app->common->crc16xmodem(sprintf('aa%02s0541564b', dechex($row['serial'])), true);
                        }
                        $dev[$key]['proto'][] = $cmd; 
                    }
                }
            }
            $data = ['dev' => $dev, 'proto' => $proto];
            echo "Generated Data:\n";
            print_r($data);
            $content = "dev:\n";
            foreach ($data['dev'] as $k => $v) {
                $content .= "  $k:\n    proto:\n";
                foreach ($v['proto'] as $p) {
                    $content .= "      - $p\n";
                }
            }
            $content .= "proto:\n";
            foreach ($data['proto'] as $k => $v) {
                $content .= "  $k: '$v'\n";
            }
            file_put_contents('./cmd/pomo/bin/data.yml', $content);
            file_put_contents('./cmd/pomo/data.yml', $content);
            echo "\nFiles updated.\n";
        }
    }
}

namespace {
    $app = new \MockApp();
    $m = new \app\model\Proto($app);
    $m->make();
}
