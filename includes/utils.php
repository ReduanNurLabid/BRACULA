<?php
/**
 * Utility functions for the BRACULA application
 */

/**
 * Sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags($data));
}

/**
 * Generate JSON response
 * 
 * @param string $status Status of the response (success/error)
 * @param string $message Message to include in the response
 * @param array $data Additional data to include in the response
 * @param int $code HTTP status code
 * @return void
 */
function jsonResponse($status, $message, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

/**
 * Generate error response
 * 
 * @param string $message Error message
 * @param int $code HTTP status code
 * @return void
 */
function errorResponse($message, $code = 500) {
    jsonResponse("error", $message, [], $code);
}

/**
 * Generate success response
 * 
 * @param string $message Success message
 * @param array $data Additional data
 * @return void
 */
function successResponse($message, $data = []) {
    jsonResponse("success", $message, $data);
}

/**
 * Validate required fields in request data
 * 
 * @param array $data Request data
 * @param array $required Required fields
 * @return bool True if all required fields are present
 */
function validateRequiredFields($data, $required) {
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            errorResponse("Missing required field: " . $field, 400);
            return false;
        }
    }
    return true;
}

/**
 * Get authenticated user ID from session
 * 
 * @return int|null User ID or null if not authenticated
 */
function getAuthUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION["user_id"] ?? null;
}
?>