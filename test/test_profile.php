<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Profile</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        #response { margin-top: 20px; padding: 15px; background-color: #f8f8f8; border-radius: 5px; white-space: pre-wrap; }
        .button-group { margin-top: 20px; }
        .button-group button { margin-right: 10px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Test Profile Management</h1>
    
    <div class="section">
        <h2>View Profile</h2>
        <div class="form-group">
            <label for="userId">User ID:</label>
            <input type="text" id="userId" placeholder="Enter user ID to view profile">
        </div>
        <button onclick="viewProfile()">View Profile</button>
    </div>

    <div class="section">
        <h2>Update Profile</h2>
        <form id="updateProfileForm">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" required>
            </div>
            <div class="form-group">
                <label for="bio">Bio:</label>
                <textarea id="bio" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" rows="3"></textarea>
            </div>
            <button type="submit">Update Profile</button>
        </form>
    </div>
    
    <h3>Response:</h3>
    <div id="response">No response yet...</div>
    
    <script>
        async function viewProfile() {
            const userId = document.getElementById('userId').value;
            if (!userId) {
                document.getElementById('response').innerText = 'Please enter a user ID';
                return;
            }

            document.getElementById('response').innerText = 'Fetching profile...';
            
            try {
                const response = await fetch(`api/profile.php?id=${userId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const responseText = await response.text();
                
                try {
                    const data = JSON.parse(responseText);
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                    
                    // If successful, populate the update form
                    if (response.ok && data) {
                        document.getElementById('name').value = data.name || '';
                        document.getElementById('bio').value = data.bio || '';
                        document.getElementById('phone').value = data.phone || '';
                        document.getElementById('address').value = data.address || '';
                    }
                } catch (e) {
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('response').innerText = 'Error: ' + error.message;
            }
        }

        document.getElementById('updateProfileForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value,
                bio: document.getElementById('bio').value,
                phone: document.getElementById('phone').value,
                address: document.getElementById('address').value
            };
            
            document.getElementById('response').innerText = 'Sending request...';
            
            try {
                const response = await fetch('api/profile.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const responseText = await response.text();
                
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