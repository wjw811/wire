<?php
require 'Next/Core/App.php';
$app = new Next\Core\App();

echo "--- User dealer2 ---\n";
$mu = new \app\model\AuthUser();
$user = $mu->get(['id', 'code'], ['code' => 'dealer2']);
print_r($user);

if ($user) {
    $uid = $user['id'];
    echo "\n--- Gateways for uid $uid ---\n";
    $mg = new \app\model\Gateway();
    print_r($mg->select(['id', 'serial', 'uid'], ['uid' => $uid, 'status[!]' => 9]));

    echo "\n--- Devices for uid $uid ---\n";
    $md = new \app\model\Dev();
    print_r($md->select(['id', 'name', 'serial', 'gid', 'uid'], ['uid' => $uid, 'status[!]' => 9]));
    
    // Also check devices under those gateways even if device.uid is not dealer2
    $gids = $mg->select('id', ['uid' => $uid, 'status[!]' => 9]);
    if ($gids) {
        echo "\n--- Devices under gateways " . implode(',', $gids) . " ---\n";
        print_r($md->select(['id', 'name', 'serial', 'gid', 'uid'], ['gid' => $gids, 'status[!]' => 9]));
    }
}





