<?php
// Get the test file parameter if provided
$testFile = isset($_GET['test']) ? $_GET['test'] : '';

// Ensure the test file is safe
if (!empty($testFile)) {
    // Only allow .py files for security
    if (!preg_match('/^[a-zA-Z0-9_]+\.py$/', $testFile)) {
        die("Invalid test file specified");
    }
    
    // Make sure the file exists
    if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . $testFile)) {
        die("Test file not found: $testFile");
    }
}

// Build the command to execute
$batPath = __DIR__ . DIRECTORY_SEPARATOR . 'run_pytest.bat';

// Make sure the batch file exists
if (!file_exists($batPath)) {
    die("Batch file not found: " . basename($batPath));
}

// Format paths properly for Windows
$batPath = str_replace('/', '\\', $batPath);

// Construct the command differently to avoid quotation issues
if (!empty($testFile)) {
    // For a specific test file
    $command = 'start cmd.exe /k "' . $batPath . ' ' . $testFile . '"';
} else {
    // For running all tests
    $command = 'start cmd.exe /k "' . $batPath . '"';
}

// Execute the command
pclose(popen($command, 'r'));

// Wait a moment before redirecting
usleep(500000); // 0.5 seconds

// Redirect back to the test index page
header('Location: index.php');
exit;
?> 