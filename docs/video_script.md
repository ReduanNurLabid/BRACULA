# BRACULA Project Demonstration Video Script

## Video Duration: Maximum 15 minutes

## Introduction (1-2 minutes)
- **Project Overview:** "Welcome to our demonstration of BRACULA, a comprehensive web-based community platform designed specifically for BRAC University students."
- **Team Introduction:** "We are team [Team Name]. My name is [Name], and my teammates are [Names]."
- **Technology Stack:** "BRACULA uses PHP with a custom MVC architecture, MySQL database, and vanilla JavaScript for frontend interactions."

## Architecture Overview (2-3 minutes)
- **MVC Architecture:** 
  - "BRACULA follows the MVC design pattern."
  - "Models handle business logic and data persistence."
  - "Views render the user interface using HTML, CSS, and JavaScript."
  - "Controllers process user requests and coordinate between models and views."
  
- **Class Diagram:**
  - "Here's our class diagram showing the relationships between our main components."
  - [Show class diagram]
  - "You can see how the models relate to each other, and how controllers interface with models and views."

- **Database Schema:**
  - "Our database design includes tables for users, posts, comments, events, rides, accommodations, and resources."
  - "These entities are related through foreign key constraints to maintain data integrity."

## Feature Demonstrations (8-10 minutes)

### Team Member 1 (2-2.5 minutes)

#### Feature Demo: User Authentication
- "I implemented the user authentication system, including registration, login, and session management."
- [Show registration form]
- "Let me demonstrate creating a new account."
- [Complete registration form and submit]
- "Now I'll try logging in with these credentials."
- [Show login process]
- "Let me walk you through the controller code for this feature."
- [Show api/auth/login.php]
- "The controller validates input, calls the User model's login method, and manages session creation."
- [Show models/User.php, focusing on login() method]
- "The User model handles password verification and retrieves user information."

#### Testing Demonstration
- "Now I'll demonstrate testing for user authentication."
- "First, let's run a test for user registration."
- [Navigate to test directory and run test]
- "As you can see, the test passes, meaning our registration function works correctly."
- "Next, let's test the login functionality."
- [Run login test]
- "This test also passes."
- "Now, I'll modify the login test to make it fail."
- [Edit test file to expect incorrect value]
- "I've changed the expected login status from true to false."
- [Run test again, showing failure]
- "As expected, the test now fails because our code correctly authenticates the user, but our test is expecting it to fail."

### Team Member 2 (2-2.5 minutes)

#### Feature Demo: Post Management
- "I implemented the post management system, including creating, editing, and deleting posts."
- [Show post creation interface]
- "Let me create a new post."
- [Create post]
- "Now I'll show how to edit a post."
- [Edit post]
- "Let's look at the controller code."
- [Show api/posts/create_post.php]
- "The controller validates the post data and calls the Post model's create method."
- [Show models/Post.php]
- "The Post model handles data sanitization and database operations."

#### Testing Demonstration
- "Let's run tests for post creation."
- [Run post creation test]
- "The test verifies that posts are correctly saved to the database."
- "Next, let's test post retrieval."
- [Run post retrieval test]
- "Now I'll modify the post retrieval test to make it fail."
- [Edit test to look for non-existent post ID]
- [Run test again, showing failure]
- "The test fails because we're looking for a post that doesn't exist."

### Team Member 3 (2-2.5 minutes)

#### Feature Demo: Resource Sharing
- "I implemented the resource sharing feature for uploading and downloading academic materials."
- [Show resource upload interface]
- "Let me upload a PDF document."
- [Upload document]
- "Now users can download this resource."
- [Demonstrate download]
- "Let's look at the controller code."
- [Show api/resources/upload_material.php]
- "The controller handles file validation, storage, and metadata creation."
- [Show models handling resource data]

#### Testing Demonstration
- "Let's run tests for resource uploading."
- [Run upload test]
- "This test verifies that files are correctly stored and metadata is saved."
- "Next, let's test resource retrieval."
- [Run retrieval test]
- "Now I'll modify the test to make it fail."
- [Edit test to expect incorrect file type]
- [Run test again, showing failure]
- "The test fails because we're expecting a different file type than what was uploaded."

### Team Member 4 (2-2.5 minutes)

#### Feature Demo: Ride Sharing
- "I implemented the ride sharing feature for offering and requesting rides."
- [Show ride creation interface]
- "Let me create a new ride offer."
- [Create ride offer]
- "Now I'll show how users can request this ride."
- [Create ride request]
- "Let's look at the controller code."
- [Show api/rides/rides.php]
- "The controller processes ride offers and connects them with requests."
- [Show models/Ride.php]
- "The Ride model handles the business logic for matching riders."

#### Testing Demonstration
- "Let's run tests for ride creation."
- [Run ride creation test]
- "This test verifies that ride offers are correctly saved."
- "Next, let's test ride request matching."
- [Run ride matching test]
- "Now I'll modify the test to make it fail."
- [Edit test to use incompatible locations]
- [Run test again, showing failure]
- "The test fails because we're trying to match rides with incompatible locations."

## Conclusion (1 minute)
- **Summary:** "Today we've demonstrated BRACULA's key features including user authentication, post management, resource sharing, and ride sharing."
- **Testing:** "We showed how our testing infrastructure helps ensure code quality and catches issues early."
- **Future Plans:** "Future enhancements could include integration with university systems, mobile applications, and improved notification features."
- **Closing:** "Thank you for watching our BRACULA project demonstration. Do you have any questions?" 