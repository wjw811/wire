<?php
define('IN_NEXT', 1);
require 'config.php';
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim($setting);
foreach ($config as $key => $val) { $app->config($key, $val); }

$r = $app->redis;
$keys = $r->keys('d:*:*');
echo "Keys found: " . count($keys) . "\n";
foreach ($keys as $key) {
    echo "Key: $key\n";
    print_r($r->hGetAll($key));
    echo "------------------\n";
}

$keys = $r->keys('gw:*');
echo "Gateway status keys found: " . count($keys) . "\n";
foreach ($keys as $key) {
    echo "Key: $key\n";
    echo "Value: " . $r->get($key) . "\n";
}





