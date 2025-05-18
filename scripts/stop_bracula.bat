@echo off
echo Stopping BRACULA Services...

REM Check if running with admin privileges
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running with administrator privileges...
) else (
    echo Please run this script as administrator!
    echo Right-click the batch file and select "Run as administrator"
    pause
    exit
)

echo Stopping XAMPP services...
net stop Apache2.4
net stop MySQL

echo.
echo BRACULA services have been stopped.
echo.
echo Press any key to close this window...
pause >nul 