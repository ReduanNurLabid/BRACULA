<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

echo "<h1>BRACULA Database Diagnostic</h1>";

try {
    // Get database name
    $result = $conn->query("SELECT DATABASE()")->fetch(PDO::FETCH_ASSOC);
    $dbName = $result["DATABASE()"];
    
    echo "<h2>Connected to database: " . $dbName . "</h2>";
    
    // Check all tables
    echo "<h3>Tables in database:</h3>";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: red;'>No tables found in the database!</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . $table . "</li>";
        }
        echo "</ul>";
    }
    
    // Check users table in detail
    if (in_array('users', $tables)) {
        echo "<h3>Users table structure:</h3>";
        $stmt = $conn->query("DESCRIBE users");
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Users table doesn't exist!</p>";
    }
    
    // Attempt to add department column if it doesn't exist
    if (in_array('users', $tables)) {
        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'department'");
        if ($stmt->rowCount() == 0) {
            echo "<h3>Adding department column...</h3>";
            $conn->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER bio");
            echo "<p style='color: green;'>Department column added.</p>";
        } else {
            echo "<p>Department column already exists.</p>";
        }
    }
    
    // Verify user creation query
    echo "<h3>Testing user creation query:</h3>";
    $testQuery = "INSERT INTO users 
            (full_name, student_id, email, password_hash, avatar_url, bio, department, interests)
            VALUES
            ('Test User', 'TEST123', 'test@example.com', 'password_hash', NULL, NULL, 'TEST', NULL)";
    echo "<pre>" . $testQuery . "</pre>";
    
    try {
        // Check if test user exists first
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_id = 'TEST123'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "<p>Test user already exists. Skipping test insertion.</p>";
        } else {
            // First, disable foreign key checks to avoid any constraint issues
            $conn->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Begin transaction for testing
            $conn->beginTransaction();
            
            // Try executing the test query
            $stmt = $conn->prepare($testQuery);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Test query executed successfully!</p>";
            } else {
                echo "<p style='color: red;'>❌ Test query failed!</p>";
                echo "<pre>Error: " . print_r($stmt->errorInfo(), true) . "</pre>";
            }
            
            // Rollback the transaction (we don't actually want to insert the test user)
            $conn->rollBack();
            
            // Re-enable foreign key checks
            $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error testing user creation query: " . $e->getMessage() . "</p>";
    }
    
    // Show available permissions
    echo "<h3>Database User Permissions:</h3>";
    try {
        $stmt = $conn->query("SHOW GRANTS FOR CURRENT_USER()");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach ($row as $grant) {
                echo htmlspecialchars($grant) . "\n";
            }
        }
        echo "</pre>";
    } catch (PDOException $e) {
        echo "<p>Could not get permissions: " . $e->getMessage() . "</p>";
    }
    
    // Database repair options
    echo "<h3>Database Repair Options:</h3>";
    echo "<p><a href='db_fix.php' style='padding: 10px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Run Database Fix Tool</a></p>";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
}
?> 