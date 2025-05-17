<?php
class Comment {
    private $conn;
    private $table_name = "comments";
    
    // Properties
    public $comment_id, public $post_id, public $user_id, public $content, public $created_at, public $updated_at, public $parent_id;
    
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

    public function getByPost() {
        // TODO: Implement getByPost method
    }

    public function getByUser() {
        // TODO: Implement getByUser method
    }

    public function getReplies() {
        // TODO: Implement getReplies method
    }
}
?>