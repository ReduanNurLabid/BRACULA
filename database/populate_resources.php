<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Sample materials data
    $materials = [
        [
            'user_id' => 2, // Test User
            'course_code' => 'CSE101',
            'semester' => 'Spring 2024',
            'file_name' => 'Introduction_to_Programming.pdf',
            'file_type' => 'pdf',
            'file_url' => 'sample_cse101_intro.pdf',
            'downloads' => 15
        ],
        [
            'user_id' => 2,
            'course_code' => 'CSE220',
            'semester' => 'Fall 2023',
            'file_name' => 'Data_Structures_Notes.pdf',
            'file_type' => 'notes',
            'file_url' => 'sample_cse220_notes.pdf',
            'downloads' => 8
        ],
        [
            'user_id' => 4, // Reduan Nur Labid
            'course_code' => 'BUS201',
            'semester' => 'Spring 2024',
            'file_name' => 'Marketing_Fundamentals.pptx',
            'file_type' => 'slides',
            'file_url' => 'sample_bus201_slides.pptx',
            'downloads' => 12
        ],
        [
            'user_id' => 4,
            'course_code' => 'EEE201',
            'semester' => 'Fall 2023',
            'file_name' => 'Circuit_Analysis_Past_Paper.pdf',
            'file_type' => 'past_paper',
            'file_url' => 'sample_eee201_paper.pdf',
            'downloads' => 25
        ],
        [
            'user_id' => 2,
            'course_code' => 'CSE370',
            'semester' => 'Spring 2024',
            'file_name' => 'Database_Systems_Slides.pptx',
            'file_type' => 'slides',
            'file_url' => 'sample_cse370_slides.pptx',
            'downloads' => 18
        ]
    ];

    // First, create the sample files in the uploads directory
    $uploadDir = __DIR__ . '/../uploads/materials/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Create sample PDF and PPTX files
    foreach ($materials as $material) {
        $filePath = $uploadDir . $material['file_url'];
        if (!file_exists(filename: $filePath)) {
            // Create a simple text file with some content
            $content = "This is a sample {$material['file_type']} file for {$material['course_code']}\n";
            $content .= "Course: {$material['course_code']}\n";
            $content .= "Semester: {$material['semester']}\n";
            file_put_contents($filePath, $content);
        }
    }

    // Insert materials into database
    $query = "INSERT INTO resources (user_id, course_code, semester, file_name, file_type, file_url, downloads) 
              VALUES (:user_id, :course_code, :semester, :file_name, :file_type, :file_url, :downloads)";
    
    $stmt = $db->prepare($query);

    foreach ($materials as $material) {
        $stmt->bindParam(":user_id", $material['user_id']);
        $stmt->bindParam(":course_code", $material['course_code']);
        $stmt->bindParam(":semester", $material['semester']);
        $stmt->bindParam(":file_name", $material['file_name']);
        $stmt->bindParam(":file_type", $material['file_type']);
        $stmt->bindParam(":file_url", $material['file_url']);
        $stmt->bindParam(":downloads", $material['downloads']);
        
        $stmt->execute();
    }

    echo "Sample materials have been added successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 