# Nginx部署指南

## 概述

使用Nginx替代PHP内置服务器，可以大幅提升页面加载速度：
- **首次加载**: 从10-30秒降低到2-5秒
- **后续加载**: 从1-3秒降低到<1秒（有缓存）
- **并发能力**: 从单线程提升到多线程

## 安装步骤

### 1. 下载Nginx

1. 访问: https://nginx.org/en/download.html
2. 下载Windows版本（推荐 `nginx/Windows-1.xx.x`）
3. 解压到 `C:\nginx`

### 2. 配置Nginx

有两种方式：

#### 方式A：使用项目提供的配置文件（推荐）

项目根目录已有 `nginx.conf` 文件，启动脚本会自动复制到Nginx目录。

#### 方式B：手动配置

1. 备份原配置: `C:\nginx\conf\nginx.conf` → `C:\nginx\conf\nginx.conf.backup`
2. 复制项目的 `nginx.conf` 到 `C:\nginx\conf\nginx.conf`
3. 修改配置中的路径（如果需要）:
   ```nginx
   root E:/test2/wire2/wire;  # 修改为你的项目路径
   ```

### 3. 修改PHP服务器端口

如果使用PHP内置服务器（而不是PHP-FPM），需要将PHP端口改为8001：

编辑 `scripts/start_backend.ps1`，将端口从8000改为8001：
```powershell
$cmd = ".\php.exe -S 0.0.0.0:8001 -t .. > ..\\logs\\php8000.out 2> ..\\logs\\php8000.err"
```

因为Nginx监听8000端口，PHP需要在其他端口（如8001）运行。

### 4. 启动服务

#### 方式A：使用一键启动脚本（推荐）

```powershell
# 启动所有服务（包括Redis、Pomo、Bridge、PHP、Nginx）
.\scripts\start_all_nginx.ps1
```

#### 方式B：分步启动

```powershell
# 1. 启动Redis
.\scripts\start_redis.ps1

# 2. 启动Pomo服务
.\scripts\start_pomo.ps1

# 3. 启动WebSocket Bridge
.\scripts\start_bridge.ps1

# 4. 启动PHP后端（端口8001）
.\scripts\start_backend_nginx.ps1

# 5. 启动Nginx（端口8000）
.\scripts\start_nginx.ps1
```

#### 方式C：手动启动

```powershell
# 启动Nginx
cd C:\nginx
.\nginx.exe

# 检查是否启动成功
netstat -ano | findstr ":8000"
```

### 5. 访问测试

访问地址保持不变:
- http://192.168.6.123:8000/static/admin/#/dashboard/index

## 两种PHP运行方式

### 方式1：PHP内置服务器（当前方案，简单但性能一般）

**优点**:
- 无需额外配置
- 适合开发环境

**缺点**:
- 性能不如PHP-FPM
- 单进程处理

**配置**:
- Nginx反向代理到 `http://127.0.0.1:8001`
- PHP运行在8001端口

### 方式2：PHP-FPM（推荐生产环境）

**优点**:
- 性能更好
- 支持进程池
- 更适合生产环境

**缺点**:
- 需要安装PHP-FPM
- 配置稍复杂

**配置步骤**:

1. 安装PHP-FPM（或使用已安装的PHP）
2. 修改 `nginx.conf`，取消注释FastCGI配置，注释掉反向代理配置：
   ```nginx
   location ~ \.php$ {
       fastcgi_pass 127.0.0.1:9000;
       fastcgi_index index.php;
       fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
       include fastcgi_params;
   }
   ```

3. 启动PHP-FPM:
   ```powershell
   php-cgi.exe -b 127.0.0.1:9000
   ```

## 停止服务

```powershell
# 一键停止所有服务
.\scripts\stop_all_nginx.ps1

# 或单独停止Nginx
.\scripts\stop_nginx.ps1

# 手动停止Nginx
cd C:\nginx
.\nginx.exe -s quit
```

## 性能对比

| 方案 | 首次加载 | 后续加载 | 并发能力 |
|------|---------|---------|---------|
| PHP内置服务器 | 10-30秒 | 1-3秒 | 单线程 |
| Nginx + PHP内置服务器 | 2-5秒 | <1秒 | 多线程（静态文件） |
| Nginx + PHP-FPM | 2-5秒 | <1秒 | 多线程 |

## 常见问题

### 1. 端口冲突

**问题**: Nginx启动失败，提示端口8000被占用

**解决**:
- 停止PHP内置服务器: `.\scripts\stop_all.ps1`
- 或修改Nginx端口（在nginx.conf中）

### 2. 配置文件错误

**问题**: Nginx启动失败，提示配置错误

**解决**:
```powershell
cd C:\nginx
.\nginx.exe -t  # 测试配置文件
```

检查错误信息，通常是因为路径问题。

### 3. 静态文件404

**问题**: JS/CSS文件404

**解决**:
- 检查 `root` 路径是否正确（Windows路径用 `/` 或 `\\`）
- 检查 `static/admin` 目录是否存在

### 4. PHP API请求404或502

**问题**: API请求失败

**解决**:
- 检查PHP是否在8001端口运行（如果使用PHP内置服务器）
- 检查Nginx反向代理配置
- 查看Nginx错误日志: `C:\nginx\logs\nginx_error.log`

## 日志文件

- Nginx访问日志: `C:\nginx\logs\nginx_access.log`
- Nginx错误日志: `C:\nginx\logs\nginx_error.log`
- PHP日志: `logs\php8000.out` / `logs\php8000.err`

## 下一步

1. ✅ 安装Nginx
2. ✅ 配置并启动
3. ✅ 测试访问
4. 🔄 考虑使用PHP-FPM进一步提升性能（可选）

