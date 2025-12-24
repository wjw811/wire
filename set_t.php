<?php
require 'vendor/autoload.php';
$app = new Next\App();
$app->redis->hSet('d:19:1', 't', time());
echo 'Set t for d:19:1' . PHP_EOL;

