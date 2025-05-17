<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Only GET requests are allowed']);
    exit();
}

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit();
}

$user_id = intval($_GET['user_id']);

// Include database connection
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Query to get saved posts with post details
    $query = "SELECT p.*, u.full_name as author, u.avatar_url, 
              CASE WHEN sp.post_id IS NOT NULL THEN 1 ELSE 0 END as is_saved,
              (SELECT COUNT(*) FROM post_votes WHERE post_id = p.post_id AND vote_type = 'upvote') as upvotes,
              (SELECT COUNT(*) FROM post_votes WHERE post_id = p.post_id AND vote_type = 'downvote') as downvotes,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
              FROM saved_posts sp
              INNER JOIN posts p ON sp.post_id = p.post_id
              INNER JOIN users u ON p.user_id = u.user_id
              WHERE sp.user_id = ?
              ORDER BY sp.saved_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $saved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format post data
    foreach ($saved_posts as &$post) {
        // Calculate net votes
        $post['net_votes'] = $post['upvotes'] - $post['downvotes'];
        
        // Format timestamp to a more readable format
        $post['created_at'] = date('M j, Y g:i A', strtotime($post['created_at']));
        
        // Ensure bool fields are properly typed
        $post['is_saved'] = (bool)$post['is_saved'];
    }
    
    echo json_encode([
        'status' => 'success',
        'count' => count($saved_posts),
        'saved_posts' => $saved_posts
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 