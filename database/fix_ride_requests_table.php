<?php
// Fix ride_requests table
require_once __DIR__ . '/../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Check if ride_requests table exists
    $checkTableQuery = "SHOW TABLES LIKE 'ride_requests'";
    $checkTableStmt = $conn->prepare($checkTableQuery);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() === 0) {
        // Table doesn't exist, create it
        echo "Creating ride_requests table...<br>";
        $createTableQuery = "
        CREATE TABLE IF NOT EXISTS ride_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            ride_id INT NOT NULL,
            user_id INT NOT NULL,
            seats INT NOT NULL,
            pickup VARCHAR(255) NOT NULL,
            notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        $conn->exec($createTableQuery);
        echo "ride_requests table created successfully!<br>";
    } else {
        echo "ride_requests table already exists.<br>";
        
        // Check if 'seats' column exists
        $checkColumnQuery = "SHOW COLUMNS FROM ride_requests LIKE 'seats'";
        $checkColumnStmt = $conn->prepare($checkColumnQuery);
        $checkColumnStmt->execute();
        
        if ($checkColumnStmt->rowCount() === 0) {
            // Column doesn't exist, add it
            echo "Adding seats column to ride_requests table...<br>";
            $alterQuery = "ALTER TABLE ride_requests ADD COLUMN seats INT NOT NULL DEFAULT 1";
            $conn->exec($alterQuery);
            echo "seats column added successfully!<br>";
        } else {
            echo "seats column already exists in ride_requests table.<br>";
        }
    }
    
    // Show current table structure
    $describeQuery = "DESCRIBE ride_requests";
    $describeStmt = $conn->prepare($describeQuery);
    $describeStmt->execute();
    
    echo "<h3>Current structure of ride_requests table:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $describeStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>Table fixed successfully!</p>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 