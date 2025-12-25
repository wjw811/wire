<?php
$r = new Redis();
$r->connect('127.0.0.1', 6379);
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
    echo "Key: $key, type: " . $r->type($key) . "\n";
    if ($r->type($key) == Redis::REDIS_STRING) {
        echo "Value: " . $r->get($key) . "\n";
    } else if ($r->type($key) == Redis::REDIS_HASH) {
        print_r($r->hGetAll($key));
    }
}





