<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
echo "--- Recently Active Devices ---\n";
$stmt = $db->query('SELECT id, name, serial, gid, uid, status, star FROM b_dev WHERE status != 9 ORDER BY id DESC LIMIT 10');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Active Gateways ---\n";
$stmt = $db->query('SELECT id, name, serial, uid FROM b_gateway WHERE status != 9');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





