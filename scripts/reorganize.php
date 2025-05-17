<?php
/**
 * BRACULA Project Reorganization Script
 * 
 * This script implements the reorganization plan for the BRACULA project.
 * It moves files to their new locations and creates redirects to maintain compatibility.
 * 
 * IMPORTANT: Make a backup of your project before running this script!
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the root directory
$root = __DIR__;

// Define file mappings (source => destination)
$fileMappings = [
    // Auth endpoints
    'api/login.php' => 'api/auth/login.php',
    'api/register.php' => 'api/auth/register.php',
    'api/logout.php' => 'api/auth/logout.php',
    
    // Post endpoints
    'api/get_posts.php' => 'api/posts/get_posts.php',
    'api/create_post.php' => 'api/posts/create_post.php',
    'api/edit_post.php' => 'api/posts/edit_post.php',
    'api/delete_post.php' => 'api/posts/delete_post.php',
    'api/vote_post.php' => 'api/posts/vote_post.php',
    'api/save_post.php' => 'api/posts/save_post.php',
    'api/get_saved_posts.php' => 'api/posts/get_saved_posts.php',
    'api/search_posts.php' => 'api/posts/search_posts.php',
    
    // Comment endpoints
    'api/comments.php' => 'api/comments/comments.php',
    'api/edit_comment.php' => 'api/comments/edit_comment.php',
    'api/delete_comment.php' => 'api/comments/delete_comment.php',
    'api/reply_comment.php' => 'api/comments/reply_comment.php',
    'api/get_comments.php' => 'api/comments/get_comments.php',
    
    // User endpoints
    'api/get_user_profile.php' => 'api/users/get_user_profile.php',
    'api/update_profile.php' => 'api/users/update_profile.php',
    'api/update_account.php' => 'api/users/update_account.php',
    'api/delete_account.php' => 'api/users/delete_account.php',
    'api/get_user_activities.php' => 'api/users/get_user_activities.php',
    'api/user_activity.php' => 'api/users/user_activity.php',
    'api/update_user_activity.php' => 'api/users/update_user_activity.php',
    'api/notifications.php' => 'api/users/notifications.php',
    
    // Ride endpoints
    'api/rides.php' => 'api/rides/rides.php',
    'api/ride_requests.php' => 'api/rides/ride_requests.php',
    'api/driver_reviews.php' => 'api/rides/driver_reviews.php',
    
    // Accommodation endpoints
    'api/accommodations.php' => 'api/accommodations/accommodations.php',
    'api/accommodation_inquiries.php' => 'api/accommodations/accommodation_inquiries.php',
    
    // Event endpoints
    'api/events.php' => 'api/events/events.php',
    'api/event_registration.php' => 'api/events/event_registration.php',
    
    // Resource endpoints
    'api/get_materials.php' => 'api/resources/get_materials.php',
    'api/upload_material.php' => 'api/resources/upload_material.php',
    'api/download_material.php' => 'api/resources/download_material.php',
];

// Define directories to create
$directories = [
    'api/auth',
    'api/posts',
    'api/comments',
    'api/users',
    'api/rides',
    'api/accommodations',
    'api/events',
    'api/resources',
    'public/css',
    'public/js',
    'public/images',
];

// HTML files to check for API references
$htmlFiles = [
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
    'test/test_comments.html',
];

// JS files to check for API references (if any)
$jsFiles = [];
if (is_dir($root . '/js')) {
    $jsFiles = glob($root . '/js/*.js');
}

// Function to create directories
function createDirectories($directories, $root) {
    echo "Creating directories...\n";
    foreach ($directories as $dir) {
        $path = $root . '/' . $dir;
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                echo "Created directory: $path\n";
            } else {
                echo "Failed to create directory: $path\n";
            }
        } else {
            echo "Directory already exists: $path\n";
        }
    }
}

// Function to copy files
function copyFiles($fileMappings, $root) {
    echo "\nCopying files...\n";
    foreach ($fileMappings as $source => $destination) {
        $sourcePath = $root . '/' . $source;
        $destPath = $root . '/' . $destination;
        
        // Check if source exists
        if (file_exists($sourcePath)) {
            // Create destination directory if it doesn't exist
            $destDir = dirname($destPath);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Copy the file
            if (copy($sourcePath, $destPath)) {
                echo "Copied: $sourcePath -> $destPath\n";
            } else {
                echo "Failed to copy: $sourcePath -> $destPath\n";
            }
        } else {
            echo "Source file not found: $sourcePath\n";
        }
    }
}

// Function to create redirects
function createRedirects($fileMappings, $root) {
    echo "\nCreating redirects...\n";
    foreach ($fileMappings as $source => $destination) {
        $sourcePath = $root . '/' . $source;
        $content = '<?php
// Redirect to new location
header("Location: /' . $destination . '");
exit;
?>';
        
        if (file_put_contents($sourcePath, $content)) {
            echo "Created redirect: $sourcePath -> $destination\n";
        } else {
            echo "Failed to create redirect: $sourcePath\n";
        }
    }
}

// Function to update references in HTML files
function updateHtmlReferences($htmlFiles, $fileMappings, $root) {
    echo "\nChecking HTML files for API references...\n";
    
    foreach ($htmlFiles as $htmlFile) {
        $filePath = $root . '/' . $htmlFile;
        
        if (file_exists($filePath)) {
            echo "Checking file: $filePath\n";
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $changed = false;
            
            foreach ($fileMappings as $source => $destination) {
                // Check for fetch calls, axios calls, form actions, etc.
                $patterns = [
                    '/' . preg_quote($source, '/') . '/',
                    '/fetch\s*\(\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                    '/fetch\s*\(\s*`' . preg_quote($source, '/') . '/',
                    '/axios\.get\s*\(\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                    '/axios\.post\s*\(\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                    '/action\s*=\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                ];
                
                $replacements = [
                    $destination,
                    'fetch(\'' . $destination . '\'',
                    'fetch(`' . $destination,
                    'axios.get(\'' . $destination . '\'',
                    'axios.post(\'' . $destination . '\'',
                    'action="' . $destination . '"',
                ];
                
                foreach ($patterns as $index => $pattern) {
                    $newContent = preg_replace($pattern, $replacements[$index], $content);
                    if ($newContent !== $content) {
                        $content = $newContent;
                        $changed = true;
                        echo "  Updated reference to $source in $htmlFile\n";
                    }
                }
            }
            
            if ($changed) {
                // Backup the original file
                copy($filePath, $filePath . '.bak');
                echo "  Created backup: $filePath.bak\n";
                
                // Write the updated content
                if (file_put_contents($filePath, $content)) {
                    echo "  Updated file: $filePath\n";
                } else {
                    echo "  Failed to update file: $filePath\n";
                }
            } else {
                echo "  No changes needed in $htmlFile\n";
            }
        } else {
            echo "File not found: $filePath\n";
        }
    }
}

// Function to update references in JS files
function updateJsReferences($jsFiles, $fileMappings, $root) {
    echo "\nChecking JS files for API references...\n";
    
    foreach ($jsFiles as $jsFile) {
        $filePath = $jsFile;
        
        if (file_exists($filePath)) {
            echo "Checking file: $filePath\n";
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $changed = false;
            
            foreach ($fileMappings as $source => $destination) {
                // Check for fetch calls, axios calls, etc.
                $patterns = [
                    '/' . preg_quote($source, '/') . '/',
                    '/fetch\s*\(\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                    '/fetch\s*\(\s*`' . preg_quote($source, '/') . '/',
                    '/axios\.get\s*\(\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                    '/axios\.post\s*\(\s*[\'"]' . preg_quote($source, '/') . '[\'"]/',
                ];
                
                $replacements = [
                    $destination,
                    'fetch(\'' . $destination . '\'',
                    'fetch(`' . $destination,
                    'axios.get(\'' . $destination . '\'',
                    'axios.post(\'' . $destination . '\'',
                ];
                
                foreach ($patterns as $index => $pattern) {
                    $newContent = preg_replace($pattern, $replacements[$index], $content);
                    if ($newContent !== $content) {
                        $content = $newContent;
                        $changed = true;
                        echo "  Updated reference to $source in " . basename($jsFile) . "\n";
                    }
                }
            }
            
            if ($changed) {
                // Backup the original file
                copy($filePath, $filePath . '.bak');
                echo "  Created backup: $filePath.bak\n";
                
                // Write the updated content
                if (file_put_contents($filePath, $content)) {
                    echo "  Updated file: $filePath\n";
                } else {
                    echo "  Failed to update file: $filePath\n";
                }
            } else {
                echo "  No changes needed in " . basename($jsFile) . "\n";
            }
        } else {
            echo "File not found: $filePath\n";
        }
    }
}

// Function to move CSS and JS to public directory
function moveAssets($root, $htmlFiles) {
    echo "\nMoving assets to public directory...\n";
    
    // Move CSS files
    if (is_dir($root . '/css')) {
        if (!is_dir($root . '/public/css')) {
            mkdir($root . '/public/css', 0755, true);
        }
        
        $cssFiles = glob($root . '/css/*.css');
        foreach ($cssFiles as $cssFile) {
            $destFile = $root . '/public/css/' . basename($cssFile);
            if (copy($cssFile, $destFile)) {
                echo "Copied CSS: " . basename($cssFile) . " -> public/css/" . basename($cssFile) . "\n";
            } else {
                echo "Failed to copy CSS: " . basename($cssFile) . "\n";
            }
        }
    }
    
    // Move style.css if it exists in root
    if (file_exists($root . '/style.css')) {
        if (!is_dir($root . '/public/css')) {
            mkdir($root . '/public/css', 0755, true);
        }
        
        if (copy($root . '/style.css', $root . '/public/css/style.css')) {
            echo "Copied style.css -> public/css/style.css\n";
        } else {
            echo "Failed to copy style.css\n";
        }
    }
    
    // Move JS files
    if (is_dir($root . '/js')) {
        if (!is_dir($root . '/public/js')) {
            mkdir($root . '/public/js', 0755, true);
        }
        
        $jsFiles = glob($root . '/js/*.js');
        foreach ($jsFiles as $jsFile) {
            $destFile = $root . '/public/js/' . basename($jsFile);
            if (copy($jsFile, $destFile)) {
                echo "Copied JS: " . basename($jsFile) . " -> public/js/" . basename($jsFile) . "\n";
            } else {
                echo "Failed to copy JS: " . basename($jsFile) . "\n";
            }
        }
    }
    
    // Update HTML files to reference new CSS and JS paths
    echo "\nUpdating asset references in HTML files...\n";
    foreach ($htmlFiles as $htmlFile) {
        $filePath = $root . '/' . $htmlFile;
        
        if (file_exists($filePath)) {
            echo "Checking file: $filePath\n";
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $changed = false;
            
            // Update CSS references
            $patterns = [
                '/<link[^>]*href\s*=\s*[\'"]css\/([^"\']*)[\'"][^>]*>/',
                '/<link[^>]*href\s*=\s*[\'"]style\.css[\'"][^>]*>/',
            ];
            
            $replacements = [
                '<link href="public/css/$1" rel="stylesheet">',
                '<link href="public/css/style.css" rel="stylesheet">',
            ];
            
            foreach ($patterns as $index => $pattern) {
                $newContent = preg_replace($pattern, $replacements[$index], $content);
                if ($newContent !== $content) {
                    $content = $newContent;
                    $changed = true;
                    echo "  Updated CSS reference in $htmlFile\n";
                }
            }
            
            // Update JS references
            $pattern = '/<script[^>]*src\s*=\s*[\'"]js\/([^"\']*)[\'"][^>]*>/';
            $replacement = '<script src="public/js/$1">';
            
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $changed = true;
                echo "  Updated JS reference in $htmlFile\n";
            }
            
            if ($changed) {
                // Backup the original file
                copy($filePath, $filePath . '.bak');
                echo "  Created backup: $filePath.bak\n";
                
                // Write the updated content
                if (file_put_contents($filePath, $content)) {
                    echo "  Updated file: $filePath\n";
                } else {
                    echo "  Failed to update file: $filePath\n";
                }
            } else {
                echo "  No asset reference changes needed in $htmlFile\n";
            }
        } else {
            echo "File not found: $filePath\n";
        }
    }
}

// Function to create CSS and JS redirect files
function createAssetRedirects($root) {
    echo "\nCreating asset redirects...\n";
    
    // Create CSS redirect
    if (is_dir($root . '/css')) {
        $cssRedirect = $root . '/css/index.php';
        $content = '<?php
// Redirect CSS requests to the new location
$file = basename($_SERVER["REQUEST_URI"]);
if ($file != "index.php") {
    header("Location: /public/css/" . $file);
    exit;
}
?>';
        
        if (file_put_contents($cssRedirect, $content)) {
            echo "Created CSS redirect: $cssRedirect\n";
        } else {
            echo "Failed to create CSS redirect: $cssRedirect\n";
        }
    }
    
    // Create JS redirect
    if (is_dir($root . '/js')) {
        $jsRedirect = $root . '/js/index.php';
        $content = '<?php
// Redirect JS requests to the new location
$file = basename($_SERVER["REQUEST_URI"]);
if ($file != "index.php") {
    header("Location: /public/js/" . $file);
    exit;
}
?>';
        
        if (file_put_contents($jsRedirect, $content)) {
            echo "Created JS redirect: $jsRedirect\n";
        } else {
            echo "Failed to create JS redirect: $jsRedirect\n";
        }
    }
    
    // Create style.css redirect
    if (file_exists($root . '/style.css')) {
        $styleRedirect = $root . '/style.css.php';
        $content = '<?php
// Redirect style.css requests to the new location
header("Location: /public/css/style.css");
exit;
?>';
        
        if (file_put_contents($styleRedirect, $content)) {
            echo "Created style.css redirect: $styleRedirect\n";
        } else {
            echo "Failed to create style.css redirect: $styleRedirect\n";
        }
        
        // Rename style.css to avoid conflicts
        rename($root . '/style.css', $root . '/style.css.original');
        echo "Renamed style.css to style.css.original\n";
    }
}

// Main execution
echo "BRACULA Project Reorganization Script\n";
echo "===================================\n\n";

// Ask for confirmation
echo "This script will reorganize your project files according to the recommended structure.\n";
echo "Make sure you have a backup of your project before proceeding.\n\n";
echo "Do you want to continue? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) != 'y') {
    echo "Operation cancelled.\n";
    exit;
}

// Create directories
createDirectories($directories, $root);

// Copy files to new locations
copyFiles($fileMappings, $root);

// Create redirects for API endpoints
createRedirects($fileMappings, $root);

// Update references in HTML files
updateHtmlReferences($htmlFiles, $fileMappings, $root);

// Update references in JS files
updateJsReferences($jsFiles, $fileMappings, $root);

// Move CSS and JS files to public directory
moveAssets($root, $htmlFiles);

// Create redirects for CSS and JS files
createAssetRedirects($root);

// Remove getConnection() file if it exists
if (file_exists($root . '/getConnection()')) {
    unlink($root . '/getConnection()');
    echo "\nRemoved getConnection() file\n";
}

echo "\nReorganization completed!\n";
echo "Please test your application to ensure everything works correctly.\n";
echo "If you encounter any issues, you can restore from your backup.\n";
?> 