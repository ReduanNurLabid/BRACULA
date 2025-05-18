<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA - Unit Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .test-card {
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4 text-center">BRACULA Unit Tests</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Feed Post Tests</h3>
                    </div>
                    <div class="card-body">
                        <p>Unit tests for feed post creation and management functionality.</p>
                        <a href="post_test.php" class="btn btn-primary">Run Tests</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">API Tests</h3>
                    </div>
                    <div class="card-body">
                        <p>Automated tests for API endpoints, focused on post creation.</p>
                        <a href="api_test.php" class="btn btn-success">Run API Tests</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header bg-warning text-white">
                        <h3 class="mb-0">Rideshare Tests</h3>
                    </div>
                    <div class="card-body">
                        <p>Tests for the rideshare functionality including creating rides and ride requests.</p>
                        <a href="rideshare_test.php" class="btn btn-warning">Run Rideshare Tests</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0">About Unit Testing</h3>
                    </div>
                    <div class="card-body">
                        <p>These unit tests help ensure that the BRACULA application's feed post creation functionality works correctly under various scenarios.</p>
                        <h5>What's being tested:</h5>
                        <ul>
                            <li>Basic post creation with valid data</li>
                            <li>Validation for empty content</li>
                            <li>Handling of very long content</li>
                            <li>Foreign key constraints with invalid user IDs</li>
                            <li>API endpoint functionality</li>
                        </ul>
                        <p><strong>Note:</strong> The tests automatically clean up any data created during the testing process.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
