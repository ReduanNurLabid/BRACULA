<?php
class Post {
    private $conn;
    private $table_name = "posts";
    
    // Properties
    public $post_id, public $user_id, public $content, public $caption, public $community, public $created_at, public $updated_at, public $vote_count, public $comment_count;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Methods
    public function create() {
        // TODO: Implement create method
    }

    public function read() {
        // TODO: Implement read method
    }

    public function update() {
        // TODO: Implement update method
    }

    public function delete() {
        // TODO: Implement delete method
    }

    public function getById() {
        // TODO: Implement getById method
    }

    public function getByUser() {
        // TODO: Implement getByUser method
    }

    public function getByCommunity() {
        // TODO: Implement getByCommunity method
    }

    public function vote() {
        // TODO: Implement vote method
    }

    public function incrementCommentCount() {
        // TODO: Implement incrementCommentCount method
    }
}
?>