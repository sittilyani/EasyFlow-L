@echo off
echo Checking for common serial port blocking applications...
echo.

echo 1. Checking for virtual serial port software...
dir "C:\Program Files\*" | findstr /i "com0com vspd eltima"
dir "C:\Program Files (x86)\*" | findstr /i "com0com vspd eltima"

echo.
echo 2. Checking running services that might use serial ports...
sc query | findstr /i "serial com port"

echo.
echo 3. Checking for Arduino IDE...
tasklist | findstr /i "arduino"

echo.
echo 4. Checking for PuTTY and other terminals...
tasklist | findstr /i "putty teraterm realterm"

echo.
echo 5. Checking for programming tools...
tasklist | findstr /i "python java node npm"

echo.
pause