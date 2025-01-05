<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all users
    $query = "SELECT * FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo "Users in database:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['user_id']}, Name: {$row['full_name']}, Email: {$row['email']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 