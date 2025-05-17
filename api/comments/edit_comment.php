<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display to avoid breaking JSON

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["status" => "success"]);
    exit;
}

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->comment_id) || !isset($data->user_id) || !isset($data->content)) {
        throw new Exception("Missing required fields");
    }
    
    // Include database
    require_once '../config/database.php';
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user is authorized to edit this comment
    $checkQuery = "SELECT user_id FROM comments WHERE comment_id = :comment_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':comment_id', $data->comment_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception("Comment not found");
    }
    
    $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comment['user_id'] != $data->user_id) {
        throw new Exception("You are not authorized to edit this comment");
    }
    
    // Update comment - let MySQL handle the updated_at timestamp automatically
    $query = "UPDATE comments SET content = :content WHERE comment_id = :comment_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':content', htmlspecialchars(strip_tags($data->content)));
    $stmt->bindParam(':comment_id', $data->comment_id);
    
    if ($stmt->execute()) {
        // Get updated comment
        $getCommentQuery = "SELECT c.*, u.full_name as author, u.avatar_url 
                          FROM comments c 
                          JOIN users u ON c.user_id = u.user_id 
                          WHERE c.comment_id = :comment_id";
        $getCommentStmt = $db->prepare($getCommentQuery);
        $getCommentStmt->bindParam(':comment_id', $data->comment_id);
        $getCommentStmt->execute();
        
        $updatedComment = $getCommentStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format comment for response
        $formattedComment = [
            'id' => $updatedComment['comment_id'],
            'post_id' => $updatedComment['post_id'],
            'user_id' => $updatedComment['user_id'],
            'author' => $updatedComment['author'],
            'content' => $updatedComment['content'],
            'timestamp' => $updatedComment['updated_at'],
            'avatar_url' => $updatedComment['avatar_url'],
            'parent_id' => $updatedComment['parent_id'] ?? null
        ];
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Comment updated successfully",
            "data" => $formattedComment
        ]);
    } else {
        throw new Exception("Failed to update comment");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 