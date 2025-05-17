<?php
// Prevent any unwanted output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure clean output
ob_start();

require_once '../config/database.php';
require_once '../models/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any previous output
ob_clean();

// Enable error logging
error_log("Update Profile API called");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $raw_data = file_get_contents('php://input');
    error_log("Raw input data: " . $raw_data);
    
    $data = json_decode($raw_data, true);
    
    if (!isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        error_log("Missing user_id in request");
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();
    
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