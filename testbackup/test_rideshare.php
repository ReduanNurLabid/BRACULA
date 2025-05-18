<?php
require_once __DIR__ . '/setup_test_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Ride.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rideshare Feature Test</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
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
                        <a class="nav-link active" href="/BRACULA/test/test_rideshare.php">Rideshare Test</a>
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

<div class="container mt-4">
    <h1>Rideshare Feature Test</h1>
    <p>This test verifies the functionality of the Ride model and rideshare-related controllers.</p>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test 1: Create Ride Offer</h3>
        </div>
        <div class="card-body">
            <h4>Create a new ride offer</h4>
            <form id="createRideForm">
                <div class="form-group mb-3">
                    <label for="from_location">From:</label>
                    <input type="text" id="from_location" class="form-control" value="BRAC University Campus" required>
                </div>
                <div class="form-group mb-3">
                    <label for="to_location">To:</label>
                    <input type="text" id="to_location" class="form-control" value="Bashundhara R/A" required>
                </div>
                <div class="form-group mb-3">
                    <label for="departure_time">Departure Time:</label>
                    <input type="datetime-local" id="departure_time" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="seats_available">Available Seats:</label>
                    <input type="number" id="seats_available" class="form-control" value="3" min="1" max="10" required>
                </div>
                <div class="form-group mb-3">
                    <label for="price">Price per Seat (BDT):</label>
                    <input type="number" id="price" class="form-control" value="100" min="0" required>
                </div>
                <div class="form-group mb-3">
                    <label for="vehicle_description">Vehicle Description:</label>
                    <input type="text" id="vehicle_description" class="form-control" value="Toyota Corolla - White" required>
                </div>
                <div class="form-group mb-3">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" class="form-control" rows="2">AC available. No smoking please.</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create Ride Offer</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="createRideResponse" class="bg-light p-3">No response yet</pre>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Test 2: Browse Ride Offers</h3>
        </div>
        <div class="card-body">
            <h4>View available rides</h4>
            <form id="getRidesForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="from_filter">From (optional):</label>
                            <input type="text" id="from_filter" class="form-control" placeholder="e.g. BRAC University">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="to_filter">To (optional):</label>
                            <input type="text" id="to_filter" class="form-control" placeholder="e.g. Bashundhara">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="date_filter">Date (optional):</label>
                            <input type="date" id="date_filter" class="form-control">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Search Rides</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="getRidesResponse" class="bg-light p-3">No response yet</pre>
            </div>
            <div id="ridesContainer" class="mt-3"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0">Test 3: Request a Ride</h3>
        </div>
        <div class="card-body">
            <h4>Submit a ride request</h4>
            <form id="requestRideForm">
                <div class="form-group mb-3">
                    <label for="ride_id">Ride ID:</label>
                    <input type="number" id="ride_id" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="passenger_count">Number of Passengers:</label>
                    <input type="number" id="passenger_count" class="form-control" value="1" min="1" max="5" required>
                </div>
                <div class="form-group mb-3">
                    <label for="request_message">Message to Driver:</label>
                    <textarea id="request_message" class="form-control" rows="2">Hi, I would like to join your ride. Thanks!</textarea>
                </div>
                <button type="submit" class="btn btn-info">Request Ride</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="requestRideResponse" class="bg-light p-3">No response yet</pre>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0">Test 4: Change Ride Status</h3>
        </div>
        <div class="card-body">
            <h4>Update ride status</h4>
            <form id="changeStatusForm">
                <div class="form-group mb-3">
                    <label for="status_ride_id">Ride ID:</label>
                    <input type="number" id="status_ride_id" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="status">New Status:</label>
                    <select id="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-warning">Update Status</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="changeStatusResponse" class="bg-light p-3">No response yet</pre>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h3 class="mb-0">Test 5: Driver Reviews</h3>
        </div>
        <div class="card-body">
            <h4>Review a driver</h4>
            <form id="reviewDriverForm">
                <div class="form-group mb-3">
                    <label for="review_ride_id">Ride ID:</label>
                    <input type="number" id="review_ride_id" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="rating">Rating:</label>
                    <select id="rating" class="form-control">
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Very Good</option>
                        <option value="3">3 - Good</option>
                        <option value="2">2 - Fair</option>
                        <option value="1">1 - Poor</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="review_comment">Comment:</label>
                    <textarea id="review_comment" class="form-control" rows="2">Great ride, very punctual and friendly driver.</textarea>
                </div>
                <button type="submit" class="btn btn-secondary">Submit Review</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="reviewDriverResponse" class="bg-light p-3">No response yet</pre>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">Test 6: MVC Analysis</h3>
        </div>
        <div class="card-body">
            <h4>MVC Implementation Analysis</h4>
            <p>The rideshare feature follows the MVC pattern:</p>
            <ul>
                <li><strong>Model (Ride.php):</strong> Handles data operations, ride business logic, and validation</li>
                <li><strong>Controllers (api/rides/):</strong> Process user ride-related requests, coordinate with the model, and prepare data for the view</li>
                <li><strong>Views (html/rides/):</strong> Present ride data to the user in a readable format</li>
            </ul>
            <p>Request flow:</p>
            <ol>
                <li>User request hits an endpoint like <code>api/rides.php</code></li>
                <li>This file redirects to the controller implementation in <code>api/rides/rides.php</code></li>
                <li>The controller instantiates the Ride model and calls appropriate methods</li>
                <li>The model performs validation and executes business logic</li>
                <li>The controller processes the model's response and formats it for the view</li>
                <li>The view displays the ride data to the user</li>
            </ol>
            <p>This separation of concerns makes the code more maintainable and follows proper MVC architecture.</p>
        </div>
    </div>
</div>

<script>
// Set default departure time to 1 hour from now
window.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const formattedDateTime = now.toISOString().slice(0, 16);
    document.getElementById('departure_time').value = formattedDateTime;
    
    // Load first ride to populate the ride ID fields
    loadInitialRide();
});

async function loadInitialRide() {
    try {
        const response = await fetch('../api/rides.php?limit=1');
        const data = await response.json();
        
        if (data.status === 'success' && data.rides && data.rides.length > 0) {
            document.getElementById('ride_id').value = data.rides[0].ride_id;
            document.getElementById('status_ride_id').value = data.rides[0].ride_id;
            document.getElementById('review_ride_id').value = data.rides[0].ride_id;
        }
    } catch (error) {
        console.error('Error fetching initial ride:', error);
    }
}

document.getElementById('createRideForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
        from_location: document.getElementById('from_location').value,
        to_location: document.getElementById('to_location').value,
        departure_time: document.getElementById('departure_time').value,
        seats_available: document.getElementById('seats_available').value,
        price: document.getElementById('price').value,
        vehicle_description: document.getElementById('vehicle_description').value,
        notes: document.getElementById('notes').value
    };
    
    document.getElementById('createRideResponse').innerText = 'Sending request...';
    
    try {
        const response = await fetch('../api/rides.php', {
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
            document.getElementById('createRideResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
            
            // Update ride ID fields if ride was created successfully
            if (data.status === 'success' && data.ride_id) {
                document.getElementById('ride_id').value = data.ride_id;
                document.getElementById('status_ride_id').value = data.ride_id;
                document.getElementById('review_ride_id').value = data.ride_id;
            }
        } catch (e) {
            document.getElementById('createRideResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('createRideResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('getRidesForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const fromFilter = document.getElementById('from_filter').value;
    const toFilter = document.getElementById('to_filter').value;
    const dateFilter = document.getElementById('date_filter').value;
    
    let url = '../api/rides.php?limit=10&offset=0';
    
    if (fromFilter) {
        url += `&from_location=${encodeURIComponent(fromFilter)}`;
    }
    
    if (toFilter) {
        url += `&to_location=${encodeURIComponent(toFilter)}`;
    }
    
    if (dateFilter) {
        url += `&date=${encodeURIComponent(dateFilter)}`;
    }
    
    document.getElementById('getRidesResponse').innerText = 'Fetching rides...';
    document.getElementById('ridesContainer').innerHTML = '';
    
    try {
        const response = await fetch(url);
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            document.getElementById('getRidesResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
            
            // Display rides in a user-friendly format
            if (data.status === 'success' && data.rides) {
                const ridesHtml = data.rides.map(ride => `
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>${ride.from_location} to ${ride.to_location}</strong> - by ${ride.full_name}
                        </div>
                        <div class="card-body">
                            <p><strong>Departure:</strong> ${new Date(ride.departure_time).toLocaleString()}</p>
                            <p><strong>Available Seats:</strong> ${ride.seats_available} | <strong>Price:</strong> ${ride.price} BDT</p>
                            <p><strong>Vehicle:</strong> ${ride.vehicle_description}</p>
                            <p><strong>Notes:</strong> ${ride.notes || 'No additional notes'}</p>
                            <small class="text-muted">Status: ${ride.status} | Ride ID: ${ride.ride_id}</small>
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('ridesContainer').innerHTML = ridesHtml;
            }
        } catch (e) {
            document.getElementById('getRidesResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('getRidesResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('requestRideForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
        ride_id: document.getElementById('ride_id').value,
        passenger_count: document.getElementById('passenger_count').value,
        message: document.getElementById('request_message').value
    };
    
    document.getElementById('requestRideResponse').innerText = 'Sending request...';
    
    try {
        const response = await fetch('../api/ride_requests.php', {
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
            document.getElementById('requestRideResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        } catch (e) {
            document.getElementById('requestRideResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('requestRideResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('changeStatusForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
        ride_id: document.getElementById('status_ride_id').value,
        status: document.getElementById('status').value
    };
    
    document.getElementById('changeStatusResponse').innerText = 'Updating status...';
    
    try {
        const response = await fetch('../api/rides.php', {
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
            document.getElementById('changeStatusResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        } catch (e) {
            document.getElementById('changeStatusResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('changeStatusResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('reviewDriverForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
        ride_id: document.getElementById('review_ride_id').value,
        rating: document.getElementById('rating').value,
        comment: document.getElementById('review_comment').value
    };
    
    document.getElementById('reviewDriverResponse').innerText = 'Submitting review...';
    
    try {
        const response = await fetch('../api/driver_reviews.php', {
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
            document.getElementById('reviewDriverResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        } catch (e) {
            document.getElementById('reviewDriverResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('reviewDriverResponse').innerText = 'Error: ' + error.message;
    }
});
</script>

<footer class="bg-light py-3 mt-5">
    <div class="container">
        <p class="text-center text-muted">BRACULA Test Environment</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 