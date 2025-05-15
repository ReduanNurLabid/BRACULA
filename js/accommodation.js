// Global variables
let accommodations = [];
let API_BASE_URL = 'http://localhost:8081/BRACULA/api';

// Utility Functions
function showNotification(title, message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <h4>${escapeHtml(title)}</h4>
        <p>${escapeHtml(message)}</p>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatPrice(price) {
    return parseFloat(price).toLocaleString('en-BD');
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}

// Main functionality
document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const modal = document.getElementById('postAccommodationModal');
    const postBtn = document.querySelector('.post-accommodation-btn');
    const closeBtn = document.querySelector('.close-modal');
    const accommodationForm = document.getElementById('postAccommodationForm');
    const accommodationsContainer = document.getElementById('accommodationsContainer');
    const filterBtn = document.querySelector('.apply-filters');

    // Store accommodations data
    accommodations = [];

    // Debug check for elements
    console.log('Elements found:', {
        modal: !!modal,
        postBtn: !!postBtn,
        closeBtn: !!closeBtn,
        form: !!accommodationForm,
        container: !!accommodationsContainer
    });

    // Fetch accommodations on page load
    fetchAccommodations();

    // Modal functionality
    postBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        console.log('Modal opened');
    });

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        console.log('Modal closed');
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
            console.log('Modal closed by outside click');
        }
    });

    // Handle accommodation submission
    accommodationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        console.log('Form submission started');
        
        const submitButton = event.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const formData = new FormData(accommodationForm);
            
            // Log form data for debugging
            console.log('Form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
            }
            
            // Validation
            const requiredFields = ['title', 'roomType', 'price', 'location', 'description', 'contactInfo'];
            for (const field of requiredFields) {
                if (!formData.get(field)) {
                    throw new Error(`${field} is required`);
                }
            }

            const price = parseFloat(formData.get('price'));
            if (isNaN(price) || price <= 0) {
                throw new Error('Please enter a valid price');
            }

            const imageFiles = formData.getAll('images[]');
            if (imageFiles.length === 0 || !imageFiles[0].name) {
                throw new Error('Please upload at least one image');
            }

            // Show loading state
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
            
            // Send data to server with better error handling
            try {
                // Log the full URL for debugging
                console.log('Sending request to:', `${API_BASE_URL}/accommodations.php`);
                
                const response = await fetch(`${API_BASE_URL}/accommodations.php`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server returned error:', response.status, errorText);
                    throw new Error(`Server error: ${response.status} ${response.statusText}. Details: ${errorText}`);
                }

                const result = await response.json();
                console.log('Server response:', result);

                if (result.status === 'success' && result.data) {
                    // Add new accommodation to the list
                    addAccommodationToList(result.data);
                    
                    // Close modal and reset form
                    modal.style.display = 'none';
                    accommodationForm.reset();
                    if (imagePreview) {
                        imagePreview.innerHTML = '';
                    }
                    
                    showNotification('Success', 'Accommodation posted successfully!', 'success');
                } else {
                    throw new Error(result.message || 'Failed to post accommodation');
                }
            } catch (fetchError) {
                console.error('Network or parsing error:', fetchError);
                
                // More detailed error message based on the type of error
                if (fetchError.message.includes('Failed to fetch')) {
                    throw new Error('Connection error: Could not connect to the server. Please check if XAMPP is running and Apache is started.');
                } else {
                    throw fetchError;
                }
            }
        } catch (error) {
            console.error('Error during form submission:', error);
            showNotification('Error', error.message || 'Failed to post accommodation', 'error');
        } finally {
            // Reset submit button
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-check"></i> Post Accommodation';
        }
    });

    // Function to fetch accommodations from server
    async function fetchAccommodations() {
        try {
            console.log('Fetching accommodations from:', `${API_BASE_URL}/accommodations.php`);
            
            const response = await fetch(`${API_BASE_URL}/accommodations.php`, {
                credentials: 'include'
            });

            if (!response.ok) {
                // Try to get the error details from the response
                try {
                    const errorText = await response.text();
                    console.error('Server error response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}, details: ${errorText}`);
                } catch (parseError) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }

            const result = await response.json();
            console.log('Accommodations data received:', result);

            if (result.status === 'success') {
                accommodations = result.data || [];
                displayAccommodations(accommodations);
            } else {
                throw new Error(result.message || 'Failed to fetch accommodations');
            }
        } catch (error) {
            console.error('Error fetching accommodations:', error);
            showNotification('Error', 'Failed to fetch accommodations', 'error');
            accommodationsContainer.innerHTML = `
                <div class="error-message">
                    Failed to load accommodations. Please try again later.<br>
                    Error: ${error.message}
                </div>`;
        }
    }

    // Function to add accommodation to listings
    function addAccommodationToList(accommodation) {
        const card = document.createElement('div');
        card.className = 'accommodation-card';
        card.dataset.accommodationId = accommodation.accommodation_id;

        const imageUrl = accommodation.images && accommodation.images.length > 0 
            ? accommodation.images[0] 
            : 'images/placeholder.jpg';

        card.innerHTML = `
            <div class="card-image">
                <img src="${imageUrl}" alt="${escapeHtml(accommodation.title)}" onerror="this.src='images/placeholder.jpg'">
            </div>
            <div class="card-content">
                <h3>${escapeHtml(accommodation.title)}</h3>
                <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(accommodation.location)}</p>
                <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(accommodation.room_type)}</p>
                <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(accommodation.price)}/month</p>
                <p class="owner"><i class="fas fa-user"></i> Posted by: ${escapeHtml(accommodation.full_name || 'Anonymous')}</p>
                <p class="time"><i class="fas fa-clock"></i> Posted: ${formatTimestamp(accommodation.created_at)}</p>
                <div class="card-buttons">
                    <button class="view-details-btn" onclick="viewDetails(${parseInt(accommodation.accommodation_id, 10)})">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                    <button class="contact-owner-btn" onclick="contactOwner(${parseInt(accommodation.accommodation_id, 10)})">
                        <i class="fas fa-envelope"></i> Contact Owner
                    </button>
                </div>
            </div>
        `;

        // Add the new card at the beginning of the container
        if (accommodationsContainer.firstChild) {
            accommodationsContainer.insertBefore(card, accommodationsContainer.firstChild);
        } else {
            accommodationsContainer.appendChild(card);
        }
    }

    // Function to display all accommodations
    function displayAccommodations(accommodationsToShow) {
        accommodationsContainer.innerHTML = '';
        if (!accommodationsToShow || accommodationsToShow.length === 0) {
            accommodationsContainer.innerHTML = '<div class="no-results">No accommodations found</div>';
            return;
        }
        accommodationsToShow.forEach(acc => addAccommodationToList(acc));
    }

    // Filter functionality
    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            const location = document.getElementById('location').value.toLowerCase();
            const minPrice = parseFloat(document.getElementById('min-price').value) || 0;
            const maxPrice = parseFloat(document.getElementById('max-price').value) || Infinity;
            const selectedRoomTypes = Array.from(document.querySelectorAll('input[name="roomType"]:checked'))
                .map(input => input.value);

            const filteredAccommodations = accommodations.filter(acc => {
                const matchesLocation = acc.location.toLowerCase().includes(location);
                const matchesPrice = acc.price >= minPrice && (!maxPrice || acc.price <= maxPrice);
                const matchesRoomType = selectedRoomTypes.length === 0 || selectedRoomTypes.includes(acc.room_type);

                return matchesLocation && matchesPrice && matchesRoomType;
            });

            displayAccommodations(filteredAccommodations);
        });
    }
});

// Global functions
window.viewDetails = function(accommodationId) {
    console.log('View details clicked for accommodation ID:', accommodationId);
    console.log('Available accommodations:', accommodations);
    
    // Convert the ID to the same type to ensure accurate comparison
    accommodationId = parseInt(accommodationId, 10);
    
    // Find the accommodation with matching ID
    const accommodation = accommodations.find(acc => parseInt(acc.accommodation_id, 10) === accommodationId);
    
    if (!accommodation) {
        console.error('Accommodation not found with ID:', accommodationId);
        showNotification('Error', 'Could not find accommodation details', 'error');
        return;
    }
    
    console.log('Found accommodation:', accommodation);

    // Create and display the modal
    const detailsModal = document.createElement('div');
    detailsModal.className = 'modal';
    detailsModal.style.display = 'block';
    
    // Check if there are images to display
    let imagesHtml = '';
    if (accommodation.images && accommodation.images.length > 0) {
        imagesHtml = `
            <div class="details-images">
                ${accommodation.images.map(img => 
                    `<img src="${img}" alt="${escapeHtml(accommodation.title)}" onerror="this.src='images/placeholder.jpg'">`
                ).join('')}
            </div>
        `;
    }
    
    detailsModal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>${escapeHtml(accommodation.title)}</h2>
            ${imagesHtml}
            <div class="details-content">
                <p><strong>Location:</strong> ${escapeHtml(accommodation.location)}</p>
                <p><strong>Room Type:</strong> ${escapeHtml(accommodation.room_type)}</p>
                <p><strong>Price:</strong> BDT ${formatPrice(accommodation.price)}/month</p>
                <p><strong>Description:</strong> ${escapeHtml(accommodation.description)}</p>
                <p><strong>Contact:</strong> ${escapeHtml(accommodation.contact_info)}</p>
                <p><strong>Posted By:</strong> ${escapeHtml(accommodation.full_name || 'Anonymous')}</p>
                <p><strong>Posted On:</strong> ${formatTimestamp(accommodation.created_at)}</p>
            </div>
            <div class="details-actions">
                <button class="contact-owner-btn" onclick="contactOwner(${accommodation.accommodation_id})">
                    <i class="fas fa-envelope"></i> Contact Owner
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(detailsModal);

    const closeBtn = detailsModal.querySelector('.close-modal');
    closeBtn.onclick = () => detailsModal.remove();
    window.onclick = (event) => {
        if (event.target === detailsModal) {
            detailsModal.remove();
        }
    };
};

window.contactOwner = function(accommodationId) {
    console.log('Contact owner clicked for accommodation ID:', accommodationId);
    
    // Convert the ID to the same type to ensure accurate comparison
    accommodationId = parseInt(accommodationId, 10);
    
    // Find the accommodation with matching ID
    const accommodation = accommodations.find(acc => parseInt(acc.accommodation_id, 10) === accommodationId);
    
    if (!accommodation) {
        console.error('Accommodation not found with ID:', accommodationId);
        showNotification('Error', 'Could not find accommodation contact information', 'error');
        return;
    }
    
    showNotification('Contact Info', accommodation.contact_info, 'info');
}; 