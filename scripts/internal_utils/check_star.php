<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
echo "--- Device 15 star status ---\n";
$stmt = $db->query("SELECT id, name, star FROM b_dev WHERE id = 15");
print_r($stmt->fetch(PDO::FETCH_ASSOC));





