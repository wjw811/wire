<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query('SELECT id, name, serial, gid, status, star FROM b_dev');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





