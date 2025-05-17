<?php
class Event {
    private $conn;
    private $table_name = "events";
    
    // Properties
    public $event_id, public $user_id, public $title, public $description, public $location, public $date_time, public $created_at, public $updated_at;
    
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

    public function register() {
        // TODO: Implement register method
    }

    public function unregister() {
        // TODO: Implement unregister method
    }

    public function getRegistrations() {
        // TODO: Implement getRegistrations method
    }
}
?>