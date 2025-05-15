<?php
// Include database connection
require_once __DIR__ . '/../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Check if the table exists first
    $tableExists = $conn->query("SHOW TABLES LIKE 'ride_requests'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table with the seats column
        $sql = "CREATE TABLE ride_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            ride_id INT NOT NULL,
            user_id INT NOT NULL,
            seats INT NOT NULL DEFAULT 1,
            pickup VARCHAR(255) NOT NULL,
            notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
        echo "Table created with seats column";
    } else {
        // Check if seats column exists
        $columnExists = $conn->query("SHOW COLUMNS FROM ride_requests LIKE 'seats'")->rowCount() > 0;
        
        if (!$columnExists) {
            // Add the seats column
            $sql = "ALTER TABLE ride_requests ADD COLUMN seats INT NOT NULL DEFAULT 1";
            $conn->exec($sql);
            echo "Seats column added successfully";
        } else {
            echo "Seats column already exists";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 