<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

try {
    // Get raw POST data
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data);

    if (!isset($data->user_id) || !isset($data->current_password) || !isset($data->email)) {
        throw new Exception("Missing required fields");
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // First, verify the current password
    $query = "SELECT password_hash FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("User not found");
    }

    // Verify the current password
    if (!password_verify($data->current_password, $user['password_hash'])) {
        throw new Exception("Current password is incorrect");
    }

    // Start transaction
    $db->beginTransaction();

    // Update email
    $query = "UPDATE users SET email = :email";
    $params = [":email" => $data->email];

    // If new password is provided, update it
    if (isset($data->new_password) && !empty($data->new_password)) {
        $new_password_hash = password_hash($data->new_password, PASSWORD_DEFAULT);
        $query .= ", password_hash = :password_hash";
        $params[":password_hash"] = $new_password_hash;
    }

    $query .= " WHERE user_id = :user_id";
    $params[":user_id"] = $data->user_id;

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    // Commit transaction
    $db->commit();

    // Get updated user data
    $query = "SELECT user_id, full_name, email, bio, avatar_url FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->execute();
    
    $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Account updated successfully",
        "data" => $updated_user
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
} 