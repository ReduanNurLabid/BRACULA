<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database and user model
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/User.php';

    // Get user ID from query parameter
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$userId) {
        throw new Exception("User ID is required");
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Create user object
    $user = new User($db);
    
    // Get user data
    $userData = $user->getById($userId);
    
    if ($userData) {
        // Remove sensitive data
        unset($userData['password_hash']);
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "user" => $userData
        ]);
    } else {
        throw new Exception("User not found");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 