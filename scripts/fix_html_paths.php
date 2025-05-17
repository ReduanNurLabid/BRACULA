<?php
/**
 * Fix HTML paths script
 * 
 * This script updates paths in HTML files to reflect the new directory structure
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the root directory
$root = dirname(__DIR__);
$htmlDir = $root . '/html';

// Get all HTML files
$htmlFiles = glob($htmlDir . '/*.html');

echo "Fixing paths in HTML files...\n";

foreach ($htmlFiles as $htmlFile) {
    $filename = basename($htmlFile);
    echo "Processing $filename...\n";
    
    // Read the file content
    $content = file_get_contents($htmlFile);
    $originalContent = $content;
    $changed = false;
    
    // Fix paths to other HTML files
    $content = preg_replace('/href="([^"]*\.html)"/', 'href="$1"', $content);
    
    // Fix paths to CSS files
    $content = preg_replace('/href="public\/css\/([^"]*)"/', 'href="../public/css/$1"', $content);
    
    // Fix paths to JS files
    $content = preg_replace('/src="public\/js\/([^"]*)"/', 'src="../public/js/$1"', $content);
    
    // Fix paths to images
    $content = preg_replace('/src="public\/images\/([^"]*)"/', 'src="../public/images/$1"', $content);
    
    // Fix navigation links
    $navLinks = [
        'index.html',
        'login.html',
        'signup.html',
        'feed.html',
        'profile.html',
        'settings.html',
        'rideshare.html',
        'accommodation.html',
        'events.html',
        'resources.html',
        'saved-posts.html'
    ];
    
    foreach ($navLinks as $link) {
        $content = str_replace("href=\"$link\"", "href=\"$link\"", $content);
    }
    
    // Check if content has changed
    if ($content !== $originalContent) {
        $changed = true;
        
        // Backup the original file
        copy($htmlFile, $htmlFile . '.bak');
        echo "  Created backup: $htmlFile.bak\n";
        
        // Write the updated content
        if (file_put_contents($htmlFile, $content)) {
            echo "  Updated file: $htmlFile\n";
        } else {
            echo "  Failed to update file: $htmlFile\n";
        }
    } else {
        echo "  No changes needed in $filename\n";
    }
}

echo "Done fixing HTML paths.\n";
?> 