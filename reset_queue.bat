@echo off
echo Resetting stuck pump queue...
echo.

echo 1. Stopping pump service...
taskkill /f /im php.exe 2>nul

echo 2. Clearing queue files...
del pump_commands.json 2>nul
del pump_results.json 2>nul
del pump_service.lock 2>nul

echo 3. Recreating empty queue files...
echo [] > pump_commands.json
echo [] > pump_results.json

echo 4. Starting fresh pump service...
start "Pump Service" "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" pump_service.php

echo 5. Waiting for service to start...
timeout /t 3 >nul

echo 6. Checking service status...
"C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" check_service_status.php

echo.
pause
