// Global variables
let accommodations = [];
let myAccommodations = [];
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

// Main functionality
document.addEventListener('DOMContentLoaded', () => {
    console.log('Unified Accommodation.js loaded');
    
    // Initialize page tabs
    initializePageTabs();
    
    // Initialize modals
    initializeModals();
    
    // Initialize accommodation page elements
    initializeAccommodationPage();
    
    // Fetch accommodations for both browse and my listings
    fetchAccommodations();
    fetchMyListings();
    fetchFavorites();
    
    // Initialize form submissions
    initializeFormSubmissions();
});

// Initialize page tabs navigation
function initializePageTabs() {
    const pageTabs = document.querySelectorAll('.page-tab');
    const pageSections = document.querySelectorAll('.page-section');
    const pageTabLinks = document.querySelectorAll('.page-tab-link');
    
    // Handle main tab navigation
    pageTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs and sections
            pageTabs.forEach(t => t.classList.remove('active'));
            pageSections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding section
            tab.classList.add('active');
            const targetSection = document.getElementById(tab.dataset.section);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
    
    // Handle tab links inside sections
    if (pageTabLinks.length > 0) {
        pageTabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const targetSectionId = link.dataset.section;
                // Find and click the corresponding tab
                const correspondingTab = document.querySelector(`.page-tab[data-section="${targetSectionId}"]`);
                if (correspondingTab) {
                    correspondingTab.click();
                }
            });
        });
    }
}

// Initialize modals
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    const openButtons = document.querySelectorAll('.post-accommodation-btn');
    const closeButtons = document.querySelectorAll('.close-modal');
    
    // Open modals
    openButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const postModal = document.getElementById('postAccommodationModal');
            if (postModal) {
                postModal.style.display = 'block';
            }
        });
    });
    
    // Close modals with X button
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', (event) => {
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
}

// Function to initialize accommodation page elements
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
            localStorage.setItem('accommodationViewMode', 'grid');
        });
        
        listViewBtn.addEventListener('click', () => {
            listViewBtn.classList.add('active');
            gridViewBtn.classList.remove('active');
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
    const filterBtn = document.querySelector('.apply-filters');
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

// Initialize form submissions
function initializeFormSubmissions() {
    // Post accommodation form
    const postForm = document.getElementById('postAccommodationForm');
    if (postForm) {
        postForm.addEventListener('submit', handlePostFormSubmission);
    }
    
    // Edit accommodation form
    const editForm = document.getElementById('editAccommodationForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditFormSubmission);
    }
    
    // Setup image preview
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.match('image.*')) {
                        return;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '80px';
                        img.style.height = '80px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '4px';
                        imagePreview.appendChild(img);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        });
    }
}

// Function to fetch accommodations for browse section
async function fetchAccommodations() {
    try {
        const loadingIndicator = document.getElementById('loading-indicator');
        const accommodationsContainer = document.getElementById('accommodationsContainer');
        
        if (!loadingIndicator || !accommodationsContainer) {
            console.error('Required DOM elements not found');
            return;
        }
        
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
        const accommodationsContainer = document.getElementById('accommodationsContainer');
        if (accommodationsContainer) {
            accommodationsContainer.innerHTML = `
                <div class="error-message">
                    Failed to load accommodations. Please try again later.<br>
                    Error: ${error.message}
                </div>`;
        }
    }
}

// Function to fetch my listings
async function fetchMyListings() {
    try {
        const loadingIndicator = document.getElementById('loading-my-listings');
        const noListingsMessage = document.getElementById('no-listings-message');
        const container = document.getElementById('my-listings-container');
        
        if (!loadingIndicator || !container) {
            console.error('Required DOM elements not found for my listings');
            return;
        }
        
        loadingIndicator.style.display = 'block';
        
        const response = await fetch(`${API_BASE_URL}/accommodations/accommodations.php?owner=me`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        loadingIndicator.style.display = 'none';
        
        if (result.status === 'success') {
            myAccommodations = result.data || [];
            
            if (myAccommodations.length === 0 && noListingsMessage) {
                noListingsMessage.style.display = 'block';
                return;
            }
            
            displayMyListings(myAccommodations);
        } else {
            throw new Error(result.message || 'Failed to fetch accommodations');
        }
    } catch (error) {
        console.error('Error fetching my listings:', error);
        showNotification('Error', 'Failed to fetch your accommodations', 'error');
    }
}

// Function to fetch favorites
async function fetchFavorites() {
    try {
        const loadingIndicator = document.getElementById('loading-favorites');
        const noFavoritesMessage = document.getElementById('no-favorites-message');
        const container = document.getElementById('favorites-container');
        
        if (!loadingIndicator || !container) {
            console.error('Required DOM elements not found for favorites');
            return;
        }
        
        loadingIndicator.style.display = 'block';
        
        // Get favorites from localStorage
        const favoriteIds = JSON.parse(localStorage.getItem('favoriteAccommodations') || '[]');
        
        if (favoriteIds.length === 0) {
            loadingIndicator.style.display = 'none';
            if (noFavoritesMessage) {
                noFavoritesMessage.style.display = 'block';
            }
            return;
        }
        
        // If we have favorite IDs, fetch all accommodations and filter
        const response = await fetch(`${API_BASE_URL}/accommodations/accommodations.php`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        loadingIndicator.style.display = 'none';
        
        if (result.status === 'success') {
            const allAccommodations = result.data || [];
            const favoriteAccommodations = allAccommodations.filter(acc => 
                favoriteIds.includes(acc.accommodation_id.toString())
            );
            
            if (favoriteAccommodations.length === 0 && noFavoritesMessage) {
                noFavoritesMessage.style.display = 'block';
                return;
            }
            
            displayFavorites(favoriteAccommodations);
        } else {
            throw new Error(result.message || 'Failed to fetch accommodations');
        }
    } catch (error) {
        console.error('Error fetching favorites:', error);
        showNotification('Error', 'Failed to fetch your favorite accommodations', 'error');
    }
}

// Function to display accommodations in the browse section
function displayAccommodations(accommodationsToShow) {
    const container = document.getElementById('accommodationsContainer');
    
    if (!container) {
        console.error('Accommodations container not found');
        return;
    }
    
    // Clear loading indicator
    container.innerHTML = '';
    
    if (accommodationsToShow.length === 0) {
        container.innerHTML = `
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
                <img src="${imageUrl}" alt="${escapeHtml(accommodation.title)}">
                <button class="favorite-btn" data-id="${accommodation.accommodation_id}">
                    <i class="far fa-heart"></i>
                </button>
            </div>
            <div class="card-content">
                <h3>${escapeHtml(accommodation.title)}</h3>
                <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(accommodation.location)}</p>
                <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(accommodation.room_type)}</p>
                <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(accommodation.price)}/month</p>
                <p class="contact-info"><i class="fas fa-address-card"></i> Contact: ${escapeHtml(accommodation.contact_info)}</p>
                <p class="owner"><i class="fas fa-user"></i> Posted by: ${escapeHtml(accommodation.full_name || 'Anonymous')}</p>
                <p class="time"><i class="fas fa-clock"></i> Posted: ${formatTimestamp(accommodation.created_at)}</p>
                <div class="card-buttons">
                    <button class="view-details-btn full-width" data-id="${accommodation.accommodation_id}">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(card);
        
        // Add event listeners for the buttons
        const viewDetailsBtn = card.querySelector('.view-details-btn');
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', () => {
                viewDetails(accommodation.accommodation_id);
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

// Function to display my listings
function displayMyListings(listings) {
    const container = document.getElementById('my-listings-container');
    
    if (!container) {
        console.error('My listings container not found');
        return;
    }
    
    container.innerHTML = '';
    
    listings.forEach(listing => {
        const card = document.createElement('div');
        card.className = 'accommodation-card';
        card.dataset.id = listing.accommodation_id;
        
        const imageUrl = listing.images && listing.images.length > 0 
            ? fixImageUrl(listing.images[0]) 
            : '../public/images/placeholder.jpg';
            
        const statusBadge = listing.status === 'active' 
            ? '<span class="status-badge active">Active</span>' 
            : '<span class="status-badge inactive">Unavailable</span>';
            
        card.innerHTML = `
            <div class="card-image">
                <img src="${imageUrl}" alt="${escapeHtml(listing.title)}">
                ${statusBadge}
            </div>
            <div class="card-content">
                <h3>${escapeHtml(listing.title)}</h3>
                <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(listing.location)}</p>
                <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(listing.room_type)}</p>
                <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(listing.price)}/month</p>
                <p class="contact-info"><i class="fas fa-address-card"></i> Contact: ${escapeHtml(listing.contact_info)}</p>
                <p class="time"><i class="fas fa-clock"></i> Posted: ${formatTimestamp(listing.created_at)}</p>
                <div class="card-actions">
                    <button class="edit-listing-btn" onclick="editListing(${listing.accommodation_id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="delete-listing-btn" onclick="deleteListing(${listing.accommodation_id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(card);
    });
}

// Function to display favorites
function displayFavorites(favorites) {
    const container = document.getElementById('favorites-container');
    
    if (!container) {
        console.error('Favorites container not found');
        return;
    }
    
    container.innerHTML = '';
    
    favorites.forEach(accommodation => {
        const card = document.createElement('div');
        card.className = 'accommodation-card';
        card.dataset.accommodationId = accommodation.accommodation_id;

        const imageUrl = accommodation.images && accommodation.images.length > 0 
            ? fixImageUrl(accommodation.images[0]) 
            : '../public/images/placeholder.jpg';

        card.innerHTML = `
            <div class="card-image">
                <img src="${imageUrl}" alt="${escapeHtml(accommodation.title)}">
                <button class="favorite-btn active" data-id="${accommodation.accommodation_id}">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            <div class="card-content">
                <h3>${escapeHtml(accommodation.title)}</h3>
                <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(accommodation.location)}</p>
                <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(accommodation.room_type)}</p>
                <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(accommodation.price)}/month</p>
                <p class="contact-info"><i class="fas fa-address-card"></i> Contact: ${escapeHtml(accommodation.contact_info)}</p>
                <p class="owner"><i class="fas fa-user"></i> Posted by: ${escapeHtml(accommodation.full_name || 'Anonymous')}</p>
                <p class="time"><i class="fas fa-clock"></i> Posted: ${formatTimestamp(accommodation.created_at)}</p>
                <div class="card-buttons">
                    <button class="view-details-btn full-width" data-id="${accommodation.accommodation_id}">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(card);
        
        // Add event listeners for the buttons
        const viewDetailsBtn = card.querySelector('.view-details-btn');
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', () => {
                viewDetails(accommodation.accommodation_id);
            });
        }
        
        const favoriteBtn = card.querySelector('.favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleFavorite(accommodation.accommodation_id, favoriteBtn);
                
                // After removal, the card should be removed from favorites section
                setTimeout(() => {
                    if (!favoriteBtn.classList.contains('active')) {
                        card.remove();
                        
                        // If no more favorites, show the no favorites message
                        if (container.children.length === 0) {
                            const noFavoritesMessage = document.getElementById('no-favorites-message');
                            if (noFavoritesMessage) {
                                noFavoritesMessage.style.display = 'block';
                            }
                        }
                    }
                }, 300);
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

// Handle post form submission
async function handlePostFormSubmission(event) {
    event.preventDefault();
    console.log('Form submission started');
    
    const submitButton = event.target.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    
    try {
        const formData = new FormData(event.target);
        
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
                document.getElementById('postAccommodationModal').style.display = 'none';
                event.target.reset();
                document.getElementById('image-preview').innerHTML = '';
                
                showNotification('Success', 'Accommodation posted successfully!', 'success');
                
                // Also refresh my listings
                fetchMyListings();
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
}

// Handle edit form submission
async function handleEditFormSubmission(event) {
    event.preventDefault();
    
    const submitButton = event.target.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    try {
        const formData = new FormData(event.target);
        const accommodationId = formData.get('accommodation_id');
        
        // Convert FormData to JSON
        const formDataObj = {};
        formData.forEach((value, key) => {
            formDataObj[key] = value;
        });
        
        const response = await fetch(`${API_BASE_URL}/accommodations/accommodations.php?id=${accommodationId}`, {
            method: 'PUT',
            body: JSON.stringify(formDataObj),
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showNotification('Success', 'Accommodation updated successfully', 'success');
            document.getElementById('editAccommodationModal').style.display = 'none';
            
            // Refresh listings
            fetchMyListings();
            fetchAccommodations();
        } else {
            throw new Error(result.message || 'Failed to update accommodation');
        }
    } catch (error) {
        console.error('Error updating accommodation:', error);
        showNotification('Error', error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    }
}

// Function to add accommodation to listings
function addAccommodationToList(accommodation) {
    const container = document.getElementById('accommodationsContainer');
    
    if (!container) {
        console.error('Accommodations container not found');
        return;
    }
    
    const card = document.createElement('div');
    card.className = 'accommodation-card';
    card.dataset.accommodationId = accommodation.accommodation_id;

    const imageUrl = accommodation.images && accommodation.images.length > 0 
        ? fixImageUrl(accommodation.images[0]) 
        : '../public/images/placeholder.jpg';

    card.innerHTML = `
        <div class="card-image">
            <img src="${imageUrl}" alt="${escapeHtml(accommodation.title)}">
            <button class="favorite-btn" data-id="${accommodation.accommodation_id}">
                <i class="far fa-heart"></i>
            </button>
        </div>
        <div class="card-content">
            <h3>${escapeHtml(accommodation.title)}</h3>
            <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(accommodation.location)}</p>
            <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(accommodation.room_type)}</p>
            <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(accommodation.price)}/month</p>
            <p class="contact-info"><i class="fas fa-address-card"></i> Contact: ${escapeHtml(accommodation.contact_info)}</p>
            <p class="owner"><i class="fas fa-user"></i> Posted by: ${escapeHtml(accommodation.full_name || 'Anonymous')}</p>
            <p class="time"><i class="fas fa-clock"></i> Posted: ${formatTimestamp(accommodation.created_at)}</p>
            <div class="card-buttons">
                <button class="view-details-btn full-width" data-id="${accommodation.accommodation_id}">
                    <i class="fas fa-info-circle"></i> View Details
                </button>
            </div>
        </div>
    `;

    // Add the new card at the beginning of the container
    if (container.firstChild) {
        container.insertBefore(card, container.firstChild);
    } else {
        container.appendChild(card);
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
    
    // Add event listeners
    const viewDetailsBtn = card.querySelector('.view-details-btn');
    if (viewDetailsBtn) {
        viewDetailsBtn.addEventListener('click', () => {
            viewDetails(accommodation.accommodation_id);
        });
    }
    
    const favoriteBtn = card.querySelector('.favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleFavorite(accommodation.accommodation_id, favoriteBtn);
        });
    }
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
    if (document.getElementById('show-favorites-only')?.checked) {
        applyFilters();
    }
    
    // Refresh favorites section if it's visible
    const favoritesSection = document.getElementById('favorites-section');
    if (favoritesSection?.classList.contains('active')) {
        fetchFavorites();
    }
}

// View details function (global)
window.viewDetails = function(accommodationId) {
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
    
    // Find the accommodation
    const allAccommodations = [...accommodations, ...myAccommodations];
    const accommodation = allAccommodations.find(acc => acc.accommodation_id == accommodationId);
    
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
            imgContainer.innerHTML = `<img src="${fixImageUrl(imageUrl)}" alt="${accommodation.title}">`;
            detailsGallery.appendChild(imgContainer);
        });
    } else {
        detailsGallery.innerHTML = '<div class="gallery-image"><img src="../public/images/placeholder.jpg" alt="No Image Available"></div>';
    }
    
    // Show modal
    modal.style.display = 'block';
};

// Contact owner function (global)
window.contactOwner = function(accommodationId) {
    viewDetails(accommodationId);
};

// Edit listing function (global)
window.editListing = function(accommodationId) {
    const editModal = document.getElementById('editAccommodationModal');
    const form = document.getElementById('editAccommodationForm');
    
    if (!editModal || !form) {
        console.error('Edit form elements not found');
        return;
    }
    
    // Find the accommodation in the list
    fetch(`${API_BASE_URL}/accommodations/accommodations.php?id=${accommodationId}`, {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success' && result.data) {
            const accommodation = result.data;
            
            // Populate form fields
            form.querySelector('#edit-accommodation-id').value = accommodation.accommodation_id;
            form.querySelector('#edit-title').value = accommodation.title;
            form.querySelector('#edit-roomType').value = accommodation.room_type;
            form.querySelector('#edit-price').value = accommodation.price;
            form.querySelector('#edit-location').value = accommodation.location;
            form.querySelector('#edit-description').value = accommodation.description;
            form.querySelector('#edit-status').value = accommodation.status;
            form.querySelector('#edit-contactInfo').value = accommodation.contact_info;
            
            // Show modal
            editModal.style.display = 'block';
        } else {
            showNotification('Error', 'Failed to load accommodation details', 'error');
        }
    })
    .catch(error => {
        console.error('Error fetching accommodation details:', error);
        showNotification('Error', 'Failed to load accommodation details', 'error');
    });
};

// Delete listing function (global)
window.deleteListing = function(accommodationId) {
    const confirmDialog = document.getElementById('confirmationDialog');
    const confirmTitle = document.getElementById('confirmation-title');
    const confirmMessage = document.getElementById('confirmation-message');
    const confirmYes = document.getElementById('confirm-yes');
    const confirmNo = document.getElementById('confirm-no');
    
    if (!confirmDialog) {
        console.error('Confirmation dialog not found');
        return;
    }
    
    confirmTitle.textContent = 'Delete Accommodation';
    confirmMessage.textContent = 'Are you sure you want to delete this accommodation? This action cannot be undone.';
    
    confirmYes.onclick = function() {
        fetch(`${API_BASE_URL}/accommodations/accommodations.php?id=${accommodationId}`, {
            method: 'DELETE',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            confirmDialog.style.display = 'none';
            
            if (result.status === 'success') {
                showNotification('Success', 'Accommodation deleted successfully', 'success');
                
                // Remove from DOM in My Listings section
                const card = document.querySelector(`.accommodation-card[data-id="${accommodationId}"]`);
                if (card) card.remove();
                
                // Refresh both listings
                fetchMyListings();
                fetchAccommodations();
            } else {
                showNotification('Error', result.message || 'Failed to delete accommodation', 'error');
            }
        })
        .catch(error => {
            confirmDialog.style.display = 'none';
            console.error('Error deleting accommodation:', error);
            showNotification('Error', 'Failed to delete accommodation', 'error');
        });
    };
    
    confirmNo.onclick = function() {
        confirmDialog.style.display = 'none';
    };
    
    confirmDialog.style.display = 'block';
}; 