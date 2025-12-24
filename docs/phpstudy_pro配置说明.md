# phpstudy_pro (小皮面板) Nginx配置说明

## 当前状态

您已经安装了phpstudy_pro，Nginx路径为：`D:\phpstudy_pro\Extensions\Nginx1.15.11`

## 配置步骤

### 方式1：使用配置脚本（推荐）

```powershell
# 1. 运行配置脚本
.\scripts\setup_nginx_phpstudy.ps1

# 2. 启动PHP后端（端口8001）
.\scripts\start_backend_nginx.ps1

# 3. 在小皮面板中重启Nginx，或运行：
cd D:\phpstudy_pro\Extensions\Nginx1.15.11
.\nginx.exe -s reload
```

### 方式2：手动配置

#### 步骤1：复制配置文件

将项目根目录的 `nginx_wire.conf` 复制到：
```
D:\phpstudy_pro\Extensions\Nginx1.15.11\conf\nginx_wire.conf
```

#### 步骤2：修改主配置文件

编辑 `D:\phpstudy_pro\Extensions\Nginx1.15.11\conf\nginx.conf`

在 `http {` 块的末尾（最后一个 `}` 之前）添加：

```nginx
http {
    # ... 其他配置 ...
    
    include conf/nginx_wire.conf;
}
```

#### 步骤3：重启Nginx

在小皮面板中点击"重启"按钮，或运行命令：
```powershell
cd D:\phpstudy_pro\Extensions\Nginx1.15.11
.\nginx.exe -s reload
```

## 重要说明

### 端口说明

- **Nginx监听**: 8000端口（已在运行）
- **PHP内置服务器**: 需要在8001端口运行
- 如果8000端口冲突，可以修改 `nginx_wire.conf` 中的 `listen 8000;` 为其他端口

### PHP后端启动

由于Nginx使用8000端口，PHP需要在8001端口运行：

```powershell
# 使用专用脚本启动（端口8001）
.\scripts\start_backend_nginx.ps1

# 或使用原来的脚本，但需要先修改端口（不推荐）
```

### 验证配置

1. 检查Nginx配置是否正确：
   ```powershell
   cd D:\phpstudy_pro\Extensions\Nginx1.15.11
   .\nginx.exe -t
   ```

2. 访问测试：
   - http://192.168.6.123:8000/static/admin/

## 注意事项

1. **端口冲突**：如果8000端口已被phpstudy_pro的其他网站使用，需要：
   - 修改 `nginx_wire.conf` 中的端口
   - 或停止其他使用8000端口的服务

2. **配置冲突**：如果phpstudy_pro的Nginx已有8000端口的server配置，需要：
   - 删除或注释掉原有配置
   - 或使用不同端口

3. **phpstudy_pro管理**：
   - 可以在小皮面板中添加新网站，指向项目目录
   - 但推荐使用include方式，保持配置独立

## 故障排查

### 配置测试失败

```powershell
cd D:\phpstudy_pro\Extensions\Nginx1.15.11
.\nginx.exe -t
```

查看错误信息，通常是：
- 路径错误（Windows路径使用 `/` 或 `\\`）
- 端口冲突
- 语法错误

### 页面无法访问

1. 检查Nginx是否运行：在小皮面板查看状态
2. 检查PHP是否在8001端口运行：
   ```powershell
   netstat -ano | findstr ":8001"
   ```
3. 查看Nginx错误日志：
   ```
   D:\phpstudy_pro\Extensions\Nginx1.15.11\logs\error.log
   ```

## 完整启动流程

```powershell
# 1. 配置Nginx（只需运行一次）
.\scripts\setup_nginx_phpstudy.ps1

# 2. 启动所有服务
.\scripts\start_all_nginx.ps1

# 3. 或分步启动：
# 启动Redis
.\scripts\start_redis.ps1

# 启动Pomo
.\scripts\start_pomo.ps1

# 启动Bridge
.\scripts\start_bridge.ps1

# 启动PHP（端口8001）
.\scripts\start_backend_nginx.ps1

# 在小皮面板中确保Nginx已启动并重新加载配置
```














