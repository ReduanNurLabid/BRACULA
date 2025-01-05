<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database and user model
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/User.php';

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