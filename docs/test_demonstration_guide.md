# Test Demonstration Guide

This guide will help you demonstrate the testing aspects of your BRACULA project in the video presentation.

## Test Demonstration Requirements

For each team member, you need to:
1. Run tests for two of your features
2. Edit one test so it fails, and demonstrate the failure

## Test Files in the Project

Based on the project structure, you have the following types of tests:

1. **PHP Test Files**
   - Located in `test/` directory
   - Include model tests and API tests

2. **Python Test Files**
   - Located in `test/` directory (with .py extensions)
   - Using pytest framework

## Demonstration Steps

### 1. Running Tests Successfully

#### PHP Tests
```bash
# Navigate to the test directory
cd test

# Run a specific test file
php test_login.php

# Or run all tests
php index.php
```

#### Python Tests
```bash
# Navigate to the test directory
cd test

# Run a specific test
pytest test_login.py

# Run all tests
pytest
```

### 2. Modifying a Test to Make it Fail

#### Example: Modifying User Authentication Test

1. **Show the original test that passes**
   - Explain what the test checks
   - Show the test passing

2. **Modify the test to make it fail**
   - Change an assertion to expect an incorrect value
   - For example in `test/models/User.php`:
     ```php
     // Change a passing assertion to a failing one
     // Original (passing):
     // $this->assertEquals($expected_user_id, $user->user_id);
     
     // Modified (failing):
     $this->assertEquals(999, $user->user_id); // Using wrong ID
     ```

3. **Run the test again to show failure**
   - Run the test with the same command
   - Show and explain the error message

4. **Explain why the test failed**
   - Briefly explain how your change caused the test to fail
   - Relate it back to the feature being tested

## Sample Test Demonstration Script

Here's a script you can follow for demonstrating tests in your video:

1. **Introduction**
   "Now I'll demonstrate the testing for my features. I implemented [Feature 1] and [Feature 2], and I'll show you how we test these features."

2. **Running First Test**
   "First, let's look at the test for [Feature 1]. This test checks that [explain what test verifies]."
   [Run the test and show it passing]
   "As you can see, the test passes, which means our feature is working as expected."

3. **Running Second Test**
   "Next, let's test [Feature 2]. This test verifies that [explain what test verifies]."
   [Run the test and show it passing]
   "This test also passes, confirming that our feature works correctly."

4. **Modifying a Test to Fail**
   "Now, I'll demonstrate what happens when a test fails. I'll modify the [Feature 1] test to expect an incorrect result."
   [Show code changes]
   "I've changed the expected value from [correct value] to [incorrect value]."
   [Run the test again]
   "As expected, the test now fails with the message [explain error message]. This demonstrates how our tests catch issues when the code doesn't behave as expected."

5. **Conclusion**
   "This test failure would alert us that there's an issue with our code that needs to be fixed. In a real development scenario, we'd need to either fix the code to match the expected behavior or update the test if our requirements have changed."

## Tests You Can Demonstrate

Based on the project structure, here are some tests you could demonstrate:

### User Authentication Tests
- Login verification
- Registration validation
- Password hashing

### Post Management Tests
- Post creation
- Post retrieval
- Post deletion

### Comment System Tests
- Comment creation
- Nested comments
- Comment deletion

### Resource Sharing Tests
- File upload
- File download
- Resource categorization

### Event Management Tests
- Event creation
- Event registration
- Event retrieval

### Ride Sharing Tests
- Ride offer creation
- Ride request matching
- Driver review system

### Accommodation Tests
- Listing creation
- Accommodation search
- Inquiry submission 