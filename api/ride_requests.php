<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once '../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        getRideRequests($conn);
        break;
    case 'POST':
        createRideRequest($conn);
        break;
    case 'PUT':
        updateRideRequest($conn);
        break;
    case 'DELETE':
        deleteRideRequest($conn);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

// Function to get ride requests
function getRideRequests($conn) {
    try {
        $query = "SELECT rr.*, r.pickup_location, r.destination, r.vehicle_type, r.departure_time, r.contact_info,
                 u1.full_name, u1.avatar_url,
                 u2.full_name as driver_name, u2.user_id as driver_id
                 FROM ride_requests rr
                 JOIN rides r ON rr.ride_id = r.ride_id
                 JOIN users u1 ON rr.user_id = u1.user_id
                 JOIN users u2 ON r.user_id = u2.user_id";
        
        $params = [];
        $conditions = [];
        
        // Filter by user_id if provided
        if (isset($_GET['user_id'])) {
            $conditions[] = "rr.user_id = ?";
            $params[] = $_GET['user_id'];
        }
        
        // Filter by ride_id if provided
        if (isset($_GET['ride_id'])) {
            $conditions[] = "rr.ride_id = ?";
            $params[] = $_GET['ride_id'];
        }
        
        // Filter by status if provided
        if (isset($_GET['status'])) {
            $conditions[] = "rr.status = ?";
            $params[] = $_GET['status'];
        }
        
        // Add conditions to query if any
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        // Order by creation date, newest first
        $query .= " ORDER BY rr.created_at DESC";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters if any
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $requests]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to create a new ride request
function createRideRequest($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['ride_id', 'user_id', 'seats', 'pickup'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Check if ride exists and has enough seats
        $checkQuery = "SELECT * FROM rides WHERE ride_id = ? AND seats >= ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(1, $data['ride_id']);
        $checkStmt->bindParam(2, $data['seats']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ride not found or not enough seats available']);
            return;
        }
        
        // Check if user already requested this ride
        $checkRequestQuery = "SELECT * FROM ride_requests WHERE ride_id = ? AND user_id = ?";
        $checkRequestStmt = $conn->prepare($checkRequestQuery);
        $checkRequestStmt->bindParam(1, $data['ride_id']);
        $checkRequestStmt->bindParam(2, $data['user_id']);
        $checkRequestStmt->execute();
        
        if ($checkRequestStmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You have already requested this ride']);
            return;
        }
        
        // Insert new ride request
        $query = "INSERT INTO ride_requests (ride_id, user_id, seats, pickup, notes, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $data['ride_id']);
        $stmt->bindParam(2, $data['user_id']);
        $stmt->bindParam(3, $data['seats']);
        $stmt->bindParam(4, $data['pickup']);
        $stmt->bindParam(5, $data['notes']);
        
        if ($stmt->execute()) {
            $request_id = $conn->lastInsertId();
            
            // Get the created request with additional info
            $query = "SELECT rr.*, r.pickup_location, r.destination, r.vehicle_type, r.departure_time,
                     u1.full_name, u1.avatar_url,
                     u2.full_name as driver_name, u2.user_id as driver_id
                     FROM ride_requests rr
                     JOIN rides r ON rr.ride_id = r.ride_id
                     JOIN users u1 ON rr.user_id = u1.user_id
                     JOIN users u2 ON r.user_id = u2.user_id
                     WHERE rr.request_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $request_id);
            $stmt->execute();
            
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'success', 'message' => 'Ride request created successfully', 'data' => $request]);
        } else {
            throw new Exception('Failed to create ride request');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to update a ride request (accept/reject)
function updateRideRequest($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate request_id and status
        if (!isset($data['request_id']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing request_id or status']);
            return;
        }
        
        // Validate status value
        if (!in_array($data['status'], ['accepted', 'rejected'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
            return;
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Get request details
            $requestQuery = "SELECT * FROM ride_requests WHERE request_id = ?";
            $requestStmt = $conn->prepare($requestQuery);
            $requestStmt->bindParam(1, $data['request_id']);
            $requestStmt->execute();
            
            if ($requestStmt->rowCount() === 0) {
                throw new Exception('Ride request not found');
            }
            
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
            
            // Update request status
            $updateQuery = "UPDATE ride_requests SET status = ? WHERE request_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(1, $data['status']);
            $updateStmt->bindParam(2, $data['request_id']);
            $updateStmt->execute();
            
            // If accepted, update available seats
            if ($data['status'] === 'accepted') {
                $updateRideQuery = "UPDATE rides SET seats = seats - ? WHERE ride_id = ?";
                $updateRideStmt = $conn->prepare($updateRideQuery);
                $updateRideStmt->bindParam(1, $request['seats']);
                $updateRideStmt->bindParam(2, $request['ride_id']);
                $updateRideStmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Ride request ' . $data['status'] . ' successfully'
            ]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a ride request
function deleteRideRequest($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate request_id
        if (!isset($data['request_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing request_id']);
            return;
        }
        
        // Check if request exists
        $checkQuery = "SELECT * FROM ride_requests WHERE request_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(1, $data['request_id']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Ride request not found']);
            return;
        }
        
        // Delete request
        $query = "DELETE FROM ride_requests WHERE request_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $data['request_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Ride request deleted successfully']);
        } else {
            throw new Exception('Failed to delete ride request');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?> 