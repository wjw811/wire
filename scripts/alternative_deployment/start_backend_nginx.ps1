# 启动PHP后端（用于Nginx模式，端口8001）
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$phpDir = Join-Path $root 'php'
$logsDir = Join-Path $root 'logs'
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

# PHP内置服务器在8001端口运行（Nginx使用8000端口）
# PHP内置服务器会自动查找文档根目录下的router.php，如果存在就会使用
Write-Host "Starting PHP backend on 0.0.0.0:8001 (for Nginx mode) ..." -ForegroundColor Cyan
$cmd = "& '.\php.exe' -S 0.0.0.0:8001 -t .. > ..\logs\php8001.out 2> ..\logs\php8001.err"
Start-Process -WindowStyle Hidden -WorkingDirectory $phpDir -FilePath powershell -ArgumentList @("-NoProfile", "-Command", $cmd) | Out-Null
Start-Sleep -Milliseconds 500

# 检查是否启动成功（等待一下让进程启动）
Start-Sleep -Milliseconds 500
$portListening = netstat -ano | Select-String ":8001.*LISTENING"
if ($portListening) {
    Write-Host "Backend started successfully on port 8001" -ForegroundColor Green
    Write-Host "Logs: logs\php8001.out / logs\php8001.err" -ForegroundColor Gray
} else {
    Write-Host "Backend may have failed to start. Check logs: logs\php8001.err" -ForegroundColor Yellow
}

