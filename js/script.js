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
        profileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            dropdownMenu.classList.remove('show');
        });

        // Handle dropdown actions
        dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                handleProfileAction(action);
            });
        });
    }
}

function handleProfileAction(action) {
    switch(action) {
        case 'profile':
            window.location.href = 'profile.html';
            break;
        case 'settings':
            window.location.href = 'settings.html';
            break;
        case 'logout':
            // Clear all user data
            localStorage.removeItem('user');
            localStorage.removeItem('userToken');
            localStorage.removeItem('savedPosts');
            // Redirect to login page
            window.location.href = 'login.html';
            break;
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
