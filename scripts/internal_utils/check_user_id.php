<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query("SELECT id, code FROM s_user WHERE code = 'dealer1'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));





