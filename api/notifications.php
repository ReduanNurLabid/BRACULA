<?php
// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection function
function getConnection() {
    $host = 'localhost';
    $dbname = 'bracula';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        error_log("Connection error: " . $e->getMessage());
        return null;
    }
}

// Function to validate user authentication
function validateUser() {
    // You would implement proper authentication here
    // For now, we'll assume the user is authenticated
    return true;
}

// Get notifications for current user
function getNotifications() {
    // Check if user is authenticated
    if (!validateUser()) {
        return [
            'status' => 'error',
            'message' => 'User not authenticated'
        ];
    }
    
    // For testing/demo purposes, just return mock notifications
    // In a real implementation, you would fetch from the database
    return [
        'status' => 'success',
        'data' => [
            [
                'id' => 1,
                'title' => 'New accommodation posted',
                'message' => 'A new apartment is available in Mohakhali',
                'created_at' => date('c', strtotime('-5 minutes')),
                'is_read' => false
            ],
            [
                'id' => 2,
                'title' => 'Price drop alert',
                'message' => 'A room you saved has reduced its price',
                'created_at' => date('c', strtotime('-2 hours')),
                'is_read' => false
            ],
            [
                'id' => 3,
                'title' => 'Message from owner',
                'message' => 'You have a new message about your inquiry',
                'created_at' => date('c', strtotime('-1 day')),
                'is_read' => true
            ]
        ]
    ];
}

// Mark notification as read
function updateNotificationReadStatus($notificationId, $isRead) {
    // In a real implementation, you would update the database
    return [
        'status' => 'success',
        'message' => 'Notification status updated'
    ];
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    // In a real implementation, you would update the database
    return [
        'status' => 'success',
        'message' => 'All notifications marked as read'
    ];
}

// Add a new notification
function addNotification($notification) {
    // In a real implementation, you would insert into the database
    // and then return the new ID
    return [
        'status' => 'success',
        'data' => [
            'id' => 4 // Server-generated ID
        ]
    ];
}

// Main request handler
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Fetch notifications
        echo json_encode(getNotifications());
    } else if ($method === 'POST') {
        // Handle POST requests
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request data'
            ]);
            exit;
        }
        
        $action = isset($data['action']) ? $data['action'] : '';
        
        switch ($action) {
            case 'update_read_status':
                if (isset($data['notification_id']) && isset($data['is_read'])) {
                    echo json_encode(updateNotificationReadStatus($data['notification_id'], $data['is_read']));
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Missing required parameters'
                    ]);
                }
                break;
                
            case 'mark_all_read':
                echo json_encode(markAllNotificationsAsRead());
                break;
                
            case 'add_notification':
                if (isset($data['notification'])) {
                    echo json_encode(addNotification($data['notification']));
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Missing notification data'
                    ]);
                }
                break;
                
            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action'
                ]);
                break;
        }
    } else {
        // Handle OPTIONS requests for CORS, or other methods
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log the error for debugging
    error_log('Error in notifications.php: ' . $e->getMessage());
} 