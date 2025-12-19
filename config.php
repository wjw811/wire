<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename config.php
* @touch date Sun 05 Jul 2020 07:21:44 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
defined('IN_NEXT') or die('Access Denied');

ini_set('display_errors', 1);

// Load FileLog class before using it
require_once __DIR__ . '/Next/Helper/FileLog.php';

/*
|--------------------------------------------------------------------------
| Setting
|--------------------------------------------------------------------------
|
| Setting for core framework Slim.
|
*/
// Application
$setting['mode'] = 'development';
// Debugging
$setting['debug'] = false;
// Logging
$setting['log.writer'] = new \Next\Helper\FileLog(['path' => './data/log/']);
$setting['log.level'] = \Slim\Log::DEBUG;
$setting['log.enabled'] = true;
// View
$setting['templates.path'] = './app/view';
$setting['view'] = '\Slim\View';
// Cookies
$setting['cookies.encrypt'] = false;
$setting['cookies.lifetime'] = '20 minutes';
$setting['cookies.path'] = '/';
$setting['cookies.domain'] = null;
$setting['cookies.secure'] = false;
$setting['cookies.httponly'] = false;
// Encryption
$setting['cookies.secret_key'] = 'CHANGE_ME';
$setting['cookies.cipher'] = MCRYPT_RIJNDAEL_256;
$setting['cookies.cipher_mode'] = MCRYPT_MODE_CBC;
// HTTP
$setting['http.version'] = '1.1';
// Routing
$setting['routes.case_sensitive'] = true;

/*
|--------------------------------------------------------------------------
| Common
|--------------------------------------------------------------------------
|
| Common configure, such as name, time.
|
*/
$config['common']['salt'] = '';
$config['common']['domain'] = 'https://your.domain.com/';

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
|
| Session configure, such as name, time.
|
*/
$config['session']['name'] = 'suid';
$config['session']['time'] = '3600';

/*
|--------------------------------------------------------------------------
| MySQL
|--------------------------------------------------------------------------
|
| MySQL configure, such as server, name, port, pwd, db, charset, dbcollat.
|
*/
$config['mysql']['dbname'] = 'wire_db';
$config['mysql']['server'] = '127.0.0.1';
$config['mysql']['charset'] = 'utf8mb4';
$config['mysql']['port'] = '3306';
$config['mysql']['username'] = 'root';
$config['mysql']['password'] = '123456';

/*
|--------------------------------------------------------------------------
| Redis
|--------------------------------------------------------------------------
|
| Redis configure, such as host, port, timeout, reserved, password
| pconnected.
|
*/
$config['redis']['host'] = '127.0.0.1';
$config['redis']['port'] = 6379;
$config['redis']['db'] = 0;
$config['redis']['timeout'] = 0;
$config['redis']['reserved'] = null;
$config['redis']['password'] = null;
$config['redis']['pconnected'] = false;

/*
|--------------------------------------------------------------------------
| Weixin
|--------------------------------------------------------------------------
|
| Weixin api account
|
*/
$config['wechat']['token'] = '';
$config['wechat']['appid'] = '';
$config['wechat']['appsecret'] = '';

$config['wxpay']['appid'] = '';
$config['wxpay']['mchid'] = '';
$config['wxpay']['sk'] = '';
$config['wxpay']['sslcert'] = '';
$config['wxpay']['sslkey'] = '';
$config['wxpay']['pxhost'] = '';
$config['wxpay']['pxport'] = 0;
$config['wxpay']['notify'] = '';

$config['wemini']['appid'] = '';
$config['wemini']['appsecret'] = '';

/*
|--------------------------------------------------------------------------
| RPC
|--------------------------------------------------------------------------
|
| RPC setting
|
*/
$config['rpc']['url'] = '';
$config['rpc']['inside'] = '';
$config['rpc']['dev'] = 'http://127.0.0.1:2010'; // pomo服务的HTTP接口

/*
|--------------------------------------------------------------------------
| SMS
|--------------------------------------------------------------------------
|
| SMS key setting
|
*/
$config["sms"]["ak"] = "";
$config["sms"]["sk"] = "";

/*
|--------------------------------------------------------------------------
| Maintenance
|--------------------------------------------------------------------------
|
| Maintenance setting
|
*/
$config["maint"]["main"] = false;

/*
|--------------------------------------------------------------------------
| Starpos
|--------------------------------------------------------------------------
|
| Starpos key setting, baidu
|
*/
$config["starpos"]["orgno"] = "";
$config["starpos"]["mercid"] = "";
$config["starpos"]["trmno"] = "";
$config["starpos"]["sk"] = "";
$config["starpos"]["appid"] = "";

/*
|--------------------------------------------------------------------------
| Ceb
|--------------------------------------------------------------------------
|
| Cebbank key setting
|
*/
$config["ceb"]["mchid"] = "";
$config["ceb"]["appid"] = "";
$config["ceb"]["appcert"] = "./data/cert/app_cert.pem";
$config["ceb"]["pubkey"] = "./data/cert/pub_key.pem";
$config["ceb"]["notify"] = ($config['common']['domain'] ?? 'http://localhost/') . "pub/notify/ceb/";

/*
|--------------------------------------------------------------------------
| Kryun
|--------------------------------------------------------------------------
|
| Ke Ru Yun key setting
|
*/
$config["kryun"]["ak"] = "";
$config["kryun"]["sk"] = "";
$config["kryun"]["sid"] = "";

/*
|--------------------------------------------------------------------------
| Twig
|--------------------------------------------------------------------------
|
| Twig setting
|
*/
$config['twig']['cache'] = './data/cache/twig';

/*
|--------------------------------------------------------------------------
| Autorun
|--------------------------------------------------------------------------
|
| Autorun Helper, Middleware, Hook
|
*/
$config['auto']['helper'] = ['common', 'session', 'redis', 'db'];
$config['auto']['middleware'] = ['nocache'];
$config['auto']['hook'] = [];

/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
|
| URI requests to default module
|
*/
$config['route']['module'] = 'pub';

/*
|--------------------------------------------------------------------------
| Upload
|--------------------------------------------------------------------------
|
| Upload File setting
|
*/
$config['upload']['image'] = ['jpg', 'jpeg', 'png'];
$config['upload']['save_path'] = './static/upload/';
$config['upload']['save_url'] = $config['common']['domain'] . 'static/upload/';
$config['upload']['max_size'] = 1000000;

/*
|--------------------------------------------------------------------------
| Mail
|--------------------------------------------------------------------------
|
| Mail setting
|
*/
$config['mail']['host'] = "";
$config['mail']['smtp_auth'] = true;
$config['mail']['username'] = "";
$config['mail']['pwd'] = "";
$config['mail']['smtp_secure'] = "";
$config['mail']['port'] = "";
$config['mail']['from'] = "";
$config['mail']['from_name'] = "";
$config['mail']['max_times'] = 5;
$config['mail']['max_process'] = 5;
