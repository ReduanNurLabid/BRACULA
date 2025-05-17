<?php
// Include session configuration
require_once __DIR__ . '/../../config/session_config.php';
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database and user model
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/User.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Create user object
    $user = new User($db);

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Check for required fields
    if (empty($data->email) || empty($data->password)) {
        throw new Exception("Email and password are required");
    }

    // Attempt to log in
    if ($user->login($data->email, $data->password)) {
        // Get full user data
        $userData = $user->getById($user->user_id);
        
        if ($userData) {
            // Remove sensitive data
            unset($userData['password_hash']);
            
            // Store user data in session
            $_SESSION['user_id'] = $user->user_id;
            $_SESSION['email'] = $user->email;
            $_SESSION['full_name'] = $user->full_name;
            
            // Debug session data
            error_log("User logged in - ID: {$user->user_id}, Email: {$user->email}");
            error_log("Session data: " . print_r($_SESSION, true));
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "user" => $userData
            ]);
        } else {
            throw new Exception("Error retrieving user data");
        }
    } else {
        throw new Exception("Invalid email or password");
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 