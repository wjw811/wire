<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');

echo "--- Gateway 2 ---\n";
$stmt = $db->query("SELECT id, serial, uid FROM b_gateway WHERE id = 2");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- All Gateways ---\n";
$stmt = $db->query("SELECT id, serial, uid FROM b_gateway WHERE status != 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Device 15 ---\n";
$stmt = $db->query("SELECT id, name, serial, gid, uid FROM b_dev WHERE id = 15");
print_r($stmt->fetch(PDO::FETCH_ASSOC));





