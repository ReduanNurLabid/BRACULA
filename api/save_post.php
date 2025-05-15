<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit();
}

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

// Check if required fields are present
if (!isset($data['post_id']) || !isset($data['user_id']) || !isset($data['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

// Validate action
if ($data['action'] !== 'save' && $data['action'] !== 'unsave') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action. Must be "save" or "unsave"']);
    exit();
}

// Include database connection
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($data['action'] === 'save') {
        // Check if the post exists
        $stmt = $conn->prepare("SELECT post_id FROM posts WHERE post_id = ?");
        $stmt->bindParam(1, $data['post_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Post not found']);
            exit();
        }
        
        // Check if the post is already saved by the user
        $stmt = $conn->prepare("SELECT * FROM saved_posts WHERE post_id = ? AND user_id = ?");
        $stmt->bindParam(1, $data['post_id'], PDO::PARAM_INT);
        $stmt->bindParam(2, $data['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Post already saved']);
            exit();
        }
        
        // Save the post
        $stmt = $conn->prepare("INSERT INTO saved_posts (post_id, user_id, saved_at) VALUES (?, ?, NOW())");
        $stmt->bindParam(1, $data['post_id'], PDO::PARAM_INT);
        $stmt->bindParam(2, $data['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Post saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save post']);
        }
    } else { // unsave
        // Delete the saved post record
        $stmt = $conn->prepare("DELETE FROM saved_posts WHERE post_id = ? AND user_id = ?");
        $stmt->bindParam(1, $data['post_id'], PDO::PARAM_INT);
        $stmt->bindParam(2, $data['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Post unsaved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Post was not saved or already unsaved']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 