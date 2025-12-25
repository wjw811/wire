<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query('SELECT id, uid, gid, serial, name FROM b_dev WHERE gid=13 AND serial=1');
print_r($stmt->fetch(PDO::FETCH_ASSOC));





