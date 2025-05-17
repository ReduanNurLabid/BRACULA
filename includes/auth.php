<?php
/**
 * Authentication utilities for the BRACULA application
 */

// Include session check
require_once __DIR__ . "/session_check.php";

/**
 * Authenticate a user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array|bool User data if authenticated, false otherwise
 */
function authenticateUser($email, $password) {
    // Include database and user model
    require_once __DIR__ . "/../config/database.php";
    require_once __DIR__ . "/../models/User.php";
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create user object
    $user = new User($db);
    
    // Attempt to log in
    if ($user->login($email, $password)) {
        // Get full user data
        $userData = $user->getById($user->user_id);
        
        if ($userData) {
            // Remove sensitive data
            unset($userData["password_hash"]);
            
            // Store user data in session
            $_SESSION["user_id"] = $user->user_id;
            $_SESSION["email"] = $user->email;
            $_SESSION["full_name"] = $user->full_name;
            
            return $userData;
        }
    }
    
    return false;
}

/**
 * Register a new user
 * 
 * @param array $userData User data
 * @return bool True if registered, false otherwise
 */
function registerUser($userData) {
    // Include database and user model
    require_once __DIR__ . "/../config/database.php";
    require_once __DIR__ . "/../models/User.php";
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create user object
    $user = new User($db);
    
    // Set user properties
    $user->full_name = $userData["full_name"];
    $user->student_id = $userData["student_id"];
    $user->email = $userData["email"];
    $user->password = $userData["password"];
    $user->department = $userData["department"];
    
    // Optional fields
    $user->avatar_url = $userData["avatar_url"] ?? null;
    $user->bio = $userData["bio"] ?? null;
    $user->interests = $userData["interests"] ?? null;
    
    // Check if email exists
    $user->email = $userData["email"];
    if ($user->emailExists()) {
        return false;
    }
    
    // Check if student ID exists
    $user->student_id = $userData["student_id"];
    if ($user->studentIdExists()) {
        return false;
    }
    
    // Create the user
    return $user->create();
}

/**
 * Log out the current user
 * 
 * @return void
 */
function logoutUser() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}
?>