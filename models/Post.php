<?php
class Post {
    private $conn;
    private $table_name = "posts";
    
    // Post properties
    public $post_id;
    public $user_id;
    public $title;
    public $content;
    public $category;
    public $votes;
    public $created_at;
    public $updated_at;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new post
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, title, content, category, votes, created_at)
                VALUES
                (:user_id, :title, :content, :category, 0, NOW())";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->category = htmlspecialchars(strip_tags($this->category));

        // Bind data
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":category", $this->category);

        try {
            if($stmt->execute()) {
                $this->post_id = $this->conn->lastInsertId();
                return true;
            }
            error_log("Failed to execute post creation query: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch(PDOException $e) {
            error_log("Error creating post: " . $e->getMessage());
            return false;
        }
    }

    // Update post
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET title = :title,
                    content = :content,
                    category = :category,
                    updated_at = NOW()
                WHERE post_id = :post_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->post_id = htmlspecialchars(strip_tags($this->post_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":post_id", $this->post_id);
        $stmt->bindParam(":user_id", $this->user_id);

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error updating post: " . $e->getMessage());
            return false;
        }
    }

    // Delete post
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE post_id = :post_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->post_id = htmlspecialchars(strip_tags($this->post_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bindParam(":post_id", $this->post_id);
        $stmt->bindParam(":user_id", $this->user_id);

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error deleting post: " . $e->getMessage());
            return false;
        }
    }

    // Get all posts
    public function getAll($limit = 10, $offset = 0, $category = null) {
        $query = "SELECT p.*, u.full_name, u.avatar_url 
                FROM " . $this->table_name . " p
                JOIN users u ON p.user_id = u.user_id";
        
        if($category) {
            $query .= " WHERE p.category = :category";
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        if($category) {
            $category = htmlspecialchars(strip_tags($category));
            $stmt->bindParam(":category", $category);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Get post by ID
    public function getById($id) {
        $query = "SELECT p.*, u.full_name, u.avatar_url 
                FROM " . $this->table_name . " p
                JOIN users u ON p.user_id = u.user_id
                WHERE p.post_id = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get posts by user ID
    public function getByUserId($user_id, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, u.full_name, u.avatar_url 
                FROM " . $this->table_name . " p
                JOIN users u ON p.user_id = u.user_id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Vote on post
    public function vote($vote_type, $user_id) {
        // First check if user already voted
        $check_query = "SELECT vote_id, vote_type FROM post_votes 
                        WHERE post_id = :post_id AND user_id = :user_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":post_id", $this->post_id);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();
        
        $post_vote = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->conn->beginTransaction();
        
        try {
            if($post_vote) {
                // User already voted, update the vote
                if($post_vote['vote_type'] == $vote_type) {
                    // Remove vote if clicking same button
                    $delete_query = "DELETE FROM post_votes WHERE vote_id = :vote_id";
                    $delete_stmt = $this->conn->prepare($delete_query);
                    $delete_stmt->bindParam(":vote_id", $post_vote['vote_id']);
                    $delete_stmt->execute();
                    
                    // Update post votes
                    $vote_change = ($vote_type == 'up') ? -1 : 1;
                } else {
                    // Change vote direction
                    $update_query = "UPDATE post_votes SET vote_type = :vote_type 
                                    WHERE vote_id = :vote_id";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(":vote_type", $vote_type);
                    $update_stmt->bindParam(":vote_id", $post_vote['vote_id']);
                    $update_stmt->execute();
                    
                    // Update post votes (2x because we're changing direction)
                    $vote_change = ($vote_type == 'up') ? 2 : -2;
                }
            } else {
                // New vote
                $insert_query = "INSERT INTO post_votes (post_id, user_id, vote_type) 
                                VALUES (:post_id, :user_id, :vote_type)";
                $insert_stmt = $this->conn->prepare($insert_query);
                $insert_stmt->bindParam(":post_id", $this->post_id);
                $insert_stmt->bindParam(":user_id", $user_id);
                $insert_stmt->bindParam(":vote_type", $vote_type);
                $insert_stmt->execute();
                
                // Update post votes
                $vote_change = ($vote_type == 'up') ? 1 : -1;
            }
            
            // Update the post's votes count
            $update_post_query = "UPDATE posts SET votes = votes + :vote_change 
                                WHERE post_id = :post_id";
            $update_post_stmt = $this->conn->prepare($update_post_query);
            $update_post_stmt->bindParam(":vote_change", $vote_change);
            $update_post_stmt->bindParam(":post_id", $this->post_id);
            $update_post_stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Error voting on post: " . $e->getMessage());
            return false;
        }
    }

    // Save post
    public function savePost($user_id) {
        // Check if already saved
        $check_query = "SELECT saved_id FROM saved_posts 
                        WHERE post_id = :post_id AND user_id = :user_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":post_id", $this->post_id);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            // Post already saved, unsave it
            $query = "DELETE FROM saved_posts WHERE post_id = :post_id AND user_id = :user_id";
            $result = "unsaved";
        } else {
            // Save the post
            $query = "INSERT INTO saved_posts (post_id, user_id, saved_at) 
                    VALUES (:post_id, :user_id, NOW())";
            $result = "saved";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $this->post_id);
        $stmt->bindParam(":user_id", $user_id);
        
        try {
            if($stmt->execute()) {
                return $result;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error saving/unsaving post: " . $e->getMessage());
            return false;
        }
    }

    // Get saved posts by user
    public function getSavedPosts($user_id, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, u.full_name, u.avatar_url, sp.saved_at 
                FROM saved_posts sp
                JOIN posts p ON sp.post_id = p.post_id
                JOIN users u ON p.user_id = u.user_id
                WHERE sp.user_id = :user_id
                ORDER BY sp.saved_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Search posts
    public function search($keywords, $limit = 10, $offset = 0) {
        $search_terms = explode(' ', $keywords);
        $query = "SELECT p.*, u.full_name, u.avatar_url 
                FROM " . $this->table_name . " p
                JOIN users u ON p.user_id = u.user_id
                WHERE ";
        
        $search_conditions = [];
        $params = [];
        
        foreach($search_terms as $i => $term) {
            $safe_term = "%" . htmlspecialchars(strip_tags($term)) . "%";
            $param_name = ":term" . $i;
            $search_conditions[] = "(p.title LIKE $param_name OR p.content LIKE $param_name)";
            $params[$param_name] = $safe_term;
        }
        
        $query .= implode(" AND ", $search_conditions);
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $param_name => $param_value) {
            $stmt->bindParam($param_name, $param_value);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
}
?>