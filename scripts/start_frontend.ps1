# 前端开发服务器启动脚本
# 注意：这只启动开发服务器（端口3100），不部署到生产环境
# 生产部署请使用：.\scripts\deploy_frontend.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$frontendDir = Join-Path $root 'x\admin'
$logsDir = Join-Path $root 'logs'
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

# 检查是否已安装依赖
if (-not (Test-Path (Join-Path $frontendDir 'node_modules'))) {
    Write-Host "Installing frontend dependencies..."
    Set-Location $frontendDir
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "npm install failed, trying pnpm..."
        pnpm install
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Both npm and pnpm failed. Please install dependencies manually."
            exit 1
        }
    }
    Set-Location $root
}

$cmd = "npm run dev > ..\\..\\logs\\frontend.out 2> ..\\..\\logs\\frontend.err"
Write-Host "Starting Vue frontend on :3100 (default Vite port)..."
Start-Process -WindowStyle Hidden -WorkingDirectory $frontendDir -FilePath powershell -ArgumentList "-NoProfile","-Command", $cmd | Out-Null
Start-Sleep -Milliseconds 1000
Write-Host "Frontend started. Logs: logs\\frontend.out / logs\\frontend.err"
Write-Host "Access: http://127.0.0.1:3100"



