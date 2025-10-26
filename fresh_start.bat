@echo off
echo Fresh start for pump system...
echo.

echo 1. Stopping all PHP processes...
taskkill /f /im php.exe 2>nul

echo 2. Cleaning up lock files...
del pump_service.lock 2>nul

echo 3. Waiting for cleanup...
timeout /t 3 >nul

echo 4. Starting fresh pump service...
start "Pump Service" "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" pump_service.php

echo 5. Testing COM3 access...
timeout /t 2 >nul
mode COM3
if %errorlevel% equ 0 (
    echo ? COM3 is accessible!
) else (
    echo ? COM3 is blocked by the service
)

echo.
pause