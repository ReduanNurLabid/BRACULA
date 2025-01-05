@echo off
echo Starting BRACULA Site...

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

REM Set paths
set XAMPP_PATH=C:\xampp
set PHP_PATH=%XAMPP_PATH%\php\php.exe
set MYSQL_PATH=%XAMPP_PATH%\mysql\bin\mysql.exe
set PROJECT_PATH=%XAMPP_PATH%\htdocs\BRACULA

echo Starting XAMPP services...
net start MySQL
net start Apache2.4

REM Wait for services to start
timeout /t 5 /nobreak

echo Initializing database and tables...
"%PHP_PATH%" "%PROJECT_PATH%\database\init_db.php"

echo Running database setup scripts...
"%PHP_PATH%" "%PROJECT_PATH%\database\create_activities_table.php"
"%PHP_PATH%" "%PROJECT_PATH%\database\create_event_registrations_table.php"

echo Populating initial data...
"%PHP_PATH%" "%PROJECT_PATH%\database\populate_users.php"
"%PHP_PATH%" "%PROJECT_PATH%\database\populate_resources.php"
"%PHP_PATH%" "%PROJECT_PATH%\database\add_test_user.php"

echo Verifying database setup...
"%PHP_PATH%" "%PROJECT_PATH%\database\check_users.php"
"%PHP_PATH%" "%PROJECT_PATH%\database\check_resources.php"

echo Opening BRACULA in your default browser...
start http://localhost:8081/bracula/

echo.
echo BRACULA is now running!
echo You can access the site at: http://localhost:8081/bracula/
echo.
echo Press any key to close this window...
pause >nul 