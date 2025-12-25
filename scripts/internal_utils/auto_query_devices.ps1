# 设备自动查询脚本 - 每3秒查询一次设备数据
# 使用方法: .\auto_query_devices.ps1

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "🚀 启动设备自动查询..." -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

$count = 0
$interval = 3  # 秒

Write-Host "⏰ 查询间隔: $interval 秒" -ForegroundColor Yellow
Write-Host "💡 按 Ctrl+C 停止" -ForegroundColor Yellow
Write-Host ""

while ($true) {
    $count++
    $now = Get-Date -Format "HH:mm:ss"
    
    Write-Host "🔄 [$count] 查询设备数据... 时间: $now" -ForegroundColor Green
    
    try {
        # 调用后端 API 获取数据（这会从 Redis 读取最新设备数据）
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/admin/dash" -UseBasicParsing -ErrorAction Stop
        $data = $response.Content | ConvertFrom-Json
        
        if ($data.result -and $data.result.info) {
            $deviceCount = $data.result.info.Count
            Write-Host "  ✅ 成功获取 $deviceCount 个设备的数据" -ForegroundColor White
            
            # 显示第一个设备的时间
            if ($data.result.info[0]) {
                $device = $data.result.info[0]
                Write-Host "  📱 设备: $($device.name) (ID: $($device.id))" -ForegroundColor White
                Write-Host "  ⏰ 时间: $($device.time)" -ForegroundColor Cyan
            }
        }
        else {
            Write-Host "  ⚠️ 返回数据格式异常" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "  ❌ 查询失败: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
    Start-Sleep -Seconds $interval
}





















