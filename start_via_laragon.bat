@echo off
echo Starting Pump Service via Laragon...
cd /d C:\laragon\www\iorpms

REM This uses the PHP that Laragon is currently configured to use
php pump_service.php

pause