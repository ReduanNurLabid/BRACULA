<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$conn = $database->getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get events with optional filters
        $query = "SELECT e.*, u.full_name as organizer_name, u.avatar_url as organizer_avatar,
                        DATE_FORMAT(e.event_date, '%Y-%m-%d') as formatted_date,
                        (SELECT COUNT(*) FROM event_registrations er 
                         WHERE er.event_id = e.event_id) as registration_count
                 FROM events e
                 JOIN users u ON e.user_id = u.user_id
                 WHERE 1=1";
        
        $params = [];
        
        // Apply filters if provided
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $types = explode(',', $_GET['type']);
            $placeholders = str_repeat('?,', count($types) - 1) . '?';
            $query .= " AND e.event_type IN ($placeholders)";
            $params = array_merge($params, $types);
        }
        
        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $query .= " AND DATE(e.event_date) = ?";
            $params[] = $_GET['date'];
        }
        
        $query .= " ORDER BY e.event_date ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $events
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create new event
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('No data received');
        }
        
        // Validate required fields
        $required_fields = ['name', 'type', 'date', 'location', 'organizer_id', 'cover_image'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        try {
            $query = "INSERT INTO events (name, event_type, event_date, location, user_id, cover_image) 
                     VALUES (?, ?, ?, ?, ?, ?)";
                     
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['type'],
                $data['date'],
                $data['location'],
                $data['organizer_id'], // This will be mapped to user_id
                $data['cover_image']
            ]);
            
            $event_id = $conn->lastInsertId();
            
            // Fetch the created event with organizer details and registration count
            $query = "SELECT e.*, u.full_name as organizer_name, u.avatar_url as organizer_avatar,
                            DATE_FORMAT(e.event_date, '%Y-%m-%d') as formatted_date,
                            0 as registration_count
                     FROM events e
                     JOIN users u ON e.user_id = u.user_id
                     WHERE e.event_id = ?";
                     
            $stmt = $conn->prepare($query);
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Event created successfully',
                'data' => $event
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    
} catch(Exception $e) {
    error_log("Error in events.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 