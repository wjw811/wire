<?php
$r = new Redis();
$r->connect('127.0.0.1', 6379);
echo "--- Data for d:15:1 ---\n";
print_r($r->hGetAll('d:15:1'));





