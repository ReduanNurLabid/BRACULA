<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Set REQUEST_METHOD for CLI
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Connect to database
    $dir = dirname(__FILE__);
    require_once $dir . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if saved_posts table exists
    $query = "SHOW TABLES LIKE 'saved_posts'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $tableExists = ($stmt->rowCount() > 0);

    // Create saved_posts table if it doesn't exist
    if (!$tableExists) {
        $sql = "CREATE TABLE saved_posts (
            id INT(11) NOT NULL AUTO_INCREMENT,
            post_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            saved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_save (post_id, user_id),
            FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";

        $stmt = $db->prepare($sql);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Saved posts table created successfully']);
        } else {
            throw new Exception('Error creating saved_posts table');
        }
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Saved posts table already exists']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?> 