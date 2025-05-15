<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Setting up Test Environment</h1>";

// Define test directory
$testDir = __DIR__ . '/test';

// Create test directory if it doesn't exist
if (!file_exists($testDir)) {
    if (mkdir($testDir, 0755, true)) {
        echo "<p>✅ Test directory created successfully at: {$testDir}</p>";
    } else {
        die("<p>❌ Failed to create test directory</p>");
    }
} else {
    echo "<p>✅ Test directory already exists at: {$testDir}</p>";
}

// Create test database config
$testConfigDir = $testDir . '/config';
if (!file_exists($testConfigDir)) {
    mkdir($testConfigDir, 0755, true);
}

$testConfigContent = <<<'EOD'
<?php
class Database {
    private $host = "localhost";
    private $db_name = "bracula_test_db";
    private $username = "root";
    private $password = "";
    private $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // First connect without database name
            $this->conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Create database if it doesn't exist
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            
            // Select the database
            $this->conn->exec("USE " . $this->db_name);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
EOD;

file_put_contents($testConfigDir . '/database.php', $testConfigContent);
echo "<p>✅ Test database configuration created</p>";

// Create test models directory and copy User.php
$testModelsDir = $testDir . '/models';
if (!file_exists($testModelsDir)) {
    mkdir($testModelsDir, 0755, true);
}

// Copy User.php with fixed paths
$userModelContent = file_get_contents(__DIR__ . '/models/User.php');
file_put_contents($testModelsDir . '/User.php', $userModelContent);
echo "<p>✅ User model copied to test directory</p>";

// Create test API directory
$testApiDir = $testDir . '/api';
if (!file_exists($testApiDir)) {
    mkdir($testApiDir, 0755, true);
}

// Create test register.php with enhanced debugging
$testRegisterContent = <<<'EOD'
<?php
// Enable error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Prevent any output before headers
ob_start();

try {
    // Headers
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // Log request details
    $debug_info = [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'raw_input' => file_get_contents("php://input"),
        'request_time' => date('Y-m-d H:i:s')
    ];
    error_log("Register API Debug Info: " . print_r($debug_info, true));

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        echo json_encode(["status" => "success"]);
        exit();
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed. Only POST requests are accepted.");
    }

    // Get raw POST data
    $raw_data = file_get_contents("php://input");
    error_log("Raw POST data: " . $raw_data);

    // Check if data is valid JSON
    $data = json_decode($raw_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data provided: " . json_last_error_msg());
    }

    // Include database and user model
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/User.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Create user object
    $user = new User($db);

    // Check required fields
    $required_fields = ['full_name', 'student_id', 'email', 'password', 'department'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($data->$field)) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Set user property values
    $user->full_name = $data->full_name;
    $user->student_id = $data->student_id;
    $user->email = $data->email;
    $user->password = $data->password;
    $user->department = $data->department;
    $user->avatar_url = !empty($data->avatar_url) ? $data->avatar_url : null;
    $user->bio = !empty($data->bio) ? $data->bio : null;
    $user->interests = !empty($data->interests) ? $data->interests : null;

    // Check if email already exists
    if ($user->emailExists()) {
        throw new Exception("Email already exists");
    }

    // Check if student ID already exists
    if ($user->studentIdExists()) {
        throw new Exception("Student ID already exists");
    }

    // Create the user
    if ($user->create()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "User was created successfully"
        ]);
    } else {
        throw new Exception("Failed to create user");
    }

} catch (Exception $e) {
    $status_code = 400;
    
    // Set appropriate status code based on error type
    if ($e instanceof PDOException) {
        $status_code = 500;
        error_log("Database Error: " . $e->getMessage());
    } else if (strpos($e->getMessage(), "Method not allowed") !== false) {
        $status_code = 405;
    }
    
    http_response_code($status_code);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "debug_info" => $debug_info ?? null
    ]);
} finally {
    // Ensure all output buffers are flushed
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("Buffered output: " . $output);
    }
    echo $output;
}
?>
EOD;

file_put_contents($testApiDir . '/register.php', $testRegisterContent);
echo "<p>✅ Test register API created</p>";

// Create test login.php
$testLoginContent = <<<'EOD'
<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

try {
    // Log request details
    error_log("Login request received: " . print_r($_SERVER, true));
    error_log("POST data: " . file_get_contents("php://input"));

    // Include database and user model
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/User.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Create user object
    $user = new User($db);

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Check for required fields
    if (empty($data->email) || empty($data->password)) {
        throw new Exception("Email and password are required");
    }

    // Attempt to log in
    if ($user->login($data->email, $data->password)) {
        // Get full user data
        $userData = $user->getById($user->user_id);
        
        if ($userData) {
            // Remove sensitive data
            unset($userData['password_hash']);
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "user" => $userData
            ]);
        } else {
            throw new Exception("Error retrieving user data");
        }
    } else {
        throw new Exception("Invalid email or password");
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
EOD;

file_put_contents($testApiDir . '/login.php', $testLoginContent);
echo "<p>✅ Test login API created</p>";

// Create database initialization script for test DB
$testDbInitContent = <<<'EOD'
<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

echo "<h1>Test Database Initialization</h1>";

try {
    // Create users table with all required columns
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        avatar_url VARCHAR(255),
        bio TEXT,
        department VARCHAR(100),
        interests TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($usersTable);
    echo "<p>✅ Users table created successfully</p>";
    
    // Show current table structure
    echo "<h3>Current 'users' table structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' (NOT NULL)' : '') . "\n";
    }
    echo "</pre>";
    
    echo "<p style='color: green;'>Database initialized successfully!</p>";
    echo "<p>You can now test user registration and login using the test APIs.</p>";
    
    // Generate test links
    echo "<h3>Test Pages:</h3>";
    echo "<ul>";
    echo "<li><a href='test_signup.php'>Test User Registration</a></li>";
    echo "<li><a href='test_login.php'>Test User Login</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h3>Error:</h3>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
}
?>
EOD;

file_put_contents($testDir . '/init_db.php', $testDbInitContent);
echo "<p>✅ Test database initialization script created</p>";

// Create test signup page
$testSignupContent = <<<'EOD'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Signup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        #response { margin-top: 20px; padding: 15px; background-color: #f8f8f8; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Test User Registration</h1>
    <form id="signupForm">
        <div class="form-group">
            <label for="fullName">Full Name:</label>
            <input type="text" id="fullName" value="Test User" required>
        </div>
        <div class="form-group">
            <label for="studentID">Student ID:</label>
            <input type="text" id="studentID" value="TEST123" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" value="test@g.bracu.ac.bd" required>
        </div>
        <div class="form-group">
            <label for="department">Department:</label>
            <input type="text" id="department" value="CSE" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" value="Test@123" required>
        </div>
        <button type="submit">Register</button>
    </form>
    
    <h3>Response:</h3>
    <div id="response">No response yet...</div>
    
    <script>
        document.getElementById('signupForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const formData = {
                full_name: document.getElementById('fullName').value,
                student_id: document.getElementById('studentID').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                department: document.getElementById('department').value,
                avatar_url: 'https://avatar.iran.liara.run/public',
                bio: ''
            };
            
            document.getElementById('response').innerText = 'Sending request...';
            
            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const responseText = await response.text();
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(responseText);
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('response').innerText = 'Error: ' + error.message;
            }
        });
    </script>
</body>
</html>
EOD;

file_put_contents($testDir . '/test_signup.php', $testSignupContent);
echo "<p>✅ Test signup page created</p>";

// Create test login page
$testLoginContent = <<<'EOD'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        #response { margin-top: 20px; padding: 15px; background-color: #f8f8f8; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Test User Login</h1>
    <form id="loginForm">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" value="test@g.bracu.ac.bd" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" value="Test@123" required>
        </div>
        <button type="submit">Login</button>
    </form>
    
    <h3>Response:</h3>
    <div id="response">No response yet...</div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };
            
            document.getElementById('response').innerText = 'Sending request...';
            
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const responseText = await response.text();
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(responseText);
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('response').innerText = 'Error: ' + error.message;
            }
        });
    </script>
</body>
</html>
EOD;

file_put_contents($testDir . '/test_login.php', $testLoginContent);
echo "<p>✅ Test login page created</p>";

// Create index page for the test directory
$testIndexContent = <<<'EOD'
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
EOD;

file_put_contents($testDir . '/index.php', $testIndexContent);
echo "<p>✅ Test environment index page created</p>";

echo "<h2>Test Environment Setup Complete!</h2>";
echo "<p>Your test environment has been created in the '/test' directory.</p>";
echo "<p>You can access it at: <a href='test/index.php'>test/index.php</a></p>";
?> 