<?php
$db = new PDO('mysql:host=localhost;dbname=wire_db', 'root', '123456');
// Update device 17 owner to uid 4 (dealer2)
$db->exec('UPDATE b_dev SET uid = 4 WHERE id = 17');
// Also ensure gateway 15 belongs to uid 4 if needed, but usually devices are enough for visibility if logic allows.
// Let's check who owns gateway 15.
$stmt = $db->query('SELECT uid FROM b_gateway WHERE id = 15');
$gw = $stmt->fetch();
if ($gw['uid'] != 4) {
    $db->exec('UPDATE b_gateway SET uid = 4 WHERE id = 15');
    echo "Updated gateway 15 owner to uid 4\n";
}
echo "Updated device 17 owner to uid 4\n";





