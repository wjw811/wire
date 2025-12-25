$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$wsPort = $env:BRIDGE_WS_PORT
if (-not $wsPort) { $wsPort = 18900 }
$targetIp = $env:BRIDGE_TARGET_IP
if (-not $targetIp) { $targetIp = "10.10.100.254" }
$targetPort = $env:BRIDGE_TARGET_PORT
if (-not $targetPort) { $targetPort = 18899 }

Write-Host ("Starting WS bridge: ws://127.0.0.1:{0} -> tcp://{1}:{2}" -f $wsPort,$targetIp,$targetPort)

# Ensure logs dir exists
$logs = Join-Path $root 'logs'
if (-not (Test-Path $logs)) { New-Item -ItemType Directory -Path $logs | Out-Null }
$outFile = Join-Path $logs 'ws_bridge.out'
$errFile = Join-Path $logs 'ws_bridge.err'

# Detect python
$py = Get-Command py -ErrorAction SilentlyContinue
if (-not $py) { $py = Get-Command python -ErrorAction SilentlyContinue }
if (-not $py) {
  Write-Warning "Python not found, skip starting WebSocket bridge (websockify)."
  return
}

# Ensure websockify is installed
try {
  & $py.Source -m pip show websockify *> $null 2>&1
  if ($LASTEXITCODE -ne 0) {
    Write-Host "Installing websockify..."
    & $py.Source -m pip install -i https://pypi.tuna.tsinghua.edu.cn/simple websockify | Tee-Object -FilePath $outFile -Append | Out-Null
  }
} catch { }

# Start bridge in background
$target = "{0}:{1}" -f $targetIp, $targetPort
$bridgeArgs = @("-m","websockify","127.0.0.1:$wsPort",$target,"--verbose")
Start-Process -FilePath $py.Source -ArgumentList $bridgeArgs -WindowStyle Minimized -RedirectStandardOutput $outFile -RedirectStandardError $errFile | Out-Null
Write-Host ("Bridge started. Logs: {0} / {1}" -f $outFile,$errFile)


