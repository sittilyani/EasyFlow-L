@echo off
echo Starting Pump Service (Single Run)...
echo.

REM Check admin
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Run as Administrator!
    pause
    exit /b 1
)

cd /d C:\laragon\www\iorpms
set PHP_EXE=C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe

if exist "%PHP_EXE%" (
    echo ? Running as Admin with PHP 8.3.26
    echo.
    "%PHP_EXE%" pump_service.php single
) else (
    echo PHP not found
    pause
)

pause