<?php
require_once '../config/database.php';
require_once '../includes/session_check.php'; // Include session check utility
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Enable CORS for development
header('Access-Control-Allow-Origin: http://localhost:8081');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require login for all operations
require_login();

// Get user ID from session
$user_id = get_user_id();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            // Get inquiries - either sent by the user or for accommodations owned by the user
            $role = isset($_GET['role']) ? $_GET['role'] : 'sender';
            
            if ($role === 'sender') {
                // Get inquiries sent by the user
                $query = "SELECT i.*, a.title as accommodation_title, u.full_name as owner_name 
                    FROM accommodation_inquiries i
                    JOIN accommodations a ON i.accommodation_id = a.accommodation_id
                    JOIN users u ON a.owner_id = u.user_id
                    WHERE i.user_id = :user_id
                    ORDER BY i.created_at DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute([':user_id' => $user_id]);
            } else if ($role === 'owner') {
                // Get inquiries for accommodations owned by the user
                $query = "SELECT i.*, a.title as accommodation_title, u.full_name as sender_name, u.email as sender_email
                    FROM accommodation_inquiries i
                    JOIN accommodations a ON i.accommodation_id = a.accommodation_id
                    JOIN users u ON i.user_id = u.user_id
                    WHERE a.owner_id = :user_id
                    ORDER BY i.created_at DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute([':user_id' => $user_id]);
            } else {
                throw new Exception("Invalid role parameter");
            }
            
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $inquiries]);
            break;
            
        case 'POST':
            // Create new inquiry
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['accommodation_id']) || !isset($data['message']) || empty($data['message'])) {
                throw new Exception("Missing required fields");
            }
            
            // Check if accommodation exists
            $checkQuery = "SELECT * FROM accommodations WHERE accommodation_id = :acc_id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([':acc_id' => $data['accommodation_id']]);
            
            if ($checkStmt->rowCount() === 0) {
                throw new Exception("Accommodation not found");
            }
            
            // Get accommodation owner
            $accommodation = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Prevent sending inquiry to your own accommodation
            if ($accommodation['owner_id'] == $user_id) {
                throw new Exception("You cannot send an inquiry to your own accommodation");
            }
            
            // Insert inquiry
            $query = "INSERT INTO accommodation_inquiries (accommodation_id, user_id, message, status) 
                VALUES (:acc_id, :user_id, :message, 'pending')";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':acc_id' => $data['accommodation_id'],
                ':user_id' => $user_id,
                ':message' => $data['message']
            ]);
            
            $inquiry_id = $conn->lastInsertId();
            
            // Get the created inquiry with details
            $query = "SELECT i.*, a.title as accommodation_title, u.full_name as sender_name
                FROM accommodation_inquiries i
                JOIN accommodations a ON i.accommodation_id = a.accommodation_id
                JOIN users u ON i.user_id = u.user_id
                WHERE i.inquiry_id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $inquiry_id]);
            $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'success', 'message' => 'Inquiry sent successfully', 'data' => $inquiry]);
            break;
            
        case 'PUT':
            // Update inquiry status (mark as read, responded, etc.)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['inquiry_id']) || !isset($data['status'])) {
                throw new Exception("Missing required fields");
            }
            
            // Validate status
            $allowedStatuses = ['pending', 'responded', 'closed'];
            if (!in_array($data['status'], $allowedStatuses)) {
                throw new Exception("Invalid status value");
            }
            
            // Check if user owns the accommodation related to this inquiry
            $checkQuery = "SELECT a.owner_id 
                FROM accommodation_inquiries i
                JOIN accommodations a ON i.accommodation_id = a.accommodation_id
                WHERE i.inquiry_id = :id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([':id' => $data['inquiry_id']]);
            
            if ($checkStmt->rowCount() === 0) {
                throw new Exception("Inquiry not found");
            }
            
            $ownerInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Only the accommodation owner can update the inquiry status
            if ($ownerInfo['owner_id'] != $user_id) {
                throw new Exception("You don't have permission to update this inquiry");
            }
            
            // Update status
            $query = "UPDATE accommodation_inquiries SET status = :status WHERE inquiry_id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':status' => $data['status'],
                ':id' => $data['inquiry_id']
            ]);
            
            echo json_encode(['status' => 'success', 'message' => 'Inquiry status updated']);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 