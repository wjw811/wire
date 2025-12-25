@echo off
chcp 65001 >nul 2>&1
title 设置静态IP
color 0E

cls
echo.
echo     ╔════════════════════════════════════════════════════╗
echo     ║                                                    ║
echo     ║            设置静态IP地址                          ║
echo     ║                                                    ║
echo     ╚════════════════════════════════════════════════════╝
echo.
echo.
echo     说明：
echo       将电脑IP固定为 192.168.2.84
echo       这样WiFi模块可以一直连接到这个IP
echo.
echo     ════════════════════════════════════════════════════
echo.
echo     ⚠️  需要管理员权限
echo.
pause
echo.
echo     正在设置静态IP...
echo.

:: 使用PowerShell设置静态IP，并设置UTF-8编码
powershell -NoProfile -ExecutionPolicy Bypass -Command "$PSDefaultParameterValues['*:Encoding'] = 'utf8'; [Console]::OutputEncoding = [System.Text.Encoding]::UTF8; $env:PYTHONIOENCODING='utf-8'; Start-Process powershell -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"%~dp0设置静态IP.ps1\"' -Verb RunAs"

echo.
echo     请在弹出的PowerShell窗口中查看结果
echo.
pause






