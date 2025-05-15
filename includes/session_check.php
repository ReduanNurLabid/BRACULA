<?php
// Include session configuration
require_once __DIR__ . '/../config/session_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to require login, redirects if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    }
    return true;
}

// Function to get current user ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user's full name
function get_user_name() {
    return $_SESSION['full_name'] ?? null;
}

// Function to get current user's email
function get_user_email() {
    return $_SESSION['email'] ?? null;
}
?> 