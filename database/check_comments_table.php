<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Connected to database: " . $database->db_name . "\n";
    
    // Show current table structure
    echo "Current comments table structure:\n";
    $stmt = $conn->query("DESCRIBE comments");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 