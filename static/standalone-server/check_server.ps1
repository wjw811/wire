# 服务器诊断脚本
Write-Host "=============================================================" -ForegroundColor Cyan
Write-Host "   DEVICE SERVER DIAGNOSTICS" -ForegroundColor Cyan
Write-Host "=============================================================" -ForegroundColor Cyan
Write-Host ""

$HTTP_PORT = 18080
$WS_PORT = 18900

# 1. 检查服务器进程
Write-Host "[1/5] 检查服务器进程..." -ForegroundColor Yellow
$process = Get-Process -Name "device_server" -ErrorAction SilentlyContinue
if ($process) {
    Write-Host "    [OK] 服务器进程正在运行 (PID: $($process.Id))" -ForegroundColor Green
} else {
    Write-Host "    [ERROR] 服务器进程未运行" -ForegroundColor Red
    Write-Host "    请运行 start_server.bat 启动服务器" -ForegroundColor Yellow
}

# 2. 检查端口监听
Write-Host ""
Write-Host "[2/5] 检查端口监听状态..." -ForegroundColor Yellow
$httpPort = Get-NetTCPConnection -LocalPort $HTTP_PORT -State Listen -ErrorAction SilentlyContinue
$wsPort = Get-NetTCPConnection -LocalPort $WS_PORT -State Listen -ErrorAction SilentlyContinue

if ($httpPort) {
    Write-Host "    [OK] HTTP端口 $HTTP_PORT 正在监听" -ForegroundColor Green
    Write-Host "    监听地址: $($httpPort.LocalAddress):$($httpPort.LocalPort)" -ForegroundColor Gray
} else {
    Write-Host "    [ERROR] HTTP端口 $HTTP_PORT 未监听" -ForegroundColor Red
}

if ($wsPort) {
    Write-Host "    [OK] WebSocket端口 $WS_PORT 正在监听" -ForegroundColor Green
    Write-Host "    监听地址: $($wsPort.LocalAddress):$($wsPort.LocalPort)" -ForegroundColor Gray
} else {
    Write-Host "    [WARN] WebSocket端口 $WS_PORT 未监听" -ForegroundColor Yellow
}

# 3. 获取本机IP地址
Write-Host ""
Write-Host "[3/5] 检测本机IP地址..." -ForegroundColor Yellow
$ipAddresses = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -like "10.10.100.*" -or $_.IPAddress -like "192.168.*" -or $_.IPAddress -notlike "127.*" } | Select-Object -First 5
if ($ipAddresses) {
    foreach ($ip in $ipAddresses) {
        Write-Host "    [INFO] 检测到IP: $($ip.IPAddress)" -ForegroundColor Cyan
    }
} else {
    Write-Host "    [WARN] 未检测到局域网IP地址" -ForegroundColor Yellow
}

# 4. 检查防火墙规则
Write-Host ""
Write-Host "[4/5] 检查防火墙规则..." -ForegroundColor Yellow
$firewallRules = Get-NetFirewallRule | Where-Object { $_.DisplayName -like "*18080*" -or $_.DisplayName -like "*device*" } | Select-Object -First 5
if ($firewallRules) {
    Write-Host "    [INFO] 找到相关防火墙规则:" -ForegroundColor Cyan
    foreach ($rule in $firewallRules) {
        Write-Host "      - $($rule.DisplayName): $($rule.Enabled) ($($rule.Direction))" -ForegroundColor Gray
    }
} else {
    Write-Host "    [WARN] 未找到相关防火墙规则，可能需要手动添加" -ForegroundColor Yellow
    Write-Host "    建议运行以下命令添加防火墙规则:" -ForegroundColor Yellow
    Write-Host "    netsh advfirewall firewall add rule name=`"Device Server HTTP`" dir=in action=allow protocol=TCP localport=$HTTP_PORT" -ForegroundColor Gray
    Write-Host "    netsh advfirewall firewall add rule name=`"Device Server WS`" dir=in action=allow protocol=TCP localport=$WS_PORT" -ForegroundColor Gray
}

# 5. 测试本地连接
Write-Host ""
Write-Host "[5/5] 测试本地HTTP连接..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:$HTTP_PORT/device_direct.html" -TimeoutSec 3 -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "    [OK] 本地HTTP连接成功 (状态码: $($response.StatusCode))" -ForegroundColor Green
    } else {
        Write-Host "    [WARN] 本地HTTP连接返回状态码: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "    [ERROR] 本地HTTP连接失败: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=============================================================" -ForegroundColor Cyan
Write-Host "   诊断完成" -ForegroundColor Cyan
Write-Host "=============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "如果服务器未运行，请执行以下步骤:" -ForegroundColor Yellow
Write-Host "1. 运行 start_server.bat 启动服务器" -ForegroundColor White
Write-Host "2. 检查防火墙是否允许端口 $HTTP_PORT 和 $WS_PORT" -ForegroundColor White
Write-Host "3. 确保移动设备和服务器在同一网络" -ForegroundColor White
Write-Host "4. 使用正确的IP地址访问（不是127.0.0.1）" -ForegroundColor White
Write-Host ""
Write-Host "按任意键退出..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")


