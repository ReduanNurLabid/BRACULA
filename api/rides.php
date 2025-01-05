<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$conn = $database->getConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Fetch rides
        try {
            $query = "SELECT r.*, u.full_name, u.avatar_url, u.student_id 
                     FROM rides r 
                     JOIN users u ON r.user_id = u.user_id 
                     WHERE r.status = 'available' 
                     ORDER BY r.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $rides = $stmt->fetchAll();
            
            echo json_encode(['status' => 'success', 'data' => $rides]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch rides: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        try {
            // Get and decode the request data
            $raw_data = file_get_contents('php://input');
            if (!$raw_data) {
                throw new Exception('No data received');
            }
            
            $data = json_decode($raw_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
            
            // Validate required fields
            $required_fields = ['vehicle', 'seats', 'fare', 'pickup', 'destination'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Get the first available user_id from the database (temporary solution)
            $query = "SELECT user_id FROM users LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('No users found in database. Please run database/add_test_user.php first');
            }
            
            $user_id = $user['user_id'];
            
            $query = "INSERT INTO rides (user_id, vehicle_type, seats, fare, pickup_location, destination, status) 
                     VALUES (:user_id, :vehicle_type, :seats, :fare, :pickup_location, :destination, 'available')";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':user_id' => $user_id,
                ':vehicle_type' => $data['vehicle'],
                ':seats' => intval($data['seats']),
                ':fare' => floatval($data['fare']),
                ':pickup_location' => $data['pickup'],
                ':destination' => $data['destination']
            ]);
            
            if (!$result) {
                throw new Exception('Failed to insert ride into database');
            }
            
            $ride_id = $conn->lastInsertId();
            
            // Fetch the newly created ride with user details
            $query = "SELECT r.*, u.full_name, u.avatar_url, u.student_id 
                     FROM rides r 
                     JOIN users u ON r.user_id = u.user_id 
                     WHERE r.ride_id = :ride_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':ride_id' => $ride_id]);
            $new_ride = $stmt->fetch();
            
            if (!$new_ride) {
                throw new Exception('Failed to fetch newly created ride');
            }
            
            echo json_encode(['status' => 'success', 'data' => $new_ride]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Failed to create ride: ' . $e->getMessage(),
                'debug_info' => [
                    'received_data' => isset($data) ? $data : null,
                    'sql_error' => isset($stmt) ? $stmt->errorInfo() : null
                ]
            ]);
        }
        break;

    case 'PUT':
        // Update ride status (for ride requests)
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $ride_id = $data['ride_id'];
            
            // First, check current seats
            $query = "SELECT seats FROM rides WHERE ride_id = :ride_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':ride_id' => $ride_id]);
            $ride = $stmt->fetch();
            
            if ($ride['seats'] > 0) {
                // Update seats and status
                $new_seats = $ride['seats'] - 1;
                $status = $new_seats > 0 ? 'available' : 'full';
                
                $query = "UPDATE rides 
                         SET seats = :seats, status = :status 
                         WHERE ride_id = :ride_id";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':seats' => $new_seats,
                    ':status' => $status,
                    ':ride_id' => $ride_id
                ]);
                
                echo json_encode(['status' => 'success', 'seats' => $new_seats]);
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'No seats available']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update ride: ' . $e->getMessage()]);
        }
        break;
}
?> 