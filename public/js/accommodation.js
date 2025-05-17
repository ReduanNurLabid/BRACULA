// Global variables
let accommodations = [];
let API_BASE_URL = '../api';

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
    console.log('Accommodation.js loaded');
    
    // DOM Elements
    const modal = document.getElementById('postAccommodationModal');
    const postBtn = document.querySelector('.post-accommodation-btn');
    const closeBtn = document.querySelector('.close-modal');
    const accommodationForm = document.getElementById('postAccommodationForm');
    const accommodationsContainer = document.getElementById('accommodationsContainer');
    const filterBtn = document.querySelector('.apply-filters');
    
    // Initialize notification dropdown
    initializeAccommodationPage();

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
    if (postBtn) {
        postBtn.addEventListener('click', () => {
            modal.style.display = 'block';
            console.log('Modal opened');
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            console.log('Modal closed');
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
            console.log('Modal closed by outside click');
        }
    });

    // Handle accommodation submission
    if (accommodationForm) {
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
                    console.log('Sending request to:', `${API_BASE_URL}/accommodations/accommodations.php`);
                    
                    const response = await fetch(`${API_BASE_URL}/accommodations/accommodations.php`, {
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
                        const imagePreview = document.getElementById('image-preview');
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
    }
    
    // Function to initialize special elements on the accommodation page
    function initializeAccommodationPage() {
        console.log('Initializing accommodation page elements');
        
        // Grid/List view toggle
        const gridViewBtn = document.querySelector('.grid-view');
        const listViewBtn = document.querySelector('.list-view');
        const accommodationsGrid = document.getElementById('accommodationsContainer');
        
        if (gridViewBtn && listViewBtn && accommodationsGrid) {
            gridViewBtn.addEventListener('click', () => {
                gridViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                accommodationsGrid.classList.remove('list-view');
                accommodationsGrid.classList.add('grid-view');
                localStorage.setItem('accommodationViewMode', 'grid');
            });
            
            listViewBtn.addEventListener('click', () => {
                listViewBtn.classList.add('active');
                gridViewBtn.classList.remove('active');
                accommodationsGrid.classList.remove('grid-view');
                accommodationsGrid.classList.add('list-view');
                localStorage.setItem('accommodationViewMode', 'list');
            });
            
            // Apply saved view mode from localStorage
            const savedViewMode = localStorage.getItem('accommodationViewMode');
            if (savedViewMode === 'list') {
                listViewBtn.click();
            }
        }
        
        // Apply filters button
        if (filterBtn) {
            filterBtn.addEventListener('click', applyFilters);
        }
        
        // Reset filters button
        const resetFilterBtn = document.querySelector('.reset-filters');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', resetFilters);
        }
        
        // Favorites filter
        const favoritesToggle = document.getElementById('show-favorites-only');
        if (favoritesToggle) {
            favoritesToggle.addEventListener('change', () => {
                applyFilters();
            });
        }
    }

    // Function to fetch accommodations from server
    async function fetchAccommodations() {
        try {
            console.log('Fetching accommodations from:', `${API_BASE_URL}/accommodations/accommodations.php`);
            
            const response = await fetch(`${API_BASE_URL}/accommodations/accommodations.php`, {
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

    // Function to fix image URLs to work with our directory structure
    function fixImageUrl(imageUrl) {
        if (!imageUrl) return '../public/images/placeholder.jpg';
        
        // If the URL starts with /uploads/, prepend .. to make it relative to the current directory
        if (imageUrl.startsWith('/uploads/')) {
            return '..' + imageUrl;
        }
        
        // If it's already a full URL (https://) or a relative path, return as is
        return imageUrl;
    }

    // Function to add accommodation to listings
    function addAccommodationToList(accommodation) {
        const card = document.createElement('div');
        card.className = 'accommodation-card';
        card.dataset.accommodationId = accommodation.accommodation_id;

        const imageUrl = accommodation.images && accommodation.images.length > 0 
            ? fixImageUrl(accommodation.images[0]) 
            : '../public/images/placeholder.jpg';

        card.innerHTML = `
            <div class="card-image">
                <img src="${imageUrl}" alt="${escapeHtml(accommodation.title)}" onerror="this.src='../public/images/placeholder.jpg'">
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

        // Highlight the new card
        setTimeout(() => {
            card.classList.add('highlight');
            
            setTimeout(() => {
                card.classList.remove('highlight');
            }, 2000);
        }, 100);

        // Add new accommodation to the array
        accommodations.unshift(accommodation);
    }

    // Function to display accommodations in the UI
    function displayAccommodations(accommodationsToShow) {
        // Clear loading indicator
        accommodationsContainer.innerHTML = '';
        
        if (accommodationsToShow.length === 0) {
            accommodationsContainer.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No accommodations found</h3>
                    <p>Try adjusting your filters or posting a new accommodation.</p>
                </div>`;
            return;
        }
        
        // Create cards for each accommodation
        accommodationsToShow.forEach(accommodation => {
            const card = document.createElement('div');
            card.className = 'accommodation-card';
            card.dataset.accommodationId = accommodation.accommodation_id;

            const imageUrl = accommodation.images && accommodation.images.length > 0 
                ? fixImageUrl(accommodation.images[0]) 
                : '../public/images/placeholder.jpg';

            card.innerHTML = `
                <div class="card-image">
                    <img src="${imageUrl}" alt="${escapeHtml(accommodation.title)}" onerror="this.src='../public/images/placeholder.jpg'">
                    <button class="favorite-btn" data-id="${accommodation.accommodation_id}">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <div class="card-content">
                    <h3>${escapeHtml(accommodation.title)}</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(accommodation.location)}</p>
                    <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(accommodation.room_type)}</p>
                    <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(accommodation.price)}/month</p>
                    <p class="owner"><i class="fas fa-user"></i> Posted by: ${escapeHtml(accommodation.full_name || 'Anonymous')}</p>
                    <p class="time"><i class="fas fa-clock"></i> Posted: ${formatTimestamp(accommodation.created_at)}</p>
                    <div class="card-buttons">
                        <button class="view-details-btn" data-id="${accommodation.accommodation_id}">
                            <i class="fas fa-info-circle"></i> View Details
                        </button>
                        <button class="contact-owner-btn" data-id="${accommodation.accommodation_id}">
                            <i class="fas fa-envelope"></i> Contact Owner
                        </button>
                    </div>
                </div>
            `;
            
            accommodationsContainer.appendChild(card);
            
            // Add event listeners for the buttons
            const viewDetailsBtn = card.querySelector('.view-details-btn');
            if (viewDetailsBtn) {
                viewDetailsBtn.addEventListener('click', () => {
                    viewDetails(accommodation.accommodation_id);
                });
            }
            
            const contactOwnerBtn = card.querySelector('.contact-owner-btn');
            if (contactOwnerBtn) {
                contactOwnerBtn.addEventListener('click', () => {
                    contactOwner(accommodation.accommodation_id);
                });
            }
            
            const favoriteBtn = card.querySelector('.favorite-btn');
            if (favoriteBtn) {
                // Check if this accommodation is in favorites
                const favorites = JSON.parse(localStorage.getItem('favoriteAccommodations') || '[]');
                if (favorites.includes(accommodation.accommodation_id.toString())) {
                    favoriteBtn.classList.add('active');
                    favoriteBtn.querySelector('i').classList.remove('far');
                    favoriteBtn.querySelector('i').classList.add('fas');
                }
                
                favoriteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleFavorite(accommodation.accommodation_id, favoriteBtn);
                });
            }
        });
    }
    
    // Function to apply filters
    function applyFilters() {
        // Get filter values
        const roomTypeFilters = [...document.querySelectorAll('input[name="roomType"]:checked')].map(input => input.value);
        const locationFilter = document.getElementById('location').value.toLowerCase().trim();
        const minPrice = parseFloat(document.getElementById('min-price').value) || 0;
        const maxPrice = parseFloat(document.getElementById('max-price').value) || Infinity;
        const showFavoritesOnly = document.getElementById('show-favorites-only').checked;
        
        // Get favorites from localStorage
        const favorites = JSON.parse(localStorage.getItem('favoriteAccommodations') || '[]');
        
        // Filter accommodations
        const filteredAccommodations = accommodations.filter(accommodation => {
            // Room type filter
            if (roomTypeFilters.length > 0 && !roomTypeFilters.includes(accommodation.room_type)) {
                return false;
            }
            
            // Location filter
            if (locationFilter && !accommodation.location.toLowerCase().includes(locationFilter)) {
                return false;
            }
            
            // Price filter
            const price = parseFloat(accommodation.price);
            if (price < minPrice || price > maxPrice) {
                return false;
            }
            
            // Favorites filter
            if (showFavoritesOnly && !favorites.includes(accommodation.accommodation_id.toString())) {
                return false;
            }
            
            return true;
        });
        
        // Display filtered results
        displayAccommodations(filteredAccommodations);
        
        // Show filter results info
        const searchResultsInfo = document.querySelector('.search-results-info');
        const resultsCount = document.getElementById('results-count');
        
        if (searchResultsInfo && resultsCount) {
            searchResultsInfo.style.display = 'block';
            resultsCount.textContent = filteredAccommodations.length;
        }
    }
    
    // Function to reset filters
    function resetFilters() {
        // Reset checkbox filters
        document.querySelectorAll('input[name="roomType"]:checked').forEach(input => {
            input.checked = false;
        });
        
        // Reset text and number inputs
        document.getElementById('location').value = '';
        document.getElementById('min-price').value = '';
        document.getElementById('max-price').value = '';
        document.getElementById('show-favorites-only').checked = false;
        
        // Hide filter results info
        const searchResultsInfo = document.querySelector('.search-results-info');
        if (searchResultsInfo) {
            searchResultsInfo.style.display = 'none';
        }
        
        // Show all accommodations
        displayAccommodations(accommodations);
    }
    
    // Function to toggle favorite status
    function toggleFavorite(accommodationId, buttonElement) {
        // Get current favorites from localStorage
        let favorites = JSON.parse(localStorage.getItem('favoriteAccommodations') || '[]');
        const accommodationIdStr = accommodationId.toString();
        
        if (favorites.includes(accommodationIdStr)) {
            // Remove from favorites
            favorites = favorites.filter(id => id !== accommodationIdStr);
            buttonElement.classList.remove('active');
            buttonElement.querySelector('i').classList.remove('fas');
            buttonElement.querySelector('i').classList.add('far');
            showNotification('Success', 'Removed from favorites', 'success');
        } else {
            // Add to favorites
            favorites.push(accommodationIdStr);
            buttonElement.classList.add('active');
            buttonElement.querySelector('i').classList.remove('far');
            buttonElement.querySelector('i').classList.add('fas');
            showNotification('Success', 'Added to favorites', 'success');
        }
        
        // Save updated favorites to localStorage
        localStorage.setItem('favoriteAccommodations', JSON.stringify(favorites));
        
        // If "Show Favorites Only" is checked, reapply filters
        if (document.getElementById('show-favorites-only').checked) {
            applyFilters();
        }
    }
});

// View details function (called from the button)
function viewDetails(accommodationId) {
    console.log('View details for accommodation:', accommodationId);
    const modal = document.getElementById('accommodationDetailsModal');
    const detailsTitle = document.getElementById('details-title');
    const detailsLocation = document.getElementById('details-location');
    const detailsRoomType = document.getElementById('details-room-type');
    const detailsPrice = document.getElementById('details-price');
    const detailsOwner = document.getElementById('details-owner');
    const detailsDate = document.getElementById('details-date');
    const detailsDescription = document.getElementById('details-description');
    const detailsContactInfo = document.getElementById('details-contact-info');
    const detailsGallery = document.getElementById('details-gallery');
    
    // Find the accommodation in the global array
    const accommodation = accommodations.find(acc => acc.accommodation_id == accommodationId);
    
    if (!accommodation) {
        showNotification('Error', 'Accommodation not found', 'error');
        return;
    }
    
    // Populate modal with accommodation details
    detailsTitle.textContent = accommodation.title;
    detailsLocation.textContent = accommodation.location;
    detailsRoomType.textContent = accommodation.room_type;
    detailsPrice.textContent = `BDT ${formatPrice(accommodation.price)}/month`;
    detailsOwner.textContent = accommodation.full_name || 'Anonymous';
    detailsDate.textContent = formatTimestamp(accommodation.created_at);
    detailsDescription.textContent = accommodation.description;
    detailsContactInfo.textContent = accommodation.contact_info;
    
    // Clear previous gallery images
    detailsGallery.innerHTML = '';
    
    // Add images to gallery
    if (accommodation.images && accommodation.images.length > 0) {
        accommodation.images.forEach(imageUrl => {
            const imgContainer = document.createElement('div');
            imgContainer.className = 'gallery-image';
            imgContainer.innerHTML = `<img src="${fixImageUrl(imageUrl)}" alt="${accommodation.title}" onerror="this.src='../public/images/placeholder.jpg'">`;
            detailsGallery.appendChild(imgContainer);
        });
    } else {
        detailsGallery.innerHTML = '<div class="gallery-image"><img src="../public/images/placeholder.jpg" alt="No Image Available"></div>';
    }
    
    // Show modal
    modal.style.display = 'block';
}

// Contact owner function (called from the button)
function contactOwner(accommodationId) {
    console.log('Contact owner for accommodation:', accommodationId);
    // Call viewDetails to open the modal and scroll to the contact form
    viewDetails(accommodationId);
    
    // Scroll to the contact information section
    setTimeout(() => {
        document.querySelector('.contact-form-section').scrollIntoView({ behavior: 'smooth' });
    }, 300);
}