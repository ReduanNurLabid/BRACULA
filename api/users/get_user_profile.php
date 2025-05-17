<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database and user model
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/User.php';

    // Get user ID from query parameter
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$userId) {
        throw new Exception("User ID is required");
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Create user object
   // $user = new User($db);
    
    // Get user data
    $user = new User($conn);
    $user->user_id = $data['user_id'];
    $user->full_name = $data['full_name'] ?? '';
    $user->bio = $data['bio'] ?? '';
    $user->avatar_url = $data['avatar_url'] ?? '';
    $user->interests = $data['interests'] ?? '';

    error_log("Attempting to update profile for user: " . $user->user_id);
    error_log("Profile data: " . print_r([
        'full_name' => $user->full_name,
        'bio' => $user->bio,
        'avatar_url' => $user->avatar_url,
        'interests' => $user->interests
    ], true));

    if ($user->update()) {
        $response = [
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user_id' => $user->user_id,
                'full_name' => $user->full_name,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'interests' => $user->interests
            ]
        ];
        error_log("Profile updated successfully");
        echo json_encode($response);
    } else {
        throw new Exception('Failed to update profile');
    }

} catch (Exception $e) {
    error_log("Error in update_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while updating profile'
    ]);
}

// Ensure no extra output
ob_end_flush();
?>
