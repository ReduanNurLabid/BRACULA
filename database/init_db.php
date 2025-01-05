<?php
// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/bracula_db.sql');
    
    // Execute multiple SQL statements
    $conn->exec($sql);
    
    echo "Database initialized successfully!\n";
} catch(PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?> 