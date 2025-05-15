<?php
// Include database connection
require_once __DIR__ . '/../config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Create accommodations table
    $sql = "
    CREATE TABLE IF NOT EXISTS accommodations (
        accommodation_id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        room_type VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        location VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        contact_info VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Accommodations table created successfully<br>";
    
    // Create accommodation_images table
    $sql = "
    CREATE TABLE IF NOT EXISTS accommodation_images (
        image_id INT AUTO_INCREMENT PRIMARY KEY,
        accommodation_id INT NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (accommodation_id) REFERENCES accommodations(accommodation_id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Accommodation images table created successfully<br>";
    
    // Create accommodation_favorites table
    $sql = "
    CREATE TABLE IF NOT EXISTS accommodation_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        accommodation_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (accommodation_id, user_id),
        FOREIGN KEY (accommodation_id) REFERENCES accommodations(accommodation_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Accommodation favorites table created successfully<br>";
    
    // Create accommodation_inquiries table
    $sql = "
    CREATE TABLE IF NOT EXISTS accommodation_inquiries (
        inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
        accommodation_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (accommodation_id) REFERENCES accommodations(accommodation_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Accommodation inquiries table created successfully<br>";
    
    // Create accommodation_reviews table
    $sql = "
    CREATE TABLE IF NOT EXISTS accommodation_reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        accommodation_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (accommodation_id) REFERENCES accommodations(accommodation_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Accommodation reviews table created successfully<br>";
    
    // Create indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodations_owner ON accommodations(owner_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodations_status ON accommodations(status)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodations_location ON accommodations(location)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodations_room_type ON accommodations(room_type)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodation_images ON accommodation_images(accommodation_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodation_favorites_user ON accommodation_favorites(user_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_accommodation_inquiries_status ON accommodation_inquiries(status)");
    echo "Indexes created successfully<br>";
    
    // Commit transaction
    $conn->commit();
    
    echo "<h3>All accommodation tables have been successfully created!</h3>";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo "Error creating tables: " . $e->getMessage();
}
?> 