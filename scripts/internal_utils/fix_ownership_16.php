<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$db->exec('UPDATE b_dev SET uid = 4 WHERE id = 16');
echo "Updated device 16 owner to uid 4\n";





