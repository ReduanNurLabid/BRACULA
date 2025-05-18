<?php
require_once '../config/database.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get POST data
    $raw_data = file_get_contents('php://input');
    error_log("Received vote data: " . $raw_data);
    
    $data = json_decode($raw_data, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }
    
    // Validate required fields
    if (!isset($data['post_id']) || !isset($data['user_id']) || !isset($data['vote_type'])) {
        throw new Exception('Missing required fields: post_id, user_id, or vote_type');
    }
    
    // Validate vote type
    if (!in_array($data['vote_type'], ['up', 'down'])) {
        throw new Exception('Invalid vote type. Must be "up" or "down"');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verify post exists
    $query = "SELECT post_id FROM posts WHERE post_id = :post_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':post_id' => $data['post_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Post not found');
    }
    
    // Verify user exists
    $query = "SELECT user_id FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $data['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }
    
    // Check if user has already voted
    $query = "SELECT vote_type FROM votes WHERE user_id = :user_id AND post_id = :post_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $data['user_id'],
        ':post_id' => $data['post_id']
    ]);
    $existing_vote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Begin transaction
    $conn->beginTransaction();
    
    if ($existing_vote) {
        // Update existing vote
        if ($existing_vote['vote_type'] === $data['vote_type']) {
            // Remove vote if clicking same button
            $query = "DELETE FROM votes WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':post_id' => $data['post_id']
            ]);
            $message = 'Vote removed';
            $user_vote = null;
        } else {
            // Change vote
            $query = "UPDATE votes SET vote_type = :vote_type WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':vote_type' => $data['vote_type'],
                ':user_id' => $data['user_id'],
                ':post_id' => $data['post_id']
            ]);
            $message = 'Vote updated';
            $user_vote = $data['vote_type'];
        }
    } else {
        // Insert new vote
        $query = "INSERT INTO votes (user_id, post_id, vote_type) VALUES (:user_id, :post_id, :vote_type)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':post_id' => $data['post_id'],
            ':vote_type' => $data['vote_type']
        ]);
        $message = 'Vote recorded';
        $user_vote = $data['vote_type'];
    }
    
    // Get updated vote count
    $query = "SELECT 
                (SELECT COUNT(*) FROM votes WHERE post_id = :post_id AND vote_type = 'up') -
                (SELECT COUNT(*) FROM votes WHERE post_id = :post_id AND vote_type = 'down') as vote_count";
    $stmt = $conn->prepare($query);
    $stmt->execute([':post_id' => $data['post_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $conn->commit();
    
    error_log("Vote operation successful: " . json_encode([
        'message' => $message,
        'vote_count' => $result['vote_count'],
        'user_vote' => $user_vote
    ]));
    
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'new_vote_count' => $result['vote_count'],
        'user_vote' => $user_vote
    ]);
    
} catch(Exception $e) {
    error_log("Vote error: " . $e->getMessage());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 