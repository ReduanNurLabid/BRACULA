<?php
// Include database connection
require_once 'config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Query to get table structure
    $query = "DESCRIBE rides";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "Structure of rides table:\n";
    echo "------------------------\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: " . $row['Field'] . 
             ", Type: " . $row['Type'] . 
             ", Null: " . $row['Null'] . 
             ", Key: " . $row['Key'] . 
             ", Default: " . $row['Default'] . 
             ", Extra: " . $row['Extra'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 