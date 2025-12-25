$ErrorActionPreference = 'SilentlyContinue'
Write-Host "Stopping all services ..."
Write-Host "  - Redis (redis-server.exe)"
taskkill /IM redis-server.exe /F 2>$null | Out-Null
Write-Host "  - Go TCP server (pomo.exe)"
taskkill /IM pomo.exe /F 2>$null | Out-Null
Write-Host "  - PHP backend (php.exe)"
taskkill /IM php.exe /F 2>$null | Out-Null
Write-Host "  - Vue frontend (node.exe)"
taskkill /IM node.exe /F 2>$null | Out-Null
Write-Host "Done."

