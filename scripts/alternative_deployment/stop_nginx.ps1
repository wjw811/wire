# Nginx停止脚本（支持phpstudy_pro）

# 检测Nginx安装路径
$nginxPath = $null
$nginxDir = $null

$possiblePaths = @(
    "D:\phpstudy_pro\Extensions\Nginx*\nginx.exe",
    "C:\phpstudy_pro\Extensions\Nginx*\nginx.exe",
    "C:\nginx\nginx.exe"
)

foreach ($pattern in $possiblePaths) {
    $found = Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($found) {
        $nginxPath = $found.FullName
        $nginxDir = $found.DirectoryName
        break
    }
}

# 检查Nginx是否已安装
if (-not $nginxPath -or -not (Test-Path $nginxPath)) {
    Write-Host "Nginx未安装" -ForegroundColor Yellow
    exit 0
}

# 查找Nginx进程
$nginxProcess = Get-Process -Name nginx -ErrorAction SilentlyContinue
if (-not $nginxProcess) {
    Write-Host "Nginx未运行" -ForegroundColor Yellow
    exit 0
}

Write-Host "正在停止Nginx..." -ForegroundColor Cyan

# 优雅停止（发送QUIT信号）
Set-Location $nginxDir
& $nginxPath -s quit

# 等待进程退出
$timeout = 10
$elapsed = 0
while (($elapsed -lt $timeout) -and (Get-Process -Name nginx -ErrorAction SilentlyContinue)) {
    Start-Sleep -Milliseconds 500
    $elapsed += 0.5
}

# 如果还在运行，强制停止
$nginxProcess = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginxProcess) {
    Write-Host "强制停止Nginx..." -ForegroundColor Yellow
    Stop-Process -Name nginx -Force
}

Write-Host "Nginx已停止" -ForegroundColor Green

