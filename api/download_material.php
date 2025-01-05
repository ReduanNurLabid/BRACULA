<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();

    // Get material ID
    $materialId = $_GET['id'] ?? '';
    if (empty($materialId)) {
        throw new Exception("Material ID is required");
    }

    // Get material info
    $query = "SELECT * FROM resources WHERE resource_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $materialId);
    $stmt->execute();

    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$material) {
        throw new Exception("Material not found");
    }

    // Increment download counter
    $updateQuery = "UPDATE resources SET downloads = downloads + 1 WHERE resource_id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(":id", $materialId);
    $updateStmt->execute();

    // Get file path
    $filePath = '../uploads/materials/' . $material['file_url'];
    if (!file_exists($filePath)) {
        throw new Exception("File not found");
    }

    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $material['file_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');

    // Output file
    readfile($filePath);
    exit;

} catch (Exception $e) {
    header("Content-Type: application/json");
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 