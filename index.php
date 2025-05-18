<?php
// Include session configuration
require_once __DIR__ . '/config/session_config.php';

// Start the session
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Redirect to appropriate page
if ($loggedIn) {
    // If user is logged in, redirect to feed page
    header('Location: html/feed.html');
    exit;
} else {
    // If user is not logged in, redirect to login page
    header('Location: html/login.html');
    exit;
}