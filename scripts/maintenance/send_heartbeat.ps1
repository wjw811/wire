$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$cmdDir = Join-Path $root 'cmd'
$logsDir = Join-Path $root 'logs'
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

$cmd = "go run test.go > ..\\logs\\pomo_client2.out 2>&1"
Write-Host "Sending one heartbeat to 127.0.0.1:2024 ..."
Start-Process -Wait -WorkingDirectory $cmdDir -FilePath powershell -ArgumentList "-NoProfile","-Command", $cmd | Out-Null
Write-Host "Sent. See logs\\pomo_client2.out"




