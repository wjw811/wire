# 设置静态IP地址
# 需要管理员权限运行

# 设置控制台输出编码为UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$ErrorActionPreference = 'Stop'

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  🔧 设置静态IP地址" -ForegroundColor Yellow
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# 检查管理员权限
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "❌ 需要管理员权限！" -ForegroundColor Red
    Write-Host ""
    Write-Host "请右键点击脚本，选择'以管理员身份运行'" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

Write-Host "✅ 管理员权限确认" -ForegroundColor Green
Write-Host ""

# 查找无线网卡
$adapter = Get-NetAdapter | Where-Object {$_.Status -eq "Up" -and $_.MediaType -like "*802.11*"} | Select-Object -First 1

if (-not $adapter) {
    Write-Host "❌ 找不到无线网卡！" -ForegroundColor Red
    pause
    exit 1
}

$interfaceName = $adapter.Name
$interfaceAlias = $adapter.InterfaceAlias

Write-Host "📡 网卡信息：" -ForegroundColor Cyan
Write-Host "   名称: $interfaceName" -ForegroundColor White
Write-Host "   描述: $($adapter.InterfaceDescription)" -ForegroundColor White
Write-Host ""

# 静态IP配置
$staticIP = "192.168.2.94"
$subnetMask = "255.255.255.0"
$gateway = "192.168.2.1"  # 路由器地址，可能需要修改
$dns1 = "192.168.2.1"     # DNS服务器，可能需要修改
$dns2 = "8.8.8.8"         # 备用DNS

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray
Write-Host ""
Write-Host "将要设置的静态IP配置：" -ForegroundColor Cyan
Write-Host ""
Write-Host "   IP地址:     $staticIP" -ForegroundColor Green
Write-Host "   子网掩码:   $subnetMask" -ForegroundColor White
Write-Host "   默认网关:   $gateway" -ForegroundColor White
Write-Host "   DNS服务器:  $dns1, $dns2" -ForegroundColor White
Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray
Write-Host ""
Write-Host "⚠️  注意：" -ForegroundColor Yellow
Write-Host "   如果网关地址不是 192.168.2.1，请先修改脚本" -ForegroundColor White
Write-Host ""

$confirm = Read-Host "确认设置静态IP？(Y/N)"

if ($confirm -ne 'Y' -and $confirm -ne 'y') {
    Write-Host ""
    Write-Host "已取消" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 0
}

try {
    Write-Host ""
    Write-Host "正在设置静态IP..." -ForegroundColor Cyan
    Write-Host ""
    
    # 移除现有IP配置
    Remove-NetIPAddress -InterfaceAlias $interfaceAlias -Confirm:$false -ErrorAction SilentlyContinue
    Remove-NetRoute -InterfaceAlias $interfaceAlias -Confirm:$false -ErrorAction SilentlyContinue
    
    # 设置新的静态IP
    New-NetIPAddress -InterfaceAlias $interfaceAlias -IPAddress $staticIP -PrefixLength 24 -DefaultGateway $gateway | Out-Null
    
    Write-Host "   ✅ IP地址已设置" -ForegroundColor Green
    
    # 设置DNS
    Set-DnsClientServerAddress -InterfaceAlias $interfaceAlias -ServerAddresses @($dns1, $dns2)
    
    Write-Host "   ✅ DNS已设置" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Green
    Write-Host ""
    Write-Host "   ✓ 静态IP设置成功！" -ForegroundColor Green
    Write-Host ""
    Write-Host "   电脑IP已固定为: $staticIP" -ForegroundColor Cyan
    Write-Host "   WiFi模块应该连接: $staticIP:2024" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Green
    Write-Host ""
    Write-Host "💡 下一步：" -ForegroundColor Yellow
    Write-Host "   1. 重启WiFi模块（让它重新连接）" -ForegroundColor White
    Write-Host "   2. 等待1-2分钟" -ForegroundColor White
    Write-Host "   3. 刷新浏览器页面" -ForegroundColor White
    Write-Host "   4. 数据应该会出现" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "❌ 设置失败: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
}

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray
Write-Host ""
pause

