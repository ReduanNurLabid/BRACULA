# Feature Demonstration Guide for Video Presentation

## Overview
This guide will help you demonstrate the Feed and Rideshare features in your video presentation, including showing how the MVC architecture is implemented and how to demonstrate test failures.

## 1. Feed Feature Demonstration

### 1.1 UI Demonstration (2-3 minutes)
Start by showing the feed functionality in the user interface:

1. Navigate to the main feed page
2. Demonstrate creating a new post
3. Show the post appearing in the feed
4. Demonstrate upvoting/downvoting posts
5. Show the comment functionality
6. Demonstrate searching for posts

### 1.2 Controller Explanation (1-2 minutes)
Explain how the controller portion works:

1. Open the `api/posts/create_post.php` file
2. Highlight the key sections:
   - Input validation
   - Model instantiation
   - Calling the model's methods
   - Response formatting

```php
// Example code to highlight
$post = new Post($db);
$post->user_id = $user_id;
$post->title = $data->title;
$post->content = $data->content;
$post->category = $data->category;

// Create post
if ($post->create()) {
    // Format response
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Post created successfully",
        "post_id" => $post->post_id
    ]);
}
```

3. Explain the MVC flow:
   - Controller receives the request
   - Controller processes input
   - Controller calls model methods
   - Model handles database operations
   - Controller formats response for view

### 1.3 Running Tests (2 minutes)

#### Run the Feed Tests
1. Navigate to `http://localhost/BRACULA/test/test_feed_breakable.php`
2. Show each test running:
   - Post creation
   - Post retrieval
   - Post voting
   - Post searching
3. Explain how each test verifies a different aspect of the feature

#### Demonstrate a Failing Test
1. Open `test/test_feed_breakable.php` in your editor
2. Locate the `testGetPost()` method
3. Modify the assertion to make it fail:

```php
// Change this line
$this->assert($post['title'] == $this->post->title, "Post title should match");

// To this (will fail)
$this->assert($post['title'] == "Wrong Title", "Post title should match");
```

4. Refresh the test page and show the failing test
5. Explain what went wrong and how tests help catch issues

## 2. Rideshare Feature Demonstration

### 2.1 UI Demonstration (2-3 minutes)
Start by showing the rideshare functionality in the user interface:

1. Navigate to the rideshare section
2. Demonstrate creating a new ride offer
3. Show the ride appearing in the listing
4. Demonstrate filtering rides (by location, date)
5. Show the ride request process
6. Demonstrate ride status updates

### 2.2 Controller Explanation (1-2 minutes)
Explain how the controller portion works:

1. Open the `api/rides/rides.php` file
2. Highlight the key sections:
   - Request method handling (GET, POST, PUT)
   - Input validation
   - Model operations
   - Response generation

```php
// Example code to highlight
$ride = new Ride($db);
$ride->user_id = $user_id;
$ride->from_location = $data->from_location;
$ride->to_location = $data->to_location;
$ride->departure_time = $data->departure_time;
$ride->seats_available = $data->seats_available;
$ride->price = $data->price;
$ride->vehicle_description = $data->vehicle_description;
$ride->notes = $data->notes;

// Create ride
if ($ride->create()) {
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Ride created successfully",
        "ride_id" => $ride->ride_id
    ]);
}
```

3. Explain the MVC structure:
   - Model (`Ride.php`) handles business logic and data
   - Controller (`api/rides/rides.php`) processes requests
   - View (HTML templates) presents the UI

### 2.3 Running Tests (2 minutes)

#### Run the Rideshare Tests
1. Navigate to `http://localhost/BRACULA/test/test_rideshare_breakable.php`
2. Show each test running:
   - Ride creation
   - Ride retrieval
   - Ride status update
   - Ride filtering
3. Explain how these tests verify the functionality

#### Demonstrate a Failing Test
1. Open `test/test_rideshare_breakable.php` in your editor
2. Locate the `testGetRide()` method
3. Modify the assertion to make it fail:

```php
// Change this line
$this->assert($ride['from_location'] == "BRAC University", "Ride origin should match");

// To this (will fail)
$this->assert($ride['from_location'] == "Incorrect Location", "Ride origin should match");
```

4. Refresh the test page and show the failing test
5. Explain the importance of testing and how it helps ensure code quality

## 3. MVC Architecture Analysis (1-2 minutes)

### Explain the MVC Implementation
1. Highlight the clear separation of concerns:
   - Models (Post.php, Ride.php) handle data and business logic
   - Controllers in api/ folder process requests and coordinate
   - Views in html/ folder present data to users

2. Show the redirection mechanism:
   - Root API files (api/login.php) redirecting to implementation controllers (api/auth/login.php)
   - Explain how this creates clean URLs while maintaining code organization

3. Emphasize the benefits:
   - Maintainability: Changes in one area don't affect others
   - Testability: Each component can be tested in isolation
   - Scalability: New features can be added without disrupting existing ones

## 4. Conclusion (30 seconds)
Summarize what you've demonstrated:
1. The feed and rideshare features working through the UI
2. The controller implementation showing proper MVC architecture
3. Tests verifying the functionality works correctly
4. How to identify issues using tests

Mention that this approach ensures code quality and maintainability for the BRACULA project. 