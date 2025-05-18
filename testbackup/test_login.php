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