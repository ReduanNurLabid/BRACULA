<?php 
require_once __DIR__ . '/../config/database.php'; 
$database = new Database(); 
$conn = $database->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS saved_posts (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT NOT NULL, 
    post_id INT NOT NULL, 
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    UNIQUE KEY unique_save (user_id, post_id), 
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE, 
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE 
)";

$conn->exec($sql); 
echo "Saved posts table created or verified.\n"; 
?> 
