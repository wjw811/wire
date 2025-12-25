# 为phpstudy_pro配置Nginx
# 设置控制台编码为UTF-8，解决中文乱码
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

$root = Split-Path -Parent $PSScriptRoot
$nginxDir = "D:\phpstudy_pro\Extensions\Nginx1.15.11"

if (-not (Test-Path $nginxDir)) {
    Write-Host "错误: 找不到phpstudy_pro的Nginx目录: $nginxDir" -ForegroundColor Red
    exit 1
}

$nginxConfigPath = Join-Path $nginxDir "conf\nginx.conf"
$wireConfigPath = Join-Path $root "nginx_wire.conf"
$targetConfigPath = Join-Path $nginxDir "conf\nginx_wire.conf"

Write-Host "配置phpstudy_pro的Nginx..." -ForegroundColor Cyan
Write-Host ""

# 1. 复制配置文件
Write-Host "[1/3] 复制配置文件..." -ForegroundColor Yellow
Copy-Item $wireConfigPath $targetConfigPath -Force
Write-Host "  已复制到: $targetConfigPath" -ForegroundColor Green

# 2. 检查主配置文件
Write-Host "[2/3] 检查主配置文件..." -ForegroundColor Yellow
if (-not (Test-Path $nginxConfigPath)) {
    Write-Host "  错误: 找不到主配置文件: $nginxConfigPath" -ForegroundColor Red
    exit 1
}

$configContent = Get-Content $nginxConfigPath -Raw
$includePattern = "nginx_wire\.conf"

if ($configContent -match $includePattern) {
    Write-Host "  配置已包含，跳过添加include" -ForegroundColor Green
} else {
    Write-Host "[3/3] 添加include到主配置文件..." -ForegroundColor Yellow
    
    # 备份原配置
    $backupPath = "$nginxConfigPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $nginxConfigPath $backupPath -Force
    Write-Host "  已备份原配置到: $backupPath" -ForegroundColor Gray
    
    # 在http块末尾添加include（在最后一个}之前）
    # 使用相对路径（相对于nginx目录）
    $includeLine = "    include conf/nginx_wire.conf;`n"
    
    # 查找http块的结束位置
    if ($configContent -match "(http\s*\{[\s\S]*?)(\s*\})") {
        $beforeHttpEnd = $matches[1]
        $httpEndBrace = $matches[2]
        
        # 在最后一个}之前添加include
        $newConfigContent = $configContent -replace ([regex]::Escape($matches[0])), ($beforeHttpEnd + $includeLine + $httpEndBrace)
        
        # 移除可能的BOM字符，使用无BOM的UTF-8保存
        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllText($nginxConfigPath, $newConfigContent, $utf8NoBom)
        Write-Host "  已添加include配置" -ForegroundColor Green
    } else {
        Write-Host "  警告: 无法自动添加include，请手动在nginx.conf的http块中添加:" -ForegroundColor Yellow
        Write-Host "    include conf/nginx_wire.conf;" -ForegroundColor Cyan
    }
}

Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "配置完成！" -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "重要提示：" -ForegroundColor Yellow
Write-Host "1. 当前8000端口已被phpstudy_pro的Nginx占用" -ForegroundColor White
Write-Host "2. 需要确保PHP内置服务器在8001端口运行" -ForegroundColor White
Write-Host "3. 可以通过小皮面板重启Nginx，或运行以下命令:" -ForegroundColor White
Write-Host ("   cd " + $nginxDir) -ForegroundColor Cyan
Write-Host "   .\nginx.exe -s reload" -ForegroundColor Cyan
Write-Host ""
Write-Host "如果端口冲突，请修改 nginx_wire.conf 中的 listen 端口" -ForegroundColor Yellow
Write-Host ""

# 测试Nginx配置
Write-Host "测试Nginx配置..." -ForegroundColor Cyan
$testCmd = "cd '$nginxDir'; .\nginx.exe -t"
$testResult = Invoke-Expression $testCmd 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "Nginx配置测试通过！" -ForegroundColor Green
} else {
    Write-Host "Nginx配置测试失败，请检查错误信息：" -ForegroundColor Red
    Write-Host $testResult -ForegroundColor Red
}

