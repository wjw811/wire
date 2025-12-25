<?php
$r = new Redis();
$r->connect('127.0.0.1', 6379);
echo "--- All d:* keys ---\n";
$keys = $r->keys('d:*');
print_r($keys);

foreach ($keys as $key) {
    echo "\n--- Data for $key ---\n";
    print_r($r->hGetAll($key));
}





