<?php
require_once __DIR__ . '/setup_test_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Post.php';

class PostTest {
    private $db;
    private $post;
    private $testPostId;
    
    public function __construct() {
        // Get database connection
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Create Post instance
        $this->post = new Post($this->db);
        
        echo "<h2>Post Model Test</h2>";
    }
    
    public function runTests() {
        $this->testCreatePost();
        $this->testGetPost();
        $this->testVotePost();
        $this->testSearchPost();
    }
    
    public function testCreatePost() {
        echo "<h3>Test 1: Create Post</h3>";
        
        // Set test data
        $this->post->user_id = 1; // Assuming user ID 1 exists
        $this->post->title = "Test Post Title " . rand(1000, 9999);
        $this->post->content = "This is a test post content created for testing.";
        $this->post->category = "general";
        
        // Execute test
        $result = $this->post->create();
        
        // Store the post ID for later tests
        if ($result) {
            $this->testPostId = $this->post->post_id;
        }
        
        // Assert result
        $this->assert($result === true, "Post creation should return true");
        $this->assert(!empty($this->post->post_id), "Post ID should be assigned");
        
        echo "<p>Created test post with ID: " . $this->post->post_id . "</p>";
    }
    
    public function testGetPost() {
        echo "<h3>Test 2: Get Post</h3>";
        
        // Skip if no test post was created
        if (empty($this->testPostId)) {
            echo "<p class='text-danger'>Skipping test: No test post available</p>";
            return;
        }
        
        // Execute test
        $post = $this->post->getById($this->testPostId);
        
        // Assert results - THIS TEST CAN BE BROKEN DURING DEMO
        $this->assert(!empty($post), "Post should be retrieved");
        $this->assert($post['post_id'] == $this->testPostId, "Retrieved post ID should match");
        $this->assert($post['title'] == $this->post->title, "Post title should match");
        
        echo "<p>Successfully retrieved post with title: " . $post['title'] . "</p>";
    }
    
    public function testVotePost() {
        echo "<h3>Test 3: Vote on Post</h3>";
        
        // Skip if no test post was created
        if (empty($this->testPostId)) {
            echo "<p class='text-danger'>Skipping test: No test post available</p>";
            return;
        }
        
        // Set up test
        $this->post->post_id = $this->testPostId;
        $user_id = 2; // Assuming this is a different user than the post creator
        
        // Execute test
        $result = $this->post->vote('up', $user_id);
        
        // Assert result
        $this->assert($result === true, "Vote operation should succeed");
        
        // Verify vote was recorded by getting updated post
        $post = $this->post->getById($this->testPostId);
        $this->assert($post['votes'] > 0, "Post votes should be positive after upvote");
        
        echo "<p>Successfully voted on post, new vote count: " . $post['votes'] . "</p>";
    }
    
    public function testSearchPost() {
        echo "<h3>Test 4: Search Posts</h3>";
        
        // Skip if no test post was created
        if (empty($this->testPostId)) {
            echo "<p class='text-danger'>Skipping test: No test post available</p>";
            return;
        }
        
        // Search for the keyword in the test post title
        $keyword = "Test Post";
        $results = $this->post->search($keyword);
        
        // Assert results
        $found = false;
        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
            if ($row['post_id'] == $this->testPostId) {
                $found = true;
                break;
            }
        }
        
        $this->assert($found, "The test post should be found in search results");
        
        echo "<p>Search successful, found test post in results.</p>";
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
    <title>Feed Feature Unit Test</title>
    
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
                        <a class="nav-link active" href="/BRACULA/test/test_feed_breakable.php">Feed Unit Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_rideshare_breakable.php">Rideshare Unit Tests</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <h1>Feed Feature Unit Tests</h1>
    <p>This page runs automated unit tests on the Post model.</p>
    <p><strong>How to demonstrate a failing test:</strong> Edit the <code>testGetPost()</code> method in this file to check for a wrong post ID or title.</p>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test Results</h3>
        </div>
        <div class="card-body">
            <?php
            $postTest = new PostTest();
            $postTest->runTests();
            ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">MVC Analysis</h3>
        </div>
        <div class="card-body">
            <h4>MVC Implementation Analysis</h4>
            <p>The feed feature follows the MVC pattern properly:</p>
            <ul>
                <li><strong>Model (Post.php):</strong> Contains all the data logic and database interactions</li>
                <li><strong>Controllers (api/posts/*):</strong> Handle user input and coordinate between models and views</li>
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