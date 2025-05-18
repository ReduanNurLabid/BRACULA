<?php
class Ride {
    private $conn;
    private $table_name = "rides";
    
    // Properties
    public $ride_id;
    public $user_id;
    public $from_location;
    public $to_location;
    public $departure_time;
    public $seats_available;
    public $price;
    public $vehicle_description;
    public $notes;
    public $status; // 'active', 'completed', 'cancelled'
    public $created_at;
    public $updated_at;
    public $post_id;
    public $content;
    public $caption;
    public $community;
    public $vote_count;
    public $comment_count;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Methods
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, from_location, to_location, departure_time, seats_available, 
                price, vehicle_description, notes, status, created_at)
                VALUES
                (:user_id, :from_location, :to_location, :departure_time, :seats_available,
                :price, :vehicle_description, :notes, 'active', NOW())";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->from_location = htmlspecialchars(strip_tags($this->from_location));
        $this->to_location = htmlspecialchars(strip_tags($this->to_location));
        $this->departure_time = htmlspecialchars(strip_tags($this->departure_time));
        $this->seats_available = htmlspecialchars(strip_tags($this->seats_available));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->vehicle_description = htmlspecialchars(strip_tags($this->vehicle_description));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind data
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":from_location", $this->from_location);
        $stmt->bindParam(":to_location", $this->to_location);
        $stmt->bindParam(":departure_time", $this->departure_time);
        $stmt->bindParam(":seats_available", $this->seats_available);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":vehicle_description", $this->vehicle_description);
        $stmt->bindParam(":notes", $this->notes);

        try {
            if($stmt->execute()) {
                $this->ride_id = $this->conn->lastInsertId();
                return true;
            }
            error_log("Failed to execute ride creation query: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch(PDOException $e) {
            error_log("Error creating ride: " . $e->getMessage());
            return false;
        }
    }

    public function read() {
        // TODO: Implement read method
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET from_location = :from_location,
                    to_location = :to_location,
                    departure_time = :departure_time,
                    seats_available = :seats_available,
                    price = :price,
                    vehicle_description = :vehicle_description,
                    notes = :notes,
                    status = :status,
                    updated_at = NOW()
                WHERE ride_id = :ride_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->from_location = htmlspecialchars(strip_tags($this->from_location));
        $this->to_location = htmlspecialchars(strip_tags($this->to_location));
        $this->departure_time = htmlspecialchars(strip_tags($this->departure_time));
        $this->seats_available = htmlspecialchars(strip_tags($this->seats_available));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->vehicle_description = htmlspecialchars(strip_tags($this->vehicle_description));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->ride_id = htmlspecialchars(strip_tags($this->ride_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bindParam(":from_location", $this->from_location);
        $stmt->bindParam(":to_location", $this->to_location);
        $stmt->bindParam(":departure_time", $this->departure_time);
        $stmt->bindParam(":seats_available", $this->seats_available);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":vehicle_description", $this->vehicle_description);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":ride_id", $this->ride_id);
        $stmt->bindParam(":user_id", $this->user_id);

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error updating ride: " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ride_id = :ride_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->ride_id = htmlspecialchars(strip_tags($this->ride_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bindParam(":ride_id", $this->ride_id);
        $stmt->bindParam(":user_id", $this->user_id);

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error deleting ride: " . $e->getMessage());
            return false;
        }
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

    // Change ride status
    public function changeStatus($status) {
        $allowed_statuses = ['active', 'completed', 'cancelled'];
        
        if(!in_array($status, $allowed_statuses)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    updated_at = NOW()
                WHERE ride_id = :ride_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Bind data
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":ride_id", $this->ride_id);
        $stmt->bindParam(":user_id", $this->user_id);

        try {
            if($stmt->execute()) {
                $this->status = $status;
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error changing ride status: " . $e->getMessage());
            return false;
        }
    }

    // Get all rides
    public function getAll($limit = 10, $offset = 0, $filters = []) {
        $query = "SELECT r.*, u.full_name, u.avatar_url 
                FROM " . $this->table_name . " r
                JOIN users u ON r.user_id = u.user_id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters if provided
        if(isset($filters['from_location']) && !empty($filters['from_location'])) {
            $query .= " AND r.from_location LIKE :from_location";
            $params[':from_location'] = '%' . htmlspecialchars(strip_tags($filters['from_location'])) . '%';
        }
        
        if(isset($filters['to_location']) && !empty($filters['to_location'])) {
            $query .= " AND r.to_location LIKE :to_location";
            $params[':to_location'] = '%' . htmlspecialchars(strip_tags($filters['to_location'])) . '%';
        }
        
        if(isset($filters['date']) && !empty($filters['date'])) {
            $query .= " AND DATE(r.departure_time) = :date";
            $params[':date'] = htmlspecialchars(strip_tags($filters['date']));
        }
        
        if(isset($filters['status']) && !empty($filters['status'])) {
            $query .= " AND r.status = :status";
            $params[':status'] = htmlspecialchars(strip_tags($filters['status']));
        }
        
        $query .= " ORDER BY r.departure_time ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Get ride requests
    public function getRideRequests($status = null) {
        $query = "SELECT rr.*, u.full_name, u.avatar_url, u.email 
                FROM ride_requests rr
                JOIN users u ON rr.user_id = u.user_id
                WHERE rr.ride_id = :ride_id";
                
        if($status) {
            $query .= " AND rr.status = :status";
        }
        
        $query .= " ORDER BY rr.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ride_id", $this->ride_id);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->execute();

        return $stmt;
    }

    // Handle ride request (accept/reject)
    public function handleRequest($request_id, $action) {
        if(!in_array($action, ['accept', 'reject'])) {
            return ["error" => "Invalid action"];
        }
        
        // Get the request details
        $request_query = "SELECT * FROM ride_requests WHERE request_id = :request_id AND ride_id = :ride_id";
        $request_stmt = $this->conn->prepare($request_query);
        $request_stmt->bindParam(":request_id", $request_id);
        $request_stmt->bindParam(":ride_id", $this->ride_id);
        $request_stmt->execute();
        
        $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$request) {
            return ["error" => "Request not found"];
        }
        
        if($request['status'] != 'pending') {
            return ["error" => "Request has already been " . $request['status']];
        }
        
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Update request status
            $update_query = "UPDATE ride_requests SET status = :status, updated_at = NOW() 
                            WHERE request_id = :request_id";
            $update_stmt = $this->conn->prepare($update_query);
            $status = ($action == 'accept') ? 'accepted' : 'rejected';
            $update_stmt->bindParam(":status", $status);
            $update_stmt->bindParam(":request_id", $request_id);
            $update_stmt->execute();
            
            // If accepting, update available seats
            if($action == 'accept') {
                $seats_query = "UPDATE rides SET seats_available = seats_available - :passenger_count, 
                               updated_at = NOW() WHERE ride_id = :ride_id";
                $seats_stmt = $this->conn->prepare($seats_query);
                $seats_stmt->bindParam(":passenger_count", $request['passenger_count']);
                $seats_stmt->bindParam(":ride_id", $this->ride_id);
                $seats_stmt->execute();
            }
            
            $this->conn->commit();
            return ["success" => "Request " . $status];
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Error handling ride request: " . $e->getMessage());
            return ["error" => "An error occurred while processing your request"];
        }
    }

    // Review driver
    public function reviewDriver($user_id, $rating, $comment) {
        // Check if user was a passenger in this ride
        $check_query = "SELECT * FROM ride_requests 
                      WHERE ride_id = :ride_id AND user_id = :user_id AND status = 'accepted'";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":ride_id", $this->ride_id);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() == 0) {
            return ["error" => "You must be an accepted passenger to leave a review"];
        }
        
        // Check if ride is completed
        $ride_query = "SELECT status FROM rides WHERE ride_id = :ride_id";
        $ride_stmt = $this->conn->prepare($ride_query);
        $ride_stmt->bindParam(":ride_id", $this->ride_id);
        $ride_stmt->execute();
        
        $ride = $ride_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($ride['status'] != 'completed') {
            return ["error" => "You can only review completed rides"];
        }
        
        // Check if user already reviewed this ride
        $existing_query = "SELECT * FROM driver_reviews 
                          WHERE ride_id = :ride_id AND reviewer_id = :user_id";
        $existing_stmt = $this->conn->prepare($existing_query);
        $existing_stmt->bindParam(":ride_id", $this->ride_id);
        $existing_stmt->bindParam(":user_id", $user_id);
        $existing_stmt->execute();
        
        if($existing_stmt->rowCount() > 0) {
            return ["error" => "You have already reviewed this ride"];
        }
        
        // Insert the review
        $query = "INSERT INTO driver_reviews 
                (ride_id, driver_id, reviewer_id, rating, comment, created_at)
                VALUES
                (:ride_id, (SELECT user_id FROM rides WHERE ride_id = :ride_id2), 
                 :reviewer_id, :rating, :comment, NOW())";

        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $comment = htmlspecialchars(strip_tags($comment));
        
        // Validate rating
        if($rating < 1 || $rating > 5) {
            return ["error" => "Rating must be between 1 and 5"];
        }
        
        // Bind data
        $stmt->bindParam(":ride_id", $this->ride_id);
        $stmt->bindParam(":ride_id2", $this->ride_id);
        $stmt->bindParam(":reviewer_id", $user_id);
        $stmt->bindParam(":rating", $rating);
        $stmt->bindParam(":comment", $comment);
        
        try {
            if($stmt->execute()) {
                return ["success" => "Review submitted successfully"];
            }
            return ["error" => "Failed to submit review"];
        } catch(PDOException $e) {
            error_log("Error reviewing driver: " . $e->getMessage());
            return ["error" => "An error occurred while processing your review"];
        }
    }

    // Get driver reviews
    public function getDriverReviews($driver_id) {
        $query = "SELECT dr.*, u.full_name, u.avatar_url 
                FROM driver_reviews dr
                JOIN users u ON dr.reviewer_id = u.user_id
                WHERE dr.driver_id = :driver_id
                ORDER BY dr.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":driver_id", $driver_id);
        $stmt->execute();

        return $stmt;
    }

    // Get driver average rating
    public function getDriverRating($driver_id) {
        $query = "SELECT AVG(rating) as average_rating, COUNT(*) as review_count 
                FROM driver_reviews 
                WHERE driver_id = :driver_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":driver_id", $driver_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>