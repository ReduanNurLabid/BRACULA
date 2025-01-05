<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        try {
            if (!isset($_GET['post_id'])) {
                throw new Exception('Post ID is required');
            }
            
            $post_id = $_GET['post_id'];
            
            // Get comments with user information
            $query = "SELECT c.*, u.full_name, u.avatar_url 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.user_id 
                     WHERE c.post_id = :post_id 
                     ORDER BY c.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([':post_id' => $post_id]);
            $comments = $stmt->fetchAll();
            
            echo json_encode([
                'status' => 'success',
                'data' => $comments
            ]);
            
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('No data received');
            }
            
            // Validate required fields
            if (!isset($data['post_id']) || !isset($data['user_id']) || !isset($data['content'])) {
                throw new Exception('Missing required fields');
            }
            
            // Insert comment
            $query = "INSERT INTO comments (post_id, user_id, content) 
                     VALUES (:post_id, :user_id, :content)";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':post_id' => $data['post_id'],
                ':user_id' => $data['user_id'],
                ':content' => $data['content']
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create comment');
            }
            
            $comment_id = $conn->lastInsertId();
            
            // Get the newly created comment with user details
            $query = "SELECT c.*, u.full_name, u.avatar_url 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.user_id 
                     WHERE c.comment_id = :comment_id";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([':comment_id' => $comment_id]);
            $comment = $stmt->fetch();
            
            echo json_encode([
                'status' => 'success',
                'data' => $comment
            ]);
            
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        break;
}
?> 