<?php
$r = new Redis();
$r->connect('127.0.0.1', 6379);
$p = $r->hGet('d:2:1', 'p');
echo "Len: " . strlen($p) . "\n";
echo "Bytes: " . (strlen($p)/2) . "\n";
$x = [];
for($i = 0; $i < strlen($p); $i = $i+2) {
    $x[] = hexdec(substr($p, $i, 2));
}
echo "Count X: " . count($x) . "\n";
echo "X[2]: " . $x[2] . "\n";





