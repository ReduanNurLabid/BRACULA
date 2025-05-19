<?php
// Prevent any unwanted output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure clean output
ob_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
    // Include database configuration
    require_once __DIR__ . '/../../config/database.php';

    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        throw new Exception("No data received");
    }

    // Validate required fields
    if (empty($data['full_name'])) {
        throw new Exception("Full name is required");
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Update users table
        $query = "UPDATE users SET full_name = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data['full_name'], $data['user_id']]);

        // Check if profile exists
        $query = "SELECT COUNT(*) FROM profiles WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data['user_id']]);
        $profileExists = $stmt->fetchColumn() > 0;

        if ($profileExists) {
            // Update existing profile
            $query = "UPDATE profiles SET 
                     bio = ?, 
                     phone = ?, 
                     address = ? 
                     WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $data['bio'] ?? null,
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['user_id']
            ]);
        } else {
            // Insert new profile
            $query = "INSERT INTO profiles (user_id, bio, phone, address) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $data['user_id'],
                $data['bio'] ?? null,
                $data['phone'] ?? null,
                $data['address'] ?? null
            ]);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in update_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Ensure no extra output
ob_end_flush();
?> 