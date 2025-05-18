# BRACULA Unit Testing Framework

This directory contains unit tests for the BRACULA application, with a focus on feed post creation functionality.

## Overview

The unit testing framework provides automated tests for both direct database operations and API endpoints. The tests are designed to verify that the feed post creation functionality works correctly under various scenarios.

## Test Files

- **index.php**: Main entry point with links to all available tests
- **post_test.php**: Tests for direct database operations related to posts
- **api_test.php**: Tests for API endpoints, particularly post creation

## What's Being Tested

### Database Operations (post_test.php)

1. **Basic post creation**: Tests creating a post with valid data
2. **Empty content validation**: Tests validation for posts with empty content
3. **Long content handling**: Tests handling of posts with very long content
4. **Invalid user validation**: Tests foreign key constraints with invalid user IDs

### API Endpoints (api_test.php)

1. **Successful post creation**: Tests creating a post via the API with valid data
2. **Missing fields validation**: Tests API validation when required fields are missing
3. **Invalid user handling**: Tests API handling of invalid user IDs

## Running the Tests

1. Navigate to the test directory in your web browser (e.g., `http://localhost/BRACULA/test/`)
2. Click on the test you want to run from the main index page
3. Review the test results displayed in the browser

## Test Results

Each test will display:
- **Pass/Fail Status**: Whether the test passed or failed
- **Message**: A description of the test result
- **Details**: Additional information about the test, such as post ID, HTTP status code, etc.
- **Data**: The actual data returned by the operation

## Cleanup

All tests automatically clean up any data created during the testing process to avoid polluting the database with test data.

## Extending the Tests

To add more tests:

1. Add new test methods to the existing test classes
2. Register the new tests in the `runTests()` method
3. Update the index.php file to include links to any new test files

## Future Improvements

- Implement a proper testing framework like PHPUnit
- Add more comprehensive tests for all CRUD operations
- Add performance tests
- Implement automated CI/CD testing 