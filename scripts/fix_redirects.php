<?php
/**
 * Fix API redirects script
 * 
 * This script updates API redirect paths to use relative paths instead of absolute paths
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the root directory
$root = dirname(__DIR__);
$apiDir = $root . '/api';

// Get all PHP files in the API directory
$apiFiles = glob($apiDir . '/*.php');

echo "Fixing redirects in API files...\n";

foreach ($apiFiles as $apiFile) {
    $filename = basename($apiFile);
    echo "Processing $filename...\n";
    
    // Read the file content
    $content = file_get_contents($apiFile);
    $originalContent = $content;
    
    // Fix absolute paths in redirects
    $content = str_replace('header("Location: /', 'header("Location: ', $content);
    
    // Check if content has changed
    if ($content !== $originalContent) {
        // Backup the original file
        copy($apiFile, $apiFile . '.bak');
        echo "  Created backup: $apiFile.bak\n";
        
        // Write the updated content
        if (file_put_contents($apiFile, $content)) {
            echo "  Updated file: $apiFile\n";
        } else {
            echo "  Failed to update file: $apiFile\n";
        }
    } else {
        echo "  No changes needed in $filename\n";
    }
}

// Also fix CSS and JS redirects
$cssRedirectFile = $root . '/css/index.php';
$jsRedirectFile = $root . '/js/index.php';

if (file_exists($cssRedirectFile)) {
    echo "Fixing CSS redirect...\n";
    $content = file_get_contents($cssRedirectFile);
    $content = str_replace('header("Location: /', 'header("Location: ', $content);
    file_put_contents($cssRedirectFile, $content);
}

if (file_exists($jsRedirectFile)) {
    echo "Fixing JS redirect...\n";
    $content = file_get_contents($jsRedirectFile);
    $content = str_replace('header("Location: /', 'header("Location: ', $content);
    file_put_contents($jsRedirectFile, $content);
}

echo "Done fixing redirects.\n";
?> 