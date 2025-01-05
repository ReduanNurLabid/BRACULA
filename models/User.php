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

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (full_name, student_id, email, password_hash, avatar_url, bio, department)
                VALUES
                (:full_name, :student_id, :email, :password_hash, :avatar_url, :bio, :department)";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->avatar_url = htmlspecialchars(strip_tags($this->avatar_url));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->department = htmlspecialchars(strip_tags($this->department));

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

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    // Login user
    public function login($email, $password) {
        $query = "SELECT user_id, full_name, email, password_hash 
                FROM " . $this->table_name . "
                WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password_hash'])) {
                $this->user_id = $row['user_id'];
                $this->full_name = $row['full_name'];
                $this->email = $row['email'];
                return true;
            }
        }

        return false;
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
        $query = "UPDATE " . $this->table_name . "
                SET full_name = :full_name,
                    avatar_url = :avatar_url,
                    bio = :bio
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->avatar_url = htmlspecialchars(strip_tags($this->avatar_url));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":avatar_url", $this->avatar_url);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":user_id", $this->user_id);

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }

    // Get user by ID
    public function getById($id) {
        $query = "SELECT user_id, full_name, email, avatar_url, bio, created_at 
                FROM " . $this->table_name . "
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 