$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$pomoDir = Join-Path $root 'cmd\pomo\bin'
$logsDir = Join-Path $root 'logs'
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

$logOut = Join-Path $logsDir 'pomo_server.out'
$logErr = Join-Path $logsDir 'pomo_server.err'
$pomoExe = Join-Path $pomoDir 'pomo.exe'

Write-Host "Starting Go TCP server on :2024 ..."
if (Test-Path $pomoExe) {
    # 先停止可能正在运行的pomo进程
    Get-Process | Where-Object {$_.ProcessName -eq "pomo"} | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Milliseconds 500
    
    # 启动pomo服务
    Start-Process -WindowStyle Hidden -WorkingDirectory $pomoDir -FilePath $pomoExe -RedirectStandardOutput $logOut -RedirectStandardError $logErr | Out-Null
    Start-Sleep -Milliseconds 1500
    
    # 等待更长时间，然后检查端口是否监听（这是最可靠的检查方式）
    Start-Sleep -Milliseconds 1000
    $port2024 = netstat -ano | findstr ":2024" | findstr "LISTENING"
    if ($port2024) {
        # 从netstat输出中提取PID
        $pidMatch = $port2024 | Select-String -Pattern "\s+(\d+)$"
        if ($pidMatch) {
            $processId = $pidMatch.Matches[0].Groups[1].Value
            Write-Host "       OK Pomo started (PID: $processId). Port 2024 listening. Logs: logs\pomo_server.out / logs\pomo_server.err" -ForegroundColor Green
        } else {
            Write-Host "       OK Pomo started. Port 2024 listening. Logs: logs\pomo_server.out / logs\pomo_server.err" -ForegroundColor Green
        }
        
        # 检查2010端口（HTTP接口）
        Start-Sleep -Milliseconds 500
        $port2010 = netstat -ano | findstr ":2010" | findstr "LISTENING"
        if ($port2010) {
            Write-Host "       OK Port 2010 (HTTP) listening" -ForegroundColor Green
        } else {
            Write-Host "       WARNING Port 2010 (HTTP) not listening yet" -ForegroundColor Yellow
        }
    } else {
        # 如果端口没监听，检查进程和错误日志
        $running = Get-Process | Where-Object {$_.ProcessName -eq "pomo"} -ErrorAction SilentlyContinue
        if ($running) {
            Write-Host "       WARNING Pomo process running (PID: $($running.Id)) but port 2024 not listening yet" -ForegroundColor Yellow
        } else {
            Write-Host "       ERROR Pomo start failed! Check log: logs\pomo_server.err" -ForegroundColor Red
            if (Test-Path $logErr) {
                Write-Host "       Recent errors:" -ForegroundColor Yellow
                Get-Content $logErr -Tail 5 | ForEach-Object { Write-Host "       $_" -ForegroundColor Red }
            }
        }
    }
} else {
    Write-Host "       ERROR pomo.exe not found ($pomoExe)" -ForegroundColor Red
}
