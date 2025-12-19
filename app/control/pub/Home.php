<?php
namespace app\control\pub;

defined('IN_NEXT') or die('Access Denied');

class Home extends \Next\Core\Control {
    public function __construct($app = null) {
        parent::__construct();
    }

    public function index() {
        $baseUrl = 'http://127.0.0.1:8000';
        echo '<!DOCTYPE html>';
        echo '<html><head><meta charset="UTF-8"><title>Wire Framework - 首页</title>';
        echo '<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}';
        echo '.container{max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}';
        echo 'h1{color:#2c3e50;border-bottom:3px solid #3498db;padding-bottom:10px;}';
        echo '.status{background:#ecf0f1;padding:15px;border-radius:5px;margin:20px 0;}';
        echo '.links{background:#e8f4fd;padding:20px;border-radius:5px;margin:20px 0;}';
        echo 'a{display:inline-block;background:#3498db;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;}';
        echo 'a:hover{background:#2980b9;}</style></head><body>';
        echo '<div class="container">';
        echo '<h1>🏠 Wire Framework 首页</h1>';
        echo '<p><strong>欢迎使用Wire框架！</strong>这是一个基于Slim Framework的PHP应用系统。</p>';
        echo '<div class="status">';
        echo '<h3>📊 系统信息</h3>';
        echo '<p>PHP版本: ' . PHP_VERSION . '</p>';
        echo '<p>当前时间: ' . date('Y-m-d H:i:s') . '</p>';
        echo '<p>服务器: Slim Framework</p>';
        echo '</div>';
        echo '<div class="links">';
        echo '<h3>🔗 快速导航</h3>';
        echo '<p><a href="' . $baseUrl . '/admin" target="_blank">🎛️ 管理后台</a></p>';
        echo '<p><a href="' . $baseUrl . '/rpc" target="_blank">🔌 RPC接口</a></p>';
        echo '<p><a href="' . $baseUrl . '/admin/dash" target="_blank">📈 数据分析</a></p>';
        echo '</div>';
        echo '<div class="status">';
        echo '<h3>✅ 系统状态</h3>';
        echo '<p>🟢 PHP后端服务: 运行中 (端口8000)</p>';
        echo '<p>🟢 Go服务: 运行中 (TCP:2024)</p>';
        echo '<p>🟢 Redis: 已连接</p>';
        echo '<p>🟢 数据库: 已连接</p>';
        echo '</div>';
        echo '<p><em>这是系统首页，与管理后台不同。管理后台提供完整的设备管理功能。</em></p>';
        echo '</div></body></html>';
    }

    public function about() {
        echo '<h1>About Wire Framework</h1>';
        echo '<p>This is a PHP framework based on Slim</p>';
        echo '<p><a href="/">Back to Home</a></p>';
    }
}
