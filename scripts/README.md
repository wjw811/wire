# Wire 项目脚本说明

本目录包含用于管理 Wire 项目的所有 PowerShell 脚本。

## 📋 快速开始

### 启动所有服务
```powershell
powershell -ExecutionPolicy Bypass -File wire\scripts\start_all.ps1
```

### 停止所有服务
```powershell
powershell -ExecutionPolicy Bypass -File wire\scripts\stop_all.ps1
```

### 查看服务状态
```powershell
powershell -ExecutionPolicy Bypass -File wire\scripts\status.ps1
```

---

## 📦 脚本分类

### 🚀 启动脚本 (start_*.ps1)

#### `start_all.ps1` ⭐
**用途：** 一键启动所有服务  
**启动顺序：**
1. Redis (端口 6379)
2. PHP 后端 (端口 8000)
3. Go TCP 服务器 (端口 2024)
4. Vue 前端 (端口 3100)
5. 发送测试心跳包
6. WebSocket 桥接 (端口 18900)

**访问地址：**
- 管理后台: http://127.0.0.1:8000/static/admin/#/dashboard/index
- 开发前端: http://127.0.0.1:3100

---

#### `start_redis.ps1`
**用途：** 启动 Redis 服务  
**端口：** 6379  
**位置：** wire/db/redis/redis-server.exe  
**依赖：** 无

---

#### `start_backend.ps1`
**用途：** 启动 PHP 后端服务  
**端口：** 8000  
**位置：** wire/php/php.exe  
**依赖：** Redis 必须先启动  
**日志：** logs/php8000.out, logs/php8000.err

---

#### `start_pomo.ps1`
**用途：** 启动 Go TCP 服务器（设备通信）  
**端口：** 2024 (TCP), 2010 (HTTP)  
**位置：** wire/cmd/pomo/pomo.exe  
**依赖：** 无  
**日志：** logs/pomo_server.out, logs/pomo_server.err

---

#### `start_frontend.ps1`
**用途：** 启动 Vue 开发服务器  
**端口：** 3100  
**位置：** wire/x/admin/  
**依赖：** 需要先运行 `npm install`  
**日志：** logs/frontend.out, logs/frontend.err

---

#### `start_bridge.ps1`
**用途：** 启动 WebSocket 桥接服务（直连模式）  
**端口：** 18900 (WebSocket)  
**位置：** wire/bridge/bridge.js  
**依赖：** Node.js  
**日志：** logs/ws_bridge.out, logs/ws_bridge.err  
**配置：**
- 源: ws://127.0.0.1:18900
- 目标: tcp://10.10.100.254:18899

---

### 🛑 停止脚本 (stop_*.ps1)

#### `stop_all.ps1` ⭐
**用途：** 停止所有服务  
**停止进程：**
- redis-server.exe
- pomo.exe
- php.exe
- node.exe

---

#### `stop_bridge.ps1`
**用途：** 仅停止 WebSocket 桥接服务  
**方法：** 查找并终止运行 bridge.js 的 node.exe 进程

---

### 🔧 工具脚本

#### `status.ps1` ⭐
**用途：** 查看所有服务的运行状态  
**显示信息：**
- 端口占用情况 (2024, 2010, 8000, 3100, 18900, 6379)
- 进程列表 (pomo.exe, php.exe, redis-server.exe, node.exe)
- 最近日志输出

---

#### `send_heartbeat.ps1`
**用途：** 向 Go TCP 服务器发送测试心跳包  
**目标：** 127.0.0.1:2024  
**用途：** 测试设备通信是否正常

---

#### `setup_direct_connection.ps1`
**用途：** 配置局域网直连功能  
**功能：**
- 配置 WebSocket 桥接
- 设置设备 IP 地址
- 测试连接

---

#### `manage_log_rotation.ps1`
**用途：** 手动触发日志轮转  
**清理：** 删除 7 天前的日志文件

---

#### `rotate_logs.ps1`
**用途：** 自动日志轮转  
**配置：** 可配置保留天数和日志目录

---

### 📄 其他文件

#### `generate_device_config.php`
**用途：** 生成设备配置文件  
**运行：** `php wire/scripts/generate_device_config.php`

#### `add_local_ip_field.sql`
**用途：** 数据库迁移脚本 - 添加本地 IP 字段

---

## 🔄 典型工作流

### 开发环境启动
```powershell
# 1. 启动所有服务
powershell -ExecutionPolicy Bypass -File wire\scripts\start_all.ps1

# 2. 查看状态
powershell -ExecutionPolicy Bypass -File wire\scripts\status.ps1

# 3. 访问管理后台
# http://127.0.0.1:8000/static/admin/#/dashboard/index
```

### 生产环境启动（不需要前端开发服务器）
```powershell
# 分别启动需要的服务
powershell -ExecutionPolicy Bypass -File wire\scripts\start_redis.ps1
powershell -ExecutionPolicy Bypass -File wire\scripts\start_backend.ps1
powershell -ExecutionPolicy Bypass -File wire\scripts\start_pomo.ps1
powershell -ExecutionPolicy Bypass -File wire\scripts\start_bridge.ps1
```

### 调试特定服务
```powershell
# 停止所有服务
powershell -ExecutionPolicy Bypass -File wire\scripts\stop_all.ps1

# 只启动需要的服务
powershell -ExecutionPolicy Bypass -File wire\scripts\start_redis.ps1
powershell -ExecutionPolicy Bypass -File wire\scripts\start_backend.ps1

# 查看日志
Get-Content wire\logs\php8000.err -Tail 20 -Wait
```

---

## 📝 注意事项

1. **Redis 依赖**  
   PHP 后端依赖 Redis，必须先启动 Redis 才能启动后端。

2. **端口冲突**  
   确保以下端口未被占用：
   - 6379 (Redis)
   - 8000 (PHP)
   - 2024, 2010 (Go)
   - 3100 (Vue 开发服务器，可选)
   - 18900 (WebSocket 桥接，可选)

3. **日志位置**  
   所有服务日志保存在 `wire/logs/` 目录：
   - php8000.out / php8000.err
   - pomo_server.out / pomo_server.err
   - frontend.out / frontend.err
   - ws_bridge.out / ws_bridge.err

4. **前端开发**  
   如果只需要使用编译后的前端，不需要启动 `start_frontend.ps1`。  
   编译后的静态文件在 `static/admin/`。

5. **权限问题**  
   如果执行策略阻止脚本运行，使用：
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   ```

---

## 🔍 故障排查

### 服务启动失败
```powershell
# 检查端口占用
netstat -ano | findstr "8000 2024 6379"

# 停止所有服务后重试
powershell -ExecutionPolicy Bypass -File wire\scripts\stop_all.ps1
powershell -ExecutionPolicy Bypass -File wire\scripts\start_all.ps1
```

### Redis 连接失败
```powershell
# 检查 Redis 是否运行
Get-Process | Where-Object { $_.ProcessName -eq "redis-server" }

# 手动启动 Redis
powershell -ExecutionPolicy Bypass -File wire\scripts\start_redis.ps1
```

### 设备无法连接
```powershell
# 发送测试心跳包
powershell -ExecutionPolicy Bypass -File wire\scripts\send_heartbeat.ps1

# 查看 Go 服务器日志
Get-Content wire\logs\pomo_server.out -Tail 20
```

---

## 📚 相关文档

- [部署说明](../../部署说明.txt)
- [直连功能实施总结](../docs/直连功能实施总结.md)
- [备份说明](../../备份说明.txt)

---

**最后更新：** 2025年10月31日  
**版本：** 2.0









