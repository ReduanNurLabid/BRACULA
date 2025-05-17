<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->post_id) || !isset($data->user_id) || 
        (!isset($data->content) && !isset($data->caption))) {
        throw new Exception("Missing required fields");
    }
    
    // Include database
    require_once '../config/database.php';
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user is authorized to edit this post
    $checkQuery = "SELECT user_id FROM posts WHERE post_id = :post_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':post_id', $data->post_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception("Post not found");
    }
    
    $post = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post['user_id'] != $data->user_id) {
        throw new Exception("You are not authorized to edit this post");
    }
    
    // Update post
    $query = "UPDATE posts SET ";
    $params = [];
    
    if (isset($data->content)) {
        $query .= "content = :content, ";
        $params[':content'] = htmlspecialchars(strip_tags($data->content));
    }
    
    if (isset($data->caption)) {
        $query .= "caption = :caption, ";
        $params[':caption'] = htmlspecialchars(strip_tags($data->caption));
    }
    
    $query .= "updated_at = NOW() WHERE post_id = :post_id";
    $params[':post_id'] = $data->post_id;
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        // Get updated post
        $getPostQuery = "SELECT p.*, u.full_name as author, u.avatar_url 
                        FROM posts p 
                        JOIN users u ON p.user_id = u.user_id 
                        WHERE p.post_id = :post_id";
        $getPostStmt = $db->prepare($getPostQuery);
        $getPostStmt->bindParam(':post_id', $data->post_id);
        $getPostStmt->execute();
        
        $updatedPost = $getPostStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format post for response
        $formattedPost = [
            'id' => $updatedPost['post_id'],
            'user_id' => $updatedPost['user_id'],
            'author' => $updatedPost['author'],
            'caption' => $updatedPost['caption'],
            'content' => $updatedPost['content'],
            'community' => $updatedPost['community'],
            'votes' => $updatedPost['votes'],
            'timestamp' => $updatedPost['updated_at'],
            'avatar_url' => $updatedPost['avatar_url']
        ];
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Post updated successfully",
            "data" => $formattedPost
        ]);
    } else {
        throw new Exception("Failed to update post");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 