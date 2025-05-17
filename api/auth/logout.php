<?php
// Include session configuration
require_once __DIR__ . '/../config/session_config.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8081');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Destroy session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Return success response
echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
?> 