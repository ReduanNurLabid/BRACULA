/**
 * Authentication utilities for BRACULA
 */

// Logout function
async function logout() {
    try {
        // Call logout API
        const response = await fetch('api/auth/logout.php', {
            method: 'POST',
            credentials: 'include'
        });
        
        // Clear local storage
        localStorage.removeItem('user');
        
        // Redirect to login page
        window.location.href = 'login.html';
        
        return true;
    } catch (error) {
        console.error('Error during logout:', error);
        return false;
    }
}

// Check if user is logged in
function isLoggedIn() {
    // Check localStorage for user data
    return localStorage.getItem('user') !== null;
}

// Get current user data
function getCurrentUser() {
    const userData = localStorage.getItem('user');
    return userData ? JSON.parse(userData) : null;
}

// Add logout listener to logout buttons
document.addEventListener('DOMContentLoaded', () => {
    const logoutButtons = document.querySelectorAll('.logout-btn');
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    });
}); 