@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title 服务器诊断工具
color 0E

echo ============================================================
echo   服务器诊断工具
echo ============================================================
echo.

set "HTTP_PORT=18080"
set "WS_PORT=18900"
cd /d %~dp0

:: 1. 检查服务器进程
echo [1/6] 检查服务器进程...
tasklist /FI "IMAGENAME eq device_server.exe" 2>NUL | find /I /N "device_server.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo    [OK] 服务器进程正在运行
) else (
    echo    [ERROR] 服务器进程未运行
    echo    请运行 start_server.bat 启动服务器
)
echo.

:: 2. 检查端口监听
echo [2/6] 检查端口监听状态...
netstat -ano | findstr ":!HTTP_PORT!.*LISTENING" >nul 2>&1
if not errorlevel 1 (
    echo    [OK] HTTP端口 !HTTP_PORT! 正在监听
    netstat -ano | findstr ":!HTTP_PORT!.*LISTENING"
) else (
    echo    [ERROR] HTTP端口 !HTTP_PORT! 未监听
)
echo.

netstat -ano | findstr ":!WS_PORT!.*LISTENING" >nul 2>&1
if not errorlevel 1 (
    echo    [OK] WebSocket端口 !WS_PORT! 正在监听
) else (
    echo    [WARN] WebSocket端口 !WS_PORT! 未监听
)
echo.

:: 3. 获取本机IP地址
echo [3/6] 检测本机IP地址...
set "LOCAL_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    call :__check_ip "%%a"
)
if not defined LOCAL_IP (
    echo    [WARN] 未检测到局域网IP地址
)
echo.

:: 4. 测试本地连接
echo [4/6] 测试本地HTTP连接...
set "TEST_URL=http://127.0.0.1:!HTTP_PORT!/device_direct.html"
powershell -NoProfile -NoLogo -Command "try { $response = Invoke-WebRequest -Uri '!TEST_URL!' -TimeoutSec 3 -UseBasicParsing -ErrorAction Stop; if ($response.StatusCode -eq 200) { Write-Host '    [OK] 本地HTTP连接成功' -ForegroundColor Green } else { Write-Host '    [WARN] 返回状态码:' $response.StatusCode -ForegroundColor Yellow } } catch { Write-Host '    [ERROR] 本地连接失败:' $_.Exception.Message -ForegroundColor Red }"
echo.

:: 5. 检查防火墙
echo [5/6] 检查防火墙状态...
netsh advfirewall show allprofiles state 2>nul | findstr /i "state" >nul 2>&1
if not errorlevel 1 (
    echo    [INFO] 防火墙已启用
    echo    如果移动端无法访问，可能需要添加防火墙规则
) else (
    echo    [INFO] 防火墙状态未知
)
echo.

:: 6. 提供解决方案
echo [6/6] 故障排除建议
echo.
echo 如果移动端无法访问，请检查以下事项：
echo.
echo 1. 确保服务器正在运行
echo    运行 start_server.bat 启动服务器
echo.
echo 2. 检查防火墙设置
echo    运行以下命令添加防火墙规则：
echo    netsh advfirewall firewall add rule name="Device Server HTTP" dir=in action=allow protocol=TCP localport=!HTTP_PORT!
echo    netsh advfirewall firewall add rule name="Device Server WS" dir=in action=allow protocol=TCP localport=!WS_PORT!
echo.
echo 3. 确保移动设备和服务器在同一网络
echo    移动设备需要连接到与服务器相同的WiFi网络
echo.
echo 4. 使用正确的IP地址
echo    不要使用 127.0.0.1 或 localhost
if defined LOCAL_IP (
    echo    使用检测到的局域网IP地址: !LOCAL_IP!
    echo    移动端访问地址: http://!LOCAL_IP!:!HTTP_PORT!/device_direct.html
) else (
    echo    请使用上面检测到的局域网IP地址（如 10.10.100.101）
)
echo.
echo 5. 检查端口是否被占用
echo    如果端口被占用，请先停止占用端口的程序
echo.

goto :end

:__check_ip
set "tempIP=%~1"
set "tempIP=%tempIP:~1%"
echo %tempIP% | findstr /r "^10\.10\.100\." >nul
if not errorlevel 1 (
    set "LOCAL_IP=%tempIP%"
    echo    [INFO] 检测到局域网IP: %tempIP%
    echo    移动端访问地址: http://%tempIP%:!HTTP_PORT!/device_direct.html
)
exit /b

:end
echo ============================================================
pause
