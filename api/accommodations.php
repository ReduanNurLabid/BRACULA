<?php
require_once '../config/database.php';
require_once '../includes/session_check.php'; // Include session check utility
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create log file for debugging
$logFile = __DIR__ . '/../logs/accommodation_api.log';

function log_error($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

try {
    // Initialize database connection
    log_error("Starting accommodations.php API call");
    $database = new Database();
    $conn = $database->getConnection();
    log_error("Database connection established");

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
    log_error("Checking if user is logged in");
    require_login();
    
    // Get user ID from session
    $user_id = get_user_id();
    log_error("User ID from session: " . $user_id);
    $method = $_SERVER['REQUEST_METHOD'];
    log_error("Request method: " . $method);

    switch($method) {
        case 'GET':
            if (isset($_GET['owner']) && $_GET['owner'] === 'me') {
                // Get accommodations owned by the current user
                $query = "SELECT a.*, u.full_name, 
                    (SELECT COUNT(*) FROM accommodation_favorites WHERE accommodation_id = a.accommodation_id AND user_id = :user_id) as isFavorite
                    FROM accommodations a 
                    JOIN users u ON a.owner_id = u.user_id 
                    WHERE a.owner_id = :owner_id
                    ORDER BY a.created_at DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':owner_id' => $user_id
                ]);
                
                $accommodations = $stmt->fetchAll();
                
                // Get images for each accommodation
                foreach ($accommodations as &$acc) {
                    $stmt = $conn->prepare("SELECT image_url FROM accommodation_images WHERE accommodation_id = :id");
                    $stmt->execute([':id' => $acc['accommodation_id']]);
                    $acc['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                
                echo json_encode(['status' => 'success', 'data' => $accommodations]);
            }
            else if (isset($_GET['id'])) {
                // Get specific accommodation
                $query = "SELECT a.*, u.full_name, u.email, 
                    (SELECT COUNT(*) FROM accommodation_favorites WHERE accommodation_id = a.accommodation_id AND user_id = :user_id) as isFavorite
                    FROM accommodations a 
                    JOIN users u ON a.owner_id = u.user_id 
                    WHERE a.accommodation_id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':id' => $_GET['id']
                ]);
                
                if ($accommodation = $stmt->fetch()) {
                    // Get images
                    $stmt = $conn->prepare("SELECT image_url FROM accommodation_images WHERE accommodation_id = :id");
                    $stmt->execute([':id' => $_GET['id']]);
                    $accommodation['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    echo json_encode(['status' => 'success', 'data' => $accommodation]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Accommodation not found']);
                }
            } else {
                // Get all accommodations
                $query = "SELECT a.*, u.full_name, 
                    (SELECT COUNT(*) FROM accommodation_favorites WHERE accommodation_id = a.accommodation_id AND user_id = :user_id) as isFavorite
                    FROM accommodations a 
                    JOIN users u ON a.owner_id = u.user_id 
                    ORDER BY a.created_at DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([':user_id' => $user_id]);
                $accommodations = $stmt->fetchAll();

                // Get images for each accommodation
                foreach ($accommodations as &$acc) {
                    $stmt = $conn->prepare("SELECT image_url FROM accommodation_images WHERE accommodation_id = :id");
                    $stmt->execute([':id' => $acc['accommodation_id']]);
                    $acc['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }

                echo json_encode(['status' => 'success', 'data' => $accommodations]);
            }
            break;

        case 'POST':
            if (isset($_GET['action']) && $_GET['action'] === 'favorite') {
                // Toggle favorite status
                $data = json_decode(file_get_contents('php://input'), true);
                $accommodation_id = $data['accommodation_id'];

                $stmt = $conn->prepare("SELECT * FROM accommodation_favorites WHERE accommodation_id = :acc_id AND user_id = :user_id");
                $stmt->execute([':acc_id' => $accommodation_id, ':user_id' => $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $stmt = $conn->prepare("DELETE FROM accommodation_favorites WHERE accommodation_id = :acc_id AND user_id = :user_id");
                    $message = "Removed from favorites";
                } else {
                    $stmt = $conn->prepare("INSERT INTO accommodation_favorites (accommodation_id, user_id) VALUES (:acc_id, :user_id)");
                    $message = "Added to favorites";
                }
                
                $stmt->execute([':acc_id' => $accommodation_id, ':user_id' => $user_id]);
                echo json_encode(['status' => 'success', 'message' => $message]);
            } else {
                // Create new accommodation
                $conn->beginTransaction();

                try {
                    // Validate required fields
                    $required_fields = ['title', 'roomType', 'price', 'location', 'description', 'contactInfo'];
                    foreach ($required_fields as $field) {
                        if (!isset($_POST[$field]) || empty($_POST[$field])) {
                            throw new Exception("Missing required field: $field");
                        }
                    }

                    // Validate price
                    if (!is_numeric($_POST['price']) || $_POST['price'] <= 0) {
                        throw new Exception("Invalid price value");
                    }

                    // Validate file upload
                    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
                        throw new Exception("At least one image is required");
                    }

                    // Insert accommodation
                    $stmt = $conn->prepare("INSERT INTO accommodations (title, room_type, price, location, description, contact_info, owner_id) 
                        VALUES (:title, :room_type, :price, :location, :description, :contact_info, :owner_id)");
                    
                    $stmt->execute([
                        ':title' => $_POST['title'],
                        ':room_type' => $_POST['roomType'],
                        ':price' => $_POST['price'],
                        ':location' => $_POST['location'],
                        ':description' => $_POST['description'],
                        ':contact_info' => $_POST['contactInfo'],
                        ':owner_id' => $user_id
                    ]);

                    $accommodation_id = $conn->lastInsertId();

                    // Handle image uploads
                    if (isset($_FILES['images'])) {
                        $uploadDir = '../uploads/accommodations/';
                        
                        // Ensure upload directory exists
                        if (!file_exists($uploadDir)) {
                            if (!mkdir($uploadDir, 0777, true)) {
                                throw new Exception("Failed to create uploads directory. Please check permissions.");
                            }
                            chmod($uploadDir, 0777); // Ensure directory is writable
                        }
                        
                        // Debug upload directory
                        error_log("Upload directory: " . realpath($uploadDir));
                        error_log("Upload directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));

                        $uploaded_images = [];
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            if (!empty($tmp_name)) {
                                $file_name = $_FILES['images']['name'][$key];
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                
                                // Debug file info
                                error_log("Processing file: $file_name, tmp: $tmp_name, exists: " . (file_exists($tmp_name) ? 'Yes' : 'No'));
                                
                                // Validate file type
                                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                                if (!in_array($file_ext, $allowed_types)) {
                                    throw new Exception("Invalid file type: $file_name. Only JPG, PNG, and GIF are allowed.");
                                }

                                // Validate file size (5MB max)
                                if ($_FILES['images']['size'][$key] > 5 * 1024 * 1024) {
                                    throw new Exception("File too large: $file_name. Maximum size is 5MB.");
                                }

                                $new_name = uniqid() . '.' . $file_ext;
                                $destination = $uploadDir . $new_name;
                                
                                error_log("Destination path: $destination");

                                if (move_uploaded_file($tmp_name, $destination)) {
                                    $image_url = 'uploads/accommodations/' . $new_name;
                                    error_log("File uploaded successfully to: $image_url");
                                    
                                    $stmt = $conn->prepare("INSERT INTO accommodation_images (accommodation_id, image_url) VALUES (:acc_id, :url)");
                                    $stmt->execute([':acc_id' => $accommodation_id, ':url' => $image_url]);
                                    $uploaded_images[] = $image_url;
                                } else {
                                    error_log("Failed to move uploaded file. PHP Error: " . error_get_last()['message']);
                                    throw new Exception("Failed to upload image: $file_name. Check server logs for details.");
                                }
                            }
                        }
                    }

                    // Get the created accommodation with all details
                    $stmt = $conn->prepare("SELECT a.*, u.full_name 
                        FROM accommodations a 
                        JOIN users u ON a.owner_id = u.user_id 
                        WHERE a.accommodation_id = :id");
                    $stmt->execute([':id' => $accommodation_id]);
                    $accommodation = $stmt->fetch();

                    // Add uploaded images to the response
                    $accommodation['images'] = $uploaded_images;

                    $conn->commit();
                    echo json_encode(['status' => 'success', 'data' => $accommodation]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            }
            break;
            
        case 'PUT':
            // Update accommodation
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['accommodation_id'])) {
                throw new Exception("Missing accommodation ID");
            }
            
            // Check ownership
            $stmt = $conn->prepare("SELECT owner_id FROM accommodations WHERE accommodation_id = :id");
            $stmt->execute([':id' => $data['accommodation_id']]);
            $accommodation = $stmt->fetch();
            
            if (!$accommodation) {
                throw new Exception("Accommodation not found");
            }
            
            if ($accommodation['owner_id'] != $user_id) {
                throw new Exception("You don't have permission to update this accommodation");
            }
            
            // Update fields
            $fieldUpdates = [];
            $params = [':id' => $data['accommodation_id']];
            
            $allowedFields = [
                'title' => 'title',
                'roomType' => 'room_type',
                'price' => 'price',
                'location' => 'location',
                'description' => 'description',
                'contactInfo' => 'contact_info',
                'status' => 'status'
            ];
            
            foreach ($allowedFields as $clientField => $dbField) {
                if (isset($data[$clientField])) {
                    $fieldUpdates[] = "$dbField = :$dbField";
                    $params[":$dbField"] = $data[$clientField];
                }
            }
            
            if (empty($fieldUpdates)) {
                throw new Exception("No fields to update");
            }
            
            $query = "UPDATE accommodations SET " . implode(', ', $fieldUpdates) . " WHERE accommodation_id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            echo json_encode(['status' => 'success', 'message' => 'Accommodation updated successfully']);
            break;
            
        case 'DELETE':
            // Delete accommodation
            if (!isset($_GET['id'])) {
                throw new Exception("Missing accommodation ID");
            }
            
            $accommodation_id = $_GET['id'];
            
            // Check ownership
            $stmt = $conn->prepare("SELECT owner_id FROM accommodations WHERE accommodation_id = :id");
            $stmt->execute([':id' => $accommodation_id]);
            $accommodation = $stmt->fetch();
            
            if (!$accommodation) {
                throw new Exception("Accommodation not found");
            }
            
            if ($accommodation['owner_id'] != $user_id) {
                throw new Exception("You don't have permission to delete this accommodation");
            }
            
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // Delete images
                $stmt = $conn->prepare("SELECT image_url FROM accommodation_images WHERE accommodation_id = :id");
                $stmt->execute([':id' => $accommodation_id]);
                $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete image files
                foreach ($images as $imageUrl) {
                    $imagePath = realpath('../' . $imageUrl);
                    if ($imagePath && file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                
                // Delete from database - cascading will handle related tables
                $stmt = $conn->prepare("DELETE FROM accommodations WHERE accommodation_id = :id");
                $stmt->execute([':id' => $accommodation_id]);
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Accommodation deleted successfully']);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            break;
    }
} catch (Exception $e) {
    log_error("Error in accommodations.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
