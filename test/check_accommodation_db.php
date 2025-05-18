<?php
/**
 * Database Structure Checker for Accommodation Tables
 * 
 * This script checks whether the accommodation-related tables exist and have the correct structure
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Tables to check
$tables = [
    'accommodations',
    'accommodation_images',
    'accommodation_inquiries',
    'accommodation_reviews',
    'accommodation_favorites'
];

echo "<h1>Accommodation Database Structure Checker</h1>";

// Check each table
foreach ($tables as $table) {
    echo "<h2>Table: $table</h2>";
    
    try {
        // Check if table exists
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            echo "<p style='color: green;'>✅ Table exists</p>";
            
            // Get table columns
            $colStmt = $conn->query("DESCRIBE $table");
            $columns = [];
            echo "<h3>Columns:</h3>";
            echo "<ul>";
            while ($row = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<li><strong>{$row['Field']}</strong> ({$row['Type']})";
                if ($row['Key'] === 'PRI') echo " - PRIMARY KEY";
                if ($row['Key'] === 'MUL') echo " - FOREIGN KEY";
                echo "</li>";
                $columns[] = $row['Field'];
            }
            echo "</ul>";
            
            // Show sample data
            $dataStmt = $conn->query("SELECT * FROM $table LIMIT 5");
            $rowCount = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            
            echo "<h3>Data:</h3>";
            echo "<p>Total records: $rowCount</p>";
            
            if ($rowCount > 0) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr>";
                foreach ($columns as $col) {
                    echo "<th>$col</th>";
                }
                echo "</tr>";
                
                while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    foreach ($columns as $col) {
                        $value = isset($row[$col]) ? htmlspecialchars(substr($row[$col], 0, 50)) : '';
                        if (strlen($row[$col] ?? '') > 50) $value .= '...';
                        echo "<td>$value</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>⚠️ No data found in this table</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Table does not exist!</p>";
            
            if ($table === 'accommodations') {
                echo "<div style='background-color: #ffeeee; padding: 10px; border: 1px solid #ffaaaa;'>";
                echo "<h3>How to create this table:</h3>";
                echo "<p>You need to run the database setup script to create the accommodation tables. Look for a file like <code>database/create_accommodation_tables.php</code> or similar.</p>";
                echo "</div>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error checking table: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Check if create_accommodation_tables.php exists
echo "<h2>Setup Script Check:</h2>";
$setupScriptPath = '../database/create_accommodation_tables.php';
if (file_exists($setupScriptPath)) {
    echo "<p style='color: green;'>✅ Setup script exists at: $setupScriptPath</p>";
    echo "<p>You can run this script to create the accommodation tables if they don't exist.</p>";
    echo "<a href='../database/create_accommodation_tables.php' style='display: inline-block; padding: 10px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Run Setup Script</a>";
} else {
    echo "<p style='color: red;'>❌ Setup script not found at expected location: $setupScriptPath</p>";
    
    // Try to find it elsewhere
    $possiblePaths = [
        '../database',
        '../db',
        '../setup',
        '..',
        '../config'
    ];
    
    $found = false;
    foreach ($possiblePaths as $path) {
        $files = glob("$path/*.php");
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'CREATE TABLE IF NOT EXISTS accommodations') !== false) {
                echo "<p style='color: green;'>✅ Found setup script at: $file</p>";
                echo "<a href='$file' style='display: inline-block; padding: 10px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Run This Script</a>";
                $found = true;
                break 2;
            }
        }
    }
    
    if (!$found) {
        echo "<p>Could not find the setup script. Please check your codebase for a file that creates the accommodation tables.</p>";
    }
}
?> 