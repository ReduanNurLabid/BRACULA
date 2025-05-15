<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->post_id) || !isset($data->user_id)) {
        throw new Exception("Missing required fields");
    }
    
    // Include database
    require_once '../config/database.php';
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user is authorized to delete this post
    $checkQuery = "SELECT user_id FROM posts WHERE post_id = :post_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':post_id', $data->post_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception("Post not found");
    }
    
    $post = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post['user_id'] != $data->user_id) {
        throw new Exception("You are not authorized to delete this post");
    }
    
    // Begin transaction to ensure all related data is deleted
    $db->beginTransaction();
    
    try {
        // Delete related comments
        $deleteCommentsQuery = "DELETE FROM comments WHERE post_id = :post_id";
        $deleteCommentsStmt = $db->prepare($deleteCommentsQuery);
        $deleteCommentsStmt->bindParam(':post_id', $data->post_id);
        $deleteCommentsStmt->execute();
        
        // Delete related votes
        $deleteVotesQuery = "DELETE FROM votes WHERE post_id = :post_id";
        $deleteVotesStmt = $db->prepare($deleteVotesQuery);
        $deleteVotesStmt->bindParam(':post_id', $data->post_id);
        $deleteVotesStmt->execute();
        
        // Delete related activities
        $deleteActivitiesQuery = "DELETE FROM user_activities WHERE activity_type IN ('post', 'comment', 'like') AND content_id = :post_id";
        $deleteActivitiesStmt = $db->prepare($deleteActivitiesQuery);
        $deleteActivitiesStmt->bindParam(':post_id', $data->post_id);
        $deleteActivitiesStmt->execute();
        
        // Delete the post
        $deletePostQuery = "DELETE FROM posts WHERE post_id = :post_id";
        $deletePostStmt = $db->prepare($deletePostQuery);
        $deletePostStmt->bindParam(':post_id', $data->post_id);
        
        if ($deletePostStmt->execute()) {
            // Commit the transaction
            $db->commit();
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Post deleted successfully"
            ]);
        } else {
            throw new Exception("Failed to delete post");
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