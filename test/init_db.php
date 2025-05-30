<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Test Database Initialization</h1>";

// Print config path for debugging
echo "<p>Database config path: " . realpath(__DIR__ . '/config/database.php') . "</p>";

try {
    // Create database connection
    $database = new Database();
    echo "<p>Database instance created</p>";
    
    $conn = $database->getConnection();
    echo "<p>Database connection established</p>";

    if (!$conn) {
        throw new Exception("Failed to get database connection");
    }
    
    // Print database name for verification
    echo "<p>Using database: " . $database->db_name . "</p>";
    
    // Create users table with all required columns
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
    echo "<p>✅ Users table created successfully</p>";
    
    // Verify table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p>✅ Confirmed 'users' table exists in the database</p>";
    } else {
        echo "<p style='color: red;'>❌ Table 'users' was not found in the database after creation attempt</p>";
    }
    
    // Show current table structure
    echo "<h3>Current 'users' table structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    echo "</pre>";
    
    echo "<p style='color: green;'>Database initialized successfully!</p>";
    echo "<p>You can now test user registration and login using the test APIs.</p>";
    
    // Generate test links
    echo "<h3>Test Pages:</h3>";
    echo "<ul>";
    echo "<li><a href='test_signup.php'>Test User Registration</a></li>";
    echo "<li><a href='test_login.php'>Test User Login</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
    
    // Print stack trace for debugging
    echo "<h4>Stack Trace:</h4>";
    echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
}
?>