$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$phpDir = Join-Path $root 'php'
$logsDir = Join-Path $root 'logs'
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

# PHP内置服务器会自动查找文档根目录下的router.php，如果存在就会使用
# 所以我们不需要显式指定，让PHP自动发现
$cmd = ".\php.exe -S 0.0.0.0:8000 -t .. > ..\\logs\\php8000.out 2> ..\\logs\\php8000.err"
Write-Host "Starting PHP backend on 0.0.0.0:8000 (accessible from network) ..."
Start-Process -WindowStyle Hidden -WorkingDirectory $phpDir -FilePath powershell -ArgumentList "-NoProfile","-Command", $cmd | Out-Null
Start-Sleep -Milliseconds 500
Write-Host "Backend started. Logs: logs\\php8000.out / logs\\php8000.err"




