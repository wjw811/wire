$root = Split-Path -Parent $PSScriptRoot
$redisExe = Join-Path $root 'db\redis\redis-server.exe'
$redisConf = Join-Path $root 'db\redis\redis.windows.conf'

Write-Host "Starting Redis on localhost:6379 ..."

Start-Process -FilePath $redisExe `
              -ArgumentList $redisConf `
              -WorkingDirectory (Join-Path $root 'db\redis') `
              -WindowStyle Hidden

Start-Sleep -Milliseconds 1000

# 验证 Redis 是否启动
if (Get-Process -Name 'redis-server' -ErrorAction SilentlyContinue) {
    Write-Host "Redis started successfully."
} else {
    Write-Host "Warning: Redis may not have started properly." -ForegroundColor Yellow
}









