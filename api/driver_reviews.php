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
        getDriverReviews($conn);
        break;
    case 'POST':
        createDriverReview($conn);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

// Function to get driver reviews
function getDriverReviews($conn) {
    try {
        // Validate driver_id
        if (!isset($_GET['driver_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing driver_id parameter']);
            return;
        }
        
        $driver_id = $_GET['driver_id'];
        
        // Get reviews for the driver
        $query = "SELECT dr.*, u.full_name, u.avatar_url 
                 FROM driver_reviews dr
                 JOIN users u ON dr.user_id = u.user_id
                 WHERE dr.driver_id = ?
                 ORDER BY dr.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $driver_id);
        $stmt->execute();
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get average rating
        $avgQuery = "SELECT AVG(rating) as average_rating, COUNT(*) as review_count 
                    FROM driver_reviews 
                    WHERE driver_id = ?";
        
        $avgStmt = $conn->prepare($avgQuery);
        $avgStmt->bindParam(1, $driver_id);
        $avgStmt->execute();
        
        $ratingData = $avgStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success', 
            'data' => [
                'reviews' => $reviews,
                'average_rating' => $ratingData['average_rating'] ? (float)$ratingData['average_rating'] : 0,
                'review_count' => (int)$ratingData['review_count']
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to create a new driver review
function createDriverReview($conn) {
    try {
        // Get posted data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['driver_id', 'user_id', 'ride_id', 'rating'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Validate rating value
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5']);
            return;
        }
        
        // Check if the user has already reviewed this driver for this ride
        $checkQuery = "SELECT * FROM driver_reviews 
                      WHERE driver_id = ? AND user_id = ? AND ride_id = ?";
        
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(1, $data['driver_id']);
        $checkStmt->bindParam(2, $data['user_id']);
        $checkStmt->bindParam(3, $data['ride_id']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You have already reviewed this driver for this ride']);
            return;
        }
        
        // Check if the ride exists and the user was a passenger
        $rideQuery = "SELECT r.* FROM rides r
                     JOIN ride_requests rr ON r.ride_id = rr.ride_id
                     WHERE r.ride_id = ? AND r.user_id = ? AND rr.user_id = ? AND rr.status = 'accepted'";
        
        $rideStmt = $conn->prepare($rideQuery);
        $rideStmt->bindParam(1, $data['ride_id']);
        $rideStmt->bindParam(2, $data['driver_id']);
        $rideStmt->bindParam(3, $data['user_id']);
        $rideStmt->execute();
        
        if ($rideStmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You can only review drivers of rides you have taken']);
            return;
        }
        
        // Insert new review
        $query = "INSERT INTO driver_reviews (driver_id, user_id, ride_id, rating, comment, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $data['driver_id']);
        $stmt->bindParam(2, $data['user_id']);
        $stmt->bindParam(3, $data['ride_id']);
        $stmt->bindParam(4, $data['rating']);
        $stmt->bindParam(5, $data['comment']);
        
        if ($stmt->execute()) {
            $review_id = $conn->lastInsertId();
            
            // Get the created review with user info
            $query = "SELECT dr.*, u.full_name, u.avatar_url 
                     FROM driver_reviews dr
                     JOIN users u ON dr.user_id = u.user_id
                     WHERE dr.review_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $review_id);
            $stmt->execute();
            
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get updated average rating
            $avgQuery = "SELECT AVG(rating) as average_rating, COUNT(*) as review_count 
                        FROM driver_reviews 
                        WHERE driver_id = ?";
            
            $avgStmt = $conn->prepare($avgQuery);
            $avgStmt->bindParam(1, $data['driver_id']);
            $avgStmt->execute();
            
            $ratingData = $avgStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Review submitted successfully',
                'data' => [
                    'review' => $review,
                    'average_rating' => $ratingData['average_rating'] ? (float)$ratingData['average_rating'] : 0,
                    'review_count' => (int)$ratingData['review_count']
                ]
            ]);
        } else {
            throw new Exception('Failed to submit review');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?> 