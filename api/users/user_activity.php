<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database configuration
    require_once __DIR__ . '/../config/database.php';

    // Get user ID from query parameters
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$user_id) {
        throw new Exception("User ID is required");
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // For now, return sample activity data
    // In a real application, you would fetch this from various tables (posts, comments, etc.)
    $activities = [
        [
            'type' => 'post',
            'description' => 'Created a new post in CSE Community',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'type' => 'comment',
            'description' => 'Commented on "Study Group for Finals"',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'type' => 'upload',
            'description' => 'Uploaded study materials for CSE220',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];

    // Send response
    echo json_encode([
        'status' => 'success',
        'activities' => $activities
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 