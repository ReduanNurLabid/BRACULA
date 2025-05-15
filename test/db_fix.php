<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

echo "<h2>BRACULA Database Fix Tool</h2>";

try {
    // 1. Check if users table exists
    $tableExists = false;
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $tableExists = true;
        echo "<p>✅ Users table exists.</p>";
    } else {
        echo "<p>❌ Users table does not exist! Will attempt to create it.</p>";
    }

    // 2. If table doesn't exist, create it
    if (!$tableExists) {
        $sql = "CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            student_id VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            avatar_url VARCHAR(255),
            bio TEXT,
            department VARCHAR(100),
            interests TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        echo "<p>✅ Users table created successfully.</p>";
    }

    // 3. Check if department column exists
    $departmentExists = false;
    if ($tableExists) {
        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'department'");
        if ($stmt->rowCount() > 0) {
            $departmentExists = true;
            echo "<p>✅ Department column exists.</p>";
        } else {
            echo "<p>❌ Department column does not exist! Will attempt to add it.</p>";
        }
    }

    // 4. If department column doesn't exist, add it
    if ($tableExists && !$departmentExists) {
        $sql = "ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER bio";
        $conn->exec($sql);
        echo "<p>✅ Department column added successfully.</p>";
    }

    // 5. Show current table structure
    echo "<h3>Current 'users' table structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    echo "</pre>";

    // 6. Initialize the database with tables from bracula_db.sql if needed
    if (!$tableExists) {
        echo "<p>Attempting to initialize all database tables from bracula_db.sql...</p>";
        if (file_exists(__DIR__ . '/database/bracula_db.sql')) {
            $sql = file_get_contents(__DIR__ . '/database/bracula_db.sql');
            $conn->exec($sql);
            echo "<p>✅ Database initialized from bracula_db.sql</p>";
        } else {
            echo "<p>❌ File bracula_db.sql not found!</p>";
        }
    }

    echo "<p style='color: green; font-weight: bold;'>Database fix operations completed. You can now try to <a href='signup.html'>register</a> or <a href='login.html'>login</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";

    // Show PDO error details
    echo "<h4>PDO Error Details:</h4>";
    echo "<pre>";
    print_r($conn->errorInfo());
    echo "</pre>";
}
?> 