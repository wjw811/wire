<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$db->exec("UPDATE b_dev SET uid = 3 WHERE id = 2");
$db->exec("UPDATE b_gateway SET uid = 3 WHERE id = 13");
$db->exec("UPDATE b_gateway SET uid = 3 WHERE id = 2");
echo "Updated ownership for devices and gateways to uid 3\n";





