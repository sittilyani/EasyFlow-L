@echo off
echo Fixing COM3 permissions...
echo.

REM Reset COM3 permissions
powershell -Command "$rule = New-Object System.Security.AccessControl.FileSystemAccessRule('Everyone','FullControl','Allow'); $acl = Get-Acl '\\.\COM3'; $acl.SetAccessRule($rule); Set-Acl '\\.\COM3' $acl; Write-Host 'COM3 permissions reset'"

REM Also fix the device itself
powershell -Command "$device = Get-PnpDevice -FriendlyName '*COM3*'; if ($device) { Disable-PnpDevice -InstanceId $device.InstanceId -Confirm:$false; Start-Sleep 3; Enable-PnpDevice -InstanceId $device.InstanceId -Confirm:$false; Write-Host 'COM3 device reset' }"

REM Check result
echo.
echo Checking new permissions:
powershell -Command "Get-Acl '\\.\COM3' | Format-List"

echo.
pause