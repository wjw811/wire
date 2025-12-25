<?php
echo "PHP Time: " . time() . "\n";
$r = new Redis();
$r->connect('127.0.0.1', 6379);
echo "Redis T: " . $r->hGet('d:2:1', 't') . "\n";
echo "Diff: " . (time() - $r->hGet('d:2:1', 't')) . "s\n";





