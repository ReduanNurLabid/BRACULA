# BRACULA - BRAC University Community Platform

BRACULA is a comprehensive community platform for BRAC University students, providing features for social interaction, resource sharing, and campus engagement.

## Project Structure

The project follows a simple MVC-like architecture:

```
BRACULA/
├── api/                  # API endpoints (controllers)
├── config/               # Configuration files
├── models/               # Data models
├── includes/             # Shared PHP utilities
├── database/             # Database scripts and migrations
├── uploads/              # User uploaded files
├── public/               # Public assets
│   ├── css/              # Stylesheets (primary location)
│   ├── js/               # JavaScript files
│   └── images/           # Image assets
├── css/                  # CSS redirect for backward compatibility
├── js/                   # JS redirect for backward compatibility
├── test/                 # Unit tests and test utilities
└── logs/                 # Application logs
```

## Recent Changes

### CSS Consolidation
- All CSS files have been consolidated to the `/public/css/` directory
- The root `/css/` directory now only contains a redirect for backward compatibility
- See `docs/css_consolidation.md` for details

## Features

### Post Management
- **Edit Posts**: Users can now edit their own posts, including the caption, content, and community.
- **Delete Posts**: Users can delete their own posts, which will also remove all associated comments.

### Comment Management
- **Edit Comments**: Users can now edit their own comments.
- **Delete Comments**: Users can delete their own comments.
- **Reply to Comments**: Users can reply to existing comments, creating a nested comment thread.
- **Nested Comments**: Comments now support a hierarchical structure with parent-child relationships.

## API Endpoints

### Post Management
- `api/edit_post.php`: Update an existing post (requires post_id, user_id, content, caption, community)
- `api/delete_post.php`: Delete a post and its associated comments (requires post_id, user_id)

### Comment Management
- `api/comments.php`: Get comments for a post or add a new comment (now supports parent_id for replies)
- `api/edit_comment.php`: Update an existing comment (requires comment_id, user_id, content)
- `api/delete_comment.php`: Delete a comment and its replies (requires comment_id, user_id)
- `api/reply_comment.php`: Add a reply to an existing comment (requires post_id, user_id, content, parent_id)

## Database Changes
- Added `parent_id` column to the `comments` table to support nested comments.

## Testing
- Use the test page at `test/test_comments.html` to verify the functionality of the new features.

## Setup
1. Start XAMPP and ensure Apache and MySQL are running.
2. Run the database update script: `php database/update_comments_table.php`
3. Access the application through your web browser at `http://localhost/BRACULA/`

### Contributors: Reduan Nur, Mahim Kabir, Anika Ferdous, Jerin khan
