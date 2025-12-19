<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Common.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Common extends \Next\Core\Control {

    private $u;
    private $t;

/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->u = $this->app->user;
        $this->t = $this->app->token;
    }
/*}}}*/

/*{{{ index */
    public function index() {
        $out = array();
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ upload */
    public function upload() {
        $out = array();

        // 1. check file
        // 调试记录：方法、Content-Type 与文件键名
        $req = $this->app->request;
        $ct  = (string)$req->headers->get('Content-Type');
        error_log("upload debug: method=".$req->getMethod()." ct=".$ct." files=".json_encode(array_keys($_FILES)));
        // 兼容不同前端字段名：优先取 'file'，否则取第一个文件字段
        if (!isset($_FILES['file'])) {
            if (!empty($_FILES)) {
                // 取第一个文件字段作为上传文件
                foreach ($_FILES as $k => $v) {
                    $file = $v;
                    break;
                }
            } else {
                // 记录原始体长度帮助定位 multipart 未被发送的问题
                $rawLen = strlen((string)file_get_contents('php://input'));
                error_log("upload debug: no _FILES, rawLen=$rawLen");
                $this->json(401, "系统忙，请稍后再试");
            }
        } else {
            $file = $_FILES['file'];
        }
        if (!empty($file['error'])) {
            $errMap = [
                '1' => '超过允许的大小',
                '2' => '超过表单允许的大小',
                '3' => '图片只有部分被上传',
                '4' => '请选择图片',
                '6' => '找不到临时目录',
                '7' => '写文件到硬盘出错',
                '8' => '文件上传中止',
            ];
            $msg = (isset($errMap[$file['error']]))? $errMap[$file['error']]: '文件上传发生了未知错误';
            $this->json(402, $msg);
        }

        $config = $this->app->config('upload');
		// 安全默认：保障 type / max_size / save_path / save_url 均可用
		if (!is_array($config)) { $config = []; }
		$allowedTypes = (isset($config['type']) && is_array($config['type'])) ? $config['type'] : ['hex', 'bin', 'txt'];
		$maxSize = isset($config['max_size']) ? (int)$config['max_size'] : 20 * 1024 * 1024; // 20MB
		$savePath = isset($config['save_path']) ? $config['save_path'] : (dirname(__DIR__, 2) . '/upload/');
		$saveUrl  = isset($config['save_url'])  ? $config['save_url']  : '/upload/';

		$ext = strtolower(substr($file['name'], strrpos($file['name'], '.') + 1));
		if (!in_array($ext, $allowedTypes)) {
            $this->json(403, "请上传正确的文件类型");
        }

        // check uploaded
        if (@is_uploaded_file($file['tmp_name']) === false) {
            $this->json(403, '文件上传失败');
        }
        // check file size
		if ($file['size'] > $maxSize) {
            $this->json(404, '上传文件大小超过限制');
        }

        $type = $this->params('type');
        switch ($type) {
        case 'hex':
            $fold = 'hex';
            break;
        default:
            $fold = 'other';
            break;
        }

        // path: fold/sub/uuid.ext
        $name = $this->app->common->uuid(false);
        $sub = substr($name, 0, 4);
		$path = sprintf("%s%s/%s/", $savePath, $fold, $sub);
        if (!file_exists($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->app->log->error("upload: created folder fail");
                $this->json("404003", "系统忙，请稍后再试");
            }
        }

        // 2. save file
        $name = sprintf('%s.%s', substr($name, 4), $ext);
        if (move_uploaded_file($file['tmp_name'], sprintf("%s/%s", $path, $name)) === false) {
            $this->app->log->error("upload: save file fail");
            $this->json(404004, "系统忙，请稍后再试");
        }

        $this->rendJSON([
            'code'    => 0,
            'message' => '',
			'url'     => sprintf("%s%s/%s/%s", $saveUrl, $fold, $sub, $name),
            'name'    => sprintf("%s/%s", $sub, $name),
        ]);
    }
/*}}}*/
/*{{{ area */
    public function area() {
        $m = new \app\model\Area();
        $this->json(0, 'area data', $m->data());
    }
/*}}}*/
/*{{{ option */
    public function option() {
        $data = [];

        $code = $this->params('code');
        if (is_array($code) || is_string($code)) {
            $m = new \app\model\Option();
            if (is_array($code)) {
                foreach ($code as $key) {
                    $data[$key] = $m->loadByCode($key);
                }
            } else {
                $data = $m->loadByCode($code);
            }
        }

        // feature
        if ($code == 'feature') {
            // 根据请求头动态返回：默认中文；包含 en 则英文
            $langHeader = strtolower((string)$this->app->request->headers->get('Lang'));
            $lang = (strpos($langHeader, 'en') !== false) ? 'en' : 'zh';
            $d = [];
            foreach ($data as $row) {
                $val = isset($row[$lang])? $row[$lang]: $row['en'];
                $d[] = [
                    'key'  => $row['key'],
                    'val'  => $val,
                    'unit' => $row['unit'],
                ];
            }
            $data = $d;
        }

        $this->json(0, 'option data', $data);
    }
/*}}}*/

}
