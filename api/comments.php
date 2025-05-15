<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display to avoid breaking JSON

require_once '../config/database.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'OPTIONS':
        // Handle preflight request
        http_response_code(200);
        echo json_encode(["status" => "success"]);
        exit();
        break;
        
    case 'GET':
        try {
            if (!isset($_GET['post_id'])) {
                throw new Exception('Post ID is required');
            }
            
            $post_id = $_GET['post_id'];
            
            // Get comments with user information, including parent_id
            $query = "SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at, c.parent_id, 
                     u.full_name as author, u.avatar_url 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.user_id 
                     WHERE c.post_id = :post_id 
                     ORDER BY c.parent_id IS NULL DESC, c.created_at ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([':post_id' => $post_id]);
            $rawComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format comments for response
            $comments = [];
            foreach ($rawComments as $comment) {
                $comments[] = [
                    'id' => $comment['comment_id'],
                    'post_id' => $comment['post_id'],
                    'user_id' => $comment['user_id'],
                    'author' => $comment['author'],
                    'content' => $comment['content'],
                    'timestamp' => $comment['created_at'],
                    'avatar_url' => $comment['avatar_url'],
                    'parent_id' => $comment['parent_id']
                ];
            }
            
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
            
            // Check if this is a reply (has parent_id)
            $hasParent = isset($data['parent_id']) && !empty($data['parent_id']);
            
            // Insert comment
            if ($hasParent) {
                $query = "INSERT INTO comments (post_id, user_id, content, parent_id) 
                         VALUES (:post_id, :user_id, :content, :parent_id)";
                
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    ':post_id' => $data['post_id'],
                    ':user_id' => $data['user_id'],
                    ':content' => htmlspecialchars(strip_tags($data['content'])),
                    ':parent_id' => $data['parent_id']
                ]);
            } else {
                $query = "INSERT INTO comments (post_id, user_id, content) 
                         VALUES (:post_id, :user_id, :content)";
                
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    ':post_id' => $data['post_id'],
                    ':user_id' => $data['user_id'],
                    ':content' => htmlspecialchars(strip_tags($data['content']))
                ]);
            }
            
            if (!$result) {
                throw new Exception('Failed to create comment');
            }
            
            $comment_id = $conn->lastInsertId();
            
            // Get the newly created comment with user details
            $query = "SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at, c.parent_id, 
                     u.full_name as author, u.avatar_url 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.user_id 
                     WHERE c.comment_id = :comment_id";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([':comment_id' => $comment_id]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            $activityStmt = $conn->prepare($activityQuery);
            $activityStmt->bindParam(':user_id', $data['user_id']);
            $activityStmt->bindParam(':content_id', $comment_id);
            $activityStmt->execute();
            
            // Update comment count on post
            $updatePostQuery = "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = :post_id) WHERE post_id = :post_id";
            $updatePostStmt = $conn->prepare($updatePostQuery);
            $updatePostStmt->bindParam(':post_id', $data['post_id']);
            $updatePostStmt->execute();
            
            // Get updated comment count
            $countQuery = "SELECT COUNT(*) as comment_count FROM comments WHERE post_id = :post_id";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bindParam(':post_id', $data['post_id']);
            $countStmt->execute();
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Comment added successfully',
                'data' => $formattedComment,
                'comment_count' => (int)$countResult['comment_count']
            ]);
            
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
        break;
}
?> 