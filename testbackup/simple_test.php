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

// Simple test function
function run_test($name, $test_function) {
    global $results;
    try {
        $start_time = microtime(true);
        $result = $test_function();
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2); // in ms
        
        $results[] = [
            'name' => $name,
            'success' => $result,
            'time' => $execution_time
        ];
        
        return $result;
    } catch (Exception $e) {
        $results[] = [
            'name' => $name,
            'success' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

// ====== Post Model Tests ======
$test_post_id = null;

// Test 1: Create Post
run_test('Create Post', function() use ($post, &$test_post_id) {
    $post->user_id = 1;
    $post->title = "Simple Test Post " . rand(1000, 9999);
    $post->content = "This is a simple test post.";
    $post->category = "general";
    
    $result = $post->create();
    if ($result) {
        $test_post_id = $post->post_id;
        return true;
    }
    return false;
});

// Test 2: Get Post
if ($test_post_id) {
    run_test('Get Post', function() use ($post, $test_post_id) {
        $retrieved_post = $post->getById($test_post_id);
        return !empty($retrieved_post) && $retrieved_post['post_id'] == $test_post_id;
    });
}

// Test 3: Vote Post
if ($test_post_id) {
    run_test('Vote Post', function() use ($post, $test_post_id) {
        $post->post_id = $test_post_id;
        return $post->vote('up', 2); // User ID 2 votes up
    });
}

// Test 4: Search Post
if ($test_post_id) {
    run_test('Search Post', function() use ($post, $test_post_id) {
        $keyword = "Simple Test";
        $results = $post->search($keyword);
        $found = false;
        
        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
            if ($row['post_id'] == $test_post_id) {
                $found = true;
                break;
            }
        }
        
        return $found;
    });
}

// ====== Ride Model Tests ======
$test_ride_id = null;

// Test 5: Create Ride
run_test('Create Ride', function() use ($ride, &$test_ride_id) {
    $ride->user_id = 1;
    $ride->from_location = "Simple Test Origin " . rand(1000, 9999);
    $ride->to_location = "Simple Test Destination " . rand(1000, 9999);
    $ride->departure_time = date('Y-m-d H:i:s', strtotime('+1 day'));
    $ride->seats_available = 3;
    $ride->price = 25.00;
    $ride->vehicle_description = "Test Vehicle";
    $ride->notes = "Simple test notes";
    
    $result = $ride->create();
    if ($result) {
        $test_ride_id = $ride->ride_id;
        return true;
    }
    return false;
});

// Test 6: Get Ride
if ($test_ride_id) {
    run_test('Get Ride', function() use ($ride, $test_ride_id) {
        $retrieved_ride = $ride->getById($test_ride_id);
        return !empty($retrieved_ride) && $retrieved_ride['ride_id'] == $test_ride_id;
    });
}

// Test 7: Change Ride Status
if ($test_ride_id) {
    run_test('Change Ride Status', function() use ($ride, $test_ride_id) {
        $ride->ride_id = $test_ride_id;
        $ride->user_id = 1;
        return $ride->changeStatus('completed');
    });
}

// Test 8: Filter Rides
if ($test_ride_id) {
    run_test('Filter Rides', function() use ($ride, $test_ride_id) {
        $filters = [
            'from_location' => 'Simple Test'
        ];
        
        $filtered_rides = $ride->getAll(10, 0, $filters);
        $found = false;
        
        while ($row = $filtered_rides->fetch(PDO::FETCH_ASSOC)) {
            if ($row['ride_id'] == $test_ride_id) {
                $found = true;
                break;
            }
        }
        
        return $found;
    });
}

// Calculate overall result
$total_tests = count($results);
$passed_tests = 0;

foreach ($results as $result) {
    if ($result['success']) {
        $passed_tests++;
    }
}

$success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple BRACULA Test</title>
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
        .summary { 
            font-size: 1.5rem; 
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">BRACULA Simple Test Suite</h1>
        
        <div class="summary <?php echo ($success_rate >= 70) ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
            Test Results: <?php echo $passed_tests; ?> / <?php echo $total_tests; ?> tests passed (<?php echo $success_rate; ?>%)
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3>Test Results</h3>
            </div>
            <div class="card-body">
                <?php foreach ($results as $result): ?>
                    <div class="test-result <?php echo $result['success'] ? 'success' : 'failure'; ?>">
                        <h4><?php echo $result['name']; ?>: <?php echo $result['success'] ? 'PASS' : 'FAIL'; ?></h4>
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
                <h3>MVC Architecture Summary</h3>
            </div>
            <div class="card-body">
                <h4>Models</h4>
                <ul>
                    <li><strong>Post.php</strong>: Handles feed post data and operations</li>
                    <li><strong>Ride.php</strong>: Handles rideshare data and operations</li>
                </ul>
                
                <h4>Views</h4>
                <ul>
                    <li>HTML templates that display data to users</li>
                    <li>JavaScript that handles user interactions</li>
                </ul>
                
                <h4>Controllers</h4>
                <ul>
                    <li>API endpoints that process requests</li>
                    <li>Handle data flow between models and views</li>
                </ul>
                
                <h4>Benefits of MVC in BRACULA</h4>
                <ul>
                    <li>Separation of concerns for better code organization</li>
                    <li>Easier testing and maintenance</li>
                    <li>Ability to change one component without affecting others</li>
                </ul>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-primary">Back to Test Home</a>
            <button class="btn btn-success" onclick="window.location.reload()">Run Tests Again</button>
        </div>
    </div>
</body>
</html> 