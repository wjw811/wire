# 前端部署脚本 - 避免路径混淆
# 使用方法: .\deploy_frontend.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "🚀 前端部署脚本" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

# 源目录和目标目录
$distDir = Join-Path $root "x\admin\dist"
$deployDir = Join-Path $root "static\admin"

Write-Host ""
Write-Host "源目录: $distDir" -ForegroundColor Yellow
Write-Host "目标目录: $deployDir" -ForegroundColor Yellow
Write-Host ""

# 检查源目录是否存在
if (-not (Test-Path $distDir)) {
    Write-Host "❌ 错误: dist 目录不存在！" -ForegroundColor Red
    Write-Host "   请先运行: cd x\admin; npm run build" -ForegroundColor Yellow
    exit 1
}

# 检查是否有构建产物
$indexFiles = Get-ChildItem (Join-Path $distDir "assets\index.*.js") -ErrorAction SilentlyContinue
if ($indexFiles.Count -eq 0) {
    Write-Host "❌ 错误: dist 目录没有构建产物！" -ForegroundColor Red
    Write-Host "   请先运行: cd x\admin; npm run build" -ForegroundColor Yellow
    exit 1
}

# 显示即将部署的主文件
$mainJs = ($indexFiles | Where-Object { $_.Length -gt 1000000 })[0]
if ($mainJs) {
    Write-Host "📦 主文件: $($mainJs.Name) ($([math]::Round($mainJs.Length/1MB, 2)) MB)" -ForegroundColor Green
}

# 确认部署
Write-Host ""
Write-Host "⚠️  即将清空目标目录并部署新文件..." -ForegroundColor Yellow
Write-Host ""
$confirm = Read-Host "确认部署? (Y/N)"
if ($confirm -ne 'Y' -and $confirm -ne 'y') {
    Write-Host "❌ 部署已取消" -ForegroundColor Red
    exit 0
}

# 清空目标目录
Write-Host ""
Write-Host "[1/3] 清空目标目录..." -ForegroundColor Cyan
Remove-Item "$deployDir\*" -Recurse -Force -ErrorAction SilentlyContinue
Write-Host "✅ 已清空" -ForegroundColor Green

# 复制新文件
Write-Host ""
Write-Host "[2/3] 复制新文件..." -ForegroundColor Cyan
Copy-Item "$distDir\*" $deployDir -Recurse -Force
Write-Host "✅ 已复制" -ForegroundColor Green

# 验证部署
Write-Host ""
Write-Host "[3/3] 验证部署..." -ForegroundColor Cyan

$deployedIndex = Get-ChildItem (Join-Path $deployDir "index.html") -ErrorAction SilentlyContinue
if ($deployedIndex) {
    Write-Host "✅ index.html 已部署" -ForegroundColor Green
    Write-Host "   修改时间: $($deployedIndex.LastWriteTime)" -ForegroundColor Gray
}

$deployedAssets = Get-ChildItem (Join-Path $deployDir "assets\*.js") -ErrorAction SilentlyContinue
if ($deployedAssets) {
    Write-Host "✅ 已部署 $($deployedAssets.Count) 个 JS 文件" -ForegroundColor Green
}

# 显示访问地址
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "✅ 部署完成！" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "访问地址: http://127.0.0.1:8000/static/admin/#/dashboard/index" -ForegroundColor Yellow
Write-Host ""
Write-Host "💡 提示: 如果浏览器显示旧内容，请:" -ForegroundColor Cyan
Write-Host "   1. 按 Ctrl+F5 强制刷新" -ForegroundColor Gray
Write-Host "   2. 或清除浏览器缓存" -ForegroundColor Gray
Write-Host "   3. 或使用无痕模式" -ForegroundColor Gray
Write-Host ""





















