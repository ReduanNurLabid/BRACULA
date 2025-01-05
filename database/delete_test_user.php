<?php
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Delete test user and their associated data
    $conn->beginTransaction();

    // Delete associated rides first (due to foreign key constraints)
    $query = "DELETE FROM rides WHERE user_id IN (SELECT user_id FROM users WHERE student_id IN ('12345678', '20301261'))";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Then delete the user
    $query = "DELETE FROM users WHERE student_id IN ('12345678', '20301261')";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $conn->commit();
    echo "Test user and associated data deleted successfully";
} catch(PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?> 