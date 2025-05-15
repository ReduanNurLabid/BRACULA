// Global variables
let myAccommodations = [];
let myFavorites = [];
let inquiries = [];
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

document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const postModal = document.getElementById('postAccommodationModal');
    const editModal = document.getElementById('editAccommodationModal');
    const inquiryModal = document.getElementById('inquiryResponseModal');
    const confirmDialog = document.getElementById('confirmationDialog');
    
    const postBtns = document.querySelectorAll('.post-accommodation-btn');
    const closeBtns = document.querySelectorAll('.close-modal');
    
    const myListingsTab = document.getElementById('my-listings-tab');
    const inquiriesTab = document.getElementById('inquiries-tab');
    const favoritesTab = document.getElementById('favorites-tab');
    
    const myListingsSection = document.getElementById('my-listings-section');
    const inquiriesSection = document.getElementById('inquiries-section');
    const favoritesSection = document.getElementById('favorites-section');
    
    // Initialize tabs
    function initTabs() {
        myListingsTab.addEventListener('click', (e) => {
            e.preventDefault();
            showSection(myListingsSection, myListingsTab);
        });
        
        inquiriesTab.addEventListener('click', (e) => {
            e.preventDefault();
            showSection(inquiriesSection, inquiriesTab);
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
                ? listing.images[0] 
                : 'images/placeholder.jpg';
                
            const statusBadge = listing.status === 'active' 
                ? '<span class="status-badge active">Active</span>' 
                : '<span class="status-badge inactive">Unavailable</span>';
                
            card.innerHTML = `
                <div class="card-image">
                    <img src="${imageUrl}" alt="${escapeHtml(listing.title)}" onerror="this.src='images/placeholder.jpg'">
                    ${statusBadge}
                </div>
                <div class="card-content">
                    <h3>${escapeHtml(listing.title)}</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(listing.location)}</p>
                    <p class="room-type"><i class="fas fa-bed"></i> ${escapeHtml(listing.room_type)}</p>
                    <p class="price"><i class="fas fa-tag"></i> BDT ${formatPrice(listing.price)}/month</p>
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
    
    // Fetch inquiries
    async function fetchInquiries() {
        try {
            const loadingIndicator = document.getElementById('loading-inquiries');
            const noInquiriesMessage = document.getElementById('no-inquiries-message');
            const container = document.getElementById('inquiries-container');
            
            loadingIndicator.style.display = 'block';
            
            const response = await fetch(`${API_BASE_URL}/accommodation_inquiries.php?role=owner`, {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            loadingIndicator.style.display = 'none';
            
            if (result.status === 'success') {
                inquiries = result.data || [];
                
                // Update inquiries count badge
                const pendingCount = inquiries.filter(inq => inq.status === 'pending').length;
                const countBadge = document.getElementById('pending-inquiries-count');
                if (countBadge) {
                    countBadge.textContent = pendingCount;
                    countBadge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
                }
                
                if (inquiries.length === 0) {
                    noInquiriesMessage.style.display = 'block';
                    return;
                }
                
                displayInquiries(inquiries);
            } else {
                throw new Error(result.message || 'Failed to fetch inquiries');
            }
        } catch (error) {
            console.error('Error fetching inquiries:', error);
            showNotification('Error', 'Failed to fetch your inquiries', 'error');
        }
    }
    
    // Display inquiries
    function displayInquiries(inquiriesList) {
        const container = document.getElementById('inquiries-container');
        container.innerHTML = '';
        
        inquiriesList.forEach(inquiry => {
            const inquiryCard = document.createElement('div');
            inquiryCard.className = `inquiry-card ${inquiry.status}`;
            inquiryCard.dataset.id = inquiry.inquiry_id;
            
            let statusText = '';
            let statusClass = '';
            
            switch(inquiry.status) {
                case 'pending':
                    statusText = 'Pending';
                    statusClass = 'pending';
                    break;
                case 'responded':
                    statusText = 'Responded';
                    statusClass = 'responded';
                    break;
                case 'closed':
                    statusText = 'Closed';
                    statusClass = 'closed';
                    break;
            }
            
            inquiryCard.innerHTML = `
                <div class="inquiry-header">
                    <h3>${escapeHtml(inquiry.accommodation_title)}</h3>
                    <span class="inquiry-status ${statusClass}">${statusText}</span>
                </div>
                <div class="inquiry-body">
                    <p><strong>From:</strong> ${escapeHtml(inquiry.sender_name)}</p>
                    <p class="inquiry-message">${escapeHtml(inquiry.message)}</p>
                    <p class="inquiry-date"><i class="fas fa-clock"></i> ${formatTimestamp(inquiry.created_at)}</p>
                </div>
                <div class="inquiry-actions">
                    <button class="respond-btn" onclick="respondToInquiry(${inquiry.inquiry_id})">
                        <i class="fas fa-reply"></i> Respond
                    </button>
                </div>
            `;
            
            container.appendChild(inquiryCard);
        });
    }
    
    // Initialize
    initTabs();
    initModals();
    fetchMyListings();
    fetchInquiries();
    
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

window.respondToInquiry = function(inquiryId) {
    const modal = document.getElementById('inquiryResponseModal');
    const inquiryDetails = document.getElementById('inquiry-details');
    const senderElement = document.getElementById('inquiry-sender');
    const accommodationElement = document.getElementById('inquiry-accommodation');
    const messageElement = document.getElementById('inquiry-message');
    const dateElement = document.getElementById('inquiry-date');
    const responseInput = document.getElementById('response-message');
    const sendBtn = document.getElementById('send-response-btn');
    const respondedBtn = document.getElementById('mark-as-responded-btn');
    const closedBtn = document.getElementById('mark-as-closed-btn');
    
    // Find the inquiry in the list
    const inquiry = inquiries.find(i => i.inquiry_id == inquiryId);
    
    if (inquiry) {
        senderElement.textContent = inquiry.sender_name;
        accommodationElement.textContent = inquiry.accommodation_title;
        messageElement.textContent = inquiry.message;
        dateElement.textContent = formatTimestamp(inquiry.created_at);
        responseInput.value = '';
        
        // Set up action buttons
        sendBtn.onclick = function() {
            // Here we would send an email or notification to the user
            // For now, just mark as responded
            updateInquiryStatus(inquiryId, 'responded');
        };
        
        respondedBtn.onclick = function() {
            updateInquiryStatus(inquiryId, 'responded');
        };
        
        closedBtn.onclick = function() {
            updateInquiryStatus(inquiryId, 'closed');
        };
        
        modal.style.display = 'block';
    } else {
        showNotification('Error', 'Inquiry not found', 'error');
    }
};

function updateInquiryStatus(inquiryId, status) {
    fetch(`${API_BASE_URL}/accommodation_inquiries.php`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            inquiry_id: inquiryId,
            status: status
        }),
        credentials: 'include'
    })
    .then(response => response.json())
    .then(result => {
        const modal = document.getElementById('inquiryResponseModal');
        modal.style.display = 'none';
        
        if (result.status === 'success') {
            showNotification('Success', `Inquiry marked as ${status}`, 'success');
            
            // Update the inquiry in DOM
            const card = document.querySelector(`.inquiry-card[data-id="${inquiryId}"]`);
            if (card) {
                card.className = `inquiry-card ${status}`;
                const statusElement = card.querySelector('.inquiry-status');
                if (statusElement) {
                    statusElement.className = `inquiry-status ${status}`;
                    statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                }
            }
            
            // Update in-memory list
            const inquiryIndex = inquiries.findIndex(i => i.inquiry_id == inquiryId);
            if (inquiryIndex !== -1) {
                inquiries[inquiryIndex].status = status;
            }
            
            // Update badge count
            const pendingCount = inquiries.filter(inq => inq.status === 'pending').length;
            const countBadge = document.getElementById('pending-inquiries-count');
            if (countBadge) {
                countBadge.textContent = pendingCount;
                countBadge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
            }
        } else {
            showNotification('Error', result.message || 'Failed to update inquiry status', 'error');
        }
    })
    .catch(error => {
        const modal = document.getElementById('inquiryResponseModal');
        modal.style.display = 'none';
        console.error('Error updating inquiry status:', error);
        showNotification('Error', 'Failed to update inquiry status', 'error');
    });
} 