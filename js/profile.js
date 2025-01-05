// Base URL for API calls
const BASE_URL = window.location.origin;

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    loadUserProfile();
    loadUserActivities();
});

async function loadUserProfile() {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData) {
            throw new Error('User not found');
        }

        // Update profile information
        document.querySelector('.profile-info h1').textContent = userData.full_name;
        document.querySelector('.profile-info .bio').textContent = userData.bio || 'No bio added yet';
        document.querySelector('.profile-info .department').textContent = userData.department;
        
        // Update profile pictures (both in header and nav)
        const profilePics = document.querySelectorAll('.profile-picture img, .profile-section img');
        profilePics.forEach(img => {
            img.src = userData.avatar_url || 'https://avatar.iran.liara.run/public';
        });
    } catch (error) {
        console.error('Error loading profile:', error);
        showNotification('Failed to load profile', 'error');
    }
}

async function loadUserActivities() {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData || !userData.user_id) {
            throw new Error('User not found');
        }

        const response = await fetch(`${BASE_URL}/BRACULA/api/get_user_activities.php?user_id=${userData.user_id}`);
        const data = await response.json();

        if (data.status === 'success') {
            displayUserActivities(data.data);
        } else {
            throw new Error(data.message || 'Failed to load activities');
        }
    } catch (error) {
        console.error('Error loading activities:', error);
        document.querySelector('.activity-list').innerHTML = 
            '<p class="error-message">Failed to load activities</p>';
    }
}

function displayUserActivities(activities) {
    const activityList = document.querySelector('.activity-list');
    
    if (!activities || activities.length === 0) {
        activityList.innerHTML = '<p>No recent activities</p>';
        return;
    }

    const activityHTML = activities.map(activity => {
        const activityTime = formatTimestamp(activity.created_at);
        let activityDescription = '';
        let icon = '';

        switch (activity.activity_type) {
            case 'post':
                icon = '<i class="fas fa-pen"></i>';
                activityDescription = `Created a new post${activity.post_caption ? `: "${activity.post_caption}"` : ''}`;
                break;
            case 'comment':
                icon = '<i class="fas fa-comment"></i>';
                activityDescription = 'Commented on a post';
                break;
            case 'like':
                icon = '<i class="fas fa-heart"></i>';
                activityDescription = 'Liked a post';
                break;
            case 'share':
                icon = '<i class="fas fa-share"></i>';
                activityDescription = 'Shared a post';
                break;
            default:
                icon = '<i class="fas fa-circle"></i>';
                activityDescription = 'Unknown activity';
        }

        return `
            <div class="activity-item">
                <div class="activity-icon">${icon}</div>
                <div class="activity-content">
                    <div class="activity-description">${activityDescription}</div>
                    <div class="activity-time">${activityTime}</div>
                    ${activity.content ? `<div class="activity-details">${activity.content.substring(0, 100)}${activity.content.length > 100 ? '...' : ''}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');

    activityList.innerHTML = activityHTML;
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // Convert milliseconds to minutes, hours, days
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 60) {
        return minutes <= 1 ? 'Just now' : `${minutes} minutes ago`;
    } else if (hours < 24) {
        return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    } else if (days < 7) {
        return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
} 