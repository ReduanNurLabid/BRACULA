document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const offerRideModal = document.getElementById('offerRideModal');
    const offerRideBtn = document.querySelector('.offer-ride-btn');
    const closeModalBtn = document.querySelector('.close-modal');
    const offerRideForm = document.getElementById('offerRideForm');
    const ridesContainer = document.getElementById('ridesContainer');
    const applyFiltersBtn = document.querySelector('.apply-filters');

    // Store rides data
    let rides = [];

    // Base API URL
    const API_BASE_URL = 'http://localhost:8081/bracula/api';

    // Fetch rides on page load
    fetchRides();

    // Modal functionality
    offerRideBtn.addEventListener('click', () => {
        offerRideModal.style.display = 'block';
    });
    
    closeModalBtn.addEventListener('click', () => {
        offerRideModal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === offerRideModal) {
            offerRideModal.style.display = 'none';
        }
    });

    // Handle Ride Offer Submission
    offerRideForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        try {
            // Get form data
            const formData = new FormData(offerRideForm);
            const rideData = {
                vehicle: formData.get('vehicle'),
                seats: parseInt(formData.get('seats')),
                fare: parseFloat(formData.get('fare')),
                pickup: formData.get('pickup'),
                destination: formData.get('destination')
            };

            // Validate data before sending
            if (!rideData.vehicle || !rideData.seats || !rideData.fare || !rideData.pickup || !rideData.destination) {
                throw new Error('Please fill in all fields');
            }

            if (rideData.seats < 1) {
                throw new Error('Number of seats must be at least 1');
            }

            if (rideData.fare < 0) {
                throw new Error('Fare cannot be negative');
            }

            const response = await fetch(`${API_BASE_URL}/rides.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(rideData)
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Add ride to display
                addRideToList(result.data);
                
                // Close modal and reset form
                offerRideModal.style.display = 'none';
                offerRideForm.reset();
            } else {
                throw new Error(result.message || 'Failed to create ride offer');
            }
        } catch (error) {
            console.error('Error:', error);
            alert(error.message || 'Failed to create ride offer. Please try again.');
        }
    });

    // Function to fetch rides from the server
    async function fetchRides() {
        try {
            const response = await fetch(`${API_BASE_URL}/rides.php`);
            const result = await response.json();

            if (result.status === 'success') {
                rides = result.data;
                displayRides(rides);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to fetch rides');
        }
    }

    // Function to Add Ride to Listings
    function addRideToList(ride) {
        const rideCard = document.createElement('div');
        rideCard.classList.add('ride-card');
        rideCard.dataset.rideId = ride.ride_id;

        const vehicleIcon = getVehicleIcon(ride.vehicle_type);
        
        rideCard.innerHTML = `
            <div class="ride-card-header">
                <h4>${vehicleIcon} ${capitalizeFirstLetter(ride.vehicle_type)}</h4>
                <span>${ride.fare} BDT</span>
            </div>
            <div class="ride-card-details">
                <p><i class="fas fa-chair"></i> Seats Available: ${ride.seats}</p>
                <p><i class="fas fa-map-marker-alt"></i> Pickup: ${ride.pickup_location}</p>
                <p><i class="fas fa-location-arrow"></i> Destination: ${ride.destination}</p>
                <p><i class="fas fa-user"></i> Offered by: ${ride.full_name}</p>
                <p><i class="fas fa-clock"></i> Posted: ${formatTimestamp(ride.created_at)}</p>
            </div>
            <button class="request-ride-btn" onclick="requestRide(${ride.ride_id})" ${ride.seats === 0 ? 'disabled' : ''}>
                <i class="fas fa-car"></i> ${ride.seats === 0 ? 'Full' : 'Request Ride'}
            </button>
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
        rides.forEach(ride => addRideToList(ride));
    }

    // Function to handle ride requests
    window.requestRide = async function(rideId) {
        try {
            const response = await fetch(`${API_BASE_URL}/rides.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ride_id: rideId })
            });

            const result = await response.json();

            if (result.status === 'success') {
                const rideCard = document.querySelector(`[data-ride-id="${rideId}"]`);
                if (rideCard) {
                    const seatsElement = rideCard.querySelector('.ride-card-details p:first-child');
                    seatsElement.innerHTML = `<i class="fas fa-chair"></i> Seats Available: ${result.seats}`;
                    
                    const requestBtn = rideCard.querySelector('.request-ride-btn');
                    if (result.seats === 0) {
                        requestBtn.disabled = true;
                        requestBtn.innerHTML = '<i class="fas fa-ban"></i> Full';
                    }
                }
                alert('Ride requested successfully!');
            } else {
                alert(result.message || 'Failed to request ride');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to request ride');
        }
    };

    // Filter functionality
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            const vehicleTypes = Array.from(document.querySelectorAll('input[name="vehicle"]:checked'))
                .map(cb => cb.value);
            const minSeats = parseInt(document.getElementById('seats').value) || 1;
            const maxFare = parseFloat(document.getElementById('max-fare').value) || Infinity;

            const filteredRides = rides.filter(ride => {
                return (vehicleTypes.length === 0 || vehicleTypes.includes(ride.vehicle_type)) &&
                    ride.seats >= minSeats &&
                    ride.fare <= maxFare;
            });

            displayRides(filteredRides);
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
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString();
    }
});