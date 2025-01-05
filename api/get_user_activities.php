<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception("User ID is required");
    }

    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT ua.*, 
              CASE 
                WHEN ua.activity_type = 'post' THEN p.content
                WHEN ua.activity_type = 'comment' THEN c.content
              END as content,
              CASE 
                WHEN ua.activity_type = 'post' THEN p.caption
                ELSE NULL
              END as post_caption
              FROM user_activities ua
              LEFT JOIN posts p ON ua.content_id = p.post_id AND ua.activity_type = 'post'
              LEFT JOIN comments c ON ua.content_id = c.comment_id AND ua.activity_type = 'comment'
              WHERE ua.user_id = :user_id
              ORDER BY ua.created_at DESC
              LIMIT 20";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_GET['user_id']);
    $stmt->execute();

    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $activities
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 