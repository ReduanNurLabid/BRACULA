# BRACULA Feature List

## Feature List by Team Member

Below is a template for documenting features implemented by each team member. Fill in the specific details for each person's contributions.

### Team Member 1

| Feature | Description | Components | Files |
|---------|-------------|------------|-------|
| User Authentication | Account creation, login, session management | - User creation<br>- Login validation<br>- Password management | - models/User.php<br>- api/auth/register.php<br>- api/auth/login.php |
| Profile Management | User profile updates and viewing | - Profile editing<br>- Avatar upload<br>- Bio/details management | - api/users/update_profile.php<br>- html/user/profile.php |
| User Activity | Track user engagement across platform | - Activity logging<br>- View activity history | - api/user_activity.php<br>- html/user/activity.php |

### Team Member 2

| Feature | Description | Components | Files |
|---------|-------------|------------|-------|
| Post Management | Create, edit, delete, view posts | - Post creation<br>- Post editing<br>- Post deletion<br>- Content display | - models/Post.php<br>- api/posts/create_post.php<br>- api/posts/edit_post.php<br>- api/posts/delete_post.php |
| Comment System | Comment on posts, reply to comments | - Comment creation<br>- Nested comments<br>- Comment display | - models/Comment.php<br>- api/comments/comments.php<br>- api/comments/reply_comment.php |
| Post Voting | Upvote/downvote functionality | - Vote tracking<br>- Vote display | - api/vote_post.php<br>- html/posts/vote.php |

### Team Member 3

| Feature | Description | Components | Files |
|---------|-------------|------------|-------|
| Resource Sharing | Upload and download academic materials | - File upload<br>- File categorization<br>- Download management | - api/resources/upload_material.php<br>- api/resources/download_material.php<br>- html/resources/view.php |
| Event Management | Create, edit, view campus events | - Event creation<br>- Event registration<br>- Calendar view | - models/Event.php<br>- api/events/events.php<br>- api/events/event_registration.php |
| Search Functionality | Find posts, resources, events | - Search algorithm<br>- Results display | - api/search_posts.php<br>- html/search/results.php |

### Team Member 4

| Feature | Description | Components | Files |
|---------|-------------|------------|-------|
| Ride Sharing | Post and request rides | - Ride offers<br>- Ride requests<br>- Driver reviews | - models/Ride.php<br>- api/rides/rides.php<br>- api/rides/ride_requests.php<br>- api/rides/driver_reviews.php |
| Accommodation | Housing listings and inquiries | - Listing creation<br>- Accommodation search<br>- Inquiry management | - models/Accommodation.php<br>- api/accommodations/accommodations.php<br>- api/accommodations/accommodation_inquiries.php |
| Notifications | Alert users about interactions | - Notification generation<br>- Notification display | - api/notifications.php<br>- html/notifications/view.php |

## MVC Implementation

### Models
- User.php: User account management and authentication
- Post.php: Content creation and management
- Comment.php: Discussion thread management
- Event.php: Campus event coordination
- Ride.php: Ride sharing functionality
- Accommodation.php: Housing and accommodation listings

### Controllers (API Endpoints)
- auth/: Authentication controllers (login, register, logout)
- posts/: Post management controllers
- comments/: Comment system controllers
- events/: Event management controllers
- rides/: Ride sharing controllers
- accommodations/: Accommodation listing controllers
- resources/: Resource sharing controllers
- users/: User profile management controllers

### Views
- HTML templates with embedded PHP in the html/ directory
- CSS styling in the css/ directory
- JavaScript functionality in the public/ directory

## Database Schema Components

- users: User account information
- posts: User-generated content
- comments: Responses to posts
- events: Campus event details
- rides: Ride sharing listings
- accommodations: Housing listings
- resources: Academic materials
- notifications: User alerts

## Testing Components

- Unit tests for models
- API endpoint tests
- Integration tests for feature workflows 