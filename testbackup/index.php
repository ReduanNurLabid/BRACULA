<?php
require_once __DIR__ . '/setup_test_env.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA Test Environment</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .feature-card {
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #6c757d;
        }
        .test-header {
            background-color: #343a40;
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/BRACULA/test/index.php">BRACULA Tests</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_feed.php">Feed Test</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_rideshare.php">Rideshare Test</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_feed_breakable.php">Feed Unit Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_rideshare_breakable.php">Rideshare Unit Tests</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="test-header">
        <div class="container text-center">
            <h1>BRACULA Test Environment</h1>
            <p class="lead">Test and demonstrate MVC implementation, API interactions, and feature functionalities</p>
        </div>
    </section>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Test Environment Overview</h3>
                    </div>
                    <div class="card-body">
                        <p>Welcome to the BRACULA test environment. This setup allows you to demonstrate the features of the BRACULA system, test API interactions, and showcase the MVC architecture implementation.</p>
                        <p>Use the navigation bar above or the feature cards below to access different test pages.</p>
                        
                        <div class="alert alert-success mt-3">
                            <strong>New Simplified Tests Available!</strong> Check out our streamlined testing options below for an easier demonstration experience.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Simplified Testing Options</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h4 class="card-title">Simple All-in-One Test</h4>
                                        <p class="card-text">Run all tests in a simple, unified interface that shows results clearly.</p>
                                        <a href="simple_test.php" class="btn btn-primary">Run Simple Tests</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h4 class="card-title">Breakable Test Demo</h4>
                                        <p class="card-text">Switch between passing and failing tests to demonstrate proper testing practices.</p>
                                        <a href="simple_breakable_test.php" class="btn btn-warning">Run Breakable Tests</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">📝</div>
                        <h4 class="card-title">Feed Feature Tests</h4>
                        <p class="card-text">Test the campus feed functionality including post creation, retrieval, voting, and searching</p>
                        <div class="mt-3">
                            <a href="/BRACULA/test/test_feed.php" class="btn btn-outline-primary">Interactive Test</a>
                            <a href="/BRACULA/test/test_feed_breakable.php" class="btn btn-outline-danger">Unit Tests</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">🚗</div>
                        <h4 class="card-title">Rideshare Feature Tests</h4>
                        <p class="card-text">Test the rideshare functionality including ride offers, requests, status changes, and driver reviews</p>
                        <div class="mt-3">
                            <a href="/BRACULA/test/test_rideshare.php" class="btn btn-outline-primary">Interactive Test</a>
                            <a href="/BRACULA/test/test_rideshare_breakable.php" class="btn btn-outline-danger">Unit Tests</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">MVC Architecture Demonstration</h3>
                    </div>
                    <div class="card-body">
                        <h4>MVC Components in BRACULA</h4>
                        <ul>
                            <li><strong>Models</strong> (models/Post.php, models/Ride.php): Handle data logic, validations, and database interactions</li>
                            <li><strong>Views</strong> (HTML files in html/): Present data to users and capture user inputs</li>
                            <li><strong>Controllers</strong> (API endpoints in api/): Process user inputs, coordinate between models and views</li>
                        </ul>
                        
                        <h4>How to Demonstrate MVC Implementation</h4>
                        <ol>
                            <li>Use the interactive tests to perform actions</li>
                            <li>Observe how user inputs from the View are processed by Controller endpoints</li>
                            <li>Notice how Controllers delegate data operations to Models</li>
                            <li>See how data flows back through Controllers to Views for display</li>
                        </ol>
                        
                        <h4>Testing Strategy</h4>
                        <p>The unit tests demonstrate:</p>
                        <ul>
                            <li>Model functionality testing in isolation</li>
                            <li>Properly separated concerns in the MVC architecture</li>
                            <li>Controlled failure scenarios for robust error handling</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container">
            <p class="text-center text-muted">BRACULA Test Environment</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>