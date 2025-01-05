<?php
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // First, check if there are any foreign key constraints
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    // Drop existing table if it exists
    $conn->exec("DROP TABLE IF EXISTS event_registrations");

    // Create event_registrations table
    $sql = "CREATE TABLE event_registrations (
        registration_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM('registered', 'cancelled') DEFAULT 'registered',
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_registration (event_id, user_id)
    ) ENGINE=InnoDB";

    $conn->exec($sql);

    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Event registrations table created successfully";

} catch(PDOException $e) {
    // Re-enable foreign key checks even if there's an error
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Error creating event registrations table: " . $e->getMessage();
}
?> 