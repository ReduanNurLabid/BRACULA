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
    
    if (!isset($data->post_id) || !isset($data->user_id) || !isset($data->content) || !isset($data->parent_id)) {
        throw new Exception("Missing required fields");
    }
    
    // Include database
    require_once '../config/database.php';
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if parent comment exists
    $checkQuery = "SELECT post_id FROM comments WHERE comment_id = :parent_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':parent_id', $data->parent_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception("Parent comment not found");
    }
    
    $parentComment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify that parent comment belongs to the same post
    if ($parentComment['post_id'] != $data->post_id) {
        throw new Exception("Parent comment does not belong to the specified post");
    }
    
    // Insert reply comment
    $query = "INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (:post_id, :user_id, :content, :parent_id)";
    $stmt = $db->prepare($query);
    
    $content = htmlspecialchars(strip_tags($data->content));
    
    $stmt->bindParam(':post_id', $data->post_id);
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':parent_id', $data->parent_id);
    
    if ($stmt->execute()) {
        $commentId = $db->lastInsertId();
        
        // Get comment with user details
        $getCommentQuery = "SELECT c.*, u.full_name as author, u.avatar_url 
                          FROM comments c 
                          JOIN users u ON c.user_id = u.user_id 
                          WHERE c.comment_id = :comment_id";
        $getCommentStmt = $db->prepare($getCommentQuery);
        $getCommentStmt->bindParam(':comment_id', $commentId);
        $getCommentStmt->execute();
        
        $comment = $getCommentStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format comment for response
        $formattedComment = [
            'id' => $comment['comment_id'],
            'post_id' => $comment['post_id'],
            'user_id' => $comment['user_id'],
            'author' => $comment['author'],
            'content' => $comment['content'],
            'timestamp' => $comment['created_at'],
            'avatar_url' => $comment['avatar_url'],
            'parent_id' => $comment['parent_id']
        ];
        
        // Track user activity
        $activityQuery = "INSERT INTO user_activities (user_id, activity_type, content_id) VALUES (:user_id, 'comment', :content_id)";
        $activityStmt = $db->prepare($activityQuery);
        $activityStmt->bindParam(':user_id', $data->user_id);
        $activityStmt->bindParam(':content_id', $commentId);
        $activityStmt->execute();
        
        // Update comment count on post
        $updatePostQuery = "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = :post_id) WHERE post_id = :post_id";
        $updatePostStmt = $db->prepare($updatePostQuery);
        $updatePostStmt->bindParam(':post_id', $data->post_id);
        $updatePostStmt->execute();
        
        // Get updated comment count
        $countQuery = "SELECT COUNT(*) as comment_count FROM comments WHERE post_id = :post_id";
        $countStmt = $db->prepare($countQuery);
        $countStmt->bindParam(':post_id', $data->post_id);
        $countStmt->execute();
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Reply added successfully",
            "data" => $formattedComment,
            "comment_count" => (int)$countResult['comment_count']
        ]);
    } else {
        throw new Exception("Failed to add reply");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 