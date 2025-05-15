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
        getRides($conn);
        break;
    case 'POST':
        createRide($conn);
        break;
    case 'PUT':
        updateRide($conn);
        break;
    case 'DELETE':
        deleteRide($conn);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

// Function to get rides
function getRides($conn) {
    try {
        $query = "SELECT r.*, u.full_name, u.avatar_url, 
                 (SELECT COUNT(*) FROM ride_requests WHERE ride_id = r.ride_id AND status = 'pending') as request_count,
                 (SELECT AVG(rating) FROM driver_reviews WHERE driver_id = r.user_id) as average_rating,
                 (SELECT COUNT(*) FROM driver_reviews WHERE driver_id = r.user_id) as rating_count
                 FROM rides r
                 LEFT JOIN users u ON r.user_id = u.user_id";
        
        $params = [];
        
        // Filter by user_id if provided
        if (isset($_GET['user_id'])) {
            $query .= " WHERE r.user_id = ?";
            $params[] = $_GET['user_id'];
        }
        
        // Filter by status if provided
        if (isset($_GET['status'])) {
            $query .= isset($_GET['user_id']) ? " AND r.status = ?" : " WHERE r.status = ?";
            $params[] = $_GET['status'];
        }
        
        // Order by creation date, newest first
        $query .= " ORDER BY r.created_at DESC";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters if any
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $rides]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to create a new ride
function createRide($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['vehicle_type', 'seats', 'fare', 'pickup_location', 'destination', 'user_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Insert new ride
        $query = "INSERT INTO rides (user_id, vehicle_type, seats, fare, pickup_location, destination, 
                 departure_time, contact_info, notes, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $data['user_id']);
        $stmt->bindParam(2, $data['vehicle_type']);
        $stmt->bindParam(3, $data['seats']);
        $stmt->bindParam(4, $data['fare']);
        $stmt->bindParam(5, $data['pickup_location']);
        $stmt->bindParam(6, $data['destination']);
        $stmt->bindParam(7, $data['departure_time']);
        $stmt->bindParam(8, $data['contact_info']);
        $stmt->bindParam(9, $data['notes']);
        
        if ($stmt->execute()) {
            $ride_id = $conn->lastInsertId();
            
            // Get the created ride with user info
            $query = "SELECT r.*, u.full_name, u.avatar_url FROM rides r
                     LEFT JOIN users u ON r.user_id = u.user_id
                     WHERE r.ride_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $ride_id);
            $stmt->execute();
            
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'success', 'message' => 'Ride created successfully', 'data' => $ride]);
        } else {
            throw new Exception('Failed to create ride');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to update a ride
function updateRide($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate ride_id
        if (!isset($data['ride_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing ride_id']);
            return;
        }
        
        // Check if ride exists
        $checkQuery = "SELECT * FROM rides WHERE ride_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(1, $data['ride_id']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Ride not found']);
            return;
        }
        
        // Build update query based on provided fields
        $updateFields = [];
        $params = [];
        
        $allowedFields = [
            'vehicle_type', 'seats', 'fare', 'pickup_location', 'destination', 
            'departure_time', 'contact_info', 'notes', 'status'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
            return;
        }
        
        // Add ride_id to params
        $params[] = $data['ride_id'];
        
        $query = "UPDATE rides SET " . implode(', ', $updateFields) . " WHERE ride_id = ?";
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        
        if ($stmt->execute()) {
            // Get the updated ride
            $query = "SELECT r.*, u.full_name, u.avatar_url FROM rides r
                     LEFT JOIN users u ON r.user_id = u.user_id
                     WHERE r.ride_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $data['ride_id']);
            $stmt->execute();
            
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'success', 'message' => 'Ride updated successfully', 'data' => $ride]);
        } else {
            throw new Exception('Failed to update ride');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a ride
function deleteRide($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate ride_id
        if (!isset($data['ride_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing ride_id']);
            return;
        }
        
        // Check if ride exists
        $checkQuery = "SELECT * FROM rides WHERE ride_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(1, $data['ride_id']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Ride not found']);
            return;
        }
        
        // Delete ride
        $query = "DELETE FROM rides WHERE ride_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $data['ride_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Ride deleted successfully']);
        } else {
            throw new Exception('Failed to delete ride');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?> 