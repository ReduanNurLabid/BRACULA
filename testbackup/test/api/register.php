<?php
// Enable error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Prevent any output before headers
ob_start();

try {
    // Headers
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // Log request details
    $debug_info = [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'raw_input' => file_get_contents("php://input"),
        'request_time' => date('Y-m-d H:i:s')
    ];
    error_log("Register API Debug Info: " . print_r($debug_info, true));

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        echo json_encode(["status" => "success"]);
        exit();
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed. Only POST requests are accepted.");
    }

    // Get raw POST data
    $raw_data = file_get_contents("php://input");
    error_log("Raw POST data: " . $raw_data);

    // Check if data is valid JSON
    $data = json_decode($raw_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data provided: " . json_last_error_msg());
    }

    // Include database and user model
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/User.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Create user object
    $user = new User($db);

    // Check required fields
    $required_fields = ['full_name', 'student_id', 'email', 'password', 'department'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($data->$field)) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Set user property values
    $user->full_name = $data->full_name;
    $user->student_id = $data->student_id;
    $user->email = $data->email;
    $user->password = $data->password;
    $user->department = $data->department;
    $user->avatar_url = !empty($data->avatar_url) ? $data->avatar_url : null;
    $user->bio = !empty($data->bio) ? $data->bio : null;
    $user->interests = !empty($data->interests) ? $data->interests : null;

    // Check if email already exists
    if ($user->emailExists()) {
        throw new Exception("Email already exists");
    }

    // Check if student ID already exists
    if ($user->studentIdExists()) {
        throw new Exception("Student ID already exists");
    }

    // Create the user
    if ($user->create()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "User was created successfully"
        ]);
    } else {
        throw new Exception("Failed to create user");
    }

} catch (Exception $e) {
    $status_code = 400;
    
    // Set appropriate status code based on error type
    if ($e instanceof PDOException) {
        $status_code = 500;
        error_log("Database Error: " . $e->getMessage());
    } else if (strpos($e->getMessage(), "Method not allowed") !== false) {
        $status_code = 405;
    }
    
    http_response_code($status_code);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "debug_info" => $debug_info ?? null
    ]);
} finally {
    // Ensure all output buffers are flushed
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("Buffered output: " . $output);
    }
    echo $output;
}
?>