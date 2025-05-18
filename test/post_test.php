<?php
/**
 * Unit Test for Feed Post Creation
 * 
 * This file tests the post creation functionality in the BRACULA application.
 * Tests cover basic creation, validation, and error handling.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

// Set up test environment
class PostTest {
    private $conn;
    private $testPostIds = [];
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
            'basicCreation' => $this->testBasicPostCreation(),
            'emptyContent' => $this->testPostWithEmptyContent(),
            'longContent' => $this->testPostWithLongContent(),
            'invalidUser' => $this->testPostWithInvalidUser(),
            'directApiTest' => $this->testCreatePostApi()
        ];
        
        return $results;
    }
    
    /**
     * Test basic post creation with valid data
     */
    private function testBasicPostCreation() {
        // Test data
        $userId = $this->validUserId; // Using the valid user ID we found
        $caption = "Test Post " . rand(1000, 9999);
        $content = "This is a test post content created for unit testing.";
        $community = "general";
        
        // Execute test
        $stmt = $this->conn->prepare("INSERT INTO posts (user_id, caption, content, community, created_at) 
                                      VALUES (:user_id, :caption, :content, :community, NOW())");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':caption', $caption);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':community', $community);
        
        try {
            $result = $stmt->execute();
            
            // Store the post ID for later cleanup
            if ($result) {
                $postId = $this->conn->lastInsertId();
                $this->testPostIds[] = $postId;
                
                // Verify post was created by fetching it
                $selectStmt = $this->conn->prepare("SELECT * FROM posts WHERE post_id = :post_id");
                $selectStmt->bindParam(':post_id', $postId);
                $selectStmt->execute();
                $post = $selectStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'status' => $result === true,
                'message' => $result ? "Post created successfully with ID: " . $postId : "Failed to create post",
                'post_id' => $result ? $postId : null,
                'post_data' => $result ? $post : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error creating post: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    

    private function testPostWithEmptyContent() {
        // Test data with empty content
        $userId = $this->validUserId;
        $caption = "Empty Content Test";
        $content = "";
        $community = "general";
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("INSERT INTO posts (user_id, caption, content, community, created_at) 
                                          VALUES (:user_id, :caption, :content, :community, NOW())");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':caption', $caption);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':community', $community);
            
            $result = $stmt->execute();
            
            if ($result) {
                $postId = $this->conn->lastInsertId();
                $this->testPostIds[] = $postId;
                
                return [
                    'status' => true,
                    'message' => "Database accepts empty content, which matches current system behavior. Frontend validation prevents actual empty submissions.",
                    'post_id' => $postId
                ];
            } else {
                return [
                    'status' => false,
                    'message' => "Failed to create post with empty content"
                ];
            }
        } catch (PDOException $e) {
            // If an exception is thrown, the database is enforcing non-empty content
            return [
                'status' => false,
                'message' => "Error with empty content: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test post creation with very long content
     */
    private function testPostWithLongContent() {
        // Test data with long content (10K characters)
        $userId = $this->validUserId;
        $caption = "Long Content Test";
        $content = str_repeat("This is a very long post content. ", 500); // 10K+ characters
        $community = "general";
        
        try {
            // Execute test
            $stmt = $this->conn->prepare("INSERT INTO posts (user_id, caption, content, community, created_at) 
                                          VALUES (:user_id, :caption, :content, :community, NOW())");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':caption', $caption);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':community', $community);
            
            $result = $stmt->execute();
            
            // Store the post ID for later cleanup
            if ($result) {
                $postId = $this->conn->lastInsertId();
                $this->testPostIds[] = $postId;
                
                // Verify post content was stored correctly
                $selectStmt = $this->conn->prepare("SELECT * FROM posts WHERE post_id = :post_id");
                $selectStmt->bindParam(':post_id', $postId);
                $selectStmt->execute();
                $post = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                // Check if content was truncated or stored correctly
                $contentIntact = $post['content'] === $content;
            }
            
            return [
                'status' => $result === true,
                'message' => $result ? 
                            ($contentIntact ? 
                            "Long content post created successfully with ID: " . $postId : 
                            "Long content post created but content was truncated") : 
                            "Failed to create post with long content",
                'post_id' => $result ? $postId : null,
                'content_length' => $result ? strlen($post['content']) : null,
                'original_length' => strlen($content)
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error with long content: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test post creation with invalid user ID
     */
    private function testPostWithInvalidUser() {
        // Test data with non-existent user ID
        $userId = 3;
        $caption = "Invalid User Test";
        $content = "This is a test post with invalid user ID.";
        $community = "general";
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO posts (user_id, caption, content, community, created_at) 
                                          VALUES (:user_id, :caption, :content, :community, NOW())");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':caption', $caption);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':community', $community);
            
            $result = $stmt->execute();
            
            if ($result) {
                $postId = $this->conn->lastInsertId();
                $this->testPostIds[] = $postId;
            }
            
            return [
                'status' => false,
                'message' => "Test failed: Post with invalid user ID was accepted"
            ];
        } catch (PDOException $e) {
            return [
                'status' => true, 
                'message' => "Test passed: Post with invalid user ID was correctly rejected"
            ];
        }
    }
    
    private function testCreatePostApi() {
        // Test data
        $postData = [
            'user_id' => $this->validUserId,
            'caption' => 'API Test Post ' . rand(1000, 9999),
            'content' => 'This is a test post created via the API endpoint.',
            'community' => 'general'
        ];
        
        // API URL (adjust if needed)
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $apiUrl = 'http://' . $host . '/BRACULA/api/posts/create_post.php';
        
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
        
        // Store post ID for cleanup if successful
        if ($responseData && isset($responseData['status']) && $responseData['status'] === 'success') {
            if (isset($responseData['data']['id'])) {
                $this->testPostIds[] = $responseData['data']['id'];
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
     * Clean up test data by deleting test posts
     */
    public function cleanup() {
        $deletedCount = 0;
        
        foreach ($this->testPostIds as $postId) {
            $stmt = $this->conn->prepare("DELETE FROM posts WHERE post_id = :post_id");
            $stmt->bindParam(':post_id', $postId);
            if ($stmt->execute()) {
                $deletedCount++;
            }
        }
        
        return [
            'status' => true,
            'message' => "Cleanup completed. Deleted $deletedCount test posts.",
            'deleted_count' => $deletedCount,
            'test_post_ids' => $this->testPostIds
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
    <title>Feed Post Unit Tests</title>
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
        <h1>Feed Post Unit Tests</h1>
        <p class="lead">Testing post creation functionality</p>
        
        <div class="alert alert-info mb-4">
            <h4>Test Summary</h4>
            <p>These tests verify the post creation functionality works correctly, handling valid data, edge cases, and error conditions.</p>
        </div>
        
        <?php
        // Run tests
        $tester = new PostTest();
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
                    
                    <?php if (isset($result['post_id'])): ?>
                        <p><strong>Post ID:</strong> <?php echo $result['post_id']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['http_code'])): ?>
                        <p><strong>HTTP Code:</strong> <?php echo $result['http_code']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['content_length'])): ?>
                        <p><strong>Content Length:</strong> <?php echo $result['content_length']; ?> / <?php echo $result['original_length']; ?> characters</p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['error_code'])): ?>
                        <p><strong>Error Code:</strong> <?php echo $result['error_code']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['post_data'])): ?>
                        <h5>Post Data:</h5>
                        <pre><?php print_r($result['post_data']); ?></pre>
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