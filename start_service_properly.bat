@echo off
echo Starting Pump Service Properly...
echo.

echo 1. Stopping any old PHP processes...
taskkill /f /im php.exe 2>nul

echo 2. Clearing old queue and lock files...
del pump_commands.json 2>nul
del pump_results.json 2>nul
del pump_service.lock 2>nul
del pump_service.log 2>nul

echo 3. Creating fresh queue files...
echo [] > pump_commands.json
echo [] > pump_results.json

echo 4. Starting pump service in a NEW window...
start "Pump Service" /min "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" "C:\laragon\www\iorpms\pump_service.php"

echo 5. Waiting for service to initialize...
timeout /t 5 >nul

echo 6. Checking if service started...
tasklist /fi "imagename eq php.exe" /fo table

echo.
echo If you see php.exe above, the service is running!
echo A new minimized window should have opened for the pump service.
echo.
pause
