# Nginx启动脚本（支持phpstudy_pro）

$root = Split-Path -Parent $PSScriptRoot

# 检测Nginx安装路径（优先phpstudy_pro）
$nginxPath = $null
$nginxConfigPath = $null
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
        $nginxConfigPath = Join-Path $nginxDir "conf\nginx.conf"
        Write-Host "检测到Nginx: $nginxPath" -ForegroundColor Green
        break
    }
}

# 检查Nginx是否已安装
if (-not $nginxPath -or -not (Test-Path $nginxPath)) {
    Write-Host "错误: 找不到Nginx" -ForegroundColor Red
    Write-Host ""
    Write-Host "请确保已安装Nginx（phpstudy_pro或独立安装）" -ForegroundColor Yellow
    exit 1
}

# 检查是否已运行
$nginxProcess = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginxProcess) {
    Write-Host "Nginx已经在运行中" -ForegroundColor Yellow
    Write-Host "PID: $($nginxProcess.Id -join ', ')" -ForegroundColor Cyan
    exit 0
}

# 检查配置文件
$configPath = Join-Path $root "nginx.conf"
if (-not (Test-Path $configPath)) {
    Write-Host "错误: 找不到nginx.conf配置文件" -ForegroundColor Red
    Write-Host "配置文件路径: $configPath" -ForegroundColor Yellow
    exit 1
}

# 测试配置文件
Write-Host "测试Nginx配置..." -ForegroundColor Cyan
$testResult = & $nginxPath -t 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Nginx配置测试失败！" -ForegroundColor Red
    Write-Host $testResult -ForegroundColor Red
    exit 1
}

# 使用include方式（phpstudy_pro推荐）或直接替换配置
$wireConfigPath = Join-Path $root "nginx_wire.conf"
$includeLine = "    include $wireConfigPath;"

if ($nginxDir -like "*phpstudy_pro*") {
    # phpstudy_pro模式：使用include方式，不覆盖主配置
    Write-Host "检测到phpstudy_pro，使用include方式配置" -ForegroundColor Cyan
    
    # 复制配置文件到Nginx目录
    $nginxWireConfigPath = Join-Path $nginxDir "conf\nginx_wire.conf"
    Copy-Item $wireConfigPath $nginxWireConfigPath -Force
    
    # 检查主配置文件是否已包含我们的配置
    $mainConfigContent = Get-Content $nginxConfigPath -Raw -ErrorAction SilentlyContinue
    if ($mainConfigContent -and $mainConfigContent -notmatch "nginx_wire\.conf") {
        # 在主配置文件的http块中添加include
        $includePathInConf = "conf\nginx_wire.conf"
        $newIncludeLine = "    include $includePathInConf;"
        
        # 在http块的末尾（最后一个}之前）添加include
        if ($mainConfigContent -match "(http\s*\{[^}]*?)(\})") {
            $httpBlock = $matches[1]
            $newHttpBlock = $httpBlock + "`n" + $newIncludeLine + "`n    " + $matches[2]
            $newConfigContent = $mainConfigContent -replace [regex]::Escape($matches[0]), $newHttpBlock
            
            # 备份原配置
            $backupPath = "$nginxConfigPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
            Copy-Item $nginxConfigPath $backupPath -Force
            Write-Host "已备份原配置到: $backupPath" -ForegroundColor Yellow
            
            # 写入新配置
            Set-Content -Path $nginxConfigPath -Value $newConfigContent -Encoding UTF8
            Write-Host "已添加include配置到主配置文件" -ForegroundColor Green
        } else {
            Write-Host "警告: 无法自动添加include，请手动在nginx.conf的http块中添加:" -ForegroundColor Yellow
            Write-Host "  include conf/nginx_wire.conf;" -ForegroundColor Cyan
        }
    } else {
        Write-Host "配置文件已包含我们的配置" -ForegroundColor Green
    }
} else {
    # 独立安装模式：直接替换配置文件
    $backupPath = "$nginxConfigPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    if (Test-Path $nginxConfigPath) {
        Copy-Item $nginxConfigPath $backupPath -Force
        Write-Host "已备份原配置文件到: $backupPath" -ForegroundColor Yellow
    }
    Copy-Item $configPath $nginxConfigPath -Force
    Write-Host "已复制配置文件到: $nginxConfigPath" -ForegroundColor Green
}

# 启动Nginx（如果未运行）
Write-Host ""
$nginxProcess = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginxProcess) {
    Write-Host "Nginx已在运行，重新加载配置..." -ForegroundColor Cyan
    Set-Location $nginxDir
    & $nginxPath -s reload
    if ($LASTEXITCODE -eq 0) {
        Write-Host "配置已重新加载" -ForegroundColor Green
    } else {
        Write-Host "重新加载失败，尝试重启..." -ForegroundColor Yellow
        & $nginxPath -s quit
        Start-Sleep -Seconds 1
        Start-Process -FilePath $nginxPath -WindowStyle Hidden
    }
} else {
    Write-Host "启动Nginx..." -ForegroundColor Cyan
    Set-Location $nginxDir
    Start-Process -FilePath $nginxPath -WindowStyle Hidden
}

Start-Sleep -Seconds 1

# 检查是否启动成功
$nginxProcess = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginxProcess) {
    Write-Host "Nginx启动成功！" -ForegroundColor Green
    Write-Host "监听端口: 8000" -ForegroundColor Cyan
    Write-Host "访问地址: http://192.168.6.123:8000/static/admin/" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "注意: 如果使用PHP内置服务器，请确保PHP在8001端口运行" -ForegroundColor Yellow
    Write-Host "      需要修改 scripts/start_backend.ps1 将PHP端口改为8001" -ForegroundColor Yellow
} else {
    $errorLogPath = Join-Path $nginxDir "logs\nginx_error.log"
    Write-Host "Nginx启动失败，请检查日志: $errorLogPath" -ForegroundColor Red
    exit 1
}

