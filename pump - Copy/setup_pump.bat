@echo off
REM setup_pump.bat - Windows setup for MasterPlex pump
echo Setting up MasterPlex Pump for XAMPP...

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Please run this script as Administrator!
    pause
    exit /b 1
)

REM Create pump directory
if not exist "C:\xampp\htdocs\iorpms\pump" mkdir "C:\xampp\htdocs\iorpms\pump"

REM Check PHP extensions
echo Checking PHP configuration...
"C:\xampp\php\php.exe" -m | findstr "dio" >nul
if %errorLevel% neq 0 (
    echo DIO extension not found. Enabling...
    REM Enable dio extension in php.ini
    findstr /c:"extension=dio" "C:\xampp\php\php.ini" >nul
    if %errorLevel% neq 0 (
        echo extension=php_dio.dll >> "C:\xampp\php\php.ini"
    )
)

REM Check for FT232R driver
echo Checking for FT232R driver...
reg query "HKLM\SYSTEM\CurrentControlSet\Enum\FTDIBUS" >nul 2>&1
if %errorLevel% neq 0 (
    echo FTDI driver not found. Please install from:
    echo https://ftdichip.com/drivers/vcp-drivers/
    pause
)

REM List COM ports
echo Available COM ports:
mode

REM Set permissions (Windows doesn't need this for COM ports usually)
echo.
echo Setup complete!
echo.
echo Instructions:
echo 1. Connect MasterPlex pump via USB
echo 2. Note the COM port (usually COM3 or COM4)
echo 3. Test the pump by visiting:
echo    http://localhost/iorpms/pump/test_pump.php
echo.
pause