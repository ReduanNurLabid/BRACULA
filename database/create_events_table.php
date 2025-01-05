<?php
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // First, check if there are any foreign key constraints
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    // Drop existing table if it exists
    $conn->exec("DROP TABLE IF EXISTS events");

    // Create events table
    $sql = "CREATE TABLE events (
        event_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        event_date DATE NOT NULL,
        location VARCHAR(255) NOT NULL,
        user_id INT NOT NULL,
        cover_image VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB";

    $conn->exec($sql);

    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Events table created successfully";

} catch(PDOException $e) {
    // Re-enable foreign key checks even if there's an error
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Error creating events table: " . $e->getMessage();
}
?> 