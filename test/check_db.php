<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Check Utility</h1>";

// Try direct connection first
try {
    echo "<h2>Step 1: Direct Database Connection</h2>";
    
    $host = "localhost";
    $username = "root";
    $password = "";
    $db_name = "bracula_test_db";
    
    echo "<p>Connecting to MySQL server...</p>";
    
    // First connect to MySQL server (without database name)
    $conn = new PDO(
        "mysql:host=$host",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connected to MySQL server successfully!</p>";
    
    // Check if database exists
    echo "<p>Checking if database '$db_name' exists...</p>";
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
    $dbExists = $stmt->rowCount() > 0;
    
    if ($dbExists) {
        echo "<p style='color:green'>✅ Database '$db_name' exists!</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Database '$db_name' does not exist. Creating it now...</p>";
        $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name");
        echo "<p style='color:green'>✅ Database '$db_name' created successfully!</p>";
    }
    
    // Now connect to the specific database
    echo "<p>Connecting to database '$db_name'...</p>";
    $conn->exec("USE $db_name");
    echo "<p style='color:green'>✅ Connected to database '$db_name' successfully!</p>";
    
    // Check if users table exists
    echo "<h2>Step 2: Check Tables</h2>";
    echo "<p>Checking if 'users' table exists...</p>";
    
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:green'>✅ Table 'users' exists!</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $conn->query("DESCRIBE users");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p style='color:red'>❌ Table 'users' does not exist!</p>";
        echo "<p>Creating 'users' table now...</p>";
        
        // Create the users table
        $usersTable = "CREATE TABLE IF NOT EXISTS users (
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
        
        $conn->exec($usersTable);
        echo "<p style='color:green'>✅ Table 'users' created successfully!</p>";
        
        // Verify table was created
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✅ Verified table 'users' now exists!</p>";
            
            // Show table structure
            echo "<h3>Table Structure:</h3>";
            $stmt = $conn->query("DESCRIBE users");
            echo "<pre>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p style='color:red'>❌ Failed to create table 'users'!</p>";
        }
    }
    
    echo "<h2>Step 3: Test API Paths</h2>";
    
    // Check if necessary files exist
    echo "<p>Checking API files...</p>";
    
    $registerApiPath = __DIR__ . '/api/register.php';
    $loginApiPath = __DIR__ . '/api/login.php';
    
    if (file_exists($registerApiPath)) {
        echo "<p style='color:green'>✅ Register API file exists at: " . realpath($registerApiPath) . "</p>";
    } else {
        echo "<p style='color:red'>❌ Register API file does not exist at expected path: $registerApiPath</p>";
    }
    
    if (file_exists($loginApiPath)) {
        echo "<p style='color:green'>✅ Login API file exists at: " . realpath($loginApiPath) . "</p>";
    } else {
        echo "<p style='color:red'>❌ Login API file does not exist at expected path: $loginApiPath</p>";
    }
    
    // Show test page links
    echo "<h2>Next Steps</h2>";
    echo "<p style='color:green'>Database check completed! Try these pages:</p>";
    echo "<ul>";
    echo "<li><a href='test_signup.php'>Test User Registration</a></li>";
    echo "<li><a href='test_login.php'>Test User Login</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 