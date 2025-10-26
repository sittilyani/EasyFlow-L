@echo off
echo Fixing USB Serial Adapter Issues...
echo.

REM 1. Uninstall the device completely
echo Step 1: Removing USB Serial device...
powershell -Command "Get-PnpDevice -FriendlyName '*USB Serial*' | ForEach-Object { pnputil /remove-device $_.InstanceId }"

echo Step 2: Waiting for device removal...
timeout /t 5

echo Step 3: Rescan for hardware changes...
powershell -Command "Get-PnpDevice -Status ERROR | ForEach-Object { pnputil /scan-devices }"

echo Step 4: Disable USB selective suspend...
powercfg /setacvalueindex SCHEME_CURRENT sub_usb usbsettings_selectivesuspend 0
powercfg /setdcvalueindex SCHEME_CURRENT sub_usb usbsettings_selectivesuspend 0

echo.
echo Please UNPLUG and REPLUG the USB cable now!
echo Then press any key to continue...
pause >nul

echo Step 5: Check if device reinstalled properly...
powershell -Command "Get-PnpDevice -FriendlyName '*USB Serial*' | Format-Table Status, FriendlyName"

echo.
pause