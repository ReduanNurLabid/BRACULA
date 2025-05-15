<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Enable CORS for development
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
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
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        $uploaded_images = [];
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            if (!empty($tmp_name)) {
                                $file_name = $_FILES['images']['name'][$key];
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                
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

                                if (move_uploaded_file($tmp_name, $destination)) {
                                    $image_url = 'uploads/accommodations/' . $new_name;
                                    $stmt = $conn->prepare("INSERT INTO accommodation_images (accommodation_id, image_url) VALUES (:acc_id, :url)");
                                    $stmt->execute([':acc_id' => $accommodation_id, ':url' => $image_url]);
                                    $uploaded_images[] = $image_url;
                                } else {
                                    throw new Exception("Failed to upload image: $file_name");
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

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            break;
    }
} catch (Exception $e) {
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
