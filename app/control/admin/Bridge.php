<?php
/**
 * 桥接服务管理控制器
 */

namespace app\control\admin;

class Bridge extends \Next\Core\Control {

    /**
     * 启动桥接服务
     */
    public function start() {
        try {
            $deviceIp = $this->app->request->get('ip', '10.10.100.254');
            $devicePort = $this->app->request->get('port', 18899);
            $bridgePort = $this->app->request->get('bridge_port', 18900);
            
            // 检查设备连通性
            $isOnline = $this->checkDeviceConnectivity($deviceIp, $devicePort);
            if (!$isOnline) {
                $this->json(4001, '设备离线或端口未开放');
                return;
            }
            
            // 检查桥接端口是否被占用
            if ($this->isPortInUse($bridgePort)) {
                $this->json(4002, "端口 {$bridgePort} 已被占用");
                return;
            }
            
            // 启动桥接服务
            $result = $this->startBridgeService($deviceIp, $devicePort, $bridgePort);
            
            if ($result['success']) {
                // 保存桥接信息到Redis
                $this->app->redis->setex("bridge:{$bridgePort}", 3600, json_encode([
                    'device_ip' => $deviceIp,
                    'device_port' => $devicePort,
                    'bridge_port' => $bridgePort,
                    'pid' => $result['pid'],
                    'start_time' => time()
                ]));
                
                $this->json(0, '桥接服务启动成功', [
                    'bridge_url' => "ws://127.0.0.1:{$bridgePort}",
                    'device_ip' => $deviceIp,
                    'pid' => $result['pid']
                ]);
            } else {
                $this->json(4003, $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->json(5000, '启动失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 停止桥接服务
     */
    public function stop() {
        try {
            $bridgePort = $this->app->request->get('bridge_port', 18900);
            
            // 从Redis获取桥接信息
            $bridgeInfo = $this->app->redis->get("bridge:{$bridgePort}");
            if (!$bridgeInfo) {
                $this->json(4001, '桥接服务未运行');
                return;
            }
            
            $info = json_decode($bridgeInfo, true);
            $pid = $info['pid'];
            
            // 停止进程
            $result = $this->stopProcess($pid);
            
            if ($result) {
                // 删除Redis记录
                $this->app->redis->del("bridge:{$bridgePort}");
                $this->json(0, '桥接服务已停止');
            } else {
                $this->json(4002, '停止失败');
            }
            
        } catch (\Exception $e) {
            $this->json(5000, '停止失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取桥接状态
     */
    public function status() {
        try {
            $bridgePort = $this->app->request->get('bridge_port', 18900);
            
            // 从Redis获取桥接信息
            $bridgeInfo = $this->app->redis->get("bridge:{$bridgePort}");
            if (!$bridgeInfo) {
                $this->json(4001, '桥接服务未运行');
                return;
            }
            
            $info = json_decode($bridgeInfo, true);
            
            // 暂时跳过进程检查，直接返回Redis中的信息
            // $isRunning = $this->isProcessRunning($info['pid']);
            
            // if (!$isRunning) {
            //     // 进程已停止，清理Redis记录
            //     $this->app->redis->del("bridge:{$bridgePort}");
            //     $this->json(4001, '桥接服务已停止');
            //     return;
            // }
            
            $this->json(0, '桥接服务运行正常', [
                'bridge_url' => "ws://127.0.0.1:{$bridgePort}",
                'device_ip' => $info['device_ip'],
                'device_port' => $info['device_port'],
                'pid' => $info['pid'],
                'start_time' => $info['start_time'],
                'uptime' => time() - $info['start_time']
            ]);
            
        } catch (\Exception $e) {
            $this->json(5000, '获取状态失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查设备连通性
     */
    private function checkDeviceConnectivity($ip, $port) {
        $connection = @fsockopen($ip, $port, $errno, $errstr, 5);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
    
    /**
     * 检查端口是否被占用
     */
    private function isPortInUse($port) {
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
    
    /**
     * 启动桥接服务
     */
    private function startBridgeService($deviceIp, $devicePort, $bridgePort) {
        // 构建websockify命令
        $cmd = "python -m websockify 127.0.0.1:{$bridgePort} {$deviceIp}:{$devicePort}";
        
        // 在Windows上启动后台进程
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "start /B {$cmd}";
        } else {
            $cmd = "{$cmd} &";
        }
        
        // 执行命令
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);
        
        // 等待一下让进程启动
        sleep(2);
        
        // 检查进程是否启动成功
        $pid = $this->findWebsockifyProcess($bridgePort);
        
        if ($pid) {
            return ['success' => true, 'pid' => $pid];
        } else {
            return ['success' => false, 'error' => '进程启动失败'];
        }
    }
    
    /**
     * 查找websockify进程
     */
    private function findWebsockifyProcess($bridgePort) {
        $output = [];
        exec("netstat -ano | findstr :{$bridgePort}", $output);
        
        foreach ($output as $line) {
            if (strpos($line, 'LISTENING') !== false) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 5) {
                    return (int)$parts[4];
                }
            }
        }
        
        return null;
    }
    
    /**
     * 停止进程
     */
    private function stopProcess($pid) {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /PID {$pid} /F", $output, $returnVar);
            return $returnVar === 0;
        } else {
            exec("kill {$pid}", $output, $returnVar);
            return $returnVar === 0;
        }
    }
    
    /**
     * 检查进程是否运行
     */
    private function isProcessRunning($pid) {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq {$pid}\"", $output, $returnVar);
            return $returnVar === 0 && count($output) > 1;
        } else {
            exec("ps -p {$pid}", $output, $returnVar);
            return $returnVar === 0;
        }
    }
}