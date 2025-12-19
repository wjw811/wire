<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Area.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Area extends \Next\Core\Model {
    protected $table = 'b_area';

/*{{{ data */
    public function data() {
        $key = 'b.area';
        if ($tmp = $this->app->redis->get($key)) {
            return json_decode($tmp, true);
        }

        $out = [];
        if ($tmp = $this->select(['id', 'pid', 'deep', 'name', 'pinyin', 'ext_name'], ['ext_id[!]' => '0'])) {
            $deep0 = [];
            $deep1 = [];
            foreach ($tmp as $row) {
                $pid = $row['pid'];

                switch ($row['deep']) {
                case '0':
                    $out[] = [
                        'key' => $row['id'],
                        'val' => $row['ext_name'],
                        'abb' => $row['name'],
                        'pin' => $row['pinyin'],
                        'row' => [],
                    ];
                    break;
                case '1':
                    if (!isset($deep0[$pid])) {
                        $deep0[$pid] = [];
                    }
                    $deep0[$pid][] = [
                        'key' => $row['id'],
                        'val' => $row['ext_name'],
                        'abb' => $row['name'],
                        'pin' => $row['pinyin'],
                        'row' => [],
                    ];
                    break;
                case '2':
                    if (!isset($deep1[$pid])) {
                        $deep1[$pid] = [];
                    }
                    $deep1[$pid][] = [
                        'key' => $row['id'],
                        'val' => $row['ext_name'],
                        'abb' => $row['abb'],
                        'pin' => $row['pinyin'],
                    ];
                    break;
                }
            }

            // merge county
            foreach ($deep0 as $key => $val) {
                foreach ($val as $k => $v) {
                    $id = $v['key'];
                    $deep0[$key][$k]['row'] = isset($deep1[$id])? array_values($deep1[$id]): []; 
                }
            }

            // merge city
            foreach ($out as &$row) {
                $id = $row['key'];
                $row['row'] = isset($deep0[$id])? array_values($deep0[$id]): [];
            }
            // cache data
            $this->app->redis->set($key, json_encode($out, JSON_UNESCAPED_UNICODE));
        }

        return $out;
    }
/*}}}*/
/*{{{ loadNameById */
    public function loadNameById($id, $flag=true) {
        if (!$id = strVal($id)) {
            return '';
        }

        $data = [];
        if ($tmp = $this->data()) {
            $data = $tmp;
        }

        $arr = [];
        while(strlen($id)) {
            $arr[] = substr($id, 0, 2);
            $id = substr($id, 2);
        }
        list($pro, $city, $cty) = $arr;

        $out = [];
        foreach($data as $r1) {
            // province
            if ($r1['key'] == $pro) {
                $out[] = $r1['val'];

                if ($city && isset($r1['row'])) {
                    $city = $pro.$city;
                    foreach($r1['row'] as $r2) {
                        // city
                        if ($r2['key'] == $city) {
                            $out[] = $r2['val'];

                            if ($cty && isset($r2['row'])) {
                                $cty = $city.$cty;
                                foreach($r2['row'] as $r3) {
                                    // county
                                    if ($r3['key'] == $cty) {
                                        $out[] = $r3['val'];
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
                break;
            }
        }

        if ($flag && count($out) > 1) {
            return $out[1];
        }
        if ($flag && count($out) > 0) {
            return $out[0];
        }

        return implode('', array_unique($out));
    }
/*}}}*/

}
