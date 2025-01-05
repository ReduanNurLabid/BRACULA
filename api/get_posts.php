<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $community = isset($_GET['community']) ? $_GET['community'] : 'general';
    $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'latest';
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    // Base query to get posts with user information and vote counts
    $query = "SELECT 
                p.*,
                u.full_name as author,
                u.avatar_url,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.post_id) as comment_count,
                COALESCE((
                    SELECT COUNT(*) FROM votes v WHERE v.post_id = p.post_id AND v.vote_type = 'up'
                ) - (
                    SELECT COUNT(*) FROM votes v WHERE v.post_id = p.post_id AND v.vote_type = 'down'
                ), 0) as votes,
                (SELECT vote_type FROM votes v WHERE v.post_id = p.post_id AND v.user_id = :user_id) as user_vote
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.community = :community";

    // Add sorting
    switch($sortBy) {
        case 'popular':
            $query .= " ORDER BY votes DESC, p.created_at DESC";
            break;
        case 'discussed':
            $query .= " ORDER BY comment_count DESC, p.created_at DESC";
            break;
        default: // 'latest'
            $query .= " ORDER BY p.created_at DESC";
    }

    $query .= " LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':community', $community, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formatted_posts = array_map(function($post) {
        return [
            'id' => $post['post_id'],
            'author' => $post['author'],
            'avatar_url' => $post['avatar_url'],
            'content' => $post['content'],
            'caption' => $post['caption'],
            'community' => $post['community'],
            'timestamp' => $post['created_at'],
            'votes' => (int)$post['votes'],
            'user_vote' => $post['user_vote'],
            'commentCount' => (int)$post['comment_count']
        ];
    }, $posts);

    echo json_encode([
        'status' => 'success',
        'data' => $formatted_posts
    ]);

} catch(Exception $e) {
    error_log("Error in get_posts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load posts'
    ]);
}
?> 