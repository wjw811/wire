<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query("SELECT id, serial, uid FROM b_gateway WHERE id = 2");
print_r($stmt->fetch(PDO::FETCH_ASSOC));





