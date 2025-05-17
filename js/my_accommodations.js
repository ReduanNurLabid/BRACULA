// Global variables
let myAccommodations = [];
let myFavorites = [];
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

document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const postModal = document.getElementById('postAccommodationModal');
    const editModal = document.getElementById('editAccommodationModal');
    const confirmDialog = document.getElementById('confirmationDialog');
    
    const postBtns = document.querySelectorAll('.post-accommodation-btn');
    const closeBtns = document.querySelectorAll('.close-modal');
    
    const myListingsTab = document.getElementById('my-listings-tab');
    const favoritesTab = document.getElementById('favorites-tab');
    
    const myListingsSection = document.getElementById('my-listings-section');
    const favoritesSection = document.getElementById('favorites-section');
    
    // Initialize tabs
    function initTabs() {
        myListingsTab.addEventListener('click', (e) => {
            e.preventDefault();
            showSection(myListingsSection, myListingsTab);
        });
        
        favoritesTab.addEventListener('click', (e) => {
            e.preventDefault();
            showSection(favoritesSection, favoritesTab);
        });
    }
    
    function showSection(section, tab) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.sidebar-nav-item').forEach(t => t.classList.remove('active'));
        
        // Show selected section
        section.style.display = 'block';
        tab.classList.add('active');
    }
    
    // Initialize modals
    function initModals() {
        // Post accommodation buttons
        postBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                postModal.style.display = 'block';
            });
        });
        
        // Close buttons
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) modal.style.display = 'none';
            });
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });
    }
    
    // Fetch my listings
    async function fetchMyListings() {
        try {
            const loadingIndicator = document.getElementById('loading-my-listings');
            const noListingsMessage = document.getElementById('no-listings-message');
            const container = document.getElementById('my-listings-container');
            
            loadingIndicator.style.display = 'block';
            
            const response = await fetch(`${API_BASE_URL}/accommodations.php?owner=me`, {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            loadingIndicator.style.display = 'none';
            
            if (result.status === 'success') {
                myAccommodations = result.data || [];
                
                if (myAccommodations.length === 0) {
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
    
    // Display my listings
    function displayMyListings(listings) {
        const container = document.getElementById('my-listings-container');
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
                    <img src="${imageUrl}" alt="${escapeHtml(listing.title)}" onerror="this.src='../public/images/placeholder.jpg'">
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
    
    // Initialize
    initTabs();
    initModals();
    fetchMyListings();
    
    // Add form submission handlers
    const postForm = document.getElementById('postAccommodationForm');
    if (postForm) {
        postForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            // This is handled by the accommodation.js file
        });
    }
    
    const editForm = document.getElementById('editAccommodationForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            const accommodationId = formData.get('accommodation_id');
            
            try {
                const response = await fetch(`${API_BASE_URL}/accommodations.php?id=${accommodationId}`, {
                    method: 'PUT',
                    body: JSON.stringify(Object.fromEntries(formData)),
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
                    editModal.style.display = 'none';
                    fetchMyListings(); // Refresh listings
                } else {
                    throw new Error(result.message || 'Failed to update accommodation');
                }
            } catch (error) {
                console.error('Error updating accommodation:', error);
                showNotification('Error', error.message, 'error');
            }
        });
    }
});

// Global functions for button actions
window.editListing = function(accommodationId) {
    const editModal = document.getElementById('editAccommodationModal');
    const form = document.getElementById('editAccommodationForm');
    
    // Find the accommodation in the list
    fetch(`${API_BASE_URL}/accommodations.php?id=${accommodationId}`, {
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

window.deleteListing = function(accommodationId) {
    const confirmDialog = document.getElementById('confirmationDialog');
    const confirmTitle = document.getElementById('confirmation-title');
    const confirmMessage = document.getElementById('confirmation-message');
    const confirmYes = document.getElementById('confirm-yes');
    const confirmNo = document.getElementById('confirm-no');
    
    confirmTitle.textContent = 'Delete Accommodation';
    confirmMessage.textContent = 'Are you sure you want to delete this accommodation? This action cannot be undone.';
    
    confirmYes.onclick = function() {
        fetch(`${API_BASE_URL}/accommodations.php?id=${accommodationId}`, {
            method: 'DELETE',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            confirmDialog.style.display = 'none';
            
            if (result.status === 'success') {
                showNotification('Success', 'Accommodation deleted successfully', 'success');
                // Remove from DOM
                const card = document.querySelector(`.accommodation-card[data-id="${accommodationId}"]`);
                if (card) card.remove();
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