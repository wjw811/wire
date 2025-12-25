<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
$stmt = $db->query("DESC b_com_user");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));





