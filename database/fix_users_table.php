<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Users Table Structure</h1>";

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<p>Connected to database: " . $database->db_name . "</p>";
    
    // Check if interests column exists in users table
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'interests'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "<p style='color:green'>✅ The 'interests' column already exists in the users table.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ The 'interests' column is missing from the users table. Adding it now...</p>";
        
        // Add the interests column
        $sql = "ALTER TABLE users ADD COLUMN interests TEXT AFTER department";
        $conn->exec($sql);
        
        echo "<p style='color:green'>✅ Successfully added 'interests' column to users table.</p>";
    }
    
    // Show current table structure
    echo "<h3>Current users table structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    echo "</pre>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>You can now try to register a user again.</p>";
    echo "<p><a href='../signup.php'>Try signup again</a></p>";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
}
?> 