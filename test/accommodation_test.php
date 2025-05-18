<?php
/**
 * Unit Test for Accommodation Functionality
 * 
 * This file tests the accommodation functionality in the BRACULA application.
 * Tests cover creating accommodations, reading accommodations, updating accommodations,
 * and managing accommodation inquiries and images.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

// Set up test environment
class AccommodationTest {
    private $conn;
    private $testAccommodationIds = [];
    private $testImageIds = [];
    private $testInquiryIds = [];
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
            // Check if accommodations table exists
            $tables = [
                'accommodations' => false,
                'accommodation_images' => false,
                'accommodation_inquiries' => false,
                'accommodation_reviews' => false,
                'accommodation_favorites' => false
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
            
            if (!$tables['accommodations']) {
                echo "<div style='background-color: #ffdddd; padding: 10px; margin-top: 10px; border-radius: 5px;'>";
                echo "<strong>Critical Error:</strong> The accommodations table is missing. Most tests will fail. ";
                echo "Please run the database setup script first: <code>database/create_accommodation_tables.php</code>";
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
        
        // Check if accommodations table exists
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'accommodations'");
            if ($stmt->rowCount() === 0) {
                return [
                    'error' => [
                        'status' => false,
                        'message' => "Cannot run tests: The accommodations table does not exist in the database. Please run database/create_accommodation_tables.php first."
                    ]
                ];
            }
            
            // Verify table structure
            $stmt = $this->conn->query("DESCRIBE accommodations");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
            echo "<h4>Accommodations Table Structure:</h4>";
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
            'createAccommodation' => $this->testCreateAccommodation(),
            'getAccommodations' => $this->testGetAccommodations(),
            'getAccommodationById' => $this->testGetAccommodationById(),
            'createAccommodationInquiry' => $this->testCreateAccommodationInquiry(),
            'updateAccommodation' => $this->testUpdateAccommodation(),
            'searchAccommodations' => $this->testSearchAccommodations(),
            'apiCreateAccommodation' => $this->testApiCreateAccommodation()
        ];
        
        return $results;
    }
    
    /**
     * Test creating an accommodation with valid data
     */
    private function testCreateAccommodation() {
        // Test data
        $ownerId = $this->validUserId;
        $title = "Test Accommodation " . rand(1000, 9999);
        $roomType = "single";
        $price = 15000;
        $location = "Test Location";
        $description = "This is a test accommodation description for unit testing.";
        $contactInfo = "01712345678";
        $status = "active";
        
        try {
            // Check table structure first
            $stmt = $this->conn->query("DESCRIBE accommodations");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Build dynamic query based on actual columns
            $fields = [];
            $values = [];
            $params = [];
            
            // Standard fields that should be present
            $fieldMap = [
                'owner_id' => $ownerId,
                'title' => $title,
                'room_type' => $roomType,
                'price' => $price,
                'location' => $location,
                'description' => $description,
                'contact_info' => $contactInfo,
                'status' => $status
            ];
            
            // Build query dynamically based on existing columns
            foreach ($fieldMap as $field => $value) {
                if (in_array($field, $columns)) {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            // Add timestamp
            if (in_array('created_at', $columns)) {
                $fields[] = 'created_at';
                $values[] = 'NOW()';
            }
            
            $sql = "INSERT INTO accommodations (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $values) . ")";
                    
            echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
            echo "<h4>SQL Query Generated:</h4>";
            echo "<pre>{$sql}</pre>";
            echo "<h4>Parameters:</h4>";
            echo "<pre>" . print_r($params, true) . "</pre>";
            echo "</div>";
            
            // Execute test
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $result = $stmt->execute();
            
            // Store the accommodation ID for later cleanup
            if ($result) {
                $accommodationId = $this->conn->lastInsertId();
                $this->testAccommodationIds[] = $accommodationId;
                
                // Verify accommodation was created by fetching it
                $selectStmt = $this->conn->prepare("SELECT * FROM accommodations WHERE accommodation_id = :accommodation_id");
                $selectStmt->bindParam(':accommodation_id', $accommodationId);
                $selectStmt->execute();
                $accommodation = $selectStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'status' => $result === true,
                'message' => $result ? "Accommodation created successfully with ID: " . $accommodationId : "Failed to create accommodation",
                'accommodation_id' => $result ? $accommodationId : null,
                'accommodation_data' => $result ? $accommodation : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error creating accommodation: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test getting all accommodations
     */
    private function testGetAccommodations() {
        try {
            // Check if users table exists and has compatible structure
            $hasUsers = false;
            try {
                $checkUsers = $this->conn->query("SHOW TABLES LIKE 'users'");
                $hasUsers = $checkUsers->rowCount() > 0;
                
                if ($hasUsers) {
                    $userFields = $this->conn->query("DESCRIBE users");
                    $hasFullName = false;
                    while ($field = $userFields->fetch(PDO::FETCH_ASSOC)) {
                        if ($field['Field'] === 'full_name') {
                            $hasFullName = true;
                            break;
                        }
                    }
                    $hasUsers = $hasUsers && $hasFullName;
                }
            } catch (PDOException $e) {
                // Ignore this check
                $hasUsers = false;
            }
            
            // Build the query based on database structure
            $sql = "SELECT a.*";
            if ($hasUsers) {
                $sql .= ", u.full_name";
            }
            $sql .= " FROM accommodations a";
            if ($hasUsers) {
                $sql .= " JOIN users u ON a.owner_id = u.user_id";
            }
            $sql .= " ORDER BY a.created_at DESC LIMIT 10";
            
            // Execute test
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => count($accommodations) > 0,
                'message' => count($accommodations) > 0 ? "Successfully retrieved " . count($accommodations) . " accommodations" : "No accommodations found",
                'accommodation_count' => count($accommodations),
                'sample_data' => count($accommodations) > 0 ? $accommodations[0] : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error getting accommodations: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test getting an accommodation by ID
     */
    private function testGetAccommodationById() {
        // Use the first test accommodation ID if available
        if (empty($this->testAccommodationIds)) {
            return [
                'status' => false,
                'message' => "No test accommodation IDs available for this test"
            ];
        }
        
        $accommodationId = $this->testAccommodationIds[0];
        
        try {
            // Check if users and accommodation_images tables exist
            $hasUsers = false;
            $hasImages = false;
            
            try {
                $checkUsers = $this->conn->query("SHOW TABLES LIKE 'users'");
                $hasUsers = $checkUsers->rowCount() > 0;
                
                $checkImages = $this->conn->query("SHOW TABLES LIKE 'accommodation_images'");
                $hasImages = $checkImages->rowCount() > 0;
            } catch (PDOException $e) {
                // Ignore this check
            }
            
            // Build query based on available tables
            $sql = "SELECT a.*";
            
            if ($hasUsers) {
                $sql .= ", u.full_name";
            }
            
            $sql .= " FROM accommodations a";
            
            if ($hasUsers) {
                $sql .= " JOIN users u ON a.owner_id = u.user_id";
            }
            
            $sql .= " WHERE a.accommodation_id = :accommodation_id";
            
            // Execute test
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':accommodation_id', $accommodationId);
            $stmt->execute();
            $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get images separately instead of using JSON_ARRAYAGG since that's not supported in older MySQL versions
            if ($hasImages) {
                $imageStmt = $this->conn->prepare("SELECT image_url FROM accommodation_images WHERE accommodation_id = :id");
                $imageStmt->bindParam(':id', $accommodationId);
                $imageStmt->execute();
                $images = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
                $accommodation['images'] = $images;
            } else {
                $accommodation['images'] = [];
            }
            
            return [
                'status' => $accommodation !== false,
                'message' => $accommodation !== false ? "Successfully retrieved accommodation with ID: " . $accommodationId : "Accommodation not found",
                'accommodation_data' => $accommodation
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error getting accommodation by ID: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test creating an accommodation inquiry
     */
    private function testCreateAccommodationInquiry() {
        // Check if accommodation_inquiries table exists
        try {
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'accommodation_inquiries'");
            if ($checkTable->rowCount() === 0) {
                return [
                    'status' => false,
                    'message' => "Cannot run test: The accommodation_inquiries table does not exist in the database."
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error checking for inquiries table: " . $e->getMessage()
            ];
        }
        
        // Use the first test accommodation ID if available
        if (empty($this->testAccommodationIds)) {
            return [
                'status' => false,
                'message' => "No test accommodation IDs available for this test"
            ];
        }
        
        $accommodationId = $this->testAccommodationIds[0];
        $userId = $this->validUserId;
        $message = "This is a test inquiry message for unit testing.";
        $status = "pending";
        
        try {
            // Check table structure first
            $stmt = $this->conn->query("DESCRIBE accommodation_inquiries");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Build dynamic query based on actual columns
            $fields = [];
            $values = [];
            $params = [];
            
            // Map data to database fields
            $fieldMap = [
                'accommodation_id' => $accommodationId,
                'user_id' => $userId,
                'message' => $message,
                'status' => $status
            ];
            
            // Build query dynamically based on existing columns
            foreach ($fieldMap as $field => $value) {
                if (in_array($field, $columns)) {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            // Add timestamp
            if (in_array('created_at', $columns)) {
                $fields[] = 'created_at';
                $values[] = 'NOW()';
            }
            
            $sql = "INSERT INTO accommodation_inquiries (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $values) . ")";
            
            // Execute test
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $result = $stmt->execute();
            
            // Store the inquiry ID for later cleanup
            if ($result) {
                $inquiryId = $this->conn->lastInsertId();
                $this->testInquiryIds[] = $inquiryId;
                
                // Verify inquiry was created by fetching it
                $selectStmt = $this->conn->prepare("SELECT * FROM accommodation_inquiries WHERE inquiry_id = :inquiry_id");
                $selectStmt->bindParam(':inquiry_id', $inquiryId);
                $selectStmt->execute();
                $inquiry = $selectStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'status' => $result === true,
                'message' => $result ? "Accommodation inquiry created successfully with ID: " . $inquiryId : "Failed to create inquiry",
                'inquiry_id' => $result ? $inquiryId : null,
                'inquiry_data' => $result ? $inquiry : null
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error creating accommodation inquiry: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test updating an accommodation
     */
    private function testUpdateAccommodation() {
        // Use the first test accommodation ID if available
        if (empty($this->testAccommodationIds)) {
            return [
                'status' => false,
                'message' => "No test accommodation IDs available for this test"
            ];
        }
        
        // Check if accommodations table exists
        if (!isset($this->databaseStructure['accommodations'])) {
            return [
                'status' => false,
                'message' => "Cannot run test: The accommodations table does not exist in the database."
            ];
        }
        
        $accommodationId = $this->testAccommodationIds[0];
        $updatedTitle = "Updated Test Accommodation " . rand(1000, 9999);
        $updatedPrice = 18000.00;
        $updatedDescription = "This is an updated test accommodation description for unit testing.";
        
        try {
            // Build dynamic update SQL based on available columns
            $updates = [];
            $params = [
                ':accommodation_id' => $accommodationId
            ];
            
            // Fields we want to update if they exist
            $updateFields = [
                'title' => $updatedTitle,
                'price' => $updatedPrice,
                'description' => $updatedDescription
            ];
            
            // Add fields that exist in the table
            foreach ($updateFields as $field => $value) {
                if (in_array($field, $this->databaseStructure['accommodations'])) {
                    $updates[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            // Add updated_at timestamp if it exists
            if (in_array('updated_at', $this->databaseStructure['accommodations'])) {
                $updates[] = "updated_at = NOW()";
            }
            
            // Execute test
            $sql = "UPDATE accommodations SET " . implode(', ', $updates) . " WHERE accommodation_id = :accommodation_id";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $result = $stmt->execute();
            
            // Verify accommodation was updated by fetching it
            if ($result) {
                $selectStmt = $this->conn->prepare("SELECT * FROM accommodations WHERE accommodation_id = :accommodation_id");
                $selectStmt->bindParam(':accommodation_id', $accommodationId);
                $selectStmt->execute();
                $accommodation = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify that the update was successful
                $updateSuccess = true;
                foreach ($updateFields as $field => $value) {
                    if (in_array($field, $this->databaseStructure['accommodations'])) {
                        // For floating point values, use approximate comparison
                        if ($field === 'price') {
                            $updateSuccess = $updateSuccess && (abs((float)$accommodation[$field] - (float)$value) < 0.01);
                        } else {
                            $updateSuccess = $updateSuccess && ($accommodation[$field] == $value);
                        }
                    }
                }
            }
            
            return [
                'status' => $result === true && $updateSuccess,
                'message' => $result 
                    ? ($updateSuccess 
                        ? "Accommodation updated successfully with ID: " . $accommodationId 
                        : "Update executed but values don't match expected values")
                    : "Failed to update accommodation",
                'accommodation_id' => $result ? $accommodationId : null,
                'updated_data' => $result ? $accommodation : null,
                'expected_data' => $updateFields
            ];
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error updating accommodation: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test searching accommodations
     */
    private function testSearchAccommodations() {
        try {
            // Check if accommodations table exists
            if (!isset($this->databaseStructure['accommodations'])) {
                return [
                    'status' => false,
                    'message' => "Cannot run test: The accommodations table does not exist in the database."
                ];
            }
            
            // Check if users table exists and has compatible structure
            $hasUsers = false;
            try {
                $checkUsers = $this->conn->query("SHOW TABLES LIKE 'users'");
                $hasUsers = $checkUsers->rowCount() > 0;
            } catch (PDOException $e) {
                // Ignore this check
                $hasUsers = false;
            }
            
            // Create a test accommodation with a unique title for searching
            $uniqueTitle = "SearchableAccommodation" . rand(1000, 9999);
            $ownerId = $this->validUserId;
            $roomType = "single";
            $price = 12000.00;
            $location = "Searchable Location";
            $description = "This is a searchable test accommodation description.";
            $contactInfo = "01712345678";
            $status = "active";
            
            // Build dynamic query based on actual columns
            $fields = [];
            $values = [];
            $params = [];
            
            // Map data to database fields
            $fieldMap = [
                'owner_id' => $ownerId,
                'title' => $uniqueTitle,
                'room_type' => $roomType,
                'price' => $price,
                'location' => $location,
                'description' => $description,
                'contact_info' => $contactInfo,
                'status' => $status
            ];
            
            // Build query dynamically based on existing columns
            foreach ($fieldMap as $field => $value) {
                if (in_array($field, $this->databaseStructure['accommodations'])) {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            // Add timestamp
            if (in_array('created_at', $this->databaseStructure['accommodations'])) {
                $fields[] = 'created_at';
                $values[] = 'NOW()';
            }
            
            // Insert the searchable accommodation
            $sql = "INSERT INTO accommodations (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $values) . ")";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $insertResult = $stmt->execute();
            
            if ($insertResult) {
                $searchableAccommodationId = $this->conn->lastInsertId();
                $this->testAccommodationIds[] = $searchableAccommodationId;
                
                // Now search for the accommodation
                $searchTerm = "Searchable";
                
                // Build search query based on available tables
                $sql = "SELECT a.*";
                
                if ($hasUsers) {
                    $sql .= ", u.full_name";
                }
                
                $sql .= " FROM accommodations a";
                
                if ($hasUsers) {
                    $sql .= " JOIN users u ON a.owner_id = u.user_id";
                }
                
                $sql .= " WHERE a.title LIKE :search 
                           OR a.location LIKE :search 
                           OR a.description LIKE :search
                           ORDER BY a.created_at DESC";
                
                $searchParam = "%{$searchTerm}%";
                $searchStmt = $this->conn->prepare($sql);
                $searchStmt->bindParam(':search', $searchParam);
                $searchStmt->execute();
                $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Check if our test accommodation was found
                $foundTestAccommodation = false;
                foreach ($searchResults as $result) {
                    if ($result['accommodation_id'] == $searchableAccommodationId) {
                        $foundTestAccommodation = true;
                        break;
                    }
                }
                
                return [
                    'status' => count($searchResults) > 0 && $foundTestAccommodation,
                    'message' => count($searchResults) > 0 
                        ? "Found " . count($searchResults) . " accommodations matching the search term" 
                        : "No matching accommodations found",
                    'search_term' => $searchTerm,
                    'result_count' => count($searchResults),
                    'test_accommodation_id' => $searchableAccommodationId,
                    'found_test_accommodation' => $foundTestAccommodation,
                    'sample_data' => count($searchResults) > 0 ? $searchResults[0] : null
                ];
            } else {
                return [
                    'status' => false,
                    'message' => "Failed to create searchable test accommodation"
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Error searching accommodations: " . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Test API endpoint for creating an accommodation
     */
    private function testApiCreateAccommodation() {
        // Simulate a POST request to the API endpoint
        $postData = [
            'title' => 'API Test Accommodation ' . rand(1000, 9999),
            'roomType' => 'single',
            'price' => 14000.00,
            'location' => 'API Test Location',
            'description' => 'This is a test accommodation created via API simulation.',
            'contactInfo' => '01712345678'
        ];
        
        try {
            // Since we're simulating the API call, we'll execute the core logic directly
            $this->conn->beginTransaction();
            
            // Check table structure first
            $stmt = $this->conn->query("DESCRIBE accommodations");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Build dynamic query based on actual columns
            $fields = [];
            $values = [];
            $params = [];
            
            // Map POST data to database fields
            $fieldMap = [
                'title' => $postData['title'],
                'room_type' => $postData['roomType'],
                'price' => $postData['price'],
                'location' => $postData['location'],
                'description' => $postData['description'],
                'contact_info' => $postData['contactInfo'],
                'owner_id' => $this->validUserId,
                'status' => 'active'
            ];
            
            // Build query dynamically based on existing columns
            foreach ($fieldMap as $field => $value) {
                if (in_array($field, $columns)) {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            $sql = "INSERT INTO accommodations (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $values) . ")";
                    
            echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
            echo "<h4>API Test SQL Query Generated:</h4>";
            echo "<pre>{$sql}</pre>";
            echo "</div>";
            
            // Insert accommodation
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            
            $accommodationId = $this->conn->lastInsertId();
            $this->testAccommodationIds[] = $accommodationId;
            
            // Check if accommodation_images table exists
            $checkImgTable = $this->conn->query("SHOW TABLES LIKE 'accommodation_images'");
            if ($checkImgTable->rowCount() > 0) {
                // Simulate image upload (we'll just insert a record)
                $imageUrl = '/uploads/accommodations/test_' . uniqid() . '.jpg';
                $imageStmt = $this->conn->prepare("INSERT INTO accommodation_images (accommodation_id, image_url) VALUES (:acc_id, :url)");
                $imageStmt->execute([':acc_id' => $accommodationId, ':url' => $imageUrl]);
            } else {
                echo "<div style='color: orange;'>Note: accommodation_images table doesn't exist, skipping image creation</div>";
            }
            
            // Get the created accommodation with all details
            $stmt = $this->conn->prepare("SELECT a.* FROM accommodations a WHERE a.accommodation_id = :id");
            $stmt->execute([':id' => $accommodationId]);
            $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Try to get user name if possible
            try {
                $userStmt = $this->conn->prepare("SELECT full_name FROM users WHERE user_id = :user_id");
                $userStmt->execute([':user_id' => $this->validUserId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $accommodation['full_name'] = $user['full_name'];
                }
            } catch (Exception $e) {
                // Ignore user name lookup errors
            }
            
            // Add uploaded images to the response
            $accommodation['images'] = $checkImgTable->rowCount() > 0 ? [$imageUrl] : [];
            
            $this->conn->commit();
            
            return [
                'status' => true,
                'message' => "API simulation: Accommodation created successfully with ID: " . $accommodationId,
                'accommodation_id' => $accommodationId,
                'post_data' => $postData,
                'accommodation_data' => $accommodation
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return [
                'status' => false,
                'message' => "API simulation error: " . $e->getMessage(),
                'error_code' => $e instanceof PDOException ? $e->getCode() : null
            ];
        }
    }
    
    /**
     * Clean up all test data created during the tests
     */
    public function cleanup() {
        $results = [
            'accommodation_cleanup' => [],
            'inquiry_cleanup' => [],
            'image_cleanup' => []
        ];
        
        // Clean up test inquiries
        foreach ($this->testInquiryIds as $inquiryId) {
            try {
                $stmt = $this->conn->prepare("DELETE FROM accommodation_inquiries WHERE inquiry_id = :inquiry_id");
                $stmt->bindParam(':inquiry_id', $inquiryId);
                $result = $stmt->execute();
                
                $results['inquiry_cleanup'][] = [
                    'inquiry_id' => $inquiryId,
                    'status' => $result,
                    'message' => $result ? "Successfully deleted test inquiry" : "Failed to delete test inquiry"
                ];
            } catch (PDOException $e) {
                $results['inquiry_cleanup'][] = [
                    'inquiry_id' => $inquiryId,
                    'status' => false,
                    'message' => "Error deleting test inquiry: " . $e->getMessage()
                ];
            }
        }
        
        // Clean up test accommodations (this will cascade delete images due to foreign key constraints)
        foreach ($this->testAccommodationIds as $accommodationId) {
            try {
                $stmt = $this->conn->prepare("DELETE FROM accommodations WHERE accommodation_id = :accommodation_id");
                $stmt->bindParam(':accommodation_id', $accommodationId);
                $result = $stmt->execute();
                
                $results['accommodation_cleanup'][] = [
                    'accommodation_id' => $accommodationId,
                    'status' => $result,
                    'message' => $result ? "Successfully deleted test accommodation" : "Failed to delete test accommodation"
                ];
            } catch (PDOException $e) {
                $results['accommodation_cleanup'][] = [
                    'accommodation_id' => $accommodationId,
                    'status' => false,
                    'message' => "Error deleting test accommodation: " . $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}

// Run tests if this file is accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new AccommodationTest();
    $results = $test->runTests();
    
    // Output results in a formatted way
    echo "<h1>Accommodation Unit Tests</h1>";
    
    foreach ($results as $testName => $result) {
        if ($testName === 'error') {
            echo "<div style='color: red;'>";
            echo "<h3>Error</h3>";
            echo "<p>{$result['message']}</p>";
            echo "</div>";
            continue;
        }
        
        $status = isset($result['status']) && $result['status'] ? "✅ PASS" : "❌ FAIL";
        $statusColor = isset($result['status']) && $result['status'] ? "green" : "red";
        
        echo "<div style='margin-bottom: 20px;'>";
        echo "<h3>{$testName} <span style='color: {$statusColor};'>{$status}</span></h3>";
        
        if (isset($result['message'])) {
            echo "<p>{$result['message']}</p>";
        }
        
        if (isset($result['accommodation_id'])) {
            echo "<p>Accommodation ID: {$result['accommodation_id']}</p>";
        }
        
        if (isset($result['accommodation_data']) && is_array($result['accommodation_data'])) {
            echo "<details>";
            echo "<summary>Accommodation Data</summary>";
            echo "<pre>" . print_r($result['accommodation_data'], true) . "</pre>";
            echo "</details>";
        }
        
        if (isset($result['search_term'])) {
            echo "<p>Search Term: {$result['search_term']}</p>";
            echo "<p>Results Found: {$result['result_count']}</p>";
        }
        
        echo "</div>";
    }
    
    // Clean up after tests
    echo "<h2>Cleaning up test data...</h2>";
    $cleanupResults = $test->cleanup();
    
    echo "<details>";
    echo "<summary>Cleanup Results</summary>";
    echo "<pre>" . print_r($cleanupResults, true) . "</pre>";
    echo "</details>";
}
?> 