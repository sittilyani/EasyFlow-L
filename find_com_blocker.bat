@echo off
echo Finding what's blocking COM3...
echo.

echo 1. Checking for processes using COM3...
powershell -Command "
    Get-WmiObject Win32_Process | ForEach-Object {
        $cmdLine = $_.CommandLine
        if ($cmdLine -and $cmdLine -like '*COM3*') {
            Write-Host 'Process using COM3:' -ForegroundColor Red
            Write-Host '  Name: ' $_.Name -ForegroundColor Yellow
            Write-Host '  PID: ' $_.ProcessId -ForegroundColor Yellow
            Write-Host '  Command: ' $cmdLine -ForegroundColor Yellow
            Write-Host ''
        }
    }
"

echo 2. Checking handle usage...
echo    Downloading Handle.exe from Sysinternals...
if not exist handle.exe (
    powershell -Command "Invoke-WebRequest -Uri 'https://live.sysinternals.com/handle.exe' -OutFile 'handle.exe'"
)

echo.
echo 3. Running handle search (this may take a moment)...
handle.exe -a -nobanner COM3 2>nul

echo.
echo 4. Checking serial port usage via WMI...
powershell -Command "
    try {
        $ports = Get-WmiObject -Query 'SELECT * FROM Win32_SerialPort'
        if ($ports) {
            foreach ($port in $ports) {
                Write-Host 'Serial Port: ' $port.DeviceID -ForegroundColor Green
                Write-Host '  Name: ' $port.Name -ForegroundColor Cyan
                Write-Host '  Provider: ' $port.ProviderType -ForegroundColor Cyan
            }
        } else {
            Write-Host 'No serial ports found via WMI' -ForegroundColor Yellow
        }
    } catch {
        Write-Host 'WMI query failed: ' $_.Exception.Message -ForegroundColor Red
    }
"

echo.
echo 5. Checking for virtual serial port software...
tasklist | findstr /i "vspd com0com hdsp"

echo.
pause