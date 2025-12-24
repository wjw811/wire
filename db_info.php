<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '123456';

try {
     $pdo = new PDO("mysql:host=$host", $user, $pass);
     echo "Databases:\n";
     foreach($pdo->query('SHOW DATABASES') as $row) echo $row[0]."\n";
     
     $pdo->exec("USE wire_db");
     echo "\nTables in wire_db:\n";
     foreach($pdo->query('SHOW TABLES') as $row) echo $row[0]."\n";
} catch (\Exception $e) {
     echo "Error: " . $e->getMessage();
}

