// Base URL for API calls
const BASE_URL = window.location.origin + '/bracula';

// Notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize state
const STATE = {
    events: [],
    filters: {
        types: [],
        date: null
    },
    currentUser: JSON.parse(localStorage.getItem('user')) || null
};

// Modal functionality
const createEventBtn = document.querySelector('.create-event-btn');
const createEventModal = document.getElementById('createEventModal');
const closeModalBtn = document.querySelector('.close');

// Show modal when create event button is clicked
createEventBtn.addEventListener('click', () => {
    if (!STATE.currentUser) {
        showNotification('Please login to create an event', 'error');
        return;
    }
    createEventModal.style.display = 'block';
});

// Close modal when clicking the close button
closeModalBtn.addEventListener('click', () => {
    createEventModal.style.display = 'none';
});

// Close modal when clicking outside the modal
window.addEventListener('click', (event) => {
    if (event.target === createEventModal) {
        createEventModal.style.display = 'none';
    }
});

// Function to format date for display
function formatEventDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Function to create event card HTML
function createEventCard(event) {
    return `
        <div class="event-card" data-type="${event.event_type}" data-event-id="${event.event_id}">
            <img src="${event.cover_image}" alt="${event.name}" class="event-image" onerror="this.src='assets/images/default-event.jpg'">
            <h3>${event.name}</h3>
            <div class="event-meta">
                <span><i class="fas fa-calendar"></i> ${formatEventDate(event.formatted_date || event.event_date)}</span>
                <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
            </div>
            <p><i class="fas fa-user"></i> Organized by: ${event.organizer_name}</p>
            <div class="event-type">${event.event_type}</div>
            <div class="event-stats">
                <span class="registration-count">
                    <i class="fas fa-users"></i> ${event.registration_count} registered
                </span>
            </div>
            <button class="register-btn" onclick="handleRegister(${event.event_id})">
                <i class="fas fa-user-plus"></i>Register
            </button>
        </div>
    `;
}

// Function to load events from the server
async function loadEvents() {
    try {
        // Show loading state
        const eventsContainer = document.getElementById('eventsContainer');
        eventsContainer.innerHTML = '<p class="loading">Loading events...</p>';

        // Build query parameters
        const params = new URLSearchParams();
        if (STATE.filters.types.length > 0) {
            params.append('type', STATE.filters.types.join(','));
        }
        if (STATE.filters.date) {
            params.append('date', STATE.filters.date);
        }

        const response = await fetch(`${BASE_URL}/api/events/events.php?${params}`);
        const result = await response.json();

        if (result.status === 'success') {
            console.log('Loaded events:', result.data); // Debug log
            STATE.events = result.data;
            refreshEvents();
        } else {
            throw new Error(result.message || 'Failed to load events');
        }
    } catch (error) {
        console.error('Error loading events:', error);
        showNotification(error.message, 'error');
        const eventsContainer = document.getElementById('eventsContainer');
        eventsContainer.innerHTML = '<p class="error">Failed to load events. Please try again later.</p>';
    }
}

// Function to refresh events display
function refreshEvents() {
    const eventsContainer = document.getElementById('eventsContainer');
    if (!STATE.events || STATE.events.length === 0) {
        eventsContainer.innerHTML = '<p class="no-events">No events found</p>';
        return;
    }
    
    // Sort events by date
    const sortedEvents = [...STATE.events].sort((a, b) => {
        const dateA = new Date(a.formatted_date || a.event_date);
        const dateB = new Date(b.formatted_date || b.event_date);
        return dateA - dateB;
    });
    
    eventsContainer.innerHTML = sortedEvents.map(event => createEventCard(event)).join('');
    
    // Check registration status for all events
    checkRegistrationStatus();
}

// Handle form submission
const createEventForm = document.getElementById('createEventForm');
createEventForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    try {
        if (!STATE.currentUser) {
            throw new Error('Please login to create an event');
        }

        // Get form values
        const formData = new FormData(createEventForm);
        const eventData = {
            name: formData.get('eventName'),
            cover_image: formData.get('coverImage'),
            type: formData.get('eventType'),
            date: formData.get('eventDate'),
            location: formData.get('eventLocation'),
            organizer_id: STATE.currentUser.user_id
        };
        
        console.log('Submitting event data:', eventData); // Debug log
        
        // Send to server
        const response = await fetch(`${BASE_URL}/api/events/events.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(eventData)
        });

        const result = await response.json();
        console.log('Server response:', result); // Debug log

        if (result.status === 'success') {
            await loadEvents();
            
            createEventModal.style.display = 'none';
            createEventForm.reset();
            
            showNotification('Event created successfully', 'success');
        } else {
            throw new Error(result.message || 'Failed to create event');
        }
    } catch (error) {
        console.error('Error creating event:', error);
        showNotification(error.message, 'error');
    }
});

document.querySelectorAll('input[name="event-type"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        STATE.filters.types = Array.from(document.querySelectorAll('input[name="event-type"]:checked'))
            .map(cb => cb.value);
        loadEvents();
    });
});

// Handle date filter changes
document.getElementById('date').addEventListener('change', (e) => {
    STATE.filters.date = e.target.value;
    loadEvents();
});

// Handle event registration
async function handleRegister(eventId) {
    try {
        if (!STATE.currentUser) {
            showNotification('Please login to register for events', 'error');
            return;
        }

        const response = await fetch(`${BASE_URL}/api/events/event_registration.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                event_id: eventId,
                user_id: STATE.currentUser.user_id
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Update button text based on registration status
            const button = document.querySelector(`.event-card[data-event-id="${eventId}"] .register-btn`);
            if (button) {
                if (result.registration_status === 'registered') {
                    button.innerHTML = '<i class="fas fa-check"></i>Registered';
                    button.classList.add('registered');
                } else {
                    button.innerHTML = '<i class="fas fa-user-plus"></i>Register';
                    button.classList.remove('registered');
                }
            }
            showNotification(result.message, 'success');
        } else {
            throw new Error(result.message || 'Failed to register for event');
        }
    } catch (error) {
        console.error('Error registering for event:', error);
        showNotification(error.message, 'error');
    }
}

// Function to check registration status for all events
async function checkRegistrationStatus() {
    if (!STATE.currentUser || !STATE.events.length) return;

    try {
        for (const event of STATE.events) {
            const response = await fetch(`${BASE_URL}/api/events/event_registration.php?event_id=${event.event_id}&user_id=${STATE.currentUser.user_id}`);
            const result = await response.json();

            if (result.status === 'success' && result.is_registered) {
                const button = document.querySelector(`.event-card[data-event-id="${event.event_id}"] .register-btn`);
                if (button) {
                    button.innerHTML = '<i class="fas fa-check"></i>Registered';
                    button.classList.add('registered');
                }
            }
        }
    } catch (error) {
        console.error('Error checking registration status:', error);
    }
}

// Initialize events
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
});
