<?php
require_once __DIR__ . '/../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Add interests column to users table if it doesn't exist
    $query = "ALTER TABLE users ADD COLUMN IF NOT EXISTS interests TEXT AFTER department";
    
    if ($conn->exec($query)) {
        echo "Successfully added interests column to users table.\n";
    } else {
        echo "Interests column already exists or couldn't be added.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 