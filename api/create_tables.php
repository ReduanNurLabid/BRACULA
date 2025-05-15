<?php
// Set headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/../database/create_rideshare_tables.sql');
    
    // Execute SQL statements
    $db->exec($sql);
    
    // Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Rideshare tables created successfully"
    ]);
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?> 