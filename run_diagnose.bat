@echo off
echo Running COM3 Diagnostic as Administrator...
cd /d C:\laragon\www\iorpms
"C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" diagnose_com3.php
pause