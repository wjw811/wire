<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query("SELECT id, serial, uid FROM b_dev WHERE status != 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





