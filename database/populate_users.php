<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Sample users data
    $users = [
        [
            'full_name' => 'John Doe',
            'student_id' => '20301001',
            'email' => 'john.doe@g.bracu.ac.bd',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'avatar_url' => 'https://avatar.iran.liara.run/public/1',
            'bio' => 'Computer Science student interested in AI and Machine Learning',
            'department' => 'Computer Science and Engineering (CSE)'
        ],
        [
            'full_name' => 'Jane Smith',
            'student_id' => '20301002',
            'email' => 'jane.smith@g.bracu.ac.bd',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'avatar_url' => 'https://avatar.iran.liara.run/public/2',
            'bio' => 'Business student passionate about Marketing',
 
        ]
    ];

    // Insert users into database
    $query = "INSERT INTO users (full_name, student_id, email, password_hash, avatar_url, bio, department) 
              VALUES (:full_name, :student_id, :email, :password_hash, :avatar_url, :bio, :department)";
    
    $stmt = $db->prepare($query);

    foreach ($users as $user) {
        $stmt->bindParam(":full_name", $user['full_name']);
        $stmt->bindParam(":student_id", $user['student_id']);
        $stmt->bindParam(":email", $user['email']);
        $stmt->bindParam(":password_hash", $user['password_hash']);
        $stmt->bindParam(":avatar_url", $user['avatar_url']);
        $stmt->bindParam(":bio", $user['bio']);
        $stmt->bindParam(":department", $user['department']);
        
        $stmt->execute();
    }

    echo "Sample users have been added successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 