<?php
// Set appropriate content type
header('Content-Type: text/html; charset=utf-8');

// Include database connection
require_once 'config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

echo "<h1>Adding 'seats' Column to ride_requests Table</h1>";

try {
    // First check if the table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ride_requests'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p style='color:red'>Error: The ride_requests table does not exist!</p>";
        echo "<p>Creating the ride_requests table first...</p>";
        
        // Create the table
        $createSql = "CREATE TABLE IF NOT EXISTS ride_requests (
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
        
        $conn->exec($createSql);
        echo "<p style='color:green'>Table created successfully!</p>";
    } else {
        echo "<p>The ride_requests table exists.</p>";
        
        // Check if the 'seats' column exists
        $columnCheck = $conn->query("SHOW COLUMNS FROM ride_requests LIKE 'seats'");
        if ($columnCheck->rowCount() == 0) {
            echo "<p>The 'seats' column is missing. Adding it now...</p>";
            
            // Add the 'seats' column
            $alterSql = "ALTER TABLE ride_requests ADD COLUMN seats INT NOT NULL DEFAULT 1";
            $conn->exec($alterSql);
            
            echo "<p style='color:green'>The 'seats' column was added successfully!</p>";
        } else {
            echo "<p>The 'seats' column already exists in the ride_requests table.</p>";
        }
    }
    
    // Show the current table structure
    echo "<h2>Current Table Structure:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $columns = $conn->query("DESCRIBE ride_requests");
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p>You can now <a href='index.php'>return to the homepage</a> or <a href='javascript:history.back()'>go back</a>.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database Error: " . $e->getMessage() . "</p>";
}
?> 