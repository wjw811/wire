# 性能优化说明

## 当前问题

使用PHP内置服务器时，页面首次加载较慢（10-30秒），原因：
1. PHP内置服务器是单线程，无法并发处理多个请求
2. 前端有84个静态文件（JS/CSS）需要加载
3. 所有请求需要排队处理

## 解决方案

### 方案1：使用浏览器缓存（推荐，立即生效）

**重要：取消"Disable cache"**

1. 打开浏览器开发者工具（F12）
2. 切换到"Network"标签
3. **取消勾选"Disable cache"**
4. 刷新页面（Ctrl+F5）
5. 第二次访问会快很多（文件已缓存）

**说明：**
- 第一次访问：慢（10-30秒），需要下载所有文件
- 第二次访问：快（1-3秒），使用浏览器缓存
- 如果修改了前端代码，重新部署后会更新缓存

### 方案2：使用Nginx（生产环境推荐）

Nginx支持并发处理，性能更好。

#### 安装Nginx（Windows）

1. 下载：https://nginx.org/en/download.html
2. 解压到 `C:\nginx`
3. 配置 `nginx.conf`：

```nginx
server {
    listen 8000;
    server_name localhost;
    root E:/test2/wire2/wire;
    index index.php index.html;

    # 静态文件直接处理（缓存1小时）
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$ {
        expires 1h;
        add_header Cache-Control "public, max-age=3600";
    }

    # 前端路由
    location /static/admin {
        try_files $uri $uri/ /static/admin/index.html;
    }

    # PHP API请求
    location ~ ^/(admin|rpc|api|pub|~)/ {
        fastcgi_pass 127.0.0.1:9000;  # PHP-FPM
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        include fastcgi_params;
    }

    # 其他PHP请求
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

4. 启动Nginx：
```powershell
cd C:\nginx
.\nginx.exe
```

5. 访问：http://192.168.6.123:8000/static/admin/#/dashboard/index

### 方案3：使用Apache（备选）

类似Nginx，配置Apache处理静态文件和PHP请求。

## 当前优化

已完成的优化：
1. ✅ 添加静态文件缓存头（1小时）
2. ✅ 优化路由逻辑（直接输出文件）
3. ✅ 页面初始化优化（先显示页面框架）
4. ✅ 添加请求超时保护（10秒）
5. ✅ 路由切换优化（立即停止请求）

## 性能对比

| 方案 | 首次加载 | 后续加载 | 并发能力 |
|------|---------|---------|---------|
| PHP内置服务器 | 10-30秒 | 1-3秒（有缓存） | 单线程 |
| Nginx + PHP-FPM | 2-5秒 | <1秒（有缓存） | 多线程 |
| Apache + PHP | 3-6秒 | <1秒（有缓存） | 多线程 |

## 建议

**开发环境：**
- 使用PHP内置服务器 + 浏览器缓存
- 取消"Disable cache"，正常使用

**生产环境：**
- 使用Nginx + PHP-FPM
- 配置CDN（如果有条件）
- 启用gzip压缩

