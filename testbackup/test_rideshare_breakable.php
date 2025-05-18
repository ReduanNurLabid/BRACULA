<?php
require_once __DIR__ . '/setup_test_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Ride.php';

class RideTest {
    private $db;
    private $ride;
    private $testRideId;
    
    public function __construct() {
        // Get database connection
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Create Ride instance
        $this->ride = new Ride($this->db);
        
        echo "<h2>Ride Model Test</h2>";
    }
    
    public function runTests() {
        $this->testCreateRide();
        $this->testGetRide();
        $this->testUpdateRideStatus();
        $this->testSearchRide();
        $this->testReviewDriver();
    }
    
    public function testCreateRide() {
        echo "<h3>Test 1: Create Ride Offer</h3>";
        
        // Set test data
        $this->ride->user_id = 1; // Assuming user ID 1 exists
        $this->ride->from_location = "Test Origin " . rand(1000, 9999);
        $this->ride->to_location = "Test Destination " . rand(1000, 9999);
        $this->ride->departure_time = date('Y-m-d H:i:s', strtotime('+1 day'));
        $this->ride->seats_available = 3;
        $this->ride->price = 25.00;
        $this->ride->vehicle_description = "Test Vehicle";
        $this->ride->notes = "Test notes for unit testing";
        
        // Execute test
        $result = $this->ride->create();
        
        // Store the ride ID for later tests
        if ($result) {
            $this->testRideId = $this->ride->ride_id;
        }
        
        // Assert result
        $this->assert($result === true, "Ride creation should return true");
        $this->assert(!empty($this->ride->ride_id), "Ride ID should be assigned");
        
        echo "<p>Created test ride with ID: " . $this->ride->ride_id . "</p>";
    }
    
    public function testGetRide() {
        echo "<h3>Test 2: Get Ride</h3>";
        
        // Skip if no test ride was created
        if (empty($this->testRideId)) {
            echo "<p class='text-danger'>Skipping test: No test ride available</p>";
            return;
        }
        
        // Execute test
        $ride = $this->ride->getById($this->testRideId);
        
        // Assert results - THIS TEST CAN BE BROKEN DURING DEMO
        $this->assert(!empty($ride), "Ride should be retrieved");
        $this->assert($ride['ride_id'] == $this->testRideId, "Retrieved ride ID should match");
        $this->assert($ride['from_location'] == $this->ride->from_location, "Ride origin should match");
        
        echo "<p>Successfully retrieved ride from " . $ride['from_location'] . " to " . $ride['to_location'] . "</p>";
    }
    
    public function testUpdateRideStatus() {
        echo "<h3>Test 3: Update Ride Status</h3>";
        
        // Skip if no test ride was created
        if (empty($this->testRideId)) {
            echo "<p class='text-danger'>Skipping test: No test ride available</p>";
            return;
        }
        
        // Set up test
        $this->ride->ride_id = $this->testRideId;
        $this->ride->user_id = 1; // Must match the creator
        $new_status = "completed";
        
        // Execute test
        $result = $this->ride->changeStatus($new_status);
        
        // Assert result
        $this->assert($result === true, "Status update operation should succeed");
        
        // Verify status was updated by getting updated ride
        $ride = $this->ride->getById($this->testRideId);
        $this->assert($ride['status'] == $new_status, "Ride status should match the new status");
        
        echo "<p>Successfully updated ride status to: " . $ride['status'] . "</p>";
    }
    
    public function testSearchRide() {
        echo "<h3>Test 4: Search Rides</h3>";
        
        // Skip if no test ride was created
        if (empty($this->testRideId)) {
            echo "<p class='text-danger'>Skipping test: No test ride available</p>";
            return;
        }
        
        // Search for the keyword in the test ride destination
        $keyword = "Test";
        $filters = [
            'from_location' => $keyword,
            'to_location' => $keyword
        ];
        
        // Execute test using getAll with filters instead of search
        $results = $this->ride->getAll(10, 0, $filters);
        
        // Assert results
        $found = false;
        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
            if ($row['ride_id'] == $this->testRideId) {
                $found = true;
                break;
            }
        }
        
        $this->assert($found, "The test ride should be found in search results");
        
        echo "<p>Search successful, found test ride in results.</p>";
    }
    
    public function testReviewDriver() {
        echo "<h3>Test 5: Review Driver</h3>";
        
        // Skip if no test ride was created
        if (empty($this->testRideId)) {
            echo "<p class='text-danger'>Skipping test: No test ride available</p>";
            return;
        }
        
        // Set up test
        $user_id = 2; // Assuming this is a passenger who took the ride
        $rating = 4;
        $comment = "Test review for unit testing";
        
        // Execute test
        $result = $this->ride->reviewDriver($user_id, $rating, $comment);
        
        // Assert result
        $this->assert($result === true, "Driver review operation should succeed");
        
        echo "<p>Successfully added driver review with rating: " . $rating . "</p>";
    }
    
    // Helper function to assert test conditions
    private function assert($condition, $message) {
        if ($condition) {
            echo "<p class='text-success'>✓ PASS: $message</p>";
        } else {
            echo "<p class='text-danger'>✗ FAIL: $message</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rideshare Feature Unit Test</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .text-success {
            color: #198754 !important;
        }
        .text-danger {
            color: #dc3545 !important;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/BRACULA/test/index.php">BRACULA Tests</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_feed.php">Feed Test</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_rideshare.php">Rideshare Test</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_feed_breakable.php">Feed Unit Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/BRACULA/test/test_rideshare_breakable.php">Rideshare Unit Tests</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <h1>Rideshare Feature Unit Tests</h1>
    <p>This page runs automated unit tests on the Ride model.</p>
    <p><strong>How to demonstrate a failing test:</strong> Edit the <code>testGetRide()</code> method in this file to check for a wrong ride ID or origin.</p>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test Results</h3>
        </div>
        <div class="card-body">
            <?php
            $rideTest = new RideTest();
            $rideTest->runTests();
            ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">MVC Analysis</h3>
        </div>
        <div class="card-body">
            <h4>MVC Implementation Analysis</h4>
            <p>The rideshare feature follows the MVC pattern properly:</p>
            <ul>
                <li><strong>Model (Ride.php):</strong> Contains all the data logic and database interactions</li>
                <li><strong>Controllers (api/rides/*):</strong> Handle user input and coordinate between models and views</li>
                <li><strong>Views (html/*):</strong> Display data to users in the appropriate format</li>
            </ul>
            <p>The implementation correctly separates concerns:</p>
            <ol>
                <li>Models don't handle HTTP requests or rendering</li>
                <li>Controllers don't contain business logic or database operations</li>
                <li>Views don't interact directly with the database</li>
            </ol>
            <p>This proper separation makes the application more maintainable and testable, as demonstrated by these unit tests.</p>
        </div>
    </div>
</div>

<footer class="bg-light py-3 mt-5">
    <div class="container">
        <p class="text-center text-muted">BRACULA Test Environment</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 