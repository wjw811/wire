<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query("SELECT * FROM b_option WHERE code = 'feature'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





