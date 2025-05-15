<?php
// Set appropriate content type
header('Content-Type: text/html; charset=utf-8');

// Include database connection
require_once 'config/database.php';

// Create a database connection
$database = new Database();
$conn = $database->getConnection();

// Set up the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA - Database Fix Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .section {
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BRACULA Database Fix Verification</h1>
        
        <?php
        try {
            // Check ride_requests table
            echo "<div class='section'>";
            echo "<h2>Ride Requests Table</h2>";
            
            $tableCheck = $conn->query("SHOW TABLES LIKE 'ride_requests'")->rowCount() > 0;
            if ($tableCheck) {
                echo "<p class='success'>✓ The ride_requests table exists.</p>";
                
                // Check for seats column
                $seatsColumnCheck = $conn->query("SHOW COLUMNS FROM ride_requests LIKE 'seats'")->rowCount() > 0;
                if ($seatsColumnCheck) {
                    echo "<p class='success'>✓ The 'seats' column exists in ride_requests table.</p>";
                } else {
                    echo "<p class='error'>✗ The 'seats' column is missing from ride_requests table!</p>";
                }
                
                // Show table structure
                echo "<h3>Table Structure:</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                $columns = $conn->query("DESCRIBE ride_requests");
                while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>{$column['Field']}" . ($column['Field'] == 'seats' ? " <span class='success'>✓</span>" : "") . "</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "<td>{$column['Extra']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>✗ The ride_requests table does not exist!</p>";
            }
            echo "</div>";
            
            // Check driver_reviews table
            echo "<div class='section'>";
            echo "<h2>Driver Reviews Table</h2>";
            
            $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_reviews'")->rowCount() > 0;
            if ($tableCheck) {
                echo "<p class='success'>✓ The driver_reviews table exists.</p>";
                
                // Show table structure
                echo "<h3>Table Structure:</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                $columns = $conn->query("DESCRIBE driver_reviews");
                while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "<td>{$column['Extra']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>✗ The driver_reviews table does not exist!</p>";
                echo "<p><a href='database/create_driver_reviews_table.php' class='btn'>Create Driver Reviews Table</a></p>";
            }
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <div>
            <a href="index.php" class="btn">Return to Homepage</a>
            <a href="setup_ride_tables.html" class="btn">Go to Rideshare Table Setup</a>
        </div>
    </div>
</body>
</html> 