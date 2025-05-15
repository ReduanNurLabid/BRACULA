document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const offerRideModal = document.getElementById('offerRideModal');
    const requestRideModal = document.getElementById('requestRideModal');
    const viewRequestsModal = document.getElementById('viewRequestsModal');
    const reviewDriverModal = document.getElementById('reviewDriverModal');
    
    const offerRideBtn = document.querySelector('.offer-ride-btn');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const offerRideForm = document.getElementById('offerRideForm');
    const requestRideForm = document.getElementById('requestRideForm');
    const reviewDriverForm = document.getElementById('reviewDriverForm');
    
    const ridesContainer = document.getElementById('ridesContainer');
    const myOffersContainer = document.getElementById('myOffersContainer');
    const myRequestsContainer = document.getElementById('myRequestsContainer');
    
    const applyFiltersBtn = document.querySelector('.apply-filters');
    const searchButton = document.getElementById('searchButton');
    const searchInput = document.getElementById('searchInput');
    
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    // Store rides data
    let rides = [];
    let myOffers = [];
    let myRequests = [];
    let currentUser = null;

    // Base API URL
    const BASE_URL = window.location.origin + '/BRACULA';

    // Initialize
    initUser();
    initTabs();
    fetchRides();
    fetchMyOffers();
    fetchMyRequests();
    initStarRating();
    
    // Global object to track request counts for each ride
    let lastKnownRequestCounts = {};
    
    // Function to check for new ride requests
    async function checkForNewRequests() {
        if (!currentUser || !currentUser.user_id) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/rides.php?user_id=${currentUser.user_id}`);
            const data = await response.json();
            
            if (data.status === 'success' && data.rides && data.rides.length > 0) {
                let totalNewRequests = 0;
                
                data.rides.forEach(ride => {
                    // Count pending requests for this ride
                    const pendingRequests = ride.requests ? 
                        ride.requests.filter(req => req.status === 'pending').length : 0;
                    
                    // If this is a ride we've seen before
                    if (lastKnownRequestCounts[ride.ride_id] !== undefined) {
                        // If there are new pending requests
                        const newRequests = pendingRequests - lastKnownRequestCounts[ride.ride_id];
                        
                        if (newRequests > 0) {
                            totalNewRequests += newRequests;
                            
                            // Get the newest requests (those we haven't seen before)
                            const latestRequests = ride.requests
                                .filter(req => req.status === 'pending')
                                .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                                .slice(0, newRequests);
                                
                            latestRequests.forEach(req => {
                                // Add notification for each new request
                                if (window.addNotification) {
                                    window.addNotification({
                                        type: 'ride_request',
                                        title: 'New Ride Request',
                                        message: `${req.user_name || 'Someone'} has requested to join your ride to ${ride.to_location || ride.destination}`,
                                        data: {
                                            requestId: req.request_id,
                                            rideId: ride.ride_id,
                                            user: req.user_name || 'Someone',
                                            destination: ride.to_location || ride.destination
                                        }
                                    });
                                }
                            });
                        }
                    }
                    
                    // Update the count for this ride
                    lastKnownRequestCounts[ride.ride_id] = pendingRequests;
                });
                
                // If we have new requests and the My Offers tab is visible
                if (totalNewRequests > 0 && document.querySelector('#my-offers-tab').style.display !== 'none') {
                    // Refresh the offers display to show updated counts
                    fetchMyOffers();
                }
            }
        } catch (error) {
            console.error('Error checking for new requests:', error);
        }
    }
    
    // Initialize request counts
    async function initRequestCounts() {
        if (!currentUser || !currentUser.user_id) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/rides.php?user_id=${currentUser.user_id}`);
            const data = await response.json();
            
            if (data.status === 'success' && data.rides && data.rides.length > 0) {
                data.rides.forEach(ride => {
                    const pendingRequests = ride.requests ? 
                        ride.requests.filter(req => req.status === 'pending').length : 0;
                    lastKnownRequestCounts[ride.ride_id] = pendingRequests;
                });
            }
        } catch (error) {
            console.error('Error initializing request counts:', error);
        }
    }
    
    // Initialize request counts when page loads
    initRequestCounts();
    
    // Check for new requests every 30 seconds
    setInterval(checkForNewRequests, 30000);
    
    // Check for new requests when user switches to My Offers tab
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.tab === 'my-offers') {
                // Check for new requests immediately when user switches to My Offers
                checkForNewRequests();
            }
        });
    });
    
    // Listen for notification events from other parts of the system
    window.addEventListener('newRideRequest', function() {
        // Force refresh my offers when a new ride request notification is received
        if (document.querySelector('#my-offers-tab').style.display !== 'none') {
            fetchMyOffers();
        }
    });

    // Initialize user
    function initUser() {
        const userData = localStorage.getItem('user');
        if (userData) {
            currentUser = JSON.parse(userData);
        } else {
            // Redirect to login if no user is found
            // window.location.href = 'login.html';
            console.log('No user found in localStorage');
        }
    }

    // Initialize tabs
    function initTabs() {
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.style.display = 'none');
                
                // Add active class to clicked button
                button.classList.add('active');
                
                // Show corresponding content
                const tabId = button.dataset.tab;
                document.getElementById(`${tabId}-tab`).style.display = 'block';
            });
        });

        // Set default tab
        document.querySelector('.tab-button.active').click();
    }

    // Initialize star rating
    function initStarRating() {
        const stars = document.querySelectorAll('.star-rating i');
        const ratingInput = document.getElementById('rating');
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.rating;
                ratingInput.value = rating;
                
                // Update star appearance
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });
    }

    // Modal functionality
    offerRideBtn.addEventListener('click', () => {
        offerRideModal.style.display = 'block';
    });
    
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            offerRideModal.style.display = 'none';
            requestRideModal.style.display = 'none';
            viewRequestsModal.style.display = 'none';
            reviewDriverModal.style.display = 'none';
        });
    });

    // Close modals when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === offerRideModal) {
            offerRideModal.style.display = 'none';
        } else if (event.target === requestRideModal) {
            requestRideModal.style.display = 'none';
        } else if (event.target === viewRequestsModal) {
            viewRequestsModal.style.display = 'none';
        } else if (event.target === reviewDriverModal) {
            reviewDriverModal.style.display = 'none';
        }
    });

    // Handle Ride Offer Submission
    offerRideForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        try {
            // Get form data
            const formData = new FormData(offerRideForm);
            
            // Debug form data
            console.log('Form data collected:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            const rideData = {
                vehicle_type: formData.get('vehicleType'),
                seats: parseInt(formData.get('seats')),
                fare: parseFloat(formData.get('fare')),
                pickup_location: formData.get('pickup'),
                destination: formData.get('destination'),
                departure_time: formData.get('departure_time'),
                contact_info: formData.get('contact_info'),
                notes: formData.get('notes'),
                user_id: currentUser ? currentUser.user_id : 1 // Default to 1 for testing
            };
            
            // Debug ride data
            console.log('Ride data to be sent:', rideData);

            // Validate data - check each field individually with specific error messages
            if (!rideData.vehicle_type || rideData.vehicle_type === "") {
                throw new Error('Please select a transport type');
            }
            
            if (!rideData.seats || isNaN(rideData.seats)) {
                throw new Error('Please enter a valid number of seats');
            }
            
            if (!rideData.fare || isNaN(rideData.fare)) {
                throw new Error('Please enter a valid fare amount');
            }
            
            if (!rideData.pickup_location) {
                throw new Error('Please enter a pickup location');
            }
            
            if (!rideData.destination) {
                throw new Error('Please enter a destination');
            }
            
            if (!rideData.departure_time) {
                throw new Error('Please select a departure time');
            }

            if (!rideData.contact_info) {
                throw new Error('Please provide contact information');
            }

            // Send request to create ride offer
            const response = await fetch(`${BASE_URL}/api/rides.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(rideData)
            });

            const result = await response.json();
            console.log('API response:', result);

            if (result.status === 'success') {
                // Add ride to display
                addRideToList(result.data);
                
                // Add to my offers
                myOffers.push(result.data);
                refreshMyOffers();
                
                // Close modal and reset form
                offerRideModal.style.display = 'none';
                offerRideForm.reset();
                
                showNotification('Ride offer created successfully!');
            } else {
                throw new Error(result.message || 'Failed to create ride offer');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message, 'error');
        }
    });

    // Handle Ride Request Submission
    requestRideForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        try {
            // Get form data
            const formData = new FormData(requestRideForm);
            const requestData = {
                ride_id: formData.get('ride_id'),
                user_id: currentUser ? currentUser.user_id : 1,
                seats: parseInt(formData.get('seats')),
                pickup: formData.get('pickup'),
                notes: formData.get('notes'),
                status: 'pending'
            };

            // Validate data
            if (!requestData.ride_id || !requestData.seats || !requestData.pickup) {
                throw new Error('Please fill in all required fields');
            }

            // Send request
            const response = await fetch(`${BASE_URL}/api/ride_requests.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Add to my requests
                myRequests.push(result.data);
                refreshMyRequests();
                
                // Close modal and reset form
                requestRideModal.style.display = 'none';
                requestRideForm.reset();
                
                showNotification('Ride request sent successfully! Waiting for driver approval.');
                
                // Get the ride details to get destination
                const rideResponse = await fetch(`${BASE_URL}/api/rides.php?ride_id=${requestData.ride_id}`);
                const rideResult = await rideResponse.json();
                
                if (rideResult.status === 'success' && rideResult.data.length > 0) {
                    const ride = rideResult.data[0];
                    
                    // Trigger notification event for the driver
                    const detail = {
                        requestId: result.data.request_id,
                        user: currentUser ? currentUser.full_name : 'Anonymous',
                        destination: ride.destination,
                        avatar: currentUser ? currentUser.avatar_url : null
                    };
                    
                    // Dispatch custom event
                    document.dispatchEvent(new CustomEvent('rideRequested', { detail: detail }));
                }
            } else {
                throw new Error(result.message || 'Failed to send ride request');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message, 'error');
        }
    });

    // Handle Driver Review Submission
    reviewDriverForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        try {
            // Get form data
            const formData = new FormData(reviewDriverForm);
            const reviewData = {
                ride_id: formData.get('ride_id'),
                driver_id: formData.get('driver_id'),
                user_id: currentUser ? currentUser.user_id : 1,
                rating: parseInt(formData.get('rating')),
                comment: formData.get('comment')
            };

            // Validate data
            if (!reviewData.ride_id || !reviewData.driver_id || !reviewData.rating) {
                throw new Error('Please provide a rating');
            }

            // Send review
            const response = await fetch(`${BASE_URL}/api/driver_reviews.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(reviewData)
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Close modal and reset form
                reviewDriverModal.style.display = 'none';
                reviewDriverForm.reset();
                
                // Reset star rating display
                document.querySelectorAll('.star-rating i').forEach(star => {
                    star.classList.remove('fas');
                    star.classList.add('far');
                });
                
                showNotification('Review submitted successfully!');
            } else {
                throw new Error(result.message || 'Failed to submit review');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message, 'error');
        }
    });

    // Function to fetch rides from the server
    async function fetchRides() {
        try {
            const response = await fetch(`${BASE_URL}/api/rides.php`);
            const result = await response.json();

            if (result.status === 'success') {
                rides = result.data;
                displayRides(rides);
            } else {
                throw new Error(result.message || 'Failed to fetch rides');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Failed to fetch rides', 'error');
        }
    }

    // Function to fetch user's offered rides
    async function fetchMyOffers() {
        if (!currentUser) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/rides.php?user_id=${currentUser.user_id}`);
            const result = await response.json();

            if (result.status === 'success') {
                myOffers = result.data;
                refreshMyOffers();
            } else {
                throw new Error(result.message || 'Failed to fetch your ride offers');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Failed to fetch your ride offers', 'error');
        }
    }

    // Function to fetch user's ride requests
    async function fetchMyRequests() {
        if (!currentUser) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/ride_requests.php?user_id=${currentUser.user_id}`);
            const result = await response.json();

            if (result.status === 'success') {
                myRequests = result.data;
                refreshMyRequests();
            } else {
                throw new Error(result.message || 'Failed to fetch your ride requests');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Failed to fetch your ride requests', 'error');
        }
    }

    // Function to Add Ride to Listings
    function addRideToList(ride) {
        const rideCard = document.createElement('div');
        rideCard.classList.add('ride-card');
        rideCard.dataset.rideId = ride.ride_id;

        const vehicleIcon = getVehicleIcon(ride.vehicle_type);
        const departureTime = formatDateTime(ride.departure_time);
        const statusBadge = ride.status ? 
            `<span class="ride-status status-${ride.status.toLowerCase()}">${capitalizeFirstLetter(ride.status)}</span>` : '';
        
        rideCard.innerHTML = `
            <div class="ride-card-header">
                <h4>${vehicleIcon} ${capitalizeFirstLetter(ride.vehicle_type)} ${statusBadge}</h4>
                <span>${ride.fare} BDT</span>
            </div>
            <div class="ride-card-details">
                <p><i class="fas fa-chair"></i> Seats Available: ${ride.seats}</p>
                <p><i class="fas fa-map-marker-alt"></i> Pickup: ${ride.pickup_location}</p>
                <p><i class="fas fa-location-arrow"></i> Destination: ${ride.destination}</p>
                <p><i class="fas fa-clock"></i> Departure: ${departureTime}</p>
                <p><i class="fas fa-user"></i> Offered by: ${ride.full_name || 'Anonymous'}</p>
                ${ride.average_rating ? 
                    `<p class="driver-rating"><i class="fas fa-star"></i> ${typeof ride.average_rating === 'number' ? ride.average_rating.toFixed(1) : ride.average_rating} (${ride.rating_count || 0} reviews)</p>` : ''}
                <p><i class="fas fa-calendar-alt"></i> Posted: ${formatTimestamp(ride.created_at)}</p>
                ${ride.notes ? `<p><i class="fas fa-info-circle"></i> Notes: ${ride.notes}</p>` : ''}
            </div>
            <div class="ride-card-actions">
                <button class="request-ride-btn" onclick="requestRide(${ride.ride_id})" ${ride.seats === 0 ? 'disabled' : ''}>
                    <i class="fas fa-car"></i> ${ride.seats === 0 ? 'Full' : 'Request Ride'}
                </button>
            </div>
        `;
        
        // Add the new ride card at the beginning of the container
        if (ridesContainer.firstChild) {
            ridesContainer.insertBefore(rideCard, ridesContainer.firstChild);
        } else {
            ridesContainer.appendChild(rideCard);
        }
    }

    // Function to display all rides
    function displayRides(rides) {
        ridesContainer.innerHTML = ''; // Clear existing rides
        
        if (!rides || rides.length === 0) {
            ridesContainer.innerHTML = '<div class="no-rides">No rides available</div>';
            return;
        }
        
        rides.forEach(ride => addRideToList(ride));
    }

    // Function to refresh my offers display
    function refreshMyOffers() {
        myOffersContainer.innerHTML = ''; // Clear existing offers
        
        if (!myOffers || myOffers.length === 0) {
            myOffersContainer.innerHTML = '<div class="no-rides">You have not offered any rides yet</div>';
            return;
        }
        
        myOffers.forEach(offer => {
            const offerCard = document.createElement('div');
            offerCard.classList.add('ride-card');
            offerCard.dataset.rideId = offer.ride_id;

            const vehicleIcon = getVehicleIcon(offer.vehicle_type);
            const departureTime = formatDateTime(offer.departure_time);
            const statusBadge = offer.status ? 
                `<span class="ride-status status-${offer.status.toLowerCase()}">${capitalizeFirstLetter(offer.status)}</span>` : '';
            
            // Check if there are pending requests
            const hasPendingRequests = offer.request_count && offer.request_count > 0;
            const requestBadge = hasPendingRequests ? 
                `<span class="notification-badge">${offer.request_count}</span>` : '';
            
            offerCard.innerHTML = `
                <div class="ride-card-header">
                    <h4>${vehicleIcon} ${capitalizeFirstLetter(offer.vehicle_type)} ${statusBadge}</h4>
                    <span>${offer.fare} BDT</span>
                </div>
                <div class="ride-card-details">
                    <p><i class="fas fa-chair"></i> Seats Available: ${offer.seats}</p>
                    <p><i class="fas fa-map-marker-alt"></i> Pickup: ${offer.pickup_location}</p>
                    <p><i class="fas fa-location-arrow"></i> Destination: ${offer.destination}</p>
                    <p><i class="fas fa-clock"></i> Departure: ${departureTime}</p>
                    <p><i class="fas fa-calendar-alt"></i> Posted: ${formatTimestamp(offer.created_at)}</p>
                    ${offer.notes ? `<p><i class="fas fa-info-circle"></i> Notes: ${offer.notes}</p>` : ''}
                </div>
                <div class="ride-card-actions">
                    <button class="view-requests-btn ${hasPendingRequests ? 'highlight-btn' : ''}" 
                      onclick="viewRequests(${offer.ride_id})"
                      title="${hasPendingRequests ? `${offer.request_count} pending request(s)` : 'No pending requests'}">
                        <i class="fas fa-list"></i> View Requests ${requestBadge}
                    </button>
                </div>
            `;
            
            myOffersContainer.appendChild(offerCard);
        });
    }

    // Function to refresh my requests display
    function refreshMyRequests() {
        myRequestsContainer.innerHTML = ''; // Clear existing requests
        
        if (!myRequests || myRequests.length === 0) {
            myRequestsContainer.innerHTML = '<div class="no-rides">You have not requested any rides yet</div>';
            return;
        }
        
        myRequests.forEach(request => {
            const requestCard = document.createElement('div');
            requestCard.classList.add('ride-card');
            requestCard.dataset.requestId = request.request_id;

            const vehicleIcon = getVehicleIcon(request.vehicle_type);
            const departureTime = formatDateTime(request.departure_time);
            
            // Status badge
            let statusClass = '';
            switch(request.status) {
                case 'pending':
                    statusClass = 'status-pending';
                    break;
                case 'accepted':
                    statusClass = 'status-accepted';
                    break;
                case 'rejected':
                    statusClass = 'status-rejected';
                    break;
                default:
                    statusClass = '';
            }
            
            requestCard.innerHTML = `
                <div class="ride-card-header">
                    <h4>${vehicleIcon} ${capitalizeFirstLetter(request.vehicle_type)}</h4>
                    <span class="request-status ${statusClass}">${capitalizeFirstLetter(request.status)}</span>
                </div>
                <div class="ride-card-details">
                    <p><i class="fas fa-chair"></i> Seats Requested: ${request.seats}</p>
                    <p><i class="fas fa-map-marker-alt"></i> Pickup: ${request.pickup}</p>
                    <p><i class="fas fa-location-arrow"></i> Destination: ${request.destination}</p>
                    <p><i class="fas fa-clock"></i> Departure: ${departureTime}</p>
                    <p><i class="fas fa-user"></i> Driver: ${request.driver_name || 'Anonymous'}</p>
                    <p><i class="fas fa-calendar-alt"></i> Requested: ${formatTimestamp(request.created_at)}</p>
                    ${request.notes ? `<p><i class="fas fa-info-circle"></i> Notes: ${request.notes}</p>` : ''}
                </div>
                ${request.status === 'accepted' ? `
                    <div class="contact-info">
                        <h5>Contact Information</h5>
                        <p>${request.contact_info || 'No contact information provided'}</p>
                    </div>
                    <div class="ride-card-actions">
                        <button class="request-ride-btn" onclick="reviewDriver(${request.ride_id}, ${request.driver_id})">
                            <i class="fas fa-star"></i> Review Driver
                        </button>
                    </div>
                ` : ''}
            `;
            
            myRequestsContainer.appendChild(requestCard);
        });
    }

    // Function to handle ride requests
    window.requestRide = function(rideId) {
        // Find the ride
        const ride = rides.find(r => r.ride_id == rideId);
        if (!ride) {
            showNotification('Ride not found', 'error');
            return;
        }
        
        // Populate request modal with ride details
        document.getElementById('request-ride-id').value = rideId;
        document.getElementById('requestRideDetails').innerHTML = `
            <div class="ride-card-details">
                <p><i class="fas fa-car"></i> ${capitalizeFirstLetter(ride.vehicle_type)}</p>
                <p><i class="fas fa-map-marker-alt"></i> From: ${ride.pickup_location}</p>
                <p><i class="fas fa-location-arrow"></i> To: ${ride.destination}</p>
                <p><i class="fas fa-chair"></i> Available Seats: ${ride.seats}</p>
                <p><i class="fas fa-money-bill-wave"></i> Fare: ${ride.fare} BDT</p>
            </div>
        `;
        
        // Set default pickup location
        document.getElementById('request-pickup').value = ride.pickup_location;
        
        // Open modal
        requestRideModal.style.display = 'block';
    };

    // Function to view requests for a ride
    window.viewRequests = async function(rideId) {
        try {
            // Fetch requests for this ride
            const response = await fetch(`${BASE_URL}/api/ride_requests.php?ride_id=${rideId}`);
            const result = await response.json();
            
            if (result.status === 'success') {
                const requests = result.data;
                const requestsList = document.getElementById('rideRequestsList');
                
                if (!requests || requests.length === 0) {
                    requestsList.innerHTML = '<div class="no-requests">No requests for this ride yet</div>';
                } else {
                    requestsList.innerHTML = '';
                    
                    requests.forEach(request => {
                        const requestItem = document.createElement('div');
                        requestItem.classList.add('request-item');
                        requestItem.dataset.requestId = request.request_id;
                        
                        // Status badge
                        let statusClass = '';
                        switch(request.status) {
                            case 'pending':
                                statusClass = 'status-pending';
                                break;
                            case 'accepted':
                                statusClass = 'status-accepted';
                                break;
                            case 'rejected':
                                statusClass = 'status-rejected';
                                break;
                            default:
                                statusClass = '';
                        }
                        
                        requestItem.innerHTML = `
                            <div class="request-header">
                                <div class="request-user">
                                    <img src="${request.avatar_url || 'https://avatar.iran.liara.run/public'}" alt="User">
                                    ${request.full_name || 'Anonymous'}
                                </div>
                                <span class="request-status ${statusClass}">${capitalizeFirstLetter(request.status)}</span>
                            </div>
                            <div class="request-details">
                                <p><i class="fas fa-chair"></i> Seats: ${request.seats}</p>
                                <p><i class="fas fa-map-marker-alt"></i> Pickup: ${request.pickup}</p>
                                <p><i class="fas fa-calendar-alt"></i> Requested: ${formatTimestamp(request.created_at)}</p>
                                ${request.notes ? `<p><i class="fas fa-info-circle"></i> Notes: ${request.notes}</p>` : ''}
                            </div>
                            ${request.status === 'pending' ? `
                                <div class="request-actions">
                                    <button class="accept-btn" onclick="respondToRequest(${request.request_id}, 'accepted')">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    <button class="reject-btn" onclick="respondToRequest(${request.request_id}, 'rejected')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            ` : ''}
                        `;
                        
                        requestsList.appendChild(requestItem);
                    });
                }
                
                // Open modal
                viewRequestsModal.style.display = 'block';
            } else {
                throw new Error(result.message || 'Failed to fetch ride requests');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Failed to fetch ride requests', 'error');
        }
    };

    // Function to respond to a ride request
    window.respondToRequest = async function(requestId, status) {
        try {
            const response = await fetch(`${BASE_URL}/api/ride_requests.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    request_id: requestId,
                    status: status
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                // Update request item in the modal
                const requestItem = document.querySelector(`.request-item[data-request-id="${requestId}"]`);
                if (requestItem) {
                    const statusBadge = requestItem.querySelector('.request-status');
                    const actionsDiv = requestItem.querySelector('.request-actions');
                    
                    // Update status badge
                    statusBadge.className = `request-status status-${status}`;
                    statusBadge.textContent = capitalizeFirstLetter(status);
                    
                    // Remove action buttons
                    if (actionsDiv) {
                        actionsDiv.remove();
                    }
                }
                
                showNotification(`Request ${status === 'accepted' ? 'accepted' : 'rejected'} successfully`);
                
                // Trigger notification event
                const requestInfo = await getRequestInfo(requestId);
                if (requestInfo) {
                    const eventName = status === 'accepted' ? 'rideAccepted' : 'rideRejected';
                    const detail = {
                        requestId: requestId,
                        driverName: currentUser ? currentUser.full_name : 'Driver',
                        destination: requestInfo.destination,
                        reason: status === 'rejected' ? 'Unavailable at this time' : '',
                        user: requestInfo.user_name || requestInfo.full_name || 'Passenger'
                    };
                    
                    // Dispatch custom event for window event listeners in notifications.js
                    window.dispatchEvent(new CustomEvent(eventName, { detail: detail }));
                }
                
                // Refresh my offers to update request counts
                fetchMyOffers();
            } else {
                throw new Error(result.message || `Failed to ${status} request`);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification(`Failed to ${status} request: ${error.message}`, 'error');
        }
    };

    // Helper function to get request information
    async function getRequestInfo(requestId) {
        try {
            const response = await fetch(`${BASE_URL}/api/ride_requests.php?request_id=${requestId}`);
            const result = await response.json();
            
            if (result.status === 'success' && result.data.length > 0) {
                return result.data[0];
            }
            return null;
        } catch (error) {
            console.error('Error fetching request info:', error);
            return null;
        }
    }

    // Function to open review driver modal
    window.reviewDriver = function(rideId, driverId) {
        document.getElementById('review-ride-id').value = rideId;
        document.getElementById('review-driver-id').value = driverId;
        reviewDriverModal.style.display = 'block';
    };

    // Filter functionality
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            const vehicleTypes = Array.from(document.querySelectorAll('input[name="vehicle"]:checked'))
                .map(cb => cb.value);
            const minSeats = parseInt(document.getElementById('seats').value) || 1;
            const maxFare = parseFloat(document.getElementById('max-fare').value) || Infinity;
            const pickupLocation = document.getElementById('pickup-location').value.toLowerCase();
            const destinationLocation = document.getElementById('destination-location').value.toLowerCase();

            const filteredRides = rides.filter(ride => {
                return (vehicleTypes.length === 0 || vehicleTypes.includes(ride.vehicle_type)) &&
                    ride.seats >= minSeats &&
                    ride.fare <= maxFare &&
                    (!pickupLocation || ride.pickup_location.toLowerCase().includes(pickupLocation)) &&
                    (!destinationLocation || ride.destination.toLowerCase().includes(destinationLocation));
            });

            displayRides(filteredRides);
        });
    }

    // Search functionality
    if (searchButton && searchInput) {
        searchButton.addEventListener('click', () => {
            const searchTerm = searchInput.value.toLowerCase();
            if (!searchTerm) {
                displayRides(rides);
                return;
            }
            
            const filteredRides = rides.filter(ride => 
                ride.pickup_location.toLowerCase().includes(searchTerm) ||
                ride.destination.toLowerCase().includes(searchTerm) ||
                ride.vehicle_type.toLowerCase().includes(searchTerm)
            );
            
            displayRides(filteredRides);
        });
        
        // Search on enter key
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchButton.click();
            }
        });
    }

    // Utility Functions
    function getVehicleIcon(vehicleType) {
        const icons = {
            'car': '<i class="fas fa-car"></i>',
            'bike': '<i class="fas fa-motorcycle"></i>',
            'cng': '<i class="fas fa-taxi"></i>',
            'rickshaw': '<i class="fas fa-bicycle"></i>'
        };
        return icons[vehicleType] || '<i class="fas fa-car"></i>';
    }

    function capitalizeFirstLetter(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function formatTimestamp(timestamp) {
        if (!timestamp) return 'Unknown';
        const date = new Date(timestamp);
        return date.toLocaleString();
    }
    
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return 'Not specified';
        const date = new Date(dateTimeStr);
        return date.toLocaleString();
    }
    
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});