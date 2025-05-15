<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>BRACULA Database Reset Tool</h1>";
echo "<p>This script will completely reset your database. All data will be lost!</p>";

// Check if confirmation is received
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo '<div style="background-color: #ffeeee; padding: 20px; border: 2px solid #ff0000; margin: 20px 0;">';
    echo '<h2 style="color: #ff0000;">⚠️ WARNING: This will delete all your data!</h2>';
    echo '<p>Are you sure you want to completely reset the database?</p>';
    echo '<p>All users, posts, comments, events, and other data will be permanently deleted.</p>';
    echo '<a href="?confirm=yes" style="display: inline-block; background-color: #ff0000; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Yes, Reset Database</a>';
    echo '<a href="../index.php" style="display: inline-block; background-color: #333; color: white; padding: 10px 20px; text-decoration: none;">Cancel</a>';
    echo '</div>';
    exit;
}

// If we get here, user has confirmed
echo "<h2>Resetting Database...</h2>";

try {
    // Connect to MySQL without selecting a database
    $host = "localhost";
    $username = "root";
    $password = "";
    $db_name = "bracula_db";
    
    echo "<p>Connecting to MySQL server...</p>";
    
    $conn = new PDO(
        "mysql:host=$host",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connected to MySQL server successfully!</p>";
    
    // Drop the database if it exists
    echo "<p>Dropping existing database '$db_name'...</p>";
    $conn->exec("DROP DATABASE IF EXISTS $db_name");
    echo "<p style='color:green'>✅ Database dropped successfully!</p>";
    
    // Create a fresh database
    echo "<p>Creating new database '$db_name'...</p>";
    $conn->exec("CREATE DATABASE $db_name");
    echo "<p style='color:green'>✅ Database created successfully!</p>";
    
    // Select the database
    echo "<p>Selecting database '$db_name'...</p>";
    $conn->exec("USE $db_name");
    echo "<p style='color:green'>✅ Database selected successfully!</p>";
    
    // Read and execute SQL file
    echo "<p>Creating tables from SQL file...</p>";
    $sql = file_get_contents(__DIR__ . '/bracula_db.sql');
    
    // Execute multiple SQL statements
    $conn->exec($sql);
    echo "<p style='color:green'>✅ Tables created successfully!</p>";
    
    // Show tables
    echo "<h3>Created Tables:</h3>";
    $stmt = $conn->query("SHOW TABLES");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>{$row[0]}</li>";
    }
    echo "</ul>";
    
    // Initialize with test data
    echo "<h2>Adding Test Data...</h2>";
    
    // Add test users
    echo "<p>Adding test users...</p>";
    $hashedPassword = password_hash("Test@123", PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("INSERT INTO users (full_name, student_id, email, password_hash, avatar_url, bio, department, interests) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Test User 1
    $stmt->execute([
        "Test User", 
        "22101804", 
        "test@g.bracu.ac.bd", 
        $hashedPassword,
        "https://avatar.iran.liara.run/public/boy",
        "I'm a test user for the BRACULA system.",
        "CSE",
        "Programming, Web Development, AI"
    ]);
    
    // Test User 2
    $stmt->execute([
        "Jane Doe", 
        "22101805", 
        "jane@g.bracu.ac.bd", 
        $hashedPassword,
        "https://avatar.iran.liara.run/public/girl",
        "Computer Science student interested in machine learning.",
        "CSE",
        "Machine Learning, Data Science, Robotics"
    ]);
    
    echo "<p style='color:green'>✅ Test users added successfully!</p>";
    
    // Add test posts
    echo "<p>Adding test posts...</p>";
    $stmt = $conn->prepare("INSERT INTO posts (user_id, caption, content, community, votes) 
                           VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute([
        1, 
        "First Post in BRACULA", 
        "Hello everyone! This is the first post in our BRACULA system. Hope you enjoy using it!",
        "General",
        5
    ]);
    
    $stmt->execute([
        2, 
        "Looking for study partners", 
        "I'm taking CSE370 this semester and looking for study partners. Anyone interested?",
        "Academic",
        3
    ]);
    
    echo "<p style='color:green'>✅ Test posts added successfully!</p>";
    
    // Add test comments
    echo "<p>Adding test comments...</p>";
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) 
                           VALUES (?, ?, ?)");
    
    $stmt->execute([
        1, 
        2, 
        "Welcome! This system looks great!"
    ]);
    
    $stmt->execute([
        2, 
        1, 
        "I'm taking that course too. Let's connect!"
    ]);
    
    echo "<p style='color:green'>✅ Test comments added successfully!</p>";
    
    // Add test resources
    echo "<p>Adding test resources...</p>";
    $stmt = $conn->prepare("INSERT INTO resources (user_id, course_code, semester, file_name, file_type, file_url, downloads) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        1, 
        "CSE370", 
        "Spring 2025",
        "Database_Systems_Slides.pptx",
        "slides",
        "uploads/resources/database_slides.pptx",
        18
    ]);
    
    $stmt->execute([
        2, 
        "CSE220", 
        "Fall 2024",
        "Data_Structures_Notes.pdf",
        "notes",
        "uploads/resources/data_structures.pdf",
        8
    ]);
    
    echo "<p style='color:green'>✅ Test resources added successfully!</p>";
    
    echo "<h2>Database Reset Complete!</h2>";
    echo "<p>Your BRACULA database has been reset and populated with test data.</p>";
    echo "<p><a href='../index.php' style='display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none;'>Go to Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error:</h3>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
}
?> 