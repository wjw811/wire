<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');

echo "--- Devices under Gateway 2 ---\n";
$stmt = $db->query("SELECT id, name, serial, uid FROM b_dev WHERE gid = 2 AND status != 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





