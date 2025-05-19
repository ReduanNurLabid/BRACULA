<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database and user model
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/User.php';

    // Get user ID from query parameter
    $userId = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$userId) {
        throw new Exception("User ID is required");
    }

    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Query to get user profile
    $query = "SELECT u.user_id, u.full_name, u.email, p.bio, p.phone, p.address, p.avatar_url 
              FROM users u 
              LEFT JOIN profiles p ON u.user_id = p.user_id 
              WHERE u.user_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception("User profile not found");
    }

    echo json_encode([
        'success' => true,
        'data' => $profile
    ]);

} catch (Exception $e) {
    error_log("Error in get_user_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
