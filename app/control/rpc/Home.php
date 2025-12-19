<?php
namespace app\control\rpc;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Home.php
* @touch date Fri 19 Sep 2025 10:48:00 AM CST
* @author: System
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
defined('IN_NEXT') or die('Access Denied');

class Home extends \Next\Core\Control
{
    /**
     * RPC模块首页
     */
    public function index()
    {
        $app = $this->app;
        
        $data = [
            'title' => 'RPC接口服务',
            'version' => '1.0.0',
            'endpoints' => [
                [
                    'name' => '设备数据接收',
                    'url' => '/rpc/dev/pomo',
                    'method' => 'POST',
                    'description' => '接收设备发送的实时数据'
                ],
                [
                    'name' => '设备数据检查',
                    'url' => '/rpc/dev/inspect',
                    'method' => 'GET',
                    'description' => '检查设备数据和网关映射状态'
                ],
                [
                    'name' => '定时任务',
                    'url' => '/rpc/cron',
                    'method' => 'GET',
                    'description' => '执行定时任务'
                ],
                [
                    'name' => '数据同步',
                    'url' => '/rpc/sync',
                    'method' => 'GET',
                    'description' => '数据同步服务'
                ],
                [
                    'name' => '测试接口',
                    'url' => '/rpc/test',
                    'method' => 'GET',
                    'description' => '系统测试接口'
                ]
            ],
            'status' => 'running',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $app->response->setBody(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
?>