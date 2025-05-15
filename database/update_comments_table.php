<?php
// Enable output buffering
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Updating Comments Table</h1>\n";

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<p>Connected to database: " . $database->db_name . "</p>\n";
    
    // Check if parent_id column exists in comments table
    $stmt = $conn->query("SHOW COLUMNS FROM comments LIKE 'parent_id'");
    $parentIdExists = $stmt->rowCount() > 0;
    
    if ($parentIdExists) {
        echo "<p style='color:green'>✅ The 'parent_id' column already exists in the comments table.</p>\n";
    } else {
        echo "<p style='color:orange'>⚠️ The 'parent_id' column is missing from the comments table. Adding it now...</p>\n";
        
        // Add the parent_id column
        $sql = "ALTER TABLE comments ADD COLUMN parent_id INT NULL AFTER content, 
                ADD CONSTRAINT fk_parent_comment FOREIGN KEY (parent_id) REFERENCES comments(comment_id) ON DELETE CASCADE";
        $conn->exec($sql);
        
        echo "<p style='color:green'>✅ Successfully added 'parent_id' column to comments table.</p>\n";
    }
    
    // Check if updated_at column exists in comments table
    $stmt = $conn->query("SHOW COLUMNS FROM comments LIKE 'updated_at'");
    $updatedAtExists = $stmt->rowCount() > 0;
    
    if ($updatedAtExists) {
        echo "<p style='color:green'>✅ The 'updated_at' column already exists in the comments table.</p>\n";
    } else {
        echo "<p style='color:orange'>⚠️ The 'updated_at' column is missing from the comments table. Adding it now...</p>\n";
        
        // Add the updated_at column
        $sql = "ALTER TABLE comments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
        $conn->exec($sql);
        
        echo "<p style='color:green'>✅ Successfully added 'updated_at' column to comments table.</p>\n";
    }
    
    // Show current table structure
    echo "<h3>Current comments table structure:</h3>\n";
    $stmt = $conn->query("DESCRIBE comments");
    echo "<pre>\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    echo "</pre>\n";
    
    echo "<h2>Next Steps</h2>\n";
    echo "<p>The comments table has been updated to support nested comments (replies) and comment editing.</p>\n";
    echo "<p><a href='../index.php'>Go to Homepage</a></p>\n";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>\n";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>\n";
}

// Flush output buffer
ob_end_flush();
?> 