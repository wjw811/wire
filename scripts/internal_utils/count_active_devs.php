<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
echo "Count: " . $db->query("SELECT count(*) FROM b_dev WHERE status != 9")->fetchColumn() . "\n";





