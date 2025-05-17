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
    if (!isset($data->user_id) || !isset($data->content)) {
        throw new Exception("Missing required fields: user_id and content are required.");
    }

    // Include database configuration
    require_once '../config/database.php';
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Prepare the SQL query
    $query = "INSERT INTO posts (user_id, caption, content, community, created_at) 
              VALUES (:user_id, :caption, :content, :community, NOW())";
    
    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':caption', $data->caption);
    $stmt->bindParam(':content', $data->content);
    $stmt->bindParam(':community', $data->community);

    // Execute the query
    if ($stmt->execute()) {
        $post_id = $db->lastInsertId();
        
        // Fetch the created post with user information
        $select_query = "SELECT p.*, u.full_name as author, u.avatar_url 
                        FROM posts p 
                        JOIN users u ON p.user_id = u.user_id 
                        WHERE p.post_id = :post_id";
        
        $select_stmt = $db->prepare($select_query);
        $select_stmt->bindParam(':post_id', $post_id);
        $select_stmt->execute();
        
        $post = $select_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "message" => "Post created successfully",
            "data" => [
                "id" => $post['post_id'],
                "author" => $post['author'],
                "content" => $post['content'],
                "caption" => $post['caption'],
                "community" => $post['community'],
                "timestamp" => $post['created_at'],
                "avatar" => $post['avatar_url'],
                "votes" => 0,
                "commentCount" => 0
            ]
        ]);
    } else {
        throw new Exception("Failed to create post");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 