<?php
/**
 * API Test Runner
 * 
 * This file provides automated testing for the BRACULA API endpoints.
 * Focused on the post creation functionality.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

class ApiTester {
    private $baseUrl;
    private $testResults = [];
    private $cleanupIds = [];
    private $validUserId = null;
    
    public function __construct() {
        // Configure the base URL for your local environment
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $this->baseUrl = 'http://' . $host . '/BRACULA/api';
        
        // Find a valid user ID for testing
        $this->findValidUser();
    }
    
    /**
     * Find a valid user ID from the database
     */
    private function findValidUser() {
        // We need to include the database config
        require_once '../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $stmt = $conn->query("SELECT user_id FROM users ORDER BY user_id LIMIT 1");
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
     * Run all API tests
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
        
        // Feed Post API Tests
        $this->testResults['create_post_success'] = $this->testCreatePostSuccess();
        $this->testResults['create_post_missing_fields'] = $this->testCreatePostMissingFields();
        $this->testResults['create_post_invalid_user'] = $this->testCreatePostInvalidUser();
        
        return $this->testResults;
    }
    
    /**
     * Test successful post creation
     */
    private function testCreatePostSuccess() {
        $endpoint = '/posts/create_post.php';
        $payload = [
            'user_id' => $this->validUserId, // Using valid user ID
            'caption' => 'API Test Post ' . rand(1000, 9999),
            'content' => 'This is a test post created via the API endpoint.',
            'community' => 'general'
        ];
        
        $response = $this->makeApiRequest('POST', $endpoint, $payload);
        
        // Add post ID to cleanup list if successful
        if (isset($response['data']['status']) && $response['data']['status'] === 'success') {
            if (isset($response['data']['data']['id'])) {
                $this->cleanupIds[] = $response['data']['data']['id'];
            }
        }
        
        return [
            'endpoint' => $endpoint,
            'method' => 'POST',
            'payload' => $payload,
            'expected_status' => 200,
            'actual_status' => $response['status'],
            'expected_response' => 'success',
            'actual_response' => isset($response['data']['status']) ? $response['data']['status'] : 'unknown',
            'response_data' => $response['data'],
            'pass' => $response['status'] === 200 && 
                     isset($response['data']['status']) && 
                     $response['data']['status'] === 'success'
        ];
    }
    
    /**
     * Test post creation with missing required fields
     */
    private function testCreatePostMissingFields() {
        $endpoint = '/posts/create_post.php';
        $payload = [
            'user_id' => $this->validUserId,
            // Missing content field
            'caption' => 'Missing Content Test',
            'community' => 'general'
        ];
        
        $response = $this->makeApiRequest('POST', $endpoint, $payload);
        
        return [
            'endpoint' => $endpoint,
            'method' => 'POST',
            'payload' => $payload,
            'expected_status' => 400,
            'actual_status' => $response['status'],
            'expected_response' => 'error',
            'actual_response' => isset($response['data']['status']) ? $response['data']['status'] : 'unknown',
            'response_data' => $response['data'],
            'pass' => $response['status'] === 400 && 
                     isset($response['data']['status']) && 
                     $response['data']['status'] === 'error'
        ];
    }
    
    /**
     * Test post creation with invalid user ID
     */
    private function testCreatePostInvalidUser() {
        $endpoint = '/posts/create_post.php';
        $payload = [
            'user_id' => 999999, // Non-existent user ID
            'caption' => 'Invalid User Test',
            'content' => 'This post has an invalid user ID.',
            'community' => 'general'
        ];
        
        $response = $this->makeApiRequest('POST', $endpoint, $payload);
        
        return [
            'endpoint' => $endpoint,
            'method' => 'POST',
            'payload' => $payload,
            'expected_status' => 400,
            'actual_status' => $response['status'],
            'expected_response' => 'error',
            'actual_response' => isset($response['data']['status']) ? $response['data']['status'] : 'unknown',
            'response_data' => $response['data'],
            'pass' => $response['status'] === 400 && 
                     isset($response['data']['status']) && 
                     $response['data']['status'] === 'error'
        ];
    }
    
    /**
     * Make an API request to the specified endpoint
     */
    private function makeApiRequest($method, $endpoint, $payload = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        return [
            'status' => $status,
            'data' => $data,
            'raw_response' => $response,
            'error' => $error
        ];
    }
    
    /**
     * Clean up test data created during tests
     */
    public function cleanup() {
        // Connect to database
        require_once '../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $deletedCount = 0;
        
        foreach ($this->cleanupIds as $postId) {
            $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = :post_id");
            $stmt->bindParam(':post_id', $postId);
            if ($stmt->execute()) {
                $deletedCount++;
            }
        }
        
        return [
            'status' => true,
            'message' => "Cleanup completed. Deleted $deletedCount test posts.",
            'deleted_count' => $deletedCount,
            'test_post_ids' => $this->cleanupIds
        ];
    }
}

// HTML output for API test results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA API Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .method-badge {
            min-width: 80px;
        }
        .method-post {
            background-color: #10b981;
        }
        .method-get {
            background-color: #3b82f6;
        }
        .method-put {
            background-color: #f59e0b;
        }
        .method-delete {
            background-color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>BRACULA API Tests</h1>
        <p class="lead">Automated testing for API endpoints</p>
        
        <div class="alert alert-info mb-4">
            <h4>Test Information</h4>
            <p>These tests verify the API endpoints function correctly, handling various scenarios and edge cases.</p>
        </div>
        
        <?php
        // Run API tests
        $apiTester = new ApiTester();
        $results = $apiTester->runTests();
        
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
            if (isset($result['pass']) && $result['pass'] === true) {
                $passedTests++;
            }
            
            $methodClass = 'method-' . strtolower($result['method']);
            ?>
            <div class="card mb-4 border-<?php echo isset($result['pass']) && $result['pass'] ? 'success' : 'danger'; ?>">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>
                            <?php if (isset($result['pass']) && $result['pass']): ?>
                                <span class="badge bg-success me-2">PASS</span>
                            <?php else: ?>
                                <span class="badge bg-danger me-2">FAIL</span>
                            <?php endif; ?>
                            
                            Test: <?php echo str_replace('_', ' ', ucfirst($testName)); ?>
                        </h3>
                        <span class="badge method-badge <?php echo $methodClass; ?>"><?php echo $result['method']; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Request Details</h5>
                            <p><strong>Endpoint:</strong> <?php echo $result['endpoint']; ?></p>
                            
                            <?php if (isset($result['payload'])): ?>
                                <h6>Payload:</h6>
                                <pre><?php echo json_encode($result['payload'], JSON_PRETTY_PRINT); ?></pre>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Response Details</h5>
                            <p>
                                <strong>Status:</strong> 
                                <?php echo $result['actual_status']; ?> 
                                <?php if ($result['actual_status'] === $result['expected_status']): ?>
                                    <span class="badge bg-success">✓</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗</span>
                                    <small>(Expected: <?php echo $result['expected_status']; ?>)</small>
                                <?php endif; ?>
                            </p>
                            
                            <p>
                                <strong>Response Type:</strong> 
                                <?php echo $result['actual_response']; ?> 
                                <?php if ($result['actual_response'] === $result['expected_response']): ?>
                                    <span class="badge bg-success">✓</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗</span>
                                    <small>(Expected: <?php echo $result['expected_response']; ?>)</small>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (isset($result['response_data'])): ?>
                                <h6>Response Data:</h6>
                                <pre><?php echo json_encode($result['response_data'], JSON_PRETTY_PRINT); ?></pre>
                            <?php endif; ?>
                        </div>
                    </div>
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
        $cleanup = $apiTester->cleanup();
        ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Cleanup Results</h3>
            </div>
            <div class="card-body">
                <p><strong>Message:</strong> <?php echo $cleanup['message']; ?></p>
                <p><strong>Test Posts Deleted:</strong> <?php echo $cleanup['deleted_count']; ?></p>
                <?php if (!empty($cleanup['test_post_ids'])): ?>
                    <pre><?php print_r($cleanup['test_post_ids']); ?></pre>
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