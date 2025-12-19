<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Fireware.php
* @touch date Tue 16 Apr 2019 09:06:39 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Fireware extends \Next\Core\Model {
    protected $table = 'b_fireware';
    private $size = 512;

/*{{{ slice */
    public function slice($file, $idx) {
        $config = $this->app->config('upload');
        $path = sprintf('%shex/%s', $config['save_path'], $file);
        if (!file_exists($path)) {
            return false;
        }

        $data = [];
        if ($fp = fopen($path, 'rb')) {
            if (fseek($fp, $idx * $this->size) > -1) {
                $data = fread($fp, $this->size);
            }
            fclose($fp);
        }

        return $data;
    }
/*}}}*/
/*{{{ num */
    public function num($size) {
        return ceil($size / $this->size);
    }
/*}}}*/

}
