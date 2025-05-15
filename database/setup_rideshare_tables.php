<?php
// Include database connection
require_once '../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Read SQL file content
    $sql = file_get_contents('create_rideshare_tables.sql');
    
    // Execute SQL statements
    $conn->exec($sql);
    
    echo "Rideshare tables have been successfully created or updated!<br>";
    
    // Check rides table structure
    $query = "DESCRIBE rides";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<h3>Structure of rides table:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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