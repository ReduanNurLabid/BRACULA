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
        input, textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        #response { margin-top: 20px; padding: 15px; background-color: #f8f8f8; border-radius: 5px; white-space: pre-wrap; }
        .button-group { margin-top: 20px; }
        .button-group button { margin-right: 10px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .timebar { width: 100%; height: 20px; background-color: #f0f0f0; border-radius: 10px; margin: 10px 0; }
        .timebar-progress { width: 0%; height: 100%; background-color: #4CAF50; border-radius: 10px; transition: width 0.3s ease; }
    </style>
</head>
<body>
    <h1>Test Event Management</h1>
    
    <div class="section">
        <h2>Create Event</h2>
        <form id="createEventForm">
            <div class="form-group">
                <label for="name">Event Name:</label>
                <input type="text" id="name" required>
            </div>
            <div class="form-group">
                <label for="type">Event Type:</label>
                <select id="type" required>
                    <option value="academic">Academic</option>
                    <option value="social">Social</option>
                    <option value="sports">Sports</option>
                    <option value="cultural">Cultural</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date">Event Date:</label>
                <input type="datetime-local" id="date" required>
            </div>
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" required>
            </div>
            <div class="form-group">
                <label for="cover_image">Cover Image URL:</label>
                <input type="text" id="cover_image" required>
            </div>
            <div class="form-group">
                <label for="organizer_id">Organizer ID:</label>
                <input type="number" id="organizer_id" required>
            </div>
            <button type="submit">Create Event</button>
        </form>
    </div>

    <div class="section">
        <h2>List Events</h2>
        <div class="form-group">
            <label for="eventType">Filter by Type:</label>
            <select id="eventType">
                <option value="">All Types</option>
                <option value="academic">Academic</option>
                <option value="social">Social</option>
                <option value="sports">Sports</option>
                <option value="cultural">Cultural</option>
            </select>
        </div>
        <div class="form-group">
            <label for="eventDate">Filter by Date:</label>
            <input type="date" id="eventDate">
        </div>
        <button onclick="listEvents()">List Events</button>
    </div>
    
    <h3>Response:</h3>
    <div id="response">No response yet...</div>
    <div class="timebar">
        <div class="timebar-progress" id="timebarProgress"></div>
    </div>
    
    <script>
        let timebarInterval;
        
        function startTimebar() {
            const timebar = document.getElementById('timebarProgress');
            let progress = 0;
            timebar.style.width = '0%';
            
            if (timebarInterval) {
                clearInterval(timebarInterval);
            }
            
            timebarInterval = setInterval(() => {
                progress += 1;
                timebar.style.width = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(timebarInterval);
                }
            }, 50);
        }
        
        function stopTimebar() {
            if (timebarInterval) {
                clearInterval(timebarInterval);
            }
            document.getElementById('timebarProgress').style.width = '0%';
        }

        document.getElementById('createEventForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value,
                type: document.getElementById('type').value,
                date: document.getElementById('date').value,
                location: document.getElementById('location').value,
                cover_image: document.getElementById('cover_image').value,
                organizer_id: document.getElementById('organizer_id').value
            };
            
            document.getElementById('response').innerText = 'Sending request...';
            startTimebar();
            
            try {
                const response = await fetch('api/events/events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const responseText = await response.text();
                stopTimebar();
                
                try {
                    const data = JSON.parse(responseText);
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                stopTimebar();
                document.getElementById('response').innerText = 'Error: ' + error.message;
            }
        });

        async function listEvents() {
            document.getElementById('response').innerText = 'Fetching events...';
            startTimebar();
            
            const type = document.getElementById('eventType').value;
            const date = document.getElementById('eventDate').value;
            
            let url = 'api/events/events.php';
            const params = new URLSearchParams();
            
            if (type) {
                params.append('type', type);
            }
            if (date) {
                params.append('date', date);
            }
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const responseText = await response.text();
                stopTimebar();
                
                try {
                    const data = JSON.parse(responseText);
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                stopTimebar();
                document.getElementById('response').innerText = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html> 