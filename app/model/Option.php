<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Option.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Option extends \Next\Core\Model {
    protected $table = 's_option';

/*{{{ loadByCode */
    public function loadByCode($code) {
        $key = 'b.option.'.$code;
        // try redis; fallback to DB only when redis unavailable
        $r = null;
        try {
            $r = $this->app->redis;
        } catch (\Throwable $e) {
            $r = null;
        }

        if ($r && ($tmp = $r->get($key))) {
            return json_decode($tmp, true);
        }

        $out = [];
        if ($tmp = $this->get('value[JSON]', ['code' => $code])) {
            $out = $tmp;
            if ($r) {
                $r->set($key, json_encode($tmp, JSON_UNESCAPED_UNICODE));
                $r->expire($key, 60);
            }
        }

        return $out;
    }
/*}}}*/
/*{{{ loadByKey */
    public function loadByKey($code, $key) {
        if ($tmp = $this->loadByCode($code)) {
            foreach($tmp as $row) {
                if ($row['key'] == $key) {
                    return $row['val'];
                }
            }
        }

        return '';
    }
/*}}}*/
/*{{{ loadKeyLikeName */
    public function loadKeyLikeName($code, $val) {
        if (!$val) {
            return '';
        }

        if ($tmp = $this->loadByCode($code)) {
            foreach($tmp as $row) {
                if (strpos($row['val'], $val) !== false || strpos($val, $row['val']) !== false) {
                    return $row['key'];
                }
            }
        }

        return '';
    }
/*}}}*/

/*{{{ featureName*/
    public function featureName($key, $unit = false) {
        global $map;
        if (!isset($map)) {
            $lang = $this->app->request->headers->get('Lang');

            if ($tmp = $this->get('value[JSON]', ['code' => 'feature'])) {
                foreach ($tmp as $row) {
                    $name = isset($row[$lang])? $row[$lang]: $row['en'];
                    $map[$row['key']] = [
                        'key'  => $row['key'],
                        'name' => $name,
                        'unit' => $row['unit'],
                    ];
                }
            }
        }



        if (!$unit) {
            return isset($map[$key])? $map[$key]['name']: '';
        }

        return isset($map[$key])? $map[$key]: [];
    }
/*}}}*/

}
