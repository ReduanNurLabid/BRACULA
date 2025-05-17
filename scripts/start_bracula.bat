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

REM === Configuration ===
REM Set the path to your XAMPP installation folder here:
set XAMPP_PATH=C:\xampp

REM Set the port your Apache server uses (default 8081)
set APACHE_PORT=8081

REM Set paths based on XAMPP_PATH
set PHP_PATH=%XAMPP_PATH%\php\php.exe
set MYSQL_PATH=%XAMPP_PATH%\mysql\bin\mysql.exe
set PROJECT_PATH=%XAMPP_PATH%\htdocs\BRACULA

echo Using XAMPP path: %XAMPP_PATH%
echo Using project path: %PROJECT_PATH%
echo Using Apache port: %APACHE_PORT%

echo Starting XAMPP services...
net start MySQL
net start Apache2.4

REM Wait for services to start
timeout /t 5 /nobreak

echo.
echo ========================================
echo Initializing core database and tables...
echo ========================================
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\init_db.php"

echo.
echo ========================================
echo Creating or updating additional tables...
echo ========================================

REM Create logs directory if it doesn't exist
if not exist "%PROJECT_PATH%\logs" (
    mkdir "%PROJECT_PATH%\logs"
    echo Created logs directory.
)

REM Create or update core tables
echo Creating activities table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\create_activities_table.php"

echo Creating event registrations table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\create_event_registrations_table.php"

echo Creating events table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\create_events_table.php"

echo Setting up rideshare tables...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\setup_rideshare_tables.php"

REM Fix missing columns in ride_requests table
echo Fixing ride_requests table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\fix_ride_requests_table.php" > "logs\fix_ride_requests.log"
echo Ride_requests table check completed. See logs/fix_ride_requests.log for details.

REM Fix missing pickup column in ride_requests table
echo Fixing pickup column in ride_requests table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\fix_pickup_column.php" > "logs\fix_pickup_column.log"
echo Pickup column check completed. See logs/fix_pickup_column.log for details.

REM Fix missing notes column in ride_requests table
echo Fixing notes column in ride_requests table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\fix_notes_column.php" > "logs\fix_notes_column.log"
echo Notes column check completed. See logs/fix_notes_column.log for details.

REM Fix missing driver_reviews table
echo Creating driver_reviews table if not exists...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\create_driver_reviews_table.php" > "logs\create_driver_reviews.log"
echo Driver_reviews table check completed. See logs/create_driver_reviews.log for details.

REM Create accommodation tables if they don't exist
echo Setting up accommodation tables...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\create_accommodation_tables.php" > "logs\create_accommodation_tables.log"
echo Accommodation tables check completed. See logs/create_accommodation_tables.log for details.

REM Call update_database.php to create saved_posts table
echo Creating saved_posts table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "api\update_database.php"

REM Create notifications table if it doesn't exist
echo Creating notifications table if not exists...
cd "%PROJECT_PATH%"
(
echo ^<?php
echo // Create notifications table
echo require_once __DIR__ . '/../config/database.php';
echo $database = new Database();
echo $conn = $database->getConnection();
echo $sql = "CREATE TABLE IF NOT EXISTS notifications (";
echo     "notification_id INT AUTO_INCREMENT PRIMARY KEY,";
echo     "user_id INT NOT NULL,";
echo     "type VARCHAR(50) NOT NULL,";
echo     "content TEXT NOT NULL,";
echo     "related_id INT,";
echo     "is_read BOOLEAN DEFAULT FALSE,";
echo     "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
echo     "FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE";
echo ")";
echo $conn-^>exec($sql);
echo echo "Notifications table created or verified.\n";
echo ?^>
) > "database\create_notifications_table.php"
"%PHP_PATH%" "database\create_notifications_table.php"

echo.
echo ========================================
echo Updating table structures...
echo ========================================

REM Update tables with required fields
echo Updating comments table structure...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\update_comments_table.php"

echo Updating posts table structure...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\update_posts_table.php"

echo.
echo ========================================
echo Populating initial data...
echo ========================================
echo Populating users...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\populate_users.php"

echo Populating resources...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\populate_resources.php"

REM Optional: Add test user if the script exists
if exist "database\add_test_user.php" (
    echo Adding test user...
    cd "%PROJECT_PATH%"
    "%PHP_PATH%" "database\add_test_user.php"
)

echo.
echo ========================================
echo Verifying database setup...
echo ========================================
echo Checking users table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\check_users.php"

echo Checking resources table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\check_resources.php"

echo Checking comments table...
cd "%PROJECT_PATH%"
"%PHP_PATH%" "database\check_comments_table.php"

echo.
echo ========================================
echo Creating required directories...
echo ========================================
if not exist "%PROJECT_PATH%\uploads" (
    mkdir "%PROJECT_PATH%\uploads"
    echo Created uploads directory.
)

if not exist "%PROJECT_PATH%\uploads\accommodations" (
    mkdir "%PROJECT_PATH%\uploads\accommodations"
    echo Created uploads/accommodations directory.
)

if not exist "%PROJECT_PATH%\public\images" (
    mkdir "%PROJECT_PATH%\public\images"
    echo Created public/images directory.
)

REM Set permissions for uploads directories
echo Setting permissions for uploads directories...
icacls "%PROJECT_PATH%\uploads" /grant Everyone:F
icacls "%PROJECT_PATH%\uploads\accommodations" /grant Everyone:F
icacls "%PROJECT_PATH%\public\images" /grant Everyone:F

echo.
echo ========================================
echo Database setup complete!
echo ========================================

echo Opening BRACULA in your default browser...
start http://localhost:%APACHE_PORT%/BRACULA/

echo.
echo BRACULA is now running!
echo You can access the site at: http://localhost:%APACHE_PORT%/BRACULA/
echo.
echo Press any key to close this window...
pause >nul
