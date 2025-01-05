<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all resources with user names
    $query = "SELECT r.*, u.full_name as uploader_name 
              FROM resources r 
              LEFT JOIN users u ON r.user_id = u.user_id";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo "Resources in database:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['resource_id']}, Course: {$row['course_code']}, File: {$row['file_name']}, " .
             "Type: {$row['file_type']}, Uploader: {$row['uploader_name']}, Downloads: {$row['downloads']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 