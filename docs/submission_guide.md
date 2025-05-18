# BRACULA Project Submission Guide

## Required Deliverables

1. **Updated Class Diagram**
   - Must show the relationships between all main classes in the system
   - Should reflect the MVC architecture
   - Include all models, controllers, and their relationships

2. **Feature List by Team Member**
   Each team member should document their contributions with:

   | Team Member | Features Implemented | Files/Modules |
   |-------------|---------------------|---------------|
   | Member 1    | - Feature 1<br>- Feature 2<br>- Feature 3 | - models/X.php<br>- api/X/controller.php<br>- html/X/view.php |
   | Member 2    | - Feature 1<br>- Feature 2<br>- Feature 3 | - models/Y.php<br>- api/Y/controller.php<br>- html/Y/view.php |
   | Member 3    | - Feature 1<br>- Feature 2<br>- Feature 3 | - models/Z.php<br>- api/Z/controller.php<br>- html/Z/view.php |
   | Member 4    | - Feature 1<br>- Feature 2<br>- Feature 3 | - models/W.php<br>- api/W/controller.php<br>- html/W/view.php |

   (Fill in specific feature details for each team member)

3. **Project Demonstration Video Checklist**
   Create a max 15-minute video covering:

   - [ ] Brief introduction to BRACULA project
   - [ ] Overview of the MVC architecture implementation
   
   For each team member:
   - [ ] Demonstrate how their features work in the UI
   - [ ] Explain the controller portion of their features
   - [ ] Run tests for two of their features
   - [ ] Edit one test so it fails and demonstrate the failure

## Video Demonstration Script Template

### Introduction (1 minute)
- Project overview
- Team introduction
- Technology stack overview

### Architecture Overview (2 minutes)
- Explain MVC architecture
- Show class diagram
- Explain database schema

### Feature Demonstrations (8-10 minutes, ~2 minutes per team member)

#### Team Member 1
1. **Feature Demo:**
   - Show feature in the UI
   - Explain how it works from a user perspective
   - Walk through the controller code (api/XXX/)
   - Explain how the controller interacts with the model

2. **Test Demonstration:**
   - Run two tests for the features
   - Edit one test to make it fail
   - Show the failing test result
   - Explain why the test failed

(Repeat for each team member)

### Conclusion (1 minute)
- Summary of project accomplishments
- Future enhancements
- Final thoughts

## Testing Guide

For demonstrating tests in the video, select appropriate tests for each feature:

1. **Running Tests:**
   - Navigate to the test directory
   - Run tests using the appropriate command (PHP or Python test runner)
   - Show the successful test results

2. **Deliberately Failing a Test:**
   - Open the test file
   - Modify an assertion to make it fail (e.g., change an expected value)
   - Run the test again
   - Show the failure message
   - Explain what was changed to make the test fail

## Submission Checklist

- [ ] Updated Class Diagram (.png or .pdf format)
- [ ] Feature List document (completed for all team members)
- [ ] Project Demonstration Video (max 15 minutes)
- [ ] Source code (clean, commented, and well-organized)
- [ ] Test files (with instructions on how to run them) 