<?php
// Setup rideshare tables
require_once __DIR__ . '/../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // First, check if there are any foreign key constraints
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Create rides table
    $sql = "CREATE TABLE IF NOT EXISTS rides (
        ride_id INT PRIMARY KEY AUTO_INCREMENT,
        driver_id INT NOT NULL,
        departure VARCHAR(255) NOT NULL,
        destination VARCHAR(255) NOT NULL,
        departure_time DATETIME NOT NULL,
        available_seats INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2),
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $conn->exec($sql);
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "Rides table created successfully";
} catch(PDOException $e) {
    // Re-enable foreign key checks even if there's an error
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Error setting up rideshare tables: " . $e->getMessage();
}
?> 