<?php
/**
 * Unit Test for Profile Functionality
 * 
 * This file tests the profile functionality in the BRACULA application.
 * Tests cover creating profiles, reading profiles, updating profiles,
 * and managing profile settings and preferences.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

// Set up test environment
class ProfileTest {
    private $conn;
    private $testProfileIds = [];
    private $testUserId = null;
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
            // Check if profiles table exists
            $tables = [
                'profiles' => false,
                'profile_settings' => false,
                'profile_preferences' => false
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
            
            if (!$tables['profiles']) {
                echo "<div style='background-color: #ffdddd; padding: 10px; margin-top: 10px; border-radius: 5px;'>";
                echo "<strong>Critical Error:</strong> The profiles table is missing. Most tests will fail. ";
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
                $this->testUserId = $user['user_id'];
            }
        } catch (PDOException $e) {
            // Users table might not exist or other issue
            $this->testUserId = null;
        }
    }
    
    /**
     * Run all test methods
     */
    public function runTests() {
        // Check if we have a valid user for testing
        if ($this->testUserId === null) {
            return [
                'error' => [
                    'status' => false,
                    'message' => "Cannot run tests: No valid user found in the database. Please ensure the users table exists and has at least one user."
                ]
            ];
        }
        
        // Check if profiles table exists
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'profiles'");
            if ($stmt->rowCount() === 0) {
                return [
                    'error' => [
                        'status' => false,
                        'message' => "Cannot run tests: The profiles table does not exist in the database."
                    ]
                ];
            }
            
            // Verify table structure
            $stmt = $this->conn->query("DESCRIBE profiles");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
            echo "<h4>Profiles Table Structure:</h4>";
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
            'createProfile' => $this->testCreateProfile(),
            'getProfile' => $this->testGetProfile(),
            'updateProfile' => $this->testUpdateProfile(),
            'updateProfileSettings' => $this->testUpdateProfileSettings(),
            'updateProfilePreferences' => $this->testUpdateProfilePreferences()
        ];
        
        return $results;
    }
    
    /**
     * Test creating a profile with valid data
     */
    private function testCreateProfile() {
        // Test data
        $userId = $this->testUserId;
        $firstName = "Test";
        $lastName = "User";
        $bio = "This is a test profile bio.";
        $phone = "01712345678";
        $address = "Test Address";
        
        try {
            // Check table structure first
            $stmt = $this->conn->query("DESCRIBE profiles");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Build dynamic query based on actual columns
            $fields = [];
            $values = [];
            $params = [];
            
            $data = [
                'user_id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'bio' => $bio,
                'phone' => $phone,
                'address' => $address
            ];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $columns)) {
                    $fields[] = $field;
                    $values[] = ":$field";
                    $params[":$field"] = $value;
                }
            }
            
            $query = "INSERT INTO profiles (" . implode(", ", $fields) . ") 
                     VALUES (" . implode(", ", $values) . ")";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $profileId = $this->conn->lastInsertId();
                $this->testProfileIds[] = $profileId;
                
                return [
                    'status' => true,
                    'message' => "Successfully created test profile",
                    'profile_id' => $profileId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to create test profile"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test retrieving a profile
     */
    private function testGetProfile() {
        if (empty($this->testProfileIds)) {
            return [
                'status' => false,
                'message' => "No test profiles available"
            ];
        }
        
        $profileId = $this->testProfileIds[0];
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM profiles WHERE profile_id = ?");
            $stmt->execute([$profileId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($profile) {
                return [
                    'status' => true,
                    'message' => "Successfully retrieved profile",
                    'profile' => $profile
                ];
            }
            
            return [
                'status' => false,
                'message' => "Profile not found"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test updating a profile
     */
    private function testUpdateProfile() {
        if (empty($this->testProfileIds)) {
            return [
                'status' => false,
                'message' => "No test profiles available"
            ];
        }
        
        $profileId = $this->testProfileIds[0];
        $newBio = "Updated test profile bio " . rand(1000, 9999);
        
        try {
            $stmt = $this->conn->prepare("UPDATE profiles SET bio = ? WHERE profile_id = ?");
            $result = $stmt->execute([$newBio, $profileId]);
            
            if ($result) {
                return [
                    'status' => true,
                    'message' => "Successfully updated profile",
                    'profile_id' => $profileId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to update profile"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test updating profile settings
     */
    private function testUpdateProfileSettings() {
        if (empty($this->testProfileIds)) {
            return [
                'status' => false,
                'message' => "No test profiles available"
            ];
        }
        
        $profileId = $this->testProfileIds[0];
        $settings = [
            'email_notifications' => true,
            'sms_notifications' => false,
            'privacy_level' => 'public'
        ];
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO profile_settings (profile_id, setting_key, setting_value) 
                                        VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            $success = true;
            foreach ($settings as $key => $value) {
                $result = $stmt->execute([$profileId, $key, json_encode($value)]);
                if (!$result) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                return [
                    'status' => true,
                    'message' => "Successfully updated profile settings",
                    'profile_id' => $profileId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to update profile settings"
            ];
            
        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test updating profile preferences
     */
    private function testUpdateProfilePreferences() {
        if (empty($this->testProfileIds)) {
            return [
                'status' => false,
                'message' => "No test profiles available"
            ];
        }
        
        $profileId = $this->testProfileIds[0];
        $preferences = [
            'language' => 'en',
            'theme' => 'light',
            'timezone' => 'UTC'
        ];
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO profile_preferences (profile_id, preference_key, preference_value) 
                                        VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)");
            
            $success = true;
            foreach ($preferences as $key => $value) {
                $result = $stmt->execute([$profileId, $key, json_encode($value)]);
                if (!$result) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                return [
                    'status' => true,
                    'message' => "Successfully updated profile preferences",
                    'profile_id' => $profileId
                ];
            }
            
            return [
                'status' => false,
                'message' => "Failed to update profile preferences"
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
            // Delete test profile settings
            if (!empty($this->testProfileIds)) {
                $placeholders = str_repeat('?,', count($this->testProfileIds) - 1) . '?';
                $stmt = $this->conn->prepare("DELETE FROM profile_settings WHERE profile_id IN ($placeholders)");
                $stmt->execute($this->testProfileIds);
                
                // Delete test profile preferences
                $stmt = $this->conn->prepare("DELETE FROM profile_preferences WHERE profile_id IN ($placeholders)");
                $stmt->execute($this->testProfileIds);
                
                // Delete test profiles
                $stmt = $this->conn->prepare("DELETE FROM profiles WHERE profile_id IN ($placeholders)");
                $stmt->execute($this->testProfileIds);
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
$test = new ProfileTest();
$results = $test->runTests();

// Display results
echo "<div style='background-color: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h2>Profile Test Results</h2>";
echo "<pre>" . print_r($results, true) . "</pre>";
echo "</div>";

// Clean up
$cleanup = $test->cleanup();
echo "<div style='background-color: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h2>Cleanup Results</h2>";
echo "<pre>" . print_r($cleanup, true) . "</pre>";
echo "</div>"; 