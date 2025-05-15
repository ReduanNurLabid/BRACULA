<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Updating Posts Table</h1>\n";

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<p>Connected to database: " . $database->db_name . "</p>\n";
    
    // Check if comment_count column exists in posts table
    $stmt = $conn->query("SHOW COLUMNS FROM posts LIKE 'comment_count'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "<p style='color:green'>✅ The 'comment_count' column already exists in the posts table.</p>\n";
    } else {
        echo "<p style='color:orange'>⚠️ The 'comment_count' column is missing from the posts table. Adding it now...</p>\n";
        
        // Add the comment_count column
        $sql = "ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 AFTER votes";
        $conn->exec($sql);
        
        echo "<p style='color:green'>✅ Successfully added 'comment_count' column to posts table.</p>\n";
        
        // Update comment counts for existing posts
        echo "<p>Updating comment counts for existing posts...</p>\n";
        
        // Get all posts
        $postsStmt = $conn->query("SELECT post_id FROM posts");
        $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updatedCount = 0;
        foreach ($posts as $post) {
            // Count comments for this post
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
            $countStmt->execute([$post['post_id']]);
            $commentCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Update the post's comment_count
            $updateStmt = $conn->prepare("UPDATE posts SET comment_count = ? WHERE post_id = ?");
            $updateStmt->execute([$commentCount, $post['post_id']]);
            $updatedCount++;
        }
        
        echo "<p style='color:green'>✅ Updated comment counts for {$updatedCount} posts.</p>\n";
    }
    
    // Show current table structure
    echo "<h3>Current posts table structure:</h3>\n";
    $stmt = $conn->query("DESCRIBE posts");
    echo "<pre>\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    echo "</pre>\n";
    
    echo "<h2>Next Steps</h2>\n";
    echo "<p>The posts table has been updated to include comment counts.</p>\n";
    echo "<p><a href='../index.php'>Go to Homepage</a></p>\n";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>\n";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>\n";
}
?> 