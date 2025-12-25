$ErrorActionPreference = 'Continue'
Write-Host "--- Ports ---"
netstat -ano | findstr ":2024" | findstr LISTENING
netstat -ano | findstr ":2010" | findstr LISTENING
netstat -ano | findstr ":8000" | findstr LISTENING
netstat -ano | findstr ":3100" | findstr LISTENING

Write-Host "--- Processes ---"
tasklist | findstr /I "pomo.exe"
tasklist | findstr /I "php.exe"
tasklist | findstr /I "node.exe"

Write-Host "--- Tail logs --- (last 10 lines)"
$root = Split-Path -Parent $PSScriptRoot
Get-Content (Join-Path $root 'logs\pomo_server.out') -ErrorAction SilentlyContinue -Tail 10
Get-Content (Join-Path $root 'logs\php8000.err') -ErrorAction SilentlyContinue -Tail 5
Get-Content (Join-Path $root 'logs\frontend.err') -ErrorAction SilentlyContinue -Tail 5

