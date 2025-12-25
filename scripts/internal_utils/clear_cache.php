<?php
$r = new Redis();
$r->connect('127.0.0.1', 6379);
$r->del('dash.total.3');
echo "Deleted dash.total.3\n";





