# 日志轮转定时任务管理脚本
param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("create", "delete", "status", "test")]
    [string]$Action
)

$TaskName = "WireLogRotation"
$ScriptPath = Join-Path $PSScriptRoot "rotate_logs.ps1"

switch ($Action) {
    "create" {
        Write-Host "创建日志轮转定时任务..."
        $FullScriptPath = (Resolve-Path $ScriptPath).Path
        $Command = "powershell.exe -ExecutionPolicy Bypass -File `"$FullScriptPath`""
        
        try {
            schtasks /create /tn $TaskName /tr $Command /sc hourly /st 00:00 /f | Out-Null
            Write-Host "✅ 定时任务创建成功！"
            Write-Host "任务名称: $TaskName"
            Write-Host "执行频率: 每小时"
            Write-Host "脚本路径: $FullScriptPath"
        }
        catch {
            Write-Host "❌ 创建定时任务失败: $($_.Exception.Message)"
        }
    }
    
    "delete" {
        Write-Host "删除日志轮转定时任务..."
        try {
            schtasks /delete /tn $TaskName /f | Out-Null
            Write-Host "✅ 定时任务删除成功！"
        }
        catch {
            Write-Host "❌ 删除定时任务失败: $($_.Exception.Message)"
        }
    }
    
    "status" {
        Write-Host "检查定时任务状态..."
        try {
            $result = schtasks /query /tn $TaskName /fo list 2>$null
            if ($result) {
                Write-Host "✅ 定时任务存在："
                $result | ForEach-Object { Write-Host "  $_" }
            } else {
                Write-Host "❌ 定时任务不存在"
            }
        }
        catch {
            Write-Host "❌ 查询定时任务失败: $($_.Exception.Message)"
        }
    }
    
    "test" {
        Write-Host "测试日志轮转功能..."
        try {
            & $ScriptPath
            Write-Host "✅ 日志轮转测试完成"
        }
        catch {
            Write-Host "❌ 日志轮转测试失败: $($_.Exception.Message)"
        }
    }
}

Write-Host ""
Write-Host "使用方法:"
Write-Host "  .\manage_log_rotation.ps1 -Action create   # 创建定时任务"
Write-Host "  .\manage_log_rotation.ps1 -Action delete   # 删除定时任务"
Write-Host "  .\manage_log_rotation.ps1 -Action status   # 查看状态"
Write-Host "  .\manage_log_rotation.ps1 -Action test     # 测试功能"