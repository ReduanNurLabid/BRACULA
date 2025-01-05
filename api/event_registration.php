<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$conn = $database->getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('No data received');
        }
        
        // Validate required fields
        if (!isset($data['event_id']) || !isset($data['user_id'])) {
            throw new Exception('Missing required fields');
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        try {
            // Check if user is already registered
            $query = "SELECT registration_id, status FROM event_registrations 
                     WHERE event_id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$data['event_id'], $data['user_id']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                if ($existing['status'] === 'registered') {
                    // Cancel registration
                    $query = "UPDATE event_registrations SET status = 'cancelled' 
                             WHERE registration_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$existing['registration_id']]);
                    $message = 'Event registration cancelled';
                    $status = 'cancelled';
                } else {
                    // Re-register
                    $query = "UPDATE event_registrations SET status = 'registered' 
                             WHERE registration_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$existing['registration_id']]);
                    $message = 'Event registration renewed';
                    $status = 'registered';
                }
            } else {
                // New registration
                $query = "INSERT INTO event_registrations (event_id, user_id) 
                         VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$data['event_id'], $data['user_id']]);
                $message = 'Successfully registered for event';
                $status = 'registered';
            }
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'registration_status' => $status
            ]);
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check registration status
        if (!isset($_GET['event_id']) || !isset($_GET['user_id'])) {
            throw new Exception('Missing required parameters');
        }
        
        $query = "SELECT status FROM event_registrations 
                 WHERE event_id = ? AND user_id = ? AND status = 'registered'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_GET['event_id'], $_GET['user_id']]);
        $registration = $stmt->fetch();
        
        echo json_encode([
            'status' => 'success',
            'is_registered' => $registration ? true : false
        ]);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 