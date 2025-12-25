# 停止所有服务（Nginx模式）
$ErrorActionPreference = 'Stop'

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  停止所有服务（Nginx模式）" -ForegroundColor Yellow
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# 停止Nginx
Write-Host "停止Nginx..." -ForegroundColor Cyan
& (Join-Path $PSScriptRoot "stop_nginx.ps1")
Write-Host ""

# 停止PHP（端口8001）
Write-Host "停止PHP后端..." -ForegroundColor Cyan
$phpProcess = Get-Process -Name php -ErrorAction SilentlyContinue
if ($phpProcess) {
    Stop-Process -Name php -Force -ErrorAction SilentlyContinue
    Write-Host "PHP已停止" -ForegroundColor Green
} else {
    Write-Host "PHP未运行" -ForegroundColor Yellow
}
Write-Host ""

# 停止其他服务（使用原有的stop_all脚本逻辑）
Write-Host "停止其他服务..." -ForegroundColor Cyan

# 停止WebSocket Bridge
$bridgeProcess = Get-Process -Name node -ErrorAction SilentlyContinue
if ($bridgeProcess) {
    Stop-Process -Name node -Force -ErrorAction SilentlyContinue
    Write-Host "Bridge已停止" -ForegroundColor Green
} else {
    Write-Host "Bridge未运行" -ForegroundColor Yellow
}

# 停止Pomo
$pomoProcess = Get-Process -Name pomo -ErrorAction SilentlyContinue
if ($pomoProcess) {
    Stop-Process -Name pomo -Force -ErrorAction SilentlyContinue
    Write-Host "Pomo已停止" -ForegroundColor Green
} else {
    Write-Host "Pomo未运行" -ForegroundColor Yellow
}

# 停止Redis
$redisProcess = Get-Process -Name redis-server -ErrorAction SilentlyContinue
if ($redisProcess) {
    Stop-Process -Name redis-server -Force -ErrorAction SilentlyContinue
    Write-Host "Redis已停止" -ForegroundColor Green
} else {
    Write-Host "Redis未运行" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "所有服务已停止" -ForegroundColor Green
Write-Host ""












