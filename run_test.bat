@echo off
echo Checking administrator privileges...
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as Administrator - OK
    cd /d "%~dp0"
    php %1
    pause
) else (
    echo ERROR: Not running as Administrator!
    echo Please right-click this file and select "Run as administrator"
    pause
)