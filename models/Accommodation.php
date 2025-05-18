<?php
class Accommodation {
    private $conn;
    private $table_name = "accommodations";
    
    // Properties
    public $accommodation_id;
    public $user_id;
    public $title;
    public $description;
    public $location;
    public $price;
    public $availability;
    public $created_at;
    public $updated_at;
    
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

    public function search() {
        // TODO: Implement search method
    }

    public function inquire() {
        // TODO: Implement inquire method
    }

    public function respondToInquiry() {
        // TODO: Implement respondToInquiry method
    }
}
?>