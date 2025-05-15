<?php
// Include database connection
require_once '../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Check if departure_time column exists
    $checkQuery = "SHOW COLUMNS FROM rides LIKE 'departure_time'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Column doesn't exist, add it
        echo "Adding departure_time column to rides table...<br>";
        $alterQuery = "ALTER TABLE rides ADD COLUMN departure_time DATETIME AFTER destination";
        $alterStmt = $conn->prepare($alterQuery);
        $alterStmt->execute();
        echo "departure_time column added successfully!<br>";
    } else {
        echo "departure_time column already exists in rides table.<br>";
    }
    
    // Check if contact_info column exists
    $checkQuery = "SHOW COLUMNS FROM rides LIKE 'contact_info'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Column doesn't exist, add it
        echo "Adding contact_info column to rides table...<br>";
        $alterQuery = "ALTER TABLE rides ADD COLUMN contact_info VARCHAR(255) AFTER departure_time";
        $alterStmt = $conn->prepare($alterQuery);
        $alterStmt->execute();
        echo "contact_info column added successfully!<br>";
    } else {
        echo "contact_info column already exists in rides table.<br>";
    }
    
    // Check if notes column exists
    $checkQuery = "SHOW COLUMNS FROM rides LIKE 'notes'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Column doesn't exist, add it
        echo "Adding notes column to rides table...<br>";
        $alterQuery = "ALTER TABLE rides ADD COLUMN notes TEXT AFTER contact_info";
        $alterStmt = $conn->prepare($alterQuery);
        $alterStmt->execute();
        echo "notes column added successfully!<br>";
    } else {
        echo "notes column already exists in rides table.<br>";
    }
    
    // Show current table structure
    $describeQuery = "DESCRIBE rides";
    $describeStmt = $conn->prepare($describeQuery);
    $describeStmt->execute();
    
    echo "<h3>Current structure of rides table:</h3>";
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
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 