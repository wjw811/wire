# 停止所有桥接服务

Write-Host "🛑 停止桥接服务..." -ForegroundColor Yellow

$pidFile = Join-Path $PSScriptRoot "bridge_pids.txt"

if (-not (Test-Path $pidFile)) {
    Write-Host "⚠️  未找到运行中的桥接服务" -ForegroundColor Yellow
    pause
    exit
}

$lines = Get-Content $pidFile
foreach ($line in $lines) {
    $parts = $line -split '\|'
    $deviceName = $parts[0]
    $port = $parts[1]
    $pid = $parts[2]
    
    try {
        $process = Get-Process -Id $pid -ErrorAction Stop
        Stop-Process -Id $pid -Force
        Write-Host "✅ 已停止: $deviceName (PID: $pid)" -ForegroundColor Green
    } catch {
        Write-Host "⚠️  进程不存在: $deviceName (PID: $pid)" -ForegroundColor Yellow
    }
}

Remove-Item $pidFile
Write-Host ""
Write-Host "✅ 所有桥接服务已停止" -ForegroundColor Green
Write-Host ""
pause


