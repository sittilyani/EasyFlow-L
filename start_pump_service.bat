@echo off
echo Starting Pump Service for PHP 8.3.26...
echo.

REM Check admin privileges
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Must run as Administrator!
    echo Right-click -> Run as administrator
    pause
    exit /b 1
)

echo ? Running as Administrator
echo.

REM Change to project directory
cd /d C:\laragon\www\iorpms
echo ? Changed to project directory
echo.

REM Use the exact PHP path
set PHP_EXE=C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe

if exist "%PHP_EXE%" (
    echo ? Found PHP: php-8.3.26-Win32-vs16-x64
    echo.
    echo Starting pump service...
    echo.
    "%PHP_EXE%" pump_service.php
) else (
    echo ? ERROR: PHP not found at:
    echo %PHP_EXE%
    echo.
    echo Please check the path is correct
    pause
    exit /b 1
)

echo.
echo Pump service stopped.
pause