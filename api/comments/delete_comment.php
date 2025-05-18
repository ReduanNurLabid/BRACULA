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
    
    if (!isset($data->comment_id) || !isset($data->user_id)) {
        throw new Exception("Missing required fields");
    }
    
    // Include database
    require_once '../config/database.php';
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user is authorized to delete this comment
    $checkQuery = "SELECT user_id, post_id FROM comments WHERE comment_id = :comment_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':comment_id', $data->comment_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception("Comment not found");
    }
    
    $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comment['user_id'] != $data->user_id) {
        throw new Exception("You are not authorized to delete this comment");
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete any child comments (replies)
        $deleteRepliesQuery = "DELETE FROM comments WHERE parent_id = :comment_id";
        $deleteRepliesStmt = $db->prepare($deleteRepliesQuery);
        $deleteRepliesStmt->bindParam(':comment_id', $data->comment_id);
        $deleteRepliesStmt->execute();
        
        // Delete related activities
        $deleteActivitiesQuery = "DELETE FROM user_activities WHERE activity_type = 'comment' AND content_id = :comment_id";
        $deleteActivitiesStmt = $db->prepare($deleteActivitiesQuery);
        $deleteActivitiesStmt->bindParam(':comment_id', $data->comment_id);
        $deleteActivitiesStmt->execute();
        
        // Delete the comment
        $deleteCommentQuery = "DELETE FROM comments WHERE comment_id = :comment_id";
        $deleteCommentStmt = $db->prepare($deleteCommentQuery);
        $deleteCommentStmt->bindParam(':comment_id', $data->comment_id);
        
        if ($deleteCommentStmt->execute()) {
            // Update comment count on post
            $updatePostQuery = "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = :post_id) WHERE post_id = :post_id";
            $updatePostStmt = $db->prepare($updatePostQuery);
            $updatePostStmt->bindParam(':post_id', $comment['post_id']);
            $updatePostStmt->execute();
            
            // Get updated comment count
            $countQuery = "SELECT COUNT(*) as comment_count FROM comments WHERE post_id = :post_id";
            $countStmt = $db->prepare($countQuery);
            $countStmt->bindParam(':post_id', $comment['post_id']);
            $countStmt->execute();
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            // Commit the transaction
            $db->commit();
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Comment deleted successfully",
                "post_id" => $comment['post_id'],
                "comment_count" => (int)$countResult['comment_count']
            ]);
        } else {
            throw new Exception("Failed to delete comment");
        }
    } catch (Exception $e) {
        // Rollback the transaction if any query fails
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 