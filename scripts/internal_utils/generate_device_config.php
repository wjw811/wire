<?php
/**
 * 从数据库生成设备配置文件
 * 用于自动桥接服务
 */

// 加载配置
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config.php';

// 连接数据库
try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8",
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ 数据库连接成功\n";
} catch (PDOException $e) {
    die("❌ 数据库连接失败: " . $e->getMessage() . "\n");
}

// 查询网关信息（假设网关表有 local_ip 字段）
$gwQuery = "SELECT id, name, serial, local_ip FROM b_gateway WHERE status != 9";
$gateways = $pdo->query($gwQuery)->fetchAll();

echo "📡 找到 " . count($gateways) . " 个网关\n";

// 查询设备信息
$devQuery = "SELECT id, name, serial, gateway_id FROM b_device WHERE status != 9";
$devices = $pdo->query($devQuery)->fetchAll();

echo "📦 找到 " . count($devices) . " 个设备\n";

// 构建配置
$deviceConfig = [
    'devices' => [],
    'autoScan' => [
        'enabled' => false,
        'subnet' => '192.168.2',
        'startIP' => 1,
        'endIP' => 254,
        'port' => 18899
    ]
];

$webConfig = [
    'devices' => [],
    'defaultBridgeUrl' => 'ws://127.0.0.1:18900'
];

$bridgePort = 18900;

// 为每个设备生成配置
foreach ($devices as $device) {
    // 查找对应的网关
    $gateway = null;
    foreach ($gateways as $gw) {
        if ($gw['id'] == $device['gateway_id']) {
            $gateway = $gw;
            break;
        }
    }
    
    if (!$gateway) {
        echo "⚠️  设备 {$device['name']} (ID:{$device['id']}) 没有绑定网关，跳过\n";
        continue;
    }
    
    // 检查网关是否有 local_ip
    if (empty($gateway['local_ip'])) {
        echo "⚠️  网关 {$gateway['name']} (ID:{$gateway['id']}) 没有配置 local_ip，跳过\n";
        continue;
    }
    
    // 添加到 PowerShell 配置
    $deviceConfig['devices'][] = [
        'id' => (int)$device['id'],
        'name' => $device['name'],
        'ip' => $gateway['local_ip'],
        'port' => 18899,
        'bridgePort' => $bridgePort,
        'gatewayId' => (int)$gateway['id'],
        'gatewayName' => $gateway['name']
    ];
    
    // 添加到前端配置
    $webConfig['devices'][] = [
        'id' => (int)$device['id'],
        'name' => $device['name'],
        'bridgeUrl' => "ws://127.0.0.1:{$bridgePort}"
    ];
    
    echo "✅ 设备: {$device['name']} → {$gateway['local_ip']}:18899 (桥接端口: {$bridgePort})\n";
    
    $bridgePort++;
}

// 写入配置文件
$configPath = __DIR__ . '/../config/devices.json';
$webConfigPath = __DIR__ . '/../static/admin/device-config.json';

file_put_contents($configPath, json_encode($deviceConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n📝 已生成配置文件: {$configPath}\n";

file_put_contents($webConfigPath, json_encode($webConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "📝 已生成前端配置: {$webConfigPath}\n";

echo "\n🎉 配置生成完成！\n";
echo "\n💡 下一步:\n";
echo "   1. 检查 config/devices.json 确认设备信息\n";
echo "   2. 运行: .\\scripts\\start_multi_bridge.ps1\n";
echo "   3. 打开网页: http://127.0.0.1:8000/static/admin/#/dashboard/index\n";


