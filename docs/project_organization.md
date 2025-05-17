# BRACULA Project Organization Plan

## Current Structure Analysis

After analyzing the codebase, it appears that the project is using a custom PHP architecture with some MVC-like patterns, but not a full framework like Laravel. The application is structured as follows:

- **API endpoints** in the `/api` directory handle HTTP requests and responses
- **Models** in the `/models` directory handle data operations
- **Configuration** in the `/config` directory manages database connections and session settings
- **Includes** in the `/includes` directory provides utility functions

## Recommended Organization

### 1. Standardize Directory Structure

```
BRACULA/
├── api/                  # API endpoints (controllers)
│   ├── auth/             # Authentication endpoints (login, register, etc.)
│   ├── posts/            # Post-related endpoints
│   ├── comments/         # Comment-related endpoints
│   ├── users/            # User-related endpoints
│   ├── rides/            # Rideshare-related endpoints
│   ├── accommodations/   # Accommodation-related endpoints
│   └── events/           # Event-related endpoints
├── config/               # Configuration files
│   ├── database.php      # Database configuration
│   └── session_config.php # Session configuration
├── models/               # Data models
│   ├── User.php          # User model
│   ├── Post.php          # Post model
│   ├── Comment.php       # Comment model
│   ├── Ride.php          # Ride model
│   └── Event.php         # Event model
├── includes/             # Shared PHP utilities
│   ├── session_check.php # Session utilities
│   ├── auth.php          # Authentication utilities
│   └── utils.php         # General utilities
├── database/             # Database scripts and migrations
│   ├── migrations/       # Database migrations
│   └── seeds/            # Database seeds
├── uploads/              # User uploaded files
├── public/               # Public assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Static images
├── test/                 # Unit tests and test utilities
└── logs/                 # Application logs
```

### 2. Create Model Classes

Create proper model classes for all entities in the system:

- User.php (already exists)
- Post.php
- Comment.php
- Ride.php
- Accommodation.php
- Event.php

### 3. Reorganize API Endpoints

Group API endpoints by functionality:

1. **Auth Endpoints**
   - login.php
   - register.php
   - logout.php

2. **Post Endpoints**
   - get_posts.php
   - create_post.php
   - edit_post.php
   - delete_post.php
   - vote_post.php
   - save_post.php

3. **Comment Endpoints**
   - comments.php
   - edit_comment.php
   - delete_comment.php
   - reply_comment.php

4. **User Endpoints**
   - get_user_profile.php
   - update_profile.php
   - update_account.php
   - delete_account.php
   - get_user_activities.php

5. **Ride Endpoints**
   - rides.php
   - ride_requests.php
   - driver_reviews.php

6. **Accommodation Endpoints**
   - accommodations.php
   - accommodation_inquiries.php

7. **Event Endpoints**
   - events.php
   - event_registration.php

### 4. Implement Router

Consider implementing a simple router to handle API requests instead of having separate PHP files for each endpoint. This would make the codebase more maintainable.

### 5. Standardize Error Handling

Create a consistent error handling approach across all API endpoints.

### 6. Clean Up Redundant Files

- Remove or fix the incomplete `getConnection()` file in the root directory
- Consolidate duplicate database connection code

### 7. Test Directory Organization

Organize the test directory to mirror the main application structure:

```
test/
├── api/
├── models/
├── includes/
└── utils/
```

### 8. Implementation Plan

1. Create the new directory structure
2. Move existing files to their appropriate locations
3. Create missing model classes
4. Update file references in all PHP files
5. Test thoroughly to ensure functionality is maintained

## Notes on Unit Testing

The test directory should be maintained for unit testing purposes. Consider implementing a proper testing framework like PHPUnit for more structured tests.

## Framework Consideration

While the current implementation uses a custom architecture, consider whether migrating to a lightweight PHP framework like Slim or Lumen would benefit the project in the long term for better maintainability and standardization. 