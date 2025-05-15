<?php
class User {
    private $conn;
    private $table_name = "users";

    // User properties
    public $user_id;
    public $full_name;
    public $student_id;
    public $email;
    public $password;
    public $avatar_url;
    public $bio;
    public $department;
    public $interests;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        // Check if interests column exists in table
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'interests'");
            $interestsColumnExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking for interests column: " . $e->getMessage());
            $interestsColumnExists = false;
        }
        
        // Build query based on table structure
        if ($interestsColumnExists) {
            $query = "INSERT INTO " . $this->table_name . "
                    (full_name, student_id, email, password_hash, avatar_url, bio, department, interests)
                    VALUES
                    (:full_name, :student_id, :email, :password_hash, :avatar_url, :bio, :department, :interests)";
        } else {
            $query = "INSERT INTO " . $this->table_name . "
                    (full_name, student_id, email, password_hash, avatar_url, bio, department)
                    VALUES
                    (:full_name, :student_id, :email, :password_hash, :avatar_url, :bio, :department)";
        }

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->avatar_url = $this->avatar_url ? htmlspecialchars(strip_tags($this->avatar_url)) : null;
        $this->bio = $this->bio ? htmlspecialchars(strip_tags($this->bio)) : null;
        $this->department = htmlspecialchars(strip_tags($this->department));
        $this->interests = $this->interests ? htmlspecialchars(strip_tags($this->interests)) : null;

        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind data
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":avatar_url", $this->avatar_url);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":department", $this->department);
        
        // Only bind interests if the column exists
        if ($interestsColumnExists) {
            $stmt->bindParam(":interests", $this->interests);
        }

        try {
            if($stmt->execute()) {
                return true;
            }
            // Log more details about the failure
            error_log("Failed to execute user creation query: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch(PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            error_log("SQL: " . $query);
            error_log("Data: " . print_r([
                'full_name' => $this->full_name,
                'student_id' => $this->student_id,
                'email' => $this->email,
                'avatar_url' => $this->avatar_url,
                'bio' => $this->bio,
                'department' => $this->department,
                'interests' => $this->interests
            ], true));
            return false;
        }
    }

    // Login user
    public function login($email, $password) {
        // Check if interests column exists in table
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'interests'");
            $interestsColumnExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking for interests column: " . $e->getMessage());
            $interestsColumnExists = false;
        }
        
        // Build query based on table structure
        if ($interestsColumnExists) {
            $query = "SELECT user_id, full_name, email, password_hash, avatar_url, bio, department, interests 
                    FROM " . $this->table_name . "
                    WHERE email = :email";
        } else {
            $query = "SELECT user_id, full_name, email, password_hash, avatar_url, bio, department
                    FROM " . $this->table_name . "
                    WHERE email = :email";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        
        try {
            $stmt->execute();

            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(password_verify($password, $row['password_hash'])) {
                    $this->user_id = $row['user_id'];
                    $this->full_name = $row['full_name'];
                    $this->email = $row['email'];
                    $this->avatar_url = $row['avatar_url'];
                    $this->bio = $row['bio'];
                    $this->department = $row['department'];
                    
                    // Only set interests if the column exists
                    if ($interestsColumnExists) {
                        $this->interests = $row['interests'];
                    } else {
                        $this->interests = null;
                    }
                    
                    return true;
                }
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error during login: " . $e->getMessage());
            error_log("SQL: " . $query);
            return false;
        }
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Check if student ID exists
    public function studentIdExists() {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Update user profile
    public function update() {
        // Check if interests column exists in table
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'interests'");
            $interestsColumnExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking for interests column: " . $e->getMessage());
            $interestsColumnExists = false;
        }
        
        // Build query based on table structure
        if ($interestsColumnExists) {
            $query = "UPDATE " . $this->table_name . "
                    SET full_name = :full_name,
                        avatar_url = :avatar_url,
                        bio = :bio,
                        interests = :interests
                    WHERE user_id = :user_id";
        } else {
            $query = "UPDATE " . $this->table_name . "
                    SET full_name = :full_name,
                        avatar_url = :avatar_url,
                        bio = :bio
                    WHERE user_id = :user_id";
        }

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->avatar_url = $this->avatar_url ? htmlspecialchars(strip_tags($this->avatar_url)) : null;
        $this->bio = $this->bio ? htmlspecialchars(strip_tags($this->bio)) : null;
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        if ($interestsColumnExists) {
            $this->interests = $this->interests ? htmlspecialchars(strip_tags($this->interests)) : null;
        }

        // Bind data
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":avatar_url", $this->avatar_url);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":user_id", $this->user_id);
        
        // Only bind interests if the column exists
        if ($interestsColumnExists) {
            $stmt->bindParam(":interests", $this->interests);
        }

        try {
            if($stmt->execute()) {
                return true;
            }
            error_log("Failed to execute user update query: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch(PDOException $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            error_log("SQL: " . $query);
            return false;
        }
    }

    // Get user by ID
    public function getById($id) {
        // Check if interests column exists in table
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'interests'");
            $interestsColumnExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking for interests column: " . $e->getMessage());
            $interestsColumnExists = false;
        }
        
        // Build query based on table structure
        if ($interestsColumnExists) {
            $query = "SELECT user_id, full_name, email, avatar_url, bio, department, interests, created_at 
                    FROM " . $this->table_name . "
                    WHERE user_id = :user_id";
        } else {
            $query = "SELECT user_id, full_name, email, avatar_url, bio, department, created_at 
                    FROM " . $this->table_name . "
                    WHERE user_id = :user_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error getting user by ID: " . $e->getMessage());
            error_log("SQL: " . $query);
            return false;
        }
    }
}
?> 