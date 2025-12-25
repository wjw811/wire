<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');

echo "--- User dealer1 ---\n";
$stmt = $db->query("SELECT id, code FROM s_user WHERE code = 'dealer1'");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);

if ($user) {
    $uid = $user['id'];
    echo "\n--- Gateways for uid $uid ---\n";
    $stmt = $db->query("SELECT id, serial, uid FROM b_gateway WHERE uid = $uid AND status != 9");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- Devices for uid $uid ---\n";
    $stmt = $db->query("SELECT id, name, serial, gid, uid FROM b_dev WHERE uid = $uid AND status != 9");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}





