<?php
/**
 * Unit Test for Event Functionality
 * 
 * This file tests the event functionality in the BRACULA application.
 * Tests cover creating events, reading events, updating events,
 * and managing event registrations and images.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

// Set up test environment
class EventTest {
    private $conn;
    private $testEventIds = [];
    private $testImageIds = [];
    private $testRegistrationIds = [];
    private $validUserId = null;
    private $databaseStructure = [];
    
    /**
     * Constructor sets up database connection and finds a valid user ID
     */
    public function __construct() {
        // Create database connection
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Check database structure
        $this->checkDatabaseStructure();
        
        // Find a valid user ID for testing
        $this->findValidUser();
    }
    
    /**
     * Check database structure to understand what tables and columns exist
     */
    private function checkDatabaseStructure() {
        try {
            // Check if events table exists
            $tables = [
                'events' => false,
                'event_images' => false,
                'event_registrations' => false,
                'event_reviews' => false
            ];
            
            foreach (array_keys($tables) as $table) {
                $stmt = $this->conn->query("SHOW TABLES LIKE '{$table}'");
                $tables[$table] = $stmt->rowCount() > 0;
                
                if ($tables[$table]) {
                    // Get columns for this table
                    $colStmt = $this->conn->query("DESCRIBE {$table}");
                    $this->databaseStructure[$table] = [];
                    while ($row = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                        $this->databaseStructure[$table][] = $row['Field'];
                    }
                }
            }
            
            echo "<div style='background-color: #f8f9fa; padding: 10px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;'>";
            echo "<h3>Database Structure Check:</h3>";
            echo "<ul>";
            foreach ($tables as $table => $exists) {
                $icon = $exists ? "✅" : "❌";
                $color = $exists ? "green" : "red";
                echo "<li style='color: {$color};'>{$icon} Table <strong>{$table}</strong>: " . ($exists ? "Exists" : "Missing") . "</li>";
                
                if ($exists) {
                    echo "<ul>";
                    echo "<li>Columns: " . implode(", ", $this->databaseStructure[$table]) . "</li>";
                    echo "</ul>";
                }
            }
            echo "</ul>";
            
            if (!$tables['events']) {
                echo "<div style='background-color: #ffdddd; padding: 10px; margin-top: 10px; border-radius: 5px;'>";
                echo "<strong>Critical Error:</strong> The events table is missing. Most tests will fail. ";
                echo "Please run the database setup script first.";
                echo "</div>";
            }
            
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div style='color: red; margin: 20px 0; padding: 10px; border: 1px solid #ffcccc; background-color: #ffeeee;'>";
            echo "<h3>Database Error:</h3>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    /**
     * Find a valid user ID from the database
     */
    private function findValidUser() {
        try {
            $stmt = $this->conn->query("SELECT user_id FROM users ORDER BY user_id LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && isset($user['user_id'])) {
                $this->validUserId = $user['user_id'];
            }
        } catch (PDOException $e) {
            // Users table might not exist or other issue
            $this->validUserId = null;
        }
    }
    
    /**
     * Run all test methods
     */
    public function runTests() {
        // Check if we have a valid user for testing
        if ($this->validUserId === null) {
            return [
                'error' => [
                    'status' => false,
                    'message' => "Cannot run tests: No valid user found in the database. Please ensure the users table exists and has at least one user."
                ]
            ];
        }
        
        // Check if events table exists
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'events'");
            if ($stmt->rowCount() === 0) {
                return [
                    'error' => [
                        'status' => false,
                        'message' => "Cannot run tests: The events table does not exist in the database."
                    ]
                ];
            }
            
            // Verify table structure
            $stmt = $this->conn->query("DESCRIBE events");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
            echo "<h4>Events Table Structure:</h4>";
            echo "<pre>" . print_r($columns, true) . "</pre>";
            echo "</div>";
            
        } catch (PDOException $e) {
            return [
                'error' => [
                    'status' => false,
                    'message' => "Database error: " . $e->getMessage()
                ]
            ];
        }
        
        $results = [
            'createEvent' => $this->testCreateEvent(),
            'getEvents' => $this->testGetEvents(),
            'getEventById' => $this->testGetEventById(),
            'createEventRegistration' => $this->testCreateEventRegistration(),
            'updateEvent' => $this->testUpdateEvent(),
            'searchEvents' => $this->testSearchEvents()
        ];
        
        return $results;
    }
    
    /**
     * Test creating an event with valid data
     */
    private function testCreateEvent() {
        // Test data
        $organizerId = $this->validUserId;
        $title = "Test Event " . rand(1000, 9999);
        $description = "This is a test event description for unit testing.";
        $eventDate = date('Y-m-d H:i:s', strtotime('+1 week'));
        $location = "Test Location";
        $maxParticipants = 100;
        $status = "active";
        
        try {
            // Check table structure first
            $stmt = $this->conn->query("DESCRIBE events");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Build dynamic query based on actual columns
            $fields = [];
            $values = [];
            $params = [];
            
            $data = [
                'organizer_id' => $organizerId,
                'title' => $title,
                'description' => $description,
                'event_date' => $eventDate,
                'location' => $location,
                'max_participants' => $maxParticipants,
                'status' => $status
            ];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $columns)) {
                    $fields[] = $field;
                    $values[] = ":$field";
                    $params[":$field"] = $value;
                }
            }
            
            $query = "INSERT INTO events (" . implode(", ", $fields) . ") 
                     VALUES (" . implode(", ", $values) . ")";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $eventId = $this->conn->lastInsertId();
                $this->testEventIds[] = $eventId;
                
                return [
                    'status' => true,
                    'message' => "Successfully created test event",
                    'event_id' => $eventId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to create test event"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test retrieving all events
     */
    private function testGetEvents() {
        try {
            $stmt = $this->conn->query("SELECT * FROM events ORDER BY event_date DESC");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'message' => "Successfully retrieved events",
                'count' => count($events),
                'events' => $events
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test retrieving a specific event by ID
     */
    private function testGetEventById() {
        if (empty($this->testEventIds)) {
            return [
                'status' => false,
                'message' => "No test events available"
            ];
        }
        
        $eventId = $this->testEventIds[0];
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM events WHERE event_id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                return [
                    'status' => true,
                    'message' => "Successfully retrieved event",
                    'event' => $event
                ];
            }
            
            return [
                'status' => false,
                'message' => "Event not found"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test creating an event registration
     */
    private function testCreateEventRegistration() {
        if (empty($this->testEventIds)) {
            return [
                'status' => false,
                'message' => "No test events available"
            ];
        }
        
        $eventId = $this->testEventIds[0];
        $userId = $this->validUserId;
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO event_registrations (event_id, user_id, registration_date) 
                                        VALUES (?, ?, NOW())");
            $result = $stmt->execute([$eventId, $userId]);
            
            if ($result) {
                $registrationId = $this->conn->lastInsertId();
                $this->testRegistrationIds[] = $registrationId;
                
                return [
                    'status' => true,
                    'message' => "Successfully created event registration",
                    'registration_id' => $registrationId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to create event registration"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test updating an event
     */
    private function testUpdateEvent() {
        if (empty($this->testEventIds)) {
            return [
                'status' => false,
                'message' => "No test events available"
            ];
        }
        
        $eventId = $this->testEventIds[0];
        $newTitle = "Updated Test Event " . rand(1000, 9999);
        
        try {
            $stmt = $this->conn->prepare("UPDATE events SET title = ? WHERE event_id = ?");
            $result = $stmt->execute([$newTitle, $eventId]);
            
            if ($result) {
                return [
                    'status' => true,
                    'message' => "Successfully updated event",
                    'event_id' => $eventId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to update event"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test searching events
     */
    private function testSearchEvents() {
        $searchTerm = "Test";
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM events WHERE title LIKE ? OR description LIKE ?");
            $searchPattern = "%{$searchTerm}%";
            $stmt->execute([$searchPattern, $searchPattern]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'message' => "Successfully searched events",
                'count' => count($events),
                'events' => $events
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean up test data
     */
    public function cleanup() {
        try {
            // Delete test registrations
            if (!empty($this->testRegistrationIds)) {
                $placeholders = str_repeat('?,', count($this->testRegistrationIds) - 1) . '?';
                $stmt = $this->conn->prepare("DELETE FROM event_registrations WHERE registration_id IN ($placeholders)");
                $stmt->execute($this->testRegistrationIds);
            }
            
            // Delete test events
            if (!empty($this->testEventIds)) {
                $placeholders = str_repeat('?,', count($this->testEventIds) - 1) . '?';
                $stmt = $this->conn->prepare("DELETE FROM events WHERE event_id IN ($placeholders)");
                $stmt->execute($this->testEventIds);
            }
            
            return [
                'status' => true,
                'message' => "Successfully cleaned up test data"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Cleanup error: " . $e->getMessage()
            ];
        }
    }
}

// Run the tests
$test = new EventTest();
$results = $test->runTests();

// Display results
echo "<div style='background-color: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h2>Event Test Results</h2>";
echo "<pre>" . print_r($results, true) . "</pre>";
echo "</div>";

// Clean up
$cleanup = $test->cleanup();
echo "<div style='background-color: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h2>Cleanup Results</h2>";
echo "<pre>" . print_r($cleanup, true) . "</pre>";
echo "</div>"; 