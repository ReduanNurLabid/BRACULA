<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS user_activities (
        activity_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        activity_type ENUM('post', 'comment', 'share', 'like') NOT NULL,
        content_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";

    if ($db->exec($sql) !== false) {
        echo "User activities table created successfully\n";
    } else {
        echo "Error creating table: " . print_r($db->errorInfo(), true) . "\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 