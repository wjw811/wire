<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=wire_db;charset=utf8mb4', 'root', '123456');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, 2); // PDO::ERRMODE_EXCEPTION

    // 查询设备1和设备2的feature配置
    for ($devId = 1; $devId <= 2; $devId++) {
        echo "=== 设备{$devId} ===" . PHP_EOL;

        $stmt = $pdo->prepare('SELECT feature FROM b_dev WHERE id = ?');
        $stmt->execute([$devId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['feature']) {
            echo 'feature配置: ' . $result['feature'] . PHP_EOL;
            $features = json_decode($result['feature'], true);
            echo '解析后的features: ' . PHP_EOL;
            print_r($features);

            // 检查是否包含241和242相关的配置
            if ($features) {
                foreach ($features as $key => $val) {
                    if (strpos($val, '241') !== false || strpos($val, '242') !== false) {
                        echo '找到相关配置: ' . $key . ' => ' . $val . PHP_EOL;
                    }
                }
            }
        } else {
            echo '没有feature配置或查询失败' . PHP_EOL;
        }
        echo PHP_EOL;
    }

} catch (Exception $e) {
    echo '数据库查询失败: ' . $e->getMessage() . PHP_EOL;
}
?>
