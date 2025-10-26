@echo off
echo Starting Pump Service - Universal Version...
cd /d C:\laragon\www\iorpms

REM Find any PHP executable in Laragon
set PHP_EXE=
for /d %%i in ("C:\laragon\bin\php\php-*") do (
    if exist "%%i\php.exe" (
        set PHP_EXE=%%i\php.exe
        goto :foundphp
    )
)

:foundphp
if "%PHP_EXE%"=="" (
    echo ERROR: No PHP executable found in C:\laragon\bin\php\
    echo Please check your Laragon installation
    pause
    exit /b 1
)

echo Using PHP: %PHP_EXE%
"%PHP_EXE%" pump_service.php

pause