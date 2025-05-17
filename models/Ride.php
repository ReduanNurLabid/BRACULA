<?php
class Ride {
    private $conn;
    private $table_name = "rides";
    
    // Properties
    public $ride_id, public $user_id, public $origin, public $destination, public $date_time, public $seats, public $price, public $description, public $created_at, public $updated_at;
    
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

    public function requestRide() {
        // TODO: Implement requestRide method
    }

    public function approveRequest() {
        // TODO: Implement approveRequest method
    }

    public function rejectRequest() {
        // TODO: Implement rejectRequest method
    }
}
?>