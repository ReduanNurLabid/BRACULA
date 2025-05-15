<?php
// Include database connection
require_once '../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // SQL for creating the driver_reviews table
    $sql = "
    CREATE TABLE IF NOT EXISTS driver_reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        driver_id INT NOT NULL,
        user_id INT NOT NULL,
        ride_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE
    );
    
    CREATE INDEX IF NOT EXISTS idx_driver_reviews_driver_id ON driver_reviews(driver_id);
    CREATE INDEX IF NOT EXISTS idx_driver_reviews_user_id ON driver_reviews(user_id);
    CREATE INDEX IF NOT EXISTS idx_driver_reviews_ride_id ON driver_reviews(ride_id);
    ";
    
    // Execute SQL
    $conn->exec($sql);
    
    echo "Driver reviews table has been successfully created!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 