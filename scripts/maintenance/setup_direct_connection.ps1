# 一键配置直连模式
# 1. 执行数据库迁移（添加 local_ip 字段）
# 2. 从数据库生成设备配置
# 3. 启动桥接服务

Write-Host "================================" -ForegroundColor Cyan
Write-Host "  直连模式一键配置向导  " -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: 数据库迁移
Write-Host "📝 步骤 1/3: 检查数据库字段..." -ForegroundColor Yellow

$sqlFile = Join-Path $PSScriptRoot "add_local_ip_field.sql"
if (Test-Path $sqlFile) {
    Write-Host "   找到数据库迁移文件" -ForegroundColor Gray
    Write-Host "   请手动执行以下SQL（如果尚未执行）:" -ForegroundColor Yellow
    Write-Host ""
    Get-Content $sqlFile | ForEach-Object { Write-Host "   $_" -ForegroundColor Cyan }
    Write-Host ""
    Write-Host "   是否已经执行过此SQL？(y/n): " -ForegroundColor Yellow -NoNewline
    $response = Read-Host
    
    if ($response -ne 'y' -and $response -ne 'Y') {
        Write-Host "   ⚠️  请先执行SQL后再继续" -ForegroundColor Yellow
        pause
        exit
    }
}

Write-Host "   ✅ 数据库字段已就绪" -ForegroundColor Green
Write-Host ""

# Step 2: 生成配置
Write-Host "📝 步骤 2/3: 从数据库生成设备配置..." -ForegroundColor Yellow

$phpScript = Join-Path $PSScriptRoot "generate_device_config.php"
if (-not (Test-Path $phpScript)) {
    Write-Host "   ❌ 配置生成脚本不存在: $phpScript" -ForegroundColor Red
    pause
    exit
}

# 查找 PHP
$phpCmd = $null
foreach ($cmd in @("php", "php.exe")) {
    try {
        $ver = & $cmd --version 2>&1
        if ($LASTEXITCODE -eq 0) {
            $phpCmd = $cmd
            break
        }
    } catch {}
}

if (-not $phpCmd) {
    Write-Host "   ❌ 未找到 PHP，请先安装 PHP" -ForegroundColor Red
    pause
    exit
}

Write-Host "   执行配置生成脚本..." -ForegroundColor Gray
& $phpCmd $phpScript

if ($LASTEXITCODE -ne 0) {
    Write-Host "   ❌ 配置生成失败" -ForegroundColor Red
    pause
    exit
}

Write-Host ""

# Step 3: 启动桥接
Write-Host "📝 步骤 3/3: 启动桥接服务..." -ForegroundColor Yellow
Write-Host "   是否现在启动桥接服务？(y/n): " -ForegroundColor Yellow -NoNewline
$startBridge = Read-Host

if ($startBridge -eq 'y' -or $startBridge -eq 'Y') {
    $bridgeScript = Join-Path $PSScriptRoot "start_multi_bridge.ps1"
    if (Test-Path $bridgeScript) {
        & $bridgeScript
    } else {
        Write-Host "   ❌ 桥接启动脚本不存在: $bridgeScript" -ForegroundColor Red
    }
} else {
    Write-Host "   ⏭️  跳过启动桥接" -ForegroundColor Gray
    Write-Host "   稍后可手动运行: .\scripts\start_multi_bridge.ps1" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "  配置完成！  " -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 下一步:" -ForegroundColor Yellow
Write-Host "   1. 在后台管理页面编辑网关，填写局域网IP" -ForegroundColor Gray
Write-Host "   2. 重新运行: .\scripts\generate_device_config.php" -ForegroundColor Gray
Write-Host "   3. 启动桥接: .\scripts\start_multi_bridge.ps1" -ForegroundColor Gray
Write-Host "   4. 打开网页: http://127.0.0.1:8000/static/admin" -ForegroundColor Gray
Write-Host ""


