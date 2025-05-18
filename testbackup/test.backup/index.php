<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA Test Environment</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .menu { margin: 20px 0; }
        .menu a { display: inline-block; margin-right: 15px; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
        .menu a:hover { background-color: #45a049; }
        .section { margin: 30px 0; padding: 20px; background-color: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>BRACULA Test Environment</h1>
    <p>This is an isolated test environment for the BRACULA application.</p>
    
    <div class="menu">
        <a href="init_db.php">Initialize Test DB</a>
        <a href="test_signup.php">Test Registration</a>
        <a href="test_login.php">Test Login</a>
    </div>
    
    <div class="section">
        <h2>Getting Started</h2>
        <ol>
            <li>First, click on <strong>Initialize Test DB</strong> to set up the test database.</li>
            <li>Then use the <strong>Test Registration</strong> page to create a test user.</li>
            <li>Finally, test logging in with the user credentials using the <strong>Test Login</strong> page.</li>
        </ol>
    </div>
    
    <div class="section">
        <h2>Test Environment Information</h2>
        <ul>
            <li><strong>Database:</strong> bracula_test_db</li>
            <li><strong>API Endpoints:</strong> /api/register.php, /api/login.php</li>
            <li><strong>Test Data:</strong> Pre-filled with sample values</li>
        </ul>
    </div>
</body>
</html>