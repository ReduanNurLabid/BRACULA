<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();

    $postId = $_GET['postId'] ?? null;
    if (!$postId) {
        throw new Exception("Post ID is required");
    }

    $query = "SELECT c.*, u.full_name, u.avatar_url 
              FROM comments c 
              LEFT JOIN users u ON c.user_id = u.user_id 
              WHERE c.post_id = :post_id 
              ORDER BY c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':post_id', $postId);
    $stmt->execute();

    $comments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $comments[] = [
            'id' => $row['comment_id'],
            'author' => $row['full_name'],
            'authorId' => $row['user_id'],
            'avatar' => $row['avatar_url'] ?? 'https://avatar.iran.liara.run/public',
            'content' => $row['content'],
            'timestamp' => $row['created_at']
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $comments
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 