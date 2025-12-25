$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  启动生产环境服务" -ForegroundColor Yellow
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# 检查IP地址
Write-Host "[0/3] 检查网络配置..." -ForegroundColor Cyan
$currentIP = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | 
    Where-Object { $_.IPAddress -like '192.168.2.*' } | 
    Select-Object -First 1).IPAddress

if ($currentIP) {
    if ($currentIP -eq "192.168.2.94") {
        Write-Host "       OK IP地址正确: $currentIP" -ForegroundColor Green
    } else {
        Write-Host "       WARNING 当前IP: $currentIP" -ForegroundColor Yellow
        Write-Host "       建议IP: 192.168.2.94" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "       如需设置静态IP，运行: .\scripts\internal_utils\设置静态IP.bat" -ForegroundColor Gray
        Write-Host ""
    }
} else {
    Write-Host "       WARNING 未检测到 192.168.2.x 网段IP" -ForegroundColor Yellow
    Write-Host ""
}

Write-Host ""
Write-Host "[1/3] Start Redis (6379)" -ForegroundColor Cyan
& (Join-Path $root 'scripts\start_redis.ps1')

Start-Sleep -Milliseconds 500

Write-Host "[2/3] Start backend (8000)" -ForegroundColor Cyan
& (Join-Path $root 'scripts\start_backend.ps1')

Start-Sleep -Milliseconds 500

Write-Host "[3/3] Start Go TCP server (2024)" -ForegroundColor Cyan
& (Join-Path $root 'scripts\start_pomo.ps1')

Start-Sleep -Milliseconds 1000

Write-Host ""
Write-Host "================================================================" -ForegroundColor Green
Write-Host "  OK 生产环境服务启动完成！" -ForegroundColor Yellow
Write-Host "================================================================" -ForegroundColor Green
Write-Host ""

# 使用固定的服务器IP地址
$hostIp = '192.168.6.190'

Write-Host "访问地址：" -ForegroundColor Cyan
Write-Host "   管理后台（生产环境）: http://${hostIp}:8000/static/admin/#/dashboard/index" -ForegroundColor Green
Write-Host ""
Write-Host "注意：前端使用已编译的静态文件，无需启动 Vite 开发服务器" -ForegroundColor Gray
Write-Host "如需启动实验环境（Vite开发服务器），请手动运行: .\scripts\start_frontend.ps1" -ForegroundColor Gray
Write-Host ""
Write-Host "服务状态检查：" -ForegroundColor Cyan
Write-Host "   运行命令: .\scripts\status.ps1" -ForegroundColor Gray
Write-Host ""

# 检查网关连接
Write-Host "检查网关连接..." -ForegroundColor Cyan
$pomoConnection = netstat -ano | findstr ":2024" | findstr "ESTABLISHED"
if ($pomoConnection) {
    Write-Host "   OK 网关已连接到 pomo.exe (端口 2024)" -ForegroundColor Green
} else {
    Write-Host "   WARNING 网关未连接到 pomo.exe (端口 2024)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "   可能原因：" -ForegroundColor Gray
    Write-Host "   • WiFi模块配置的目标IP与当前IP不一致" -ForegroundColor White
    Write-Host "   • WiFi模块未上线" -ForegroundColor White
    Write-Host ""
    Write-Host "   如需设置静态IP（推荐）：" -ForegroundColor Yellow
    Write-Host "   右键运行: .\scripts\设置静态IP.bat (管理员)" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "================================================================" -ForegroundColor Green
Write-Host ""