<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed. Only POST requests are accepted.");
    }

    // Get raw POST data
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data provided.");
    }

    // Validate required fields
    if (!isset($data->user_id) || !isset($data->activity_type) || !isset($data->content_id)) {
        throw new Exception("Missing required fields: user_id, activity_type, and content_id are required.");
    }

    // Include database configuration
    require_once '../config/database.php';
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Prepare the SQL query
    $query = "INSERT INTO user_activities (user_id, activity_type, content_id, created_at) 
              VALUES (:user_id, :activity_type, :content_id, NOW())";
    
    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':activity_type', $data->activity_type);
    $stmt->bindParam(':content_id', $data->content_id);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Activity recorded successfully"
        ]);
    } else {
        throw new Exception("Failed to record activity");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 