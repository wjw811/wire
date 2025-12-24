<?php
$host = '127.0.0.1';
$db   = 'wire_db';
$user = 'root';
$pass = '123456';
try {
     $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
     foreach($pdo->query("SELECT name, serial FROM b_dev") as $row) {
         echo "{$row['name']}: {$row['serial']}\n";
     }
} catch (\Exception $e) {
     echo "Error: " . $e->getMessage();
}

