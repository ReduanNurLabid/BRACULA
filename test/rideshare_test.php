<?php
/**
 * Unit Test for Rideshare Functionality
 * 
 * This file tests the rideshare functionality in the BRACULA application.
 * Tests cover creating rides, requesting rides, updating ride status, and more.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

// Set up test environment
class RideshareTest {
    private $conn;
    private $testRideIds = [];
    private $testRequestIds = [];
    private $validUserId = null;
    
    /**
     * Constructor sets up database connection and finds a valid user ID
     */
    public function __construct() {
        // Create database connection
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Find a valid user ID for testing
        $this->findValidUser();
    }
    
    /**
     * Find a valid user ID from the database
     */
    private function findValidUser() {
        try {
            $stmt = $this->conn->query("SELECT user_id FROM users ORDER BY user_id LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && isset($user['user_id'])) {
                $this->validUserId = $user['user_id'];
            }
        } catch (PDOException $e) {
            // Users table might not exist or other issue
            $this->validUserId = null;
        }
    }
    
    /**
     * Run all test methods
     */
    public function runTests() {
        // Check if we have a valid user for testing
        if ($this->validUserId === null) {
            return [
                'error' => [
                    'status' => false,
                    'message' => "Cannot run tests: No valid user found in the database. Please ensure the users table exists and has at least one user."
                ]
            ];
        }
        
        $results = [
            'createRide' => $this->testCreateRide(),
            'getRides' => $this->testGetRides(),
            'getRideById' => $this->testGetRideById(),
            'createRideRequest' => $this->testCreateRideRequest(),
            'getRideRequests' => $this->testGetRideRequests(),
            'updateRideRequest' => $this->testUpdateRideRequest(),
            'updateRide' => $this->testUpdateRide(),
            'apiCreateRide' => $this->testApiCreateRide(),
            'apiCreateRideRequest' => $this->testApiCreateRideRequest()
        ];
        
        return $results;
    }
    
    /**
     * Test creating a ride with valid data
     */
    private function testCreateRide() {
        // Test data
        $userId = $this->validUserId;
        $vehicleType = "car";
        $seats = 3;
        $fare = 50.00;
        $pickupLocation = "Test Pickup Location";
        $destination = "Test Destination";
        $departureTime = date('Y-m-d H:i:s', strtotime('+1 day'));
        $contactInfo = "01712345678";
        $notes = "Test ride notes";
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("INSERT INTO rides (user_id, vehicle_type, seats, fare, pickup_location, destination, 
                                      departure_time, contact_info, notes, status, created_at) 
                                      VALUES (:user_id, :vehicle_type, :seats, :fare, :pickup_location, :destination, 
                                      :departure_time, :contact_info, :notes, 'available', NOW())");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':vehicle_type', $vehicleType);
            $stmt->bindParam(':seats', $seats);
            $stmt->bindParam(':fare', $fare);
            $stmt->bindParam(':pickup_location', $pickupLocation);
            $stmt->bindParam(':destination', $destination);
            $stmt->bindParam(':departure_time', $departureTime);
            $stmt->bindParam(':contact_info', $contactInfo);
            $stmt->bindParam(':notes', $notes);
            
            $result = $stmt->execute();
            
            // Store the ride ID for later cleanup
            if ($result) {
                $rideId = $this->conn->lastInsertId();
                $this->testRideIds[] = $rideId;
                
                // Verify ride was created by fetching it
                $selectStmt = $this->conn->prepare("SELECT * FROM rides WHERE ride_id = :ride_id");
                $selectStmt->bindParam(':ride_id', $rideId);
                $selectStmt->execute();
                $ride = $selectStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'status' => $result === true,
                'message' => $result ? "Ride created successfully with ID: " . $rideId : "Failed to create ride",
                'ride_id' => $result ? $rideId : null,
                'ride_data' => $result ? $ride : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error creating ride: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test getting all rides
     */
    private function testGetRides() {
        try {
            // Execute test
            $stmt = $this->conn->prepare("SELECT * FROM rides ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => count($rides) > 0,
                'message' => count($rides) > 0 ? "Successfully retrieved " . count($rides) . " rides" : "No rides found",
                'ride_count' => count($rides),
                'sample_data' => count($rides) > 0 ? $rides[0] : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error getting rides: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test getting a ride by ID
     */
    private function testGetRideById() {
        // Use the first test ride ID if available
        if (empty($this->testRideIds)) {
            return [
                'status' => false,
                'message' => "No test ride IDs available for this test"
            ];
        }
        
        $rideId = $this->testRideIds[0];
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("SELECT * FROM rides WHERE ride_id = :ride_id");
            $stmt->bindParam(':ride_id', $rideId);
            $stmt->execute();
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $ride !== false,
                'message' => $ride !== false ? "Successfully retrieved ride with ID: " . $rideId : "Ride not found",
                'ride_data' => $ride
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error getting ride by ID: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test creating a ride request
     */
    private function testCreateRideRequest() {
        // Use the first test ride ID if available
        if (empty($this->testRideIds)) {
            return [
                'status' => false,
                'message' => "No test ride IDs available for this test"
            ];
        }
        
        $rideId = $this->testRideIds[0];
        $userId = $this->validUserId;
        $seats = 10;
        $pickup = "Test Pickup Point";
        $notes = "Test request notes";
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("INSERT INTO ride_requests (ride_id, user_id, seats, pickup, notes, status, created_at) 
                                       VALUES (:ride_id, :user_id, :seats, :pickup, :notes, 'pending', NOW())");
            
            $stmt->bindParam(':ride_id', $rideId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':seats', $seats);
            $stmt->bindParam(':pickup', $pickup);
            $stmt->bindParam(':notes', $notes);
            
            $result = $stmt->execute();
            
            // Store the request ID for later cleanup
            if ($result) {
                $requestId = $this->conn->lastInsertId();
                $this->testRequestIds[] = $requestId;
                
                // Verify request was created by fetching it
                $selectStmt = $this->conn->prepare("SELECT * FROM ride_requests WHERE request_id = :request_id");
                $selectStmt->bindParam(':request_id', $requestId);
                $selectStmt->execute();
                $request = $selectStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'status' => $result === true,
                'message' => $result ? "Ride request created successfully with ID: " . $requestId : "Failed to create ride request",
                'request_id' => $result ? $requestId : null,
                'request_data' => $result ? $request : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error creating ride request: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test getting ride requests
     */
    private function testGetRideRequests() {
        try {
            // Execute test
            $stmt = $this->conn->prepare("SELECT * FROM ride_requests ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => count($requests) > 0,
                'message' => count($requests) > 0 ? "Successfully retrieved " . count($requests) . " ride requests" : "No ride requests found",
                'request_count' => count($requests),
                'sample_data' => count($requests) > 0 ? $requests[0] : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error getting ride requests: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test updating a ride request status
     */
    private function testUpdateRideRequest() {
        // Use the first test request ID if available
        if (empty($this->testRequestIds)) {
            return [
                'status' => false,
                'message' => "No test request IDs available for this test"
            ];
        }
        
        $requestId = $this->testRequestIds[0];
        $newStatus = "accepted";
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("UPDATE ride_requests SET status = :status WHERE request_id = :request_id");
            $stmt->bindParam(':status', $newStatus);
            $stmt->bindParam(':request_id', $requestId);
            $result = $stmt->execute();
            
            // Verify update was successful
            if ($result) {
                $selectStmt = $this->conn->prepare("SELECT * FROM ride_requests WHERE request_id = :request_id");
                $selectStmt->bindParam(':request_id', $requestId);
                $selectStmt->execute();
                $request = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                $updateSuccessful = $request && $request['status'] === $newStatus;
            }
            
            return [
                'status' => $result === true && $updateSuccessful,
                'message' => $result && $updateSuccessful ? "Successfully updated ride request status to: " . $newStatus : "Failed to update ride request status",
                'request_data' => $request ?? null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error updating ride request: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test updating a ride
     */
    private function testUpdateRide() {
        // Use the first test ride ID if available
        if (empty($this->testRideIds)) {
            return [
                'status' => false,
                'message' => "No test ride IDs available for this test"
            ];
        }
        
        $rideId = $this->testRideIds[0];
        $newFare = 300.00;
        $newNotes = "Updated test notes";
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("UPDATE rides SET fare = :fare, notes = :notes WHERE ride_id = :ride_id");
            $stmt->bindParam(':fare', $newFare);
            $stmt->bindParam(':notes', $newNotes);
            $stmt->bindParam(':ride_id', $rideId);
            $result = $stmt->execute();
            
            // Verify update was successful
            if ($result) {
                $selectStmt = $this->conn->prepare("SELECT * FROM rides WHERE ride_id = :ride_id");
                $selectStmt->bindParam(':ride_id', $rideId);
                $selectStmt->execute();
                $ride = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                $updateSuccessful = $ride && $ride['fare'] == $newFare && $ride['notes'] === $newNotes;
            }
            
            return [
                'status' => $result === true && $updateSuccessful,
                'message' => $result && $updateSuccessful ? "Successfully updated ride information" : "Failed to update ride information",
                'ride_data' => $ride ?? null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error updating ride: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test the rides API endpoint for creating a ride
     */
    private function testApiCreateRide() {
        // Test data
        $postData = [
            'user_id' => $this->validUserId,
            'vehicle_type' => 'rocket',
            'seats' => 1,
            'fare' => 150.00,
            'pickup_location' => 'API Test Pickup',
            'destination' => 'API Test Destination',
            'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'contact_info' => '01798765432',
            'notes' => 'Created via API test'
        ];
        
        // API URL
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $apiUrl = 'http://' . $host . '/BRACULA/api/rides/rides.php';
        
        // Execute API test using cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Parse response
        $responseData = json_decode($response, true);
        
        // Store ride ID for cleanup if successful
        if ($responseData && isset($responseData['status']) && $responseData['status'] === 'success') {
            if (isset($responseData['data']['ride_id'])) {
                $this->testRideIds[] = $responseData['data']['ride_id'];
            }
        }
        
        return [
            'status' => $httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success',
            'http_code' => $httpCode,
            'message' => isset($responseData['message']) ? $responseData['message'] : 'No message returned',
            'response' => $responseData,
            'raw_response' => $response,
            'curl_error' => $error ?: null
        ];
    }
    
    /**
     * Test the ride_requests API endpoint for creating a ride request
     */
    private function testApiCreateRideRequest() {
        // First create a new ride specifically for this test
        // to avoid "already requested" validation errors
        $rideData = [
            'user_id' => $this->validUserId,
            'vehicle_type' => 'rocket',
            'seats' => 2,
            'fare' => 200.00,
            'pickup_location' => 'API Request Test Pickup',
            'destination' => 'API Request Test Destination',
            'departure_time' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'contact_info' => '01712345678',
            'notes' => 'Created for API request test'
        ];
        
        // API URL for creating a ride
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $rideApiUrl = 'http://' . $host . '/BRACULA/api/rides/rides.php';
        
        // Create the ride first
        $ch = curl_init($rideApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rideData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $rideResponse = curl_exec($ch);
        curl_close($ch);
        
        // Parse response to get the ride ID
        $rideResponseData = json_decode($rideResponse, true);
        
        // If we couldn't create a ride, return an error
        if (!$rideResponseData || !isset($rideResponseData['status']) || $rideResponseData['status'] !== 'success') {
            return [
                'status' => false,
                'message' => "Failed to create a test ride for the ride request test",
                'response' => $rideResponseData
            ];
        }
        
        // Get the new ride's ID
        $newRideId = $rideResponseData['data']['ride_id'];
        $this->testRideIds[] = $newRideId;
        
        // Now test creating a request for this specific ride
        $postData = [
            'ride_id' => $newRideId,
            'user_id' => $this->validUserId,
            'seats' => 1,
            'pickup' => 'API Request Pickup Point',
            'notes' => 'API test ride request'
        ];
        
        // API URL for ride requests
        $requestApiUrl = 'http://' . $host . '/BRACULA/api/rides/ride_requests.php';
        
        // Execute API test using cURL
        $ch = curl_init($requestApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Parse response
        $responseData = json_decode($response, true);
        
        // Store request ID for cleanup if successful
        if ($responseData && isset($responseData['status']) && $responseData['status'] === 'success') {
            if (isset($responseData['data']['request_id'])) {
                $this->testRequestIds[] = $responseData['data']['request_id'];
            }
        }
        
        return [
            'status' => $httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success',
            'http_code' => $httpCode,
            'message' => isset($responseData['message']) ? $responseData['message'] : 'No message returned',
            'response' => $responseData,
            'raw_response' => $response,
            'curl_error' => $error ?: null,
            'created_ride_id' => $newRideId
        ];
    }
    
    /**
     * Clean up test data by deleting test ride requests and rides
     */
    public function cleanup() {
        $deletedRequests = 0;
        $deletedRides = 0;
        
        // Delete test ride requests first (due to foreign key constraints)
        foreach ($this->testRequestIds as $requestId) {
            try {
                $stmt = $this->conn->prepare("DELETE FROM ride_requests WHERE request_id = :request_id");
                $stmt->bindParam(':request_id', $requestId);
                if ($stmt->execute()) {
                    $deletedRequests++;
                }
            } catch (PDOException $e) {
                // Just continue if there's an error deleting a specific request
                continue;
            }
        }
        
        // Then delete test rides
        foreach ($this->testRideIds as $rideId) {
            try {
                $stmt = $this->conn->prepare("DELETE FROM rides WHERE ride_id = :ride_id");
                $stmt->bindParam(':ride_id', $rideId);
                if ($stmt->execute()) {
                    $deletedRides++;
                }
            } catch (PDOException $e) {
                // Just continue if there's an error deleting a specific ride
                continue;
            }
        }
        
        return [
            'status' => true,
            'message' => "Cleanup completed. Deleted $deletedRequests ride requests and $deletedRides rides.",
            'deleted_requests' => $deletedRequests,
            'deleted_rides' => $deletedRides,
            'test_request_ids' => $this->testRequestIds,
            'test_ride_ids' => $this->testRideIds
        ];
    }
}

// HTML output for test results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rideshare Unit Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .test-result {
            margin-bottom: 30px;
            border-left: 5px solid #ccc;
            padding-left: 15px;
        }
        .test-success {
            border-left-color: #28a745;
        }
        .test-failure {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>Rideshare Unit Tests</h1>
        <p class="lead">Testing rideshare functionality</p>
        
        <div class="alert alert-info mb-4">
            <h4>Test Summary</h4>
            <p>These tests verify the rideshare functionality works correctly, handling ride creation, ride requests, status updates, and more.</p>
        </div>
        
        <?php
        // Run tests
        $tester = new RideshareTest();
        $results = $tester->runTests();
        
        // Check if there was an error finding a valid user
        if (isset($results['error'])) {
            ?>
            <div class="alert alert-danger">
                <h4>Error Running Tests</h4>
                <p><?php echo $results['error']['message']; ?></p>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-primary">Back to Tests</a>
                </div>
            </div>
            <?php
            exit;
        }
        
        // Count passed/failed tests
        $passedTests = 0;
        $totalTests = count($results);
        
        foreach ($results as $testName => $result) {
            if (isset($result['status']) && $result['status'] === true) {
                $passedTests++;
            }
            
            $resultClass = (isset($result['status']) && $result['status'] === true) ? 'test-success' : 'test-failure';
            ?>
            <div class="card mb-4 test-result <?php echo $resultClass; ?>">
                <div class="card-header">
                    <h3>
                        <?php if (isset($result['status']) && $result['status'] === true): ?>
                            <span class="badge bg-success me-2">PASS</span>
                        <?php else: ?>
                            <span class="badge bg-danger me-2">FAIL</span>
                        <?php endif; ?>
                        
                        Test: <?php echo ucfirst($testName); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <p><strong>Message:</strong> <?php echo $result['message'] ?? 'No message'; ?></p>
                    
                    <?php if (isset($result['ride_id'])): ?>
                        <p><strong>Ride ID:</strong> <?php echo $result['ride_id']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['request_id'])): ?>
                        <p><strong>Request ID:</strong> <?php echo $result['request_id']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['created_ride_id'])): ?>
                        <p><strong>Created Ride ID:</strong> <?php echo $result['created_ride_id']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['http_code'])): ?>
                        <p><strong>HTTP Code:</strong> <?php echo $result['http_code']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['ride_count'])): ?>
                        <p><strong>Ride Count:</strong> <?php echo $result['ride_count']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['request_count'])): ?>
                        <p><strong>Request Count:</strong> <?php echo $result['request_count']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['error_code'])): ?>
                        <p><strong>Error Code:</strong> <?php echo $result['error_code']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['ride_data'])): ?>
                        <h5>Ride Data:</h5>
                        <pre><?php print_r($result['ride_data']); ?></pre>
                    <?php endif; ?>
                    
                    <?php if (isset($result['request_data'])): ?>
                        <h5>Request Data:</h5>
                        <pre><?php print_r($result['request_data']); ?></pre>
                    <?php endif; ?>
                    
                    <?php if (isset($result['sample_data'])): ?>
                        <h5>Sample Data:</h5>
                        <pre><?php print_r($result['sample_data']); ?></pre>
                    <?php endif; ?>
                    
                    <?php if (isset($result['response'])): ?>
                        <h5>API Response:</h5>
                        <pre><?php print_r($result['response']); ?></pre>
                    <?php endif; ?>
                    
                    <?php if (isset($result['curl_error']) && $result['curl_error']): ?>
                        <h5>cURL Error:</h5>
                        <pre><?php echo $result['curl_error']; ?></pre>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        
        // Display test summary
        $successRate = ($passedTests / $totalTests) * 100;
        ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Test Summary</h3>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $successRate; ?>%" 
                         aria-valuenow="<?php echo $successRate; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo number_format($successRate, 1); ?>%
                    </div>
                </div>
                <p><strong>Tests Passed:</strong> <?php echo $passedTests; ?> of <?php echo $totalTests; ?></p>
            </div>
        </div>
        
        <?php
        // Clean up test data
        $cleanup = $tester->cleanup();
        ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Cleanup Results</h3>
            </div>
            <div class="card-body">
                <p><strong>Message:</strong> <?php echo $cleanup['message']; ?></p>
                <p><strong>Test Ride Requests Deleted:</strong> <?php echo $cleanup['deleted_requests']; ?></p>
                <p><strong>Test Rides Deleted:</strong> <?php echo $cleanup['deleted_rides']; ?></p>
                <?php if (!empty($cleanup['test_request_ids']) || !empty($cleanup['test_ride_ids'])): ?>
                    <pre><?php 
                        echo "Ride Request IDs: ";
                        print_r($cleanup['test_request_ids']);
                        echo "\nRide IDs: ";
                        print_r($cleanup['test_ride_ids']);
                    ?></pre>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Back to Tests</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 