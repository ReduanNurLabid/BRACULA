# BRACULA Project Implementation Plan

This document outlines the step-by-step process to safely organize the PHP files in the BRACULA project without breaking functionality.

## Phase 1: Preparation

1. **Create a Full Backup**
   ```bash
   # Create a backup of the entire project
   xcopy /E /I /H /Y C:\xampp\htdocs\BRACULA C:\xampp\htdocs\BRACULA_backup
   ```

2. **Create the New Directory Structure**
   - Run the `organize_project.php` script with the file moving disabled (as it is by default)
   - This will create all necessary directories and model templates

## Phase 2: File Organization

### Step 1: Move Authentication Files
1. Create the auth directory if not already created:
   ```php
   mkdir('api/auth', 0755, true);
   ```

2. Move authentication files:
   - Copy `api/login.php` to `api/auth/login.php`
   - Copy `api/register.php` to `api/auth/register.php`
   - Copy `api/logout.php` to `api/auth/logout.php`

3. Update references in HTML files:
   - Search for references to the old paths and update them
   - Example: Update `fetch('api/login.php')` to `fetch('api/auth/login.php')`

### Step 2: Move Post-Related Files
1. Create the posts directory if not already created:
   ```php
   mkdir('api/posts', 0755, true);
   ```

2. Move post-related files:
   - Copy `api/get_posts.php` to `api/posts/get_posts.php`
   - Copy `api/create_post.php` to `api/posts/create_post.php`
   - Copy `api/edit_post.php` to `api/posts/edit_post.php`
   - Copy `api/delete_post.php` to `api/posts/delete_post.php`
   - Copy `api/vote_post.php` to `api/posts/vote_post.php`
   - Copy `api/save_post.php` to `api/posts/save_post.php`
   - Copy `api/get_saved_posts.php` to `api/posts/get_saved_posts.php`
   - Copy `api/search_posts.php` to `api/posts/search_posts.php`

3. Update references in HTML and JS files

### Step 3: Move Comment-Related Files
1. Create the comments directory if not already created:
   ```php
   mkdir('api/comments', 0755, true);
   ```

2. Move comment-related files:
   - Copy `api/comments.php` to `api/comments/comments.php`
   - Copy `api/edit_comment.php` to `api/comments/edit_comment.php`
   - Copy `api/delete_comment.php` to `api/comments/delete_comment.php`
   - Copy `api/reply_comment.php` to `api/comments/reply_comment.php`
   - Copy `api/get_comments.php` to `api/comments/get_comments.php`

3. Update references in HTML and JS files

### Step 4: Move User-Related Files
1. Create the users directory if not already created:
   ```php
   mkdir('api/users', 0755, true);
   ```

2. Move user-related files:
   - Copy `api/get_user_profile.php` to `api/users/get_user_profile.php`
   - Copy `api/update_profile.php` to `api/users/update_profile.php`
   - Copy `api/update_account.php` to `api/users/update_account.php`
   - Copy `api/delete_account.php` to `api/users/delete_account.php`
   - Copy `api/get_user_activities.php` to `api/users/get_user_activities.php`
   - Copy `api/user_activity.php` to `api/users/user_activity.php`
   - Copy `api/update_user_activity.php` to `api/users/update_user_activity.php`
   - Copy `api/notifications.php` to `api/users/notifications.php`

3. Update references in HTML and JS files

### Step 5: Move Ride-Related Files
1. Create the rides directory if not already created:
   ```php
   mkdir('api/rides', 0755, true);
   ```

2. Move ride-related files:
   - Copy `api/rides.php` to `api/rides/rides.php`
   - Copy `api/ride_requests.php` to `api/rides/ride_requests.php`
   - Copy `api/driver_reviews.php` to `api/rides/driver_reviews.php`

3. Update references in HTML and JS files

### Step 6: Move Accommodation-Related Files
1. Create the accommodations directory if not already created:
   ```php
   mkdir('api/accommodations', 0755, true);
   ```

2. Move accommodation-related files:
   - Copy `api/accommodations.php` to `api/accommodations/accommodations.php`
   - Copy `api/accommodation_inquiries.php` to `api/accommodations/accommodation_inquiries.php`

3. Update references in HTML and JS files

### Step 7: Move Event-Related Files
1. Create the events directory if not already created:
   ```php
   mkdir('api/events', 0755, true);
   ```

2. Move event-related files:
   - Copy `api/events.php` to `api/events/events.php`
   - Copy `api/event_registration.php` to `api/events/event_registration.php`

3. Update references in HTML and JS files

### Step 8: Move Resource-Related Files
1. Create the resources directory if not already created:
   ```php
   mkdir('api/resources', 0755, true);
   ```

2. Move resource-related files:
   - Copy `api/get_materials.php` to `api/resources/get_materials.php`
   - Copy `api/upload_material.php` to `api/resources/upload_material.php`
   - Copy `api/download_material.php` to `api/resources/download_material.php`

3. Update references in HTML and JS files

### Step 9: Move CSS and JS Files
1. Create the public directory if not already created:
   ```php
   mkdir('public/css', 0755, true);
   mkdir('public/js', 0755, true);
   ```

2. Move CSS files:
   - Copy `style.css` to `public/css/style.css`
   - Copy all files in `css/` to `public/css/`

3. Move JS files:
   - Copy all files in `js/` to `public/js/`

4. Update references in HTML files

## Phase 3: Create Redirects

To maintain backward compatibility, create redirect files in the original locations:

1. Create a redirect template function:
   ```php
   function createRedirect($oldPath, $newPath) {
       $content = '<?php
   // Redirect to new location
   header("Location: ' . $newPath . '");
   exit;
   ?>';
       file_put_contents($oldPath, $content);
   }
   ```

2. Create redirects for each moved file:
   ```php
   createRedirect('api/login.php', 'api/auth/login.php');
   createRedirect('api/register.php', 'api/auth/register.php');
   // ... and so on for all moved files
   ```

## Phase 4: Testing

1. Test each feature to ensure it still works:
   - Login/Registration
   - Post creation, editing, deletion
   - Comment functionality
   - User profiles
   - Rides
   - Accommodations
   - Events
   - Resources

2. Check for any errors in the browser console or PHP error logs

## Phase 5: Cleanup

1. After confirming everything works, remove the redirect files
2. Remove the `getConnection()` file from the root directory
3. Update the README.md with the new structure

## Rollback Plan

If issues are encountered:
1. Stop the web server
2. Restore from the backup:
   ```bash
   xcopy /E /I /H /Y C:\xampp\htdocs\BRACULA_backup C:\xampp\htdocs\BRACULA
   ```
3. Restart the web server 