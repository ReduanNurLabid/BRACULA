<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_check.php'; // Fixed path to session_check.php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Enable CORS for development
header('Access-Control-Allow-Origin: http://localhost:8081');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require login for all operations
require_login();

// Get user ID from session
$user_id = get_user_id();
$method = $_SERVER['REQUEST_METHOD'];

// Return message that the inquiry system is disabled
echo json_encode([
    'status' => 'info',
    'message' => 'The inquiry messaging system has been disabled. Please use the contact information provided on the accommodation listings to contact owners directly.'
]); 