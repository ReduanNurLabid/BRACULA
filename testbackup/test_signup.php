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