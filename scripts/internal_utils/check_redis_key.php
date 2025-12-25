<?php
$r = new Redis();
$r->connect('127.0.0.1', 6379);
var_dump($r->get('dash.total.3'));





