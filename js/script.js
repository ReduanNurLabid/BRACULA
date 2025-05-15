// Common functionality across pages
document.addEventListener('DOMContentLoaded', () => {
    initializeProfileDropdown();
    updateNavigation();
});

// Profile Dropdown Functionality
function initializeProfileDropdown() {
    const profileToggle = document.querySelector('.profile-dropdown-toggle');
    const dropdownMenu = document.querySelector('.profile-dropdown-menu');

    if (profileToggle && dropdownMenu) {
        // Use event delegation to ensure all click events are properly captured
        profileToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('Profile toggle clicked');
            
            // Toggle show class on the dropdown menu
            dropdownMenu.classList.toggle('show');
            
            // Focus the first menu item for accessibility
            if (dropdownMenu.classList.contains('show')) {
                const firstItem = dropdownMenu.querySelector('.dropdown-item');
                if (firstItem) firstItem.focus();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            // Check if the click was outside the profile dropdown
            if (!profileToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                console.log('Clicked outside dropdown - closing');
                dropdownMenu.classList.remove('show');
            }
        });

        // Handle dropdown actions with improved event handling
        dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const action = e.currentTarget.dataset.action;
                console.log('Dropdown item clicked:', action);
                
                // Close the dropdown
                dropdownMenu.classList.remove('show');
                
                // Handle the action
                handleProfileAction(action);
            });
        });
    } else {
        console.warn('Profile dropdown elements not found');
    }
}

function handleProfileAction(action) {
    console.log('Profile action triggered:', action);
    
    switch(action) {
        case 'profile':
            console.log('Navigating to profile page');
            window.location.href = 'profile.html';
            break;
        case 'saved-posts':
            console.log('Navigating to saved-posts page');
            window.location.href = 'saved-posts.html';
            break;
        case 'settings':
            console.log('Navigating to settings page');
            window.location.href = 'settings.html';
            break;
        case 'logout':
            console.log('Logging out user');
            // Clear all user data
            localStorage.removeItem('user');
            localStorage.removeItem('userToken');
            localStorage.removeItem('savedPosts');
            // Redirect to login page
            window.location.href = 'login.html';
            break;
        default:
            console.log('Unknown action:', action);
    }
}

// Update navigation based on authentication status
function updateNavigation() {
    const userData = localStorage.getItem('user');
    const navLinks = document.querySelector('.nav-links');
    const profileSection = document.querySelector('.profile-section');

    if (userData) {
        // User is logged in
        const user = JSON.parse(userData);
        if (profileSection) {
            const profileImage = profileSection.querySelector('.profile-dropdown-toggle');
            if (profileImage) {
                // Use the stored avatar_url if available, otherwise use default
                profileImage.src = user.avatar_url || 'https://avatar.iran.liara.run/public';
            }
        }
    } else {
        // User is not logged in
        if (window.location.pathname.includes('feed.html') || 
            window.location.pathname.includes('profile.html') || 
            window.location.pathname.includes('settings.html')) {
            window.location.href = 'login.html';
        }
    }
}

// Common Modal Functions
function openModal(modal) {
    if (modal) modal.style.display = 'block';
}

function closeModal(modal) {
    if (modal) modal.style.display = 'none';
}

// Common utility functions
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));

    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return `${Math.floor(diffInMinutes / 1440)}d ago`;
}
