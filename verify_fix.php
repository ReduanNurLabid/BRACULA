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
            margin: 5px 5px 5px 0;
        }
        .fix-btn {
            background: #4CAF50;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .success-msg {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .error-msg {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BRACULA Database Fix Verification</h1>
        
        <?php
        // Check if a fix has been applied
        if (isset($_GET['fix'])) {
            $fix = $_GET['fix'];
            if ($fix === 'seats') {
                echo "<div class='message success-msg'>The 'seats' column fix has been applied. Checking status...</div>";
                include 'database/fix_ride_requests_table.php';
            } elseif ($fix === 'pickup') {
                echo "<div class='message success-msg'>The 'pickup' column fix has been applied. Checking status...</div>";
                include 'database/fix_pickup_column.php';
            } elseif ($fix === 'notes') {
                echo "<div class='message success-msg'>The 'notes' column fix has been applied. Checking status...</div>";
                include 'database/fix_notes_column.php';
            } elseif ($fix === 'all') {
                echo "<div class='message success-msg'>Applied fixes for all missing columns. Checking status...</div>";
                include 'database/fix_ride_requests_table.php';
                include 'database/fix_pickup_column.php';
                include 'database/fix_notes_column.php';
            } elseif ($fix === 'driver_reviews') {
                echo "<div class='message success-msg'>Creating driver_reviews table. Checking status...</div>";
                include 'database/create_driver_reviews_table.php';
            }
        }
        
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
                    echo "<p><a href='verify_fix.php?fix=seats' class='btn fix-btn'>Fix Seats Column</a></p>";
                }
                
                // Check for pickup column
                $pickupColumnCheck = $conn->query("SHOW COLUMNS FROM ride_requests LIKE 'pickup'")->rowCount() > 0;
                if ($pickupColumnCheck) {
                    echo "<p class='success'>✓ The 'pickup' column exists in ride_requests table.</p>";
                } else {
                    echo "<p class='error'>✗ The 'pickup' column is missing from ride_requests table!</p>";
                    echo "<p><a href='verify_fix.php?fix=pickup' class='btn fix-btn'>Fix Pickup Column</a></p>";
                }
                
                // Check for notes column
                $notesColumnCheck = $conn->query("SHOW COLUMNS FROM ride_requests LIKE 'notes'")->rowCount() > 0;
                if ($notesColumnCheck) {
                    echo "<p class='success'>✓ The 'notes' column exists in ride_requests table.</p>";
                } else {
                    echo "<p class='error'>✗ The 'notes' column is missing from ride_requests table!</p>";
                    echo "<p><a href='verify_fix.php?fix=notes' class='btn fix-btn'>Fix Notes Column</a></p>";
                }
                
                // Show fix all button if any column is missing
                if (!$seatsColumnCheck || !$pickupColumnCheck || !$notesColumnCheck) {
                    echo "<p><a href='verify_fix.php?fix=all' class='btn fix-btn'>Fix All Missing Columns</a></p>";
                }
                
                // Show table structure
                echo "<h3>Table Structure:</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                $columns = $conn->query("DESCRIBE ride_requests");
                while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                    $highlight = '';
                    if ($column['Field'] == 'seats' || $column['Field'] == 'pickup' || $column['Field'] == 'notes') {
                        $highlight = " <span class='success'>✓</span>";
                    }
                    echo "<tr>";
                    echo "<td>{$column['Field']}{$highlight}</td>";
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
                echo "<p><a href='verify_fix.php?fix=all' class='btn fix-btn'>Create Ride Requests Table</a></p>";
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
                echo "<p><a href='verify_fix.php?fix=driver_reviews' class='btn fix-btn'>Create Driver Reviews Table</a></p>";
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