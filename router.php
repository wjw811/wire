<?php
// 路由文件 - 处理静态文件和API请求

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $full = __DIR__ . $path;
    
    // 如果请求的是文件，直接返回
    if (is_file($full)) {
        return false;
    }
    
    // 处理路径（带或不带尾部斜杠）
    $pathNormalized = rtrim($path, '/');
    $fullNormalized = __DIR__ . $pathNormalized;
    
    // 如果请求的是目录（带或不带尾部斜杠），尝试返回index.html
    if (is_dir($full) || is_dir($fullNormalized)) {
        $dirPath = is_dir($full) ? $full : $fullNormalized;
        $index = rtrim($dirPath, "/\\") . DIRECTORY_SEPARATOR . 'index.html';
        if (is_file($index)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($index);
            exit;
        }
    }
    
    // 对于静态文件路径（如 /static/*），先尝试返回index.html，如果不存在则让主应用处理
    // 这样可以支持前端路由（如 /static/admin/#/dashboard/index）
    if (preg_match('/^\/static\//', $path)) {
        // 如果是 /static/admin 目录，直接返回index.html
        $staticPath = __DIR__ . $path;
        if (is_dir($staticPath) || (is_dir(rtrim($staticPath, '/')))) {
            $staticDir = is_dir($staticPath) ? $staticPath : rtrim($staticPath, '/');
            $indexFile = rtrim($staticDir, "/\\") . DIRECTORY_SEPARATOR . 'index.html';
            if (is_file($indexFile)) {
                header('Content-Type: text/html; charset=UTF-8');
                readfile($indexFile);
                exit;
            }
        }
        // 如果静态文件不存在，让主应用处理（支持前端路由）
        require_once __DIR__ . '/index.php';
        exit;
    }
    
    // 对于API请求（如 /admin/*, /rpc/* 等），加载主应用
    if (preg_match('/^\/(admin|rpc|api|pub|~)\//', $path)) {
        require_once __DIR__ . '/index.php';
        exit;
    }
    
    // 对于其他请求，返回404
    http_response_code(404);
    echo "File not found: " . $path;
    exit;
} else {
    // 非内置服务器环境，直接加载主应用
    require_once __DIR__ . '/index.php';
}
