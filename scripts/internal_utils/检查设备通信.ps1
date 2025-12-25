# 检查Go服务与设备通信状态

$ErrorActionPreference = 'Continue'
$root = Split-Path -Parent $PSScriptRoot

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  🔍 Go服务与设备通信诊断" -ForegroundColor Yellow
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# 1. 检查Go服务是否运行
Write-Host "[1/5] 检查Go服务状态..." -ForegroundColor Cyan
$pomoListening = netstat -ano | findstr ":2024.*LISTENING"
if ($pomoListening) {
    Write-Host "   ✅ Go服务正在监听端口 2024" -ForegroundColor Green
    $pomoListening | ForEach-Object { Write-Host "      $_" -ForegroundColor White }
} else {
    Write-Host "   ❌ Go服务未运行或未监听端口 2024" -ForegroundColor Red
    Write-Host "      请运行: .\scripts\start_pomo.ps1" -ForegroundColor Yellow
}

Write-Host ""

# 2. 检查设备连接
Write-Host "[2/5] 检查设备连接..." -ForegroundColor Cyan
$established = netstat -ano | findstr ":2024.*ESTABLISHED"
if ($established) {
    Write-Host "   ✅ 有设备已连接到Go服务" -ForegroundColor Green
    $established | ForEach-Object { Write-Host "      $_" -ForegroundColor White }
} else {
    Write-Host "   ⚠️  没有设备连接到Go服务" -ForegroundColor Yellow
    Write-Host "      设备需要连接到: [电脑IP]:2024" -ForegroundColor White
}

Write-Host ""

# 3. 显示当前电脑IP地址
Write-Host "[3/5] 当前电脑IP地址..." -ForegroundColor Cyan
$ips = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | 
    Where-Object { 
        $_.IPAddress -notlike '127.*' -and 
        $_.IPAddress -notlike '169.254.*' -and
        ($_.IPAddress -like '192.168.*' -or $_.IPAddress -like '10.*')
    } | 
    Sort-Object -Property PrefixLength -Descending

if ($ips) {
    foreach ($ip in $ips) {
        $ipStr = $ip.IPAddress
        Write-Host "   IP: $ipStr (接口: $($ip.InterfaceAlias))" -ForegroundColor White
        Write-Host "      设备应连接: $ipStr`:2024" -ForegroundColor Cyan
    }
} else {
    Write-Host "   ⚠️  未检测到局域网IP地址" -ForegroundColor Yellow
}

Write-Host ""

# 4. 检查HTTP服务（用于推送数据）
Write-Host "[4/5] 检查HTTP推送服务..." -ForegroundColor Cyan
$httpListening = netstat -ano | findstr ":2010.*LISTENING"
if ($httpListening) {
    Write-Host "   ✅ HTTP推送服务正在监听端口 2010" -ForegroundColor Green
} else {
    Write-Host "   ❌ HTTP推送服务未运行" -ForegroundColor Red
}

Write-Host ""

# 5. 显示最近的日志
Write-Host "[5/5] 最近的服务日志..." -ForegroundColor Cyan
$logFile = Join-Path $root 'logs\pomo_server.out'
if (Test-Path $logFile) {
    Write-Host "   Go服务日志 (最后10行):" -ForegroundColor White
    $lines = Get-Content $logFile -Tail 10 -ErrorAction SilentlyContinue
    if ($lines) {
        $lines | ForEach-Object { Write-Host "      $_" -ForegroundColor Gray }
    } else {
        Write-Host "      (日志文件为空)" -ForegroundColor Yellow
    }
} else {
    Write-Host "   ⚠️  日志文件不存在: $logFile" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  💡 诊断结果和建议" -ForegroundColor Yellow
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

if (-not $established) {
    Write-Host "⚠️  设备未连接到Go服务，可能的原因：" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. 设备配置的目标IP不正确" -ForegroundColor White
    Write-Host "   - 检查WiFi模块配置的目标IP是否与电脑IP一致" -ForegroundColor Gray
    Write-Host "   - 目标端口应该是: 2024" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. 设备未启动或离线" -ForegroundColor White
    Write-Host "   - 检查设备是否上电" -ForegroundColor Gray
    Write-Host "   - 检查设备网络连接" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. 网络不通" -ForegroundColor White
    Write-Host "   - 测试网络: ping [设备IP]" -ForegroundColor Gray
    Write-Host "   - 检查防火墙是否阻止了端口2024" -ForegroundColor Gray
    Write-Host ""
    Write-Host "4. 电脑IP地址变化" -ForegroundColor White
    Write-Host "   - 如果使用动态IP，IP可能变化导致设备连不上" -ForegroundColor Gray
    Write-Host "   - 建议设置静态IP: .\scripts\设置静态IP.bat" -ForegroundColor Gray
    Write-Host ""
} else {
    Write-Host "✅ 设备已连接到Go服务，通信正常！" -ForegroundColor Green
    Write-Host ""
}

Write-Host "📋 下一步操作：" -ForegroundColor Cyan
Write-Host "   - 查看完整日志: Get-Content logs\pomo_server.out" -ForegroundColor White
Write-Host "   - 检查所有服务: .\scripts\status.ps1" -ForegroundColor White
Write-Host "   - 发送测试心跳: .\scripts\send_heartbeat.ps1" -ForegroundColor White
Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""









