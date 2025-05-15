<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();

    // Get filter parameters
    $courseCode = $_GET['courseCode'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $fileType = $_GET['fileType'] ?? '';

    // Build query
    $query = "SELECT r.*, u.full_name as uploader_name 
              FROM resources r 
              LEFT JOIN users u ON r.user_id = u.user_id 
              WHERE 1=1";
    $params = [];

    if (!empty($courseCode)) {
        $query .= " AND r.course_code LIKE :course_code";
        $params[':course_code'] = "%$courseCode%";
    }

    if (!empty($semester)) {
        $query .= " AND r.semester = :semester";
        $params[':semester'] = $semester;
    }

    if (!empty($fileType)) {
        if ($fileType === 'pdf') {
            $query .= " AND (r.file_type = 'pdf' OR r.file_type = 'notes')";
            // No need to bind anything here since values are hardcoded
        } else {
            $query .= " AND r.file_type = :file_type";
            $params[':file_type'] = $fileType;
        }
    }

    $query .= " ORDER BY r.created_at DESC";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $materials = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $materials[] = [
            'id' => $row['resource_id'],
            'courseCode' => $row['course_code'],
            'semester' => $row['semester'],
            'fileName' => $row['file_name'],
            'fileType' => $row['file_type'],
            'fileUrl' => $row['file_url'],
            'downloads' => $row['downloads'],
            'uploadDate' => $row['created_at'],
            'uploaderName' => $row['uploader_name']
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $materials
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 