<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Event</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        #response { margin-top: 20px; padding: 15px; background-color: #f8f8f8; border-radius: 5px; white-space: pre-wrap; }
        .button-group { margin-top: 20px; }
        .button-group button { margin-right: 10px; }
    </style>
</head>
<body>
    <h1>Test Event Management</h1>
    
    <h2>Create Event</h2>
    <form id="createEventForm">
        <div class="form-group">
            <label for="title">Event Title:</label>
            <input type="text" id="title" required>
        </div>
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" rows="4" required></textarea>
        </div>
        <div class="form-group">
            <label for="date">Event Date:</label>
            <input type="datetime-local" id="date" required>
        </div>
        <div class="form-group">
            <label for="location">Location:</label>
            <input type="text" id="location" required>
        </div>
        <button type="submit">Create Event</button>
    </form>

    <div class="button-group">
        <button onclick="listEvents()">List All Events</button>
    </div>
    
    <h3>Response:</h3>
    <div id="response">No response yet...</div>
    
    <script>
        document.getElementById('createEventForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const formData = {
                title: document.getElementById('title').value,
                description: document.getElementById('description').value,
                date: document.getElementById('date').value,
                location: document.getElementById('location').value
            };
            
            document.getElementById('response').innerText = 'Sending request...';
            
            try {
                const response = await fetch('api/events.php', {
                    method: 'POST',
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

        async function listEvents() {
            document.getElementById('response').innerText = 'Fetching events...';
            
            try {
                const response = await fetch('api/events.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
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
        }
    </script>
</body>
</html> 