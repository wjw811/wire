<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
echo "--- ALL Gateways (including deleted) ---\n";
$stmt = $db->query('SELECT id, name, serial, uid, status FROM b_gateway');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- ALL Devices (including deleted) ---\n";
$stmt = $db->query('SELECT id, name, serial, gid, uid, status FROM b_dev');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





