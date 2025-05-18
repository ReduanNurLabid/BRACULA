<?php
require_once __DIR__ . '/setup_test_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Post.php';

/**
 * Unit Testing for Post Creation
 * 
 * This file demonstrates how to write unit tests for the Post creation feature.
 */
class PostCreationTest {
    private $db;
    private $post;
    private $testPostIds = [];
    private $results = [];
    
    /**
     * Set up the test environment by initializing database connection and Post model
     */
    public function __construct() {
        // Get database connection
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Create Post instance
        $this->post = new Post($this->db);
    }
    
    /**
     * Run all the test cases
     */
    public function runTests() {
        $this->results['testBasicPostCreation'] = $this->testBasicPostCreation();
        $this->results['testPostWithEmptyTitle'] = $this->testPostWithEmptyTitle();
        $this->results['testPostWithLongTitle'] = $this->testPostWithLongTitle();
        $this->results['testPostWithDifferentCategories'] = $this->testPostWithDifferentCategories();
        $this->results['testPostVoteAfterCreation'] = $this->testPostVoteAfterCreation();
        
        // Clean up test data
        $this->cleanUp();
        
        return $this->results;
    }
    
    /**
     * Test basic post creation with valid data
     */
    private function testBasicPostCreation() {
        // Set test data
        $this->post->user_id = 1; // Assuming user ID 1 exists
        $this->post->title = "Test Post Title " . rand(1000, 9999);
        $this->post->content = "";
        $this->post->category = "general";
        
        // Execute test
        $result = $this->post->create();
        
        // Store the post ID for later cleanup
        if ($result) {
            $this->testPostIds[] = $this->post->post_id;
        }
        
        // Verify post was created
        $createdPost = null;
        if ($result) {
            $createdPost = $this->post->getById($this->post->post_id);
        }
        
        return [
            'status' => $result === true,
            'message' => $result ? "Post created successfully with ID: " . $this->post->post_id : "Failed to create post",
            'post_id' => $result ? $this->post->post_id : null,
            'post_data' => $createdPost
        ];
    }
    
    /**
     * Test post creation with empty title (should fail validation)
     */
    private function testPostWithEmptyTitle() {
        // Set test data with empty title
        $this->post->user_id = 1;
        $this->post->title = "";
        $this->post->content = "This is a test post with empty title.";
        $this->post->category = "general";
        
        // Execute test - expect this to fail
        $result = $this->post->create();
        
        // Store the post ID if somehow created
        if ($result) {
            $this->testPostIds[] = $this->post->post_id;
        }
        
        return [
            'status' => $result === false, // We expect this to fail, so true means test passed
            'message' => !$result ? "Test passed: Empty title correctly rejected" : "Test failed: Empty title was accepted"
        ];
    }
    
    /**
     * Test post creation with very long title (edge case testing)
     */
    private function testPostWithLongTitle() {
        // Set test data with long title (255+ characters)
        $longTitle = str_repeat("Very long title test ", 15); // Around 300 characters
        
        $this->post->user_id = 1;
        $this->post->title = $longTitle;
        $this->post->content = "This is a test post with very long title.";
        $this->post->category = "general";
        
        // Execute test
        $result = $this->post->create();
        
        // Store the post ID for later cleanup
        if ($result) {
            $this->testPostIds[] = $this->post->post_id;
            $createdPost = $this->post->getById($this->post->post_id);
            $titleLength = strlen($createdPost['title']);
        }
        
        return [
            'status' => true, // This test is informative only
            'message' => $result ? "Post with long title created, stored length: " . $titleLength : "Failed to create post with long title",
            'post_id' => $result ? $this->post->post_id : null
        ];
    }
    
    /**
     * Test post creation with different categories
     */
    private function testPostWithDifferentCategories() {
        $categories = ['general', 'academic', 'events', 'question'];
        $results = [];
        
        foreach ($categories as $category) {
            // Set test data
            $this->post->user_id = 1;
            $this->post->title = "Test Post in $category category " . rand(100, 999);
            $this->post->content = "This is a test post in the $category category.";
            $this->post->category = $category;
            
            // Execute test
            $result = $this->post->create();
            
            // Store the post ID for later cleanup
            if ($result) {
                $this->testPostIds[] = $this->post->post_id;
            }
            
            $results[$category] = [
                'status' => $result === true,
                'message' => $result ? "Post created in $category category with ID: " . $this->post->post_id : "Failed to create post in $category category",
                'post_id' => $result ? $this->post->post_id : null
            ];
        }
        
        $allSucceeded = !in_array(false, array_column($results, 'status'));
        
        return [
            'status' => $allSucceeded,
            'message' => $allSucceeded ? "Successfully created posts in all categories" : "Failed to create posts in some categories",
            'category_results' => $results
        ];
    }
    
    /**
     * Test voting on a post after creation
     */
    private function testPostVoteAfterCreation() {
        // First create a test post
        $this->post->user_id = 1;
        $this->post->title = "Test Post for Voting " . rand(1000, 9999);
        $this->post->content = "This is a test post to test voting functionality.";
        $this->post->category = "general";
        
        $createResult = $this->post->create();
        
        if (!$createResult) {
            return [
                'status' => false,
                'message' => "Failed to create post for vote testing"
            ];
        }
        
        $postId = $this->post->post_id;
        $this->testPostIds[] = $postId;
        
        // Vote on the post
        $this->post->post_id = $postId;
        $voteResult = $this->post->vote('up', 2); // Use a different user ID for voting
        
        // Get the post after voting
        $postAfterVote = $this->post->getById($postId);
        
        return [
            'status' => $voteResult === true && $postAfterVote['votes'] > 0,
            'message' => ($voteResult && $postAfterVote['votes'] > 0) ? 
                "Successfully voted on post, vote count: " . $postAfterVote['votes'] : 
                "Failed to vote on post or vote count didn't update",
            'post_id' => $postId,
            'vote_count' => $postAfterVote['votes']
        ];
    }
    
    /**
     * Clean up test data by deleting created posts
     */
    private function cleanUp() {
        foreach ($this->testPostIds as $postId) {
            $this->post->post_id = $postId;
            $this->post->user_id = 1; // Assuming posts were created with user_id 1
            $this->post->delete();
        }
    }
    
    /**
     * Helper function for displaying results in a user-friendly way
     */
    public function displayResults() {
        foreach ($this->results as $testName => $result) {
            $statusClass = $result['status'] ? 'success' : 'danger';
            $statusIcon = $result['status'] ? '✓' : '✗';
            
            echo "<div class='test-result'>";
            echo "<h4>$testName</h4>";
            echo "<p class='text-$statusClass'>$statusIcon {$result['message']}</p>";
            
            // Display additional details if available
            if (isset($result['post_data']) && is_array($result['post_data'])) {
                echo "<div class='post-details'>";
                echo "<h5>Post Details:</h5>";
                echo "<pre>" . json_encode($result['post_data'], JSON_PRETTY_PRINT) . "</pre>";
                echo "</div>";
            }
            
            if (isset($result['category_results']) && is_array($result['category_results'])) {
                echo "<div class='category-results'>";
                echo "<h5>Category Test Results:</h5>";
                echo "<ul>";
                foreach ($result['category_results'] as $category => $catResult) {
                    $catStatusClass = $catResult['status'] ? 'success' : 'danger';
                    $catStatusIcon = $catResult['status'] ? '✓' : '✗';
                    echo "<li class='text-$catStatusClass'>$catStatusIcon $category: {$catResult['message']}</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            
            echo "</div>";
        }
    }
}

// Initialize test class
$postTest = new PostCreationTest();

// For use with API calls
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    $results = $postTest->runTests();
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Post Creation Unit Tests</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .test-result {
            margin-bottom: 2rem;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
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
        .post-details, .category-results {
            margin-top: 1rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
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
                        <a class="nav-link active" href="/BRACULA/test/feed_post_test.php">Feed Post Creation Tests</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Feed Post Creation Unit Tests</h1>
        <p>This page demonstrates comprehensive unit testing for the post creation functionality.</p>
        <p>These tests verify:</p>
        <ul>
            <li>Basic post creation with valid data</li>
            <li>Validation handling with empty titles</li>
            <li>Edge case testing with very long titles</li>
            <li>Post creation with different categories</li>
            <li>Post voting functionality after creation</li>
        </ul>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Test Results</h3>
            </div>
            <div class="card-body">
                <?php
                // Run tests and display results
                $postTest->runTests();
                $postTest->displayResults();
                ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h3 class="mb-0">Understanding Unit Testing</h3>
            </div>
            <div class="card-body">
                <h4>What is Unit Testing?</h4>
                <p>Unit testing is a software testing method where individual units or components of a software are tested in isolation. The purpose is to validate that each unit of the software performs as designed.</p>
                
                <h4>Key Unit Testing Concepts Demonstrated</h4>
                <ul>
                    <li><strong>Setup/Teardown:</strong> Test environment is set up before tests and cleaned up after</li>
                    <li><strong>Isolation:</strong> Each test function tests one specific aspect of functionality</li>
                    <li><strong>Assertions:</strong> Tests verify expected outcomes through assertions</li>
                    <li><strong>Edge Cases:</strong> Testing boundary conditions (like very long titles)</li>
                    <li><strong>Expected Failures:</strong> Testing scenarios expected to fail (validation tests)</li>
                </ul>
                
                <h4>PHP Unit Testing Best Practices</h4>
                <ul>
                    <li>Write tests that are independent of each other</li>
                    <li>Clean up test data to avoid affecting other tests</li>
                    <li>Test both valid and invalid inputs</li>
                    <li>Make tests repeatable and deterministic</li>
                    <li>Consider using a testing framework like PHPUnit for larger projects</li>
                </ul>
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