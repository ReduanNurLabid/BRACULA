<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA Unit Tests</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f9fa; margin: 0; padding: 0; }
        h1 { text-align: center; margin-top: 40px; font-size: 2.5rem; }
        .test-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 32px; margin: 40px auto; max-width: 1200px; }
        .test-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 32px 28px 24px 28px;
            width: 340px;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .test-card h2 { margin: 0 0 10px 0; font-size: 1.4rem; }
        .test-card p { margin: 0 0 18px 0; color: #444; }
        .test-card .run-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .feed { background: #1976d2; }
        .api { background: #388e3c; }
        .rideshare { background: #ffc107; color: #222; }
        .accommodation { background: #d32f2f; }
        .resources { background: #00bcd4; }
        .event { background: #8e24aa; }
        .profile { background: #5d4037; }
        .test-card .run-btn.feed { background: #1976d2; }
        .test-card .run-btn.api { background: #388e3c; }
        .test-card .run-btn.rideshare { background: #ffc107; color: #222; }
        .test-card .run-btn.accommodation { background: #d32f2f; }
        .test-card .run-btn.resources { background: #00bcd4; }
        .test-card .run-btn.event { background: #8e24aa; }
        .test-card .run-btn.profile { background: #5d4037; }
        @media (max-width: 900px) {
            .test-grid { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <h1>BRACULA Unit Tests</h1>
    <div class="test-grid">
        <div class="test-card feed">
            <h2>Feed Post Tests</h2>
            <p>Unit tests for feed post creation and management functionality.</p>
            <a href="feed.test" class="run-btn feed">Run Tests</a>
        </div>
        <div class="test-card api">
            <h2>API Tests</h2>
            <p>Automated tests for API endpoints, focused on post creation.</p>
            <a href="test_api.php" class="run-btn api">Run API Tests</a>
        </div>
        <div class="test-card rideshare">
            <h2>Rideshare Tests</h2>
            <p>Tests for the rideshare functionality including creating rides and ride requests.</p>
            <a href="test_rideshare.php" class="run-btn rideshare">Run Rideshare Tests</a>
        </div>
        <div class="test-card accommodation">
            <h2>Accommodation Tests</h2>
            <p>Tests for the accommodation functionality including creating and managing accommodations.</p>
            <a href="test_accommodation.php" class="run-btn accommodation">Run Accommodation Tests</a>
        </div>
        <div class="test-card resources">
            <h2>Resources Tests</h2>
            <p>Tests for the resources functionality including uploading and managing resources.</p>
            <a href="test_resources.php" class="run-btn resources">Run Resources Tests</a>
        </div>
        <div class="test-card event">
            <h2>Event Tests</h2>
            <p>Tests for event creation, listing, and management functionality.</p>
            <a href="test_event.php" class="run-btn event">Run Event Tests</a>
        </div>
        <div class="test-card profile">
            <h2>Profile Tests</h2>
            <p>Tests for viewing and updating user profile information.</p>
            <a href="test_profile.php" class="run-btn profile">Run Profile Tests</a>
        </div>
    </div>

    <div class="section" style="margin:40px auto; max-width:800px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:32px;">
        <h2 style="color:#8e24aa;">Event Tests</h2>
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
        <div class="button-group" style="margin-top:20px;">
            <button onclick="listEvents();return false;">List All Events</button>
        </div>
        <h3>Response:</h3>
        <div id="event-response">No response yet...</div>
        <script>
        document.getElementById('createEventForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = {
                title: document.getElementById('title').value,
                description: document.getElementById('description').value,
                date: document.getElementById('date').value,
                location: document.getElementById('location').value
            };
            document.getElementById('event-response').innerText = 'Sending request...';
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
                    document.getElementById('event-response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('event-response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('event-response').innerText = 'Error: ' + error.message;
            }
        });
        async function listEvents() {
            document.getElementById('event-response').innerText = 'Fetching events...';
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
                    document.getElementById('event-response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('event-response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('event-response').innerText = 'Error: ' + error.message;
            }
        }
        </script>
    </div>

    <div class="section" style="margin:40px auto; max-width:800px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:32px;">
        <h2 style="color:#5d4037;">Profile Tests</h2>
        <div class="section">
            <h3>View Profile</h3>
            <div class="form-group">
                <label for="userId">User ID:</label>
                <input type="text" id="userId" placeholder="Enter user ID to view profile">
            </div>
            <button onclick="viewProfile();return false;">View Profile</button>
        </div>
        <div class="section">
            <h3>Update Profile</h3>
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
        <div id="profile-response">No response yet...</div>
        <script>
        async function viewProfile() {
            const userId = document.getElementById('userId').value;
            if (!userId) {
                document.getElementById('profile-response').innerText = 'Please enter a user ID';
                return;
            }
            document.getElementById('profile-response').innerText = 'Fetching profile...';
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
                    document.getElementById('profile-response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                    if (response.ok && data) {
                        document.getElementById('name').value = data.name || '';
                        document.getElementById('bio').value = data.bio || '';
                        document.getElementById('phone').value = data.phone || '';
                        document.getElementById('address').value = data.address || '';
                    }
                } catch (e) {
                    document.getElementById('profile-response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('profile-response').innerText = 'Error: ' + error.message;
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
            document.getElementById('profile-response').innerText = 'Sending request...';
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
                    document.getElementById('profile-response').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
                } catch (e) {
                    document.getElementById('profile-response').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
                }
            } catch (error) {
                document.getElementById('profile-response').innerText = 'Error: ' + error.message;
            }
        });
        </script>
    </div>
</body>
</html>