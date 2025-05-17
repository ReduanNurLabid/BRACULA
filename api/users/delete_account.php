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
error_log("Delete Account API called");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $raw_data = file_get_contents('php://input');
    error_log("Raw input data: " . $raw_data);
    
    $data = json_decode($raw_data, true);
    
    if (!isset($data['userId']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        error_log("Missing fields in request: " . print_r($data, true));
        exit;
    }

    $userId = $data['userId'];
    $password = $data['password'];
    error_log("Attempting to delete user with ID: " . $userId);

    $db = new Database();
    $conn = $db->getConnection();
    
    // First verify the user's password
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User data found: " . print_r($user, true));

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        error_log("User not found with ID: " . $userId);
        exit;
    }

    // Debug password verification
    error_log("Input password: " . $password);
    error_log("Stored hash: " . $user['password_hash']);
    $verified = password_verify($password, $user['password_hash']);
    error_log("Password verification result: " . ($verified ? 'true' : 'false'));

    if (!$verified) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
        error_log("Invalid password for user: " . $userId);
        exit;
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Delete user's comments
        $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete user's votes
        $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete user's saved posts
        $stmt = $conn->prepare("DELETE FROM saved_posts WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete user's event registrations
        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete user's ride requests
        $stmt = $conn->prepare("DELETE FROM ride_requests WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete user's rides
        $stmt = $conn->prepare("DELETE FROM rides WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete user's posts
        $stmt = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error in deletion transaction: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Fatal error in delete account: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}

// Ensure no extra output
ob_end_flush();
?> 