<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();

    // Check if file was uploaded
    if (!isset($_FILES['file'])) {
        throw new Exception("No file uploaded");
    }

    $file = $_FILES['file'];
    $courseCode = $_POST['courseCode'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $materialType = $_POST['materialType'] ?? '';
    $userId = $_POST['userId'] ?? '';

    // Validate required fields
    if (empty($courseCode) || empty($semester) || empty($materialType) || empty($userId)) {
        throw new Exception("All fields are required");
    }

    // Validate file
    $allowedTypes = ['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type. Only PDF, PPT, PPTX, DOC, and DOCX files are allowed.");
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/materials/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $uniqueFilename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload file");
    }

    // Store in database
    $query = "INSERT INTO resources (user_id, course_code, semester, file_name, file_type, file_url, downloads) 
              VALUES (:user_id, :course_code, :semester, :file_name, :file_type, :file_url, 0)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $userId);
    $stmt->bindParam(":course_code", $courseCode);
    $stmt->bindParam(":semester", $semester);
    $stmt->bindParam(":file_name", $file['name']);
    $stmt->bindParam(":file_type", $materialType);
    $stmt->bindParam(":file_url", $uniqueFilename);

    if (!$stmt->execute()) {
        // Delete uploaded file if database insert fails
        unlink($targetPath);
        throw new Exception("Failed to store material information");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Material uploaded successfully",
        "data" => [
            "id" => $db->lastInsertId(),
            "fileName" => $file['name'],
            "fileUrl" => $uniqueFilename,
            "courseCode" => $courseCode,
            "semester" => $semester,
            "fileType" => $materialType,
            "downloads" => 0
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 