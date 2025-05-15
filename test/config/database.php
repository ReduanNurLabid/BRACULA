<?php
class Database {
    private $host = "localhost";
    public $db_name = "bracula_test_db";
    private $username = "root";
    private $password = "";
    private $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // First connect without database name
            $this->conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Create database if it doesn't exist
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            
            // Select the database
            $this->conn->exec("USE " . $this->db_name);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>