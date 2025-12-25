<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
// Update device 17 serial to 1
$db->exec('UPDATE b_dev SET serial = "1" WHERE id = 17');
echo "Updated device 17 serial to 1\n";





