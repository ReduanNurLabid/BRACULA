<?php
/**
 * BRACULA Project Organization Script
 * 
 * This script helps organize the PHP files in the BRACULA project according to 
 * the recommended structure. It creates the necessary directories and moves files
 * to their appropriate locations.
 * 
 * IMPORTANT: Make a backup of your project before running this script!
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the root directory
$root = __DIR__;

// Define the directories to create
$directories = [
    // API directories
    'api/auth',
    'api/posts',
    'api/comments',
    'api/users',
    'api/rides',
    'api/accommodations',
    'api/events',
    
    // Model directory (already exists but ensure it's there)
    'models',
    
    // Config directory (already exists but ensure it's there)
    'config',
    
    // Includes directory (already exists but ensure it's there)
    'includes',
    
    // Database directories
    'database/migrations',
    'database/seeds',
    
    // Public assets
    'public/css',
    'public/js',
    'public/images',
    
    // Test directories
    'test/api',
    'test/models',
    'test/includes',
    'test/utils',
];

// Define file mappings (source => destination)
$fileMappings = [
    // Auth endpoints
    'api/login.php' => 'api/auth/login.php',
    'api/register.php' => 'api/auth/register.php',
    'api/logout.php' => 'api/auth/logout.php',
    
    // Post endpoints
    'api/get_posts.php' => 'api/posts/get_posts.php',
    'api/create_post.php' => 'api/posts/create_post.php',
    'api/edit_post.php' => 'api/posts/edit_post.php',
    'api/delete_post.php' => 'api/posts/delete_post.php',
    'api/vote_post.php' => 'api/posts/vote_post.php',
    'api/save_post.php' => 'api/posts/save_post.php',
    'api/get_saved_posts.php' => 'api/posts/get_saved_posts.php',
    'api/search_posts.php' => 'api/posts/search_posts.php',
    
    // Comment endpoints
    'api/comments.php' => 'api/comments/comments.php',
    'api/edit_comment.php' => 'api/comments/edit_comment.php',
    'api/delete_comment.php' => 'api/comments/delete_comment.php',
    'api/reply_comment.php' => 'api/comments/reply_comment.php',
    'api/get_comments.php' => 'api/comments/get_comments.php',
    
    // User endpoints
    'api/get_user_profile.php' => 'api/users/get_user_profile.php',
    'api/update_profile.php' => 'api/users/update_profile.php',
    'api/update_account.php' => 'api/users/update_account.php',
    'api/delete_account.php' => 'api/users/delete_account.php',
    'api/get_user_activities.php' => 'api/users/get_user_activities.php',
    'api/user_activity.php' => 'api/users/user_activity.php',
    'api/update_user_activity.php' => 'api/users/update_user_activity.php',
    'api/notifications.php' => 'api/users/notifications.php',
    
    // Ride endpoints
    'api/rides.php' => 'api/rides/rides.php',
    'api/ride_requests.php' => 'api/rides/ride_requests.php',
    'api/driver_reviews.php' => 'api/rides/driver_reviews.php',
    
    // Accommodation endpoints
    'api/accommodations.php' => 'api/accommodations/accommodations.php',
    'api/accommodation_inquiries.php' => 'api/accommodations/accommodation_inquiries.php',
    
    // Event endpoints
    'api/events.php' => 'api/events/events.php',
    'api/event_registration.php' => 'api/events/event_registration.php',
    
    // Resource endpoints
    'api/get_materials.php' => 'api/resources/get_materials.php',
    'api/upload_material.php' => 'api/resources/upload_material.php',
    'api/download_material.php' => 'api/resources/download_material.php',
    
    // Move CSS files
    'css' => 'public/css',
    'style.css' => 'public/css/style.css',
    
    // Move JS files
    'js' => 'public/js',
];

// Function to create directories
function createDirectories($directories, $root) {
    echo "Creating directories...\n";
    foreach ($directories as $dir) {
        $path = $root . '/' . $dir;
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                echo "Created directory: $path\n";
            } else {
                echo "Failed to create directory: $path\n";
            }
        } else {
            echo "Directory already exists: $path\n";
        }
    }
}

// Function to move files
function moveFiles($fileMappings, $root) {
    echo "\nMoving files...\n";
    foreach ($fileMappings as $source => $destination) {
        $sourcePath = $root . '/' . $source;
        $destPath = $root . '/' . $destination;
        
        // Check if source exists
        if (file_exists($sourcePath)) {
            // Create destination directory if it doesn't exist
            $destDir = dirname($destPath);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Copy the file
            if (is_dir($sourcePath)) {
                // If it's a directory, we need to copy all contents
                echo "Skipping directory copy for now: $sourcePath -> $destPath\n";
                // This would require a recursive copy function
            } else {
                if (copy($sourcePath, $destPath)) {
                    echo "Copied: $sourcePath -> $destPath\n";
                    // Don't delete the original file yet for safety
                    // unlink($sourcePath);
                } else {
                    echo "Failed to copy: $sourcePath -> $destPath\n";
                }
            }
        } else {
            echo "Source file not found: $sourcePath\n";
        }
    }
}

// Function to create model class templates
function createModelTemplates($root) {
    echo "\nCreating model templates...\n";
    
    $models = [
        'Post.php' => [
            'properties' => [
                'post_id', 'user_id', 'content', 'caption', 'community', 
                'created_at', 'updated_at', 'vote_count', 'comment_count'
            ],
            'methods' => [
                'create', 'read', 'update', 'delete', 'getById', 'getByUser', 
                'getByCommunity', 'vote', 'incrementCommentCount'
            ]
        ],
        'Comment.php' => [
            'properties' => [
                'comment_id', 'post_id', 'user_id', 'content', 
                'created_at', 'updated_at', 'parent_id'
            ],
            'methods' => [
                'create', 'read', 'update', 'delete', 'getById', 'getByPost', 
                'getByUser', 'getReplies'
            ]
        ],
        'Ride.php' => [
            'properties' => [
                'ride_id', 'user_id', 'origin', 'destination', 'date_time', 
                'seats', 'price', 'description', 'created_at', 'updated_at'
            ],
            'methods' => [
                'create', 'read', 'update', 'delete', 'getById', 'getByUser', 
                'search', 'requestRide', 'approveRequest', 'rejectRequest'
            ]
        ],
        'Event.php' => [
            'properties' => [
                'event_id', 'user_id', 'title', 'description', 'location', 
                'date_time', 'created_at', 'updated_at'
            ],
            'methods' => [
                'create', 'read', 'update', 'delete', 'getById', 'getByUser', 
                'register', 'unregister', 'getRegistrations'
            ]
        ],
        'Accommodation.php' => [
            'properties' => [
                'accommodation_id', 'user_id', 'title', 'description', 'location', 
                'price', 'availability', 'created_at', 'updated_at'
            ],
            'methods' => [
                'create', 'read', 'update', 'delete', 'getById', 'getByUser', 
                'search', 'inquire', 'respondToInquiry'
            ]
        ]
    ];
    
    foreach ($models as $modelName => $modelData) {
        $modelPath = $root . '/models/' . $modelName;
        
        // Skip if model already exists
        if (file_exists($modelPath)) {
            echo "Model already exists: $modelPath\n";
            continue;
        }
        
        // Create model template
        $properties = implode(', ', array_map(function($prop) {
            return 'public $' . $prop;
        }, $modelData['properties']));
        
        $methods = implode("\n\n    ", array_map(function($method) {
            return 'public function ' . $method . '() {
        // TODO: Implement ' . $method . ' method
    }';
        }, $modelData['methods']));
        
        $template = '<?php
class ' . substr($modelName, 0, -4) . ' {
    private $conn;
    private $table_name = "' . strtolower(substr($modelName, 0, -4)) . 's";
    
    // Properties
    ' . $properties . ';
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Methods
    ' . $methods . '
}
?>';
        
        if (file_put_contents($modelPath, $template)) {
            echo "Created model template: $modelPath\n";
        } else {
            echo "Failed to create model template: $modelPath\n";
        }
    }
}

// Function to create a utils file
function createUtilsFile($root) {
    echo "\nCreating utils file...\n";
    
    $utilsPath = $root . '/includes/utils.php';
    
    // Skip if file already exists
    if (file_exists($utilsPath)) {
        echo "Utils file already exists: $utilsPath\n";
        return;
    }
    
    $template = '<?php
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
?>';
    
    if (file_put_contents($utilsPath, $template)) {
        echo "Created utils file: $utilsPath\n";
    } else {
        echo "Failed to create utils file: $utilsPath\n";
    }
}

// Function to create an auth utilities file
function createAuthFile($root) {
    echo "\nCreating auth utilities file...\n";
    
    $authPath = $root . '/includes/auth.php';
    
    // Skip if file already exists
    if (file_exists($authPath)) {
        echo "Auth file already exists: $authPath\n";
        return;
    }
    
    $template = '<?php
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
?>';
    
    if (file_put_contents($authPath, $template)) {
        echo "Created auth utilities file: $authPath\n";
    } else {
        echo "Failed to create auth utilities file: $authPath\n";
    }
}

// Main execution
echo "BRACULA Project Organization Script\n";
echo "==================================\n\n";

// Ask for confirmation
echo "This script will reorganize your project files according to the recommended structure.\n";
echo "Make sure you have a backup of your project before proceeding.\n\n";
echo "Do you want to continue? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) != 'y') {
    echo "Operation cancelled.\n";
    exit;
}

// Create directories
createDirectories($directories, $root);

// Create model templates
createModelTemplates($root);

// Create utils file
createUtilsFile($root);

// Create auth file
createAuthFile($root);

// Move files (commented out for safety - uncomment when ready)
// moveFiles($fileMappings, $root);

echo "\nScript completed!\n";
echo "Note: File moving is commented out for safety. Review the script and uncomment the moveFiles() call when ready.\n";
echo "Remember to update file references in your code after moving files.\n";
?> 