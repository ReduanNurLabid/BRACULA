<?php
require_once __DIR__ . '/setup_test_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Ride.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create model instances
$post = new Post($db);
$ride = new Ride($db);

// Test results storage
$results = [];

// Flag to determine if tests should intentionally break
$should_break = isset($_GET['break']) && $_GET['break'] == 'true';

// Simple test function
function run_test($name, $test_function, $breakable_function = null) {
    global $results, $should_break;
    try {
        $start_time = microtime(true);
        
        // If in break mode and a breakable function is provided, use it instead
        $result = ($should_break && $breakable_function !== null) 
            ? $breakable_function() 
            : $test_function();
            
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2); // in ms
        
        $results[] = [
            'name' => $name,
            'success' => $result,
            'time' => $execution_time,
            'breakable' => ($breakable_function !== null)
        ];
        
        return $result;
    } catch (Exception $e) {
        $results[] = [
            'name' => $name,
            'success' => false,
            'error' => $e->getMessage(),
            'breakable' => ($breakable_function !== null)
        ];
        return false;
    }
}

// Create test data
$test_post_id = null;
$test_ride_id = null;

// Test 1: Create Post (Not breakable)
run_test('Create Post', function() use ($post, &$test_post_id) {
    $post->user_id = 1;
    $post->title = "Breakable Test Post " . rand(1000, 9999);
    $post->content = "This is a test post that can be used in breakable tests.";
    $post->category = "general";
    
    $result = $post->create();
    if ($result) {
        $test_post_id = $post->post_id;
        return true;
    }
    return false;
});

// Test 2: Get Post (Breakable)
if ($test_post_id) {
    run_test('Get Post', 
        // Normal function
        function() use ($post, $test_post_id) {
            $retrieved_post = $post->getById($test_post_id);
            return !empty($retrieved_post) && $retrieved_post['post_id'] == $test_post_id;
        },
        // Breakable function - checks for wrong ID
        function() use ($post, $test_post_id) {
            $retrieved_post = $post->getById($test_post_id);
            // This will fail because we check for a different ID than what was retrieved
            return !empty($retrieved_post) && $retrieved_post['post_id'] == ($test_post_id + 1000);
        }
    );
}

// Test 3: Create Ride (Not breakable)
run_test('Create Ride', function() use ($ride, &$test_ride_id) {
    $ride->user_id = 1;
    $ride->from_location = "Breakable Test Origin " . rand(1000, 9999);
    $ride->to_location = "Breakable Test Destination " . rand(1000, 9999);
    $ride->departure_time = date('Y-m-d H:i:s', strtotime('+1 day'));
    $ride->seats_available = 3;
    $ride->price = 25.00;
    $ride->vehicle_description = "Test Vehicle";
    $ride->notes = "Test notes for breakable testing";
    
    $result = $ride->create();
    if ($result) {
        $test_ride_id = $ride->ride_id;
        return true;
    }
    return false;
});

// Test 4: Get Ride (Breakable)
if ($test_ride_id) {
    run_test('Get Ride', 
        // Normal function
        function() use ($ride, $test_ride_id) {
            $retrieved_ride = $ride->getById($test_ride_id);
            return !empty($retrieved_ride) && $retrieved_ride['ride_id'] == $test_ride_id;
        },
        // Breakable function - checks for wrong location
        function() use ($ride, $test_ride_id) {
            $retrieved_ride = $ride->getById($test_ride_id);
            // This will fail because we check for a different origin than what was stored
            return !empty($retrieved_ride) && $retrieved_ride['from_location'] == "Wrong Origin";
        }
    );
}

// Calculate overall result
$total_tests = count($results);
$passed_tests = 0;
$breakable_tests = 0;

foreach ($results as $result) {
    if ($result['success']) {
        $passed_tests++;
    }
    if (isset($result['breakable']) && $result['breakable']) {
        $breakable_tests++;
    }
}

$success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breakable BRACULA Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .test-result { 
            padding: 10px; 
            margin-bottom: 10px; 
            border-radius: 4px; 
        }
        .success { background-color: #d4edda; }
        .failure { background-color: #f8d7da; }
        .breakable { border-left: 5px solid #fd7e14; }
        .summary { 
            font-size: 1.5rem; 
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .code-block {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">BRACULA Breakable Test Demo</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h3>Test Mode</h3>
            </div>
            <div class="card-body">
                <p>Current mode: <strong><?php echo $should_break ? 'BREAK MODE (showing intentional failures)' : 'NORMAL MODE (all tests should pass)'; ?></strong></p>
                
                <div class="d-grid gap-2 d-md-flex">
                    <a href="?break=true" class="btn btn-danger">Switch to Break Mode</a>
                    <a href="?break=false" class="btn btn-success">Switch to Normal Mode</a>
                </div>
            </div>
        </div>
        
        <div class="summary <?php echo ($success_rate >= 70) ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
            Test Results: <?php echo $passed_tests; ?> / <?php echo $total_tests; ?> tests passed (<?php echo $success_rate; ?>%)
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3>Test Results</h3>
            </div>
            <div class="card-body">
                <?php foreach ($results as $result): ?>
                    <div class="test-result <?php echo $result['success'] ? 'success' : 'failure'; ?> <?php echo (isset($result['breakable']) && $result['breakable']) ? 'breakable' : ''; ?>">
                        <h4>
                            <?php echo $result['name']; ?>: <?php echo $result['success'] ? 'PASS' : 'FAIL'; ?>
                            <?php if (isset($result['breakable']) && $result['breakable']): ?>
                                <span class="badge bg-warning">Breakable</span>
                            <?php endif; ?>
                        </h4>
                        <?php if (isset($result['time'])): ?>
                            <p>Execution time: <?php echo $result['time']; ?> ms</p>
                        <?php endif; ?>
                        <?php if (isset($result['error'])): ?>
                            <p>Error: <?php echo $result['error']; ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h3>How to Demonstrate Test Failures</h3>
            </div>
            <div class="card-body">
                <p>This page showcases how tests can be designed to pass or fail intentionally.</p>
                
                <h4>Example of a breakable test:</h4>
                <div class="code-block">
<pre>
run_test('Get Post', 
    // Normal function
    function() use ($post, $test_post_id) {
        $retrieved_post = $post->getById($test_post_id);
        return !empty($retrieved_post) && $retrieved_post['post_id'] == $test_post_id; // Correct check
    },
    // Breakable function - this runs when in "break mode"
    function() use ($post, $test_post_id) {
        $retrieved_post = $post->getById($test_post_id);
        return !empty($retrieved_post) && $retrieved_post['post_id'] == ($test_post_id + 1000); // Wrong ID check
    }
);
</pre>
                </div>
                
                <p class="alert alert-warning">
                    <strong>For your video demonstration:</strong> Show how the tests pass in normal mode, then switch to break mode to demonstrate how tests can fail with improper assertions.
                </p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h3>MVC Implementation</h3>
            </div>
            <div class="card-body">
                <p>The BRACULA project follows the MVC (Model-View-Controller) architecture pattern:</p>
                
                <h4>Models:</h4>
                <ul>
                    <li><strong>Post.php</strong>: Encapsulates all data operations for the feed feature</li>
                    <li><strong>Ride.php</strong>: Encapsulates all data operations for the rideshare feature</li>
                </ul>
                
                <h4>Controllers:</h4>
                <ul>
                    <li><strong>API endpoints</strong>: Handle request processing and coordinate between models and views</li>
                    <li>Example: <code>api/posts/create_post.php</code> creates posts by using the Post model</li>
                </ul>
                
                <h4>Views:</h4>
                <ul>
                    <li><strong>HTML templates</strong>: Present information to the user</li>
                    <li>Example: <code>html/feed.html</code> displays the feed using data returned from controllers</li>
                </ul>
                
                <p>This separation of concerns makes the code more maintainable, reusable, and testable.</p>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-primary">Back to Test Home</a>
            <button class="btn btn-success" onclick="window.location.reload()">Run Tests Again</button>
        </div>
    </div>
</body>
</html> 