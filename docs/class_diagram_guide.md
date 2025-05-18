# Class Diagram Guide for BRACULA

## Class Diagram Requirements

For your submission, you need to create an updated class diagram that shows:
- All main classes in the system
- Relationships between classes
- MVC architecture components
- Properties and methods of each class

## Main Components to Include

Based on the project structure, your class diagram should include:

### Models
- **User**
  - Properties: user_id, full_name, student_id, email, password, avatar_url, bio, department, interests
  - Methods: create(), login(), emailExists(), studentIdExists(), update(), getById()
  
- **Post**
  - Properties: post_id, user_id, title, content, category, created_at, updated_at
  - Methods: create(), update(), delete(), getById(), getByUserId(), getAll()
  
- **Comment**
  - Properties: comment_id, post_id, user_id, parent_comment_id, content, created_at
  - Methods: create(), update(), delete(), getByPostId(), getByUserId()
  
- **Event**
  - Properties: event_id, user_id, title, description, location, start_time, end_time, created_at
  - Methods: create(), update(), delete(), getAll(), getById(), registerUser()
  
- **Ride**
  - Properties: ride_id, user_id, from_location, to_location, departure_time, seats_available, created_at
  - Methods: create(), update(), delete(), getAll(), getById(), requestRide()
  
- **Accommodation**
  - Properties: accommodation_id, user_id, title, description, location, price, available_from, created_at
  - Methods: create(), update(), delete(), getAll(), getById(), makeInquiry()

### Controllers (API Endpoints)
Group these by functionality:

- **AuthController**
  - Methods: register(), login(), logout()
  
- **PostController**
  - Methods: createPost(), updatePost(), deletePost(), getPosts(), getPostById(), votePost(), savePost()
  
- **CommentController**
  - Methods: createComment(), updateComment(), deleteComment(), getComments(), replyToComment()
  
- **EventController**
  - Methods: createEvent(), updateEvent(), deleteEvent(), getEvents(), registerForEvent()
  
- **RideController**
  - Methods: createRide(), updateRide(), deleteRide(), getRides(), requestRide(), reviewDriver()
  
- **AccommodationController**
  - Methods: createAccommodation(), updateAccommodation(), deleteAccommodation(), getAccommodations(), inquireAccommodation()
  
- **ResourceController**
  - Methods: uploadMaterial(), downloadMaterial(), getMaterials()
  
- **UserController**
  - Methods: updateProfile(), getUserProfile(), getUserActivity(), updateUserActivity()

### Views
Represent views as separate components that interact with controllers:

- **UserViews**
- **PostViews**
- **CommentViews**
- **EventViews**
- **RideViews**
- **AccommodationViews**
- **ResourceViews**

## Relationships to Include

1. **Inheritance**
   - Any parent-child class relationships

2. **Association**
   - Basic relationships between classes
   - Example: User interacts with Post

3. **Composition/Aggregation**
   - When one class contains/owns another
   - Example: Post contains Comments

4. **Dependency**
   - When one class uses another
   - Example: Controllers depend on Models

## MVC Structure in the Diagram

Make sure to clearly show the MVC architecture:

1. **Models Layer**
   - Show all model classes
   - Indicate database interactions
   
2. **Controllers Layer**
   - Show all controller classes
   - Indicate relationships to models

3. **Views Layer**
   - Show view components
   - Indicate relationships to controllers

## Class Diagram Tools

You can create your class diagram using:

1. **Draw.io** (diagrams.net) - Free online diagram software
2. **Lucidchart** - Web-based diagramming tool (free tier available)
3. **Visual Paradigm** - UML diagramming tool (free community edition)
4. **PlantUML** - Text-based UML diagram creation

## Example Class Diagram Structure

```
+------------------+       +------------------+       +------------------+
|      Models      |       |   Controllers    |       |      Views       |
+------------------+       +------------------+       +------------------+
| - User           |<----->| - AuthController |<----->| - UserViews      |
| - Post           |<----->| - PostController |<----->| - PostViews      |
| - Comment        |<----->| - CommentCtrl    |<----->| - CommentViews   |
| - Event          |<----->| - EventController|<----->| - EventViews     |
| - Ride           |<----->| - RideController |<----->| - RideViews      |
| - Accommodation  |<----->| - AccommodCtrl   |<----->| - AccommodViews  |
+------------------+       +------------------+       +------------------+
        ^                          ^                          ^
        |                          |                          |
        v                          v                          v
+------------------+       +------------------+       +------------------+
|    Database      |       |  Request Handler |       |   HTML/CSS/JS    |
+------------------+       +------------------+       +------------------+
```

## Submission Format

- Save your class diagram as a high-resolution image (.png) or PDF (.pdf)
- Make sure all text is readable
- Include a legend explaining any symbols or colors used
- If necessary, include a brief explanation document with the diagram 