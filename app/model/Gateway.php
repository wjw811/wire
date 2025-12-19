<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Gateway.php
* @touch date Tue 16 Apr 2019 09:06:39 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Gateway extends \Next\Core\Model {
    protected $table = 'b_gateway';

/*{{{ invoke */
    public function invoke($uri, $data) {
        $config = $this->app->config('rpc');
        $url = sprintf('%s%s', $config['dev'], $uri);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if($resp === false || $httpCode != 200){
            $this->app->log->error("gw.invoke > invoke error: $error, http_code: $httpCode, url: $url, data: " . json_encode($data));
            return false;
        }

        $this->app->log->info("gw.invoke > success: url: $url, data: " . json_encode($data) . ", resp: $resp");
        return true;
    }
/*}}}*/

/*{{{ hex */
    public function hex($str) {
        return bin2hex($str);
    }
/*}}}*/

/*{{{ ascii */
public function ascii($str) {
    $v = [];
    $size = strlen($str);
    for ($i=0; $i < $size; $i+=2) { 
        $v[] = hex2bin(substr($str, $i, 2));
    }
    return implode('', $v);
}
/*}}}*/

}
