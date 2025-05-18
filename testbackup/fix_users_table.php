<?php
// Include database configuration
require_once __DIR__ . '/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Show current table structure
    echo "<h3>Current 'users' table structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";

    // Add department column if it doesn't exist
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) AFTER bio");
    
    echo "<h3>Added 'department' column if it didn't exist.</h3>";
    
    // Show updated table structure
    echo "<h3>Updated 'users' table structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
    
    echo "<p>The issue should be fixed. You can now try to <a href='signup.html'>register</a> or <a href='login.html'>login</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?> 