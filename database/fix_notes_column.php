<?php
// Fix notes column in ride_requests table
require_once __DIR__ . '/../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Check if the table exists first
    $tableExists = $conn->query("SHOW TABLES LIKE 'ride_requests'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table with all necessary columns
        $sql = "CREATE TABLE ride_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            ride_id INT NOT NULL,
            user_id INT NOT NULL,
            seats INT NOT NULL DEFAULT 1,
            pickup VARCHAR(255) NOT NULL DEFAULT '',
            notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
        echo "Table created with notes column";
    } else {
        // Check if notes column exists
        $columnExists = $conn->query("SHOW COLUMNS FROM ride_requests LIKE 'notes'")->rowCount() > 0;
        
        if (!$columnExists) {
            // Add the notes column
            $sql = "ALTER TABLE ride_requests ADD COLUMN notes TEXT";
            $conn->exec($sql);
            echo "Notes column added successfully";
        } else {
            echo "Notes column already exists";
        }
    }
    
    // Show current table structure
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
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 