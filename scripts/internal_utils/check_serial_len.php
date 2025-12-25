<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query("SELECT id, serial, length(serial) as len FROM b_dev WHERE id = 7");
print_r($stmt->fetch(PDO::FETCH_ASSOC));





