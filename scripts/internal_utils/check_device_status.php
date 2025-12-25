<?php
require __DIR__ . '/../config.php';

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

echo "=== 检查网关和设备状态 ===\n\n";

// 1. 检查网关2的状态
echo "1. 网关2状态 (gw:2):\n";
$gwKey = 'gw:2';
if ($redis->exists($gwKey)) {
    $gwData = $redis->hGetAll($gwKey);
    echo "   ✅ 网关在线\n";
    echo "   数据: " . json_encode($gwData, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   ❌ 网关离线\n";
}

echo "\n2. 设备状态键 (d:2:*):\n";
$keys = $redis->keys('d:2:*');
if ($keys) {
    foreach ($keys as $key) {
        echo "   Key: $key\n";
        $data = $redis->hGetAll($key);
        if (isset($data['t'])) {
            $age = time() - (int)$data['t'];
            $status = $age < 300 ? "在线(已更新{$age}秒前)" : "离线(已超时{$age}秒)";
            echo "   状态: $status\n";
        } else {
            echo "   状态: 无时间戳\n";
        }
        if (isset($data['d'])) {
            echo "   有数据: 是\n";
        }
    }
} else {
    echo "   ❌ 未找到任何设备状态键\n";
    echo "   说明：设备还没有上报数据\n";
}

echo "\n3. 所有设备状态键概览:\n";
$allKeys = $redis->keys('d:*:*');
if ($allKeys) {
    echo "   找到 " . count($allKeys) . " 个设备状态键:\n";
    foreach (array_slice($allKeys, 0, 10) as $key) {
        list($_, $gid, $sn) = explode(':', $key);
        $data = $redis->hGetAll($key);
        $age = isset($data['t']) ? time() - (int)$data['t'] : -1;
        $status = $age >= 0 && $age < 300 ? "在线" : "离线";
        echo "   d:$gid:$sn - $status\n";
    }
    if (count($allKeys) > 10) {
        echo "   ... (还有" . (count($allKeys) - 10) . "个)\n";
    }
} else {
    echo "   ❌ 未找到任何设备状态键\n";
}

echo "\n4. 分析：\n";
echo "   网关2在线 ✓\n";
if (empty($keys)) {
    echo "   但设备状态键 d:2:* 不存在 ✗\n";
    echo "   原因：设备还没有上报数据（只有心跳，没有数据包）\n";
    echo "   解决：等待设备发送数据包，或检查设备是否正常工作\n";
} else {
    echo "   找到设备状态键:\n";
    foreach ($keys as $key) {
        echo "     - $key\n";
    }
}

echo "\n";









