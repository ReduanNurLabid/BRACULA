# BRACULA Project Reorganization Instructions

This document provides step-by-step instructions for reorganizing the BRACULA project files without breaking functionality.

## Overview

We've created the following files to help with the reorganization:

1. `project_organization.md` - Detailed plan for organizing the PHP files
2. `organize_project.php` - Script to create model templates and directory structure
3. `reorganize.php` - Script to move files and update references
4. `implementation_steps.md` - Step-by-step implementation plan
5. `project_summary.md` - Analysis and recommendations

## Implementation Instructions

### Step 1: Create a Backup

Before making any changes, create a full backup of your project:

```bash
# Windows (using xcopy)
xcopy /E /I /H /Y C:\xampp\htdocs\BRACULA C:\xampp\htdocs\BRACULA_backup

# Or using Windows Explorer
# Copy the entire BRACULA folder and paste it as BRACULA_backup
```

### Step 2: Run the Reorganization Script

The `reorganize.php` script will:
1. Create the necessary directories
2. Copy files to their new locations
3. Create redirects for backward compatibility
4. Update references in HTML and JS files
5. Move CSS and JS files to the public directory

To run the script:

```bash
# Navigate to your project directory
cd C:\xampp\htdocs\BRACULA

# Run the script
php reorganize.php
```

The script will ask for confirmation before making any changes.

### Step 3: Test the Application

After running the script, thoroughly test your application to ensure everything works correctly:

1. Test login and registration
2. Test post creation, editing, and deletion
3. Test comment functionality
4. Test user profiles
5. Test rideshare features
6. Test accommodation features
7. Test event features
8. Test resource features

### Step 4: Fix Any Issues

If you encounter any issues:

1. Check the browser console for JavaScript errors
2. Check the PHP error logs for server-side errors
3. Verify that all file paths are correct
4. Make sure all redirects are working properly

### Step 5: Clean Up (Optional)

Once you've confirmed everything is working correctly, you can:

1. Remove the redirect files (not recommended until after thorough testing)
2. Delete the `getConnection()` file from the root directory
3. Update the README.md with the new project structure

## Model Templates

The reorganization includes templates for the following models:

1. `Post.php` - For managing posts
2. `Comment.php` - For managing comments
3. `Ride.php` - For managing rideshare features
4. `Event.php` - For managing events
5. `Accommodation.php` - For managing accommodations

These templates provide a consistent structure for your data models.

## Directory Structure

After reorganization, your project will have the following structure:

```
BRACULA/
├── api/                  # API endpoints (controllers)
│   ├── auth/             # Authentication endpoints
│   ├── posts/            # Post-related endpoints
│   ├── comments/         # Comment-related endpoints
│   ├── users/            # User-related endpoints
│   ├── rides/            # Rideshare-related endpoints
│   ├── accommodations/   # Accommodation-related endpoints
│   ├── events/           # Event-related endpoints
│   └── resources/        # Resource-related endpoints
├── config/               # Configuration files
├── models/               # Data models
├── includes/             # Shared PHP utilities
├── database/             # Database scripts and migrations
├── uploads/              # User uploaded files
├── public/               # Public assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Static images
├── test/                 # Unit tests and test utilities
└── logs/                 # Application logs
```

## Rollback Plan

If you need to revert the changes:

1. Stop the web server
2. Delete the reorganized project
3. Restore from your backup
4. Restart the web server

## Next Steps

After successfully reorganizing the project, consider these improvements:

1. Implement a simple router for API endpoints
2. Enhance the authentication system
3. Add consistent input validation
4. Standardize API responses
5. Improve documentation

These changes will make your codebase more maintainable and easier to extend in the future. 