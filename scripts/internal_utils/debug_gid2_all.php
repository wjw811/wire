<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
echo "--- ALL Devices under Gateway 2 ---\n";
$stmt = $db->query("SELECT id, name, serial, gid, uid, status FROM b_dev WHERE gid = 2");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





