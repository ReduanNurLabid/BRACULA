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
    // Check if viewing own profile or another user's profile
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('user_id');
    
    if (userId) {
        // Viewing another user's profile
        loadUserProfileById(userId);
        loadUserActivitiesById(userId);
        // Hide edit profile picture button for other users
        const editButton = document.querySelector('.edit-profile-picture');
        if (editButton) {
            editButton.style.display = 'none';
        }
    } else {
        // Viewing own profile
        loadUserProfile();
        loadUserActivities();
        initializeProfilePictureUpdate();
    }
});

async function loadUserProfileById(userId) {
    try {
        // Check if userId is valid
        if (!userId || userId === 'undefined') {
            throw new Error('Invalid user ID');
        }
        
        const response = await fetch(`${BASE_URL}/BRACULA/api/users/get_user_profile.php?user_id=${userId}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            displayUserProfile(data.user);
        } else {
            throw new Error(data.message || 'Failed to load user profile');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showNotification('Failed to load profile: ' + error.message, 'error');
        
        // Redirect to own profile if there's an error
        setTimeout(() => {
            window.location.href = 'profile.html';
        }, 2000);
    }
}

async function loadUserActivitiesById(userId) {
    try {
        const response = await fetch(`${BASE_URL}/BRACULA/api/users/get_user_activities.php?user_id=${userId}`);
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

function displayUserProfile(userData) {
    // Update profile information
    const fullName = userData.full_name && userData.full_name.trim() !== '' ? userData.full_name : DEFAULT_USER_PROFILE.full_name;
    const bio = userData.bio && userData.bio.trim() !== '' ? userData.bio : DEFAULT_USER_PROFILE.bio;
    const department = userData.department && userData.department.trim() !== '' ? userData.department : DEFAULT_USER_PROFILE.department;
    const avatarUrl = userData.avatar_url && userData.avatar_url.trim() !== '' ? userData.avatar_url : DEFAULT_USER_PROFILE.avatar_url;
    const interests = userData.interests && userData.interests.trim() !== '' ? userData.interests : DEFAULT_USER_PROFILE.interests;
    document.querySelector('.profile-info h1').textContent = userData.full_name;
    document.querySelector('.profile-info .bio').textContent = userData.bio || 'No bio added yet';
    document.querySelector('.profile-info .department').textContent = userData.department;

    // Update profile pictures
    const profilePics = document.querySelectorAll('.profile-picture img, .profile-section img');
profilePics.forEach(img => {
    if (img) {
            const profilePics = document.querySelectorAll('.profile-picture img, .profile-section img');
    profilePics.forEach(img => img.src = avatarUrl);
    }
})

    // Display interests
    displayInterests(interests);
    
    // For demo purposes, we'll set a random status
    setRandomStatus();
}

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
            // Use the stored avatar_url if available, otherwise use default
            img.src = userData.avatar_url || 'https://avatar.iran.liara.run/public';
        });

        // Display interests
        displayInterests(userData.interests);
        
        // For demo purposes, we'll set a random status
        setRandomStatus();
    } catch (error) {
        console.error('Error loading profile:', error);
        showNotification('Failed to load profile', 'error');
    }
}

function displayInterests(interests) {
    const interestsContainer = document.querySelector('.interests-tags');
    
    if (!interests) {
        interestsContainer.innerHTML = '<p>No interests added yet</p>';
        return;
    }
    
    // Split interests by commas and generate tags
    const interestsList = interests.split(',').map(interest => interest.trim());
    if (interestsList.length === 0 || (interestsList.length === 1 && interestsList[0] === '')) {
        interestsContainer.innerHTML = '<p>No interests added yet</p>';
        return;
    }
    
    const tagsHTML = interestsList.map(interest => 
        `<span class="interest-tag">${interest}</span>`
    ).join('');
    
    interestsContainer.innerHTML = tagsHTML;
}

function setRandomStatus() {
    // This would be replaced with actual user status in a real app
    const statuses = ['online', 'away', 'offline'];
    const statusTexts = ['Online', 'Away', 'Offline'];
    
    const randomIndex = Math.floor(Math.random() * statuses.length);
    const status = statuses[randomIndex];
    const statusText = statusTexts[randomIndex];
    
    const statusDot = document.querySelector('.status-dot');
    const statusTextElement = document.querySelector('.status-text');
    
    // Remove all status classes
    statusDot.classList.remove('online', 'away', 'offline');
    // Add the selected status class
    statusDot.classList.add(status);
    // Update the status text
    statusTextElement.textContent = statusText;
}

function initializeProfilePictureUpdate() {
    const editButton = document.querySelector('.edit-profile-picture');
    const modal = document.getElementById('profile-picture-modal');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('profile-picture-form');
    
    // Open modal when clicking the edit button
    editButton.addEventListener('click', () => {
        openModal(modal);
    });
    
    // Close modal when clicking the X
    closeBtn.addEventListener('click', () => {
        closeModal(modal);
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal(modal);
        }
    });
    
    // Handle form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const avatarUrl = document.getElementById('avatar-url').value.trim();
        if (!avatarUrl) {
            showNotification('Please enter a valid image URL', 'error');
            return;
        }
        
        await updateProfilePicture(avatarUrl);
        closeModal(modal);
    });
}

async function updateProfilePicture(avatarUrl) {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData || !userData.user_id) {
            throw new Error('User not logged in');
        }
        
        const response = await fetch(`${BASE_URL}/BRACULA/api/users/update_profile.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userData.user_id,
                full_name: userData.full_name,
                bio: userData.bio,
                avatar_url: avatarUrl,
                interests: userData.interests
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update local storage with new avatar URL
            userData.avatar_url = avatarUrl;
            localStorage.setItem('user', JSON.stringify(userData));
            
            // Update profile pictures on page
            const profilePics = document.querySelectorAll('.profile-picture img, .profile-section img');
            profilePics.forEach(img => {
                img.src = avatarUrl;
            });
            
            showNotification('Profile picture updated successfully', 'success');
        } else {
            throw new Error(data.error || 'Failed to update profile picture');
        }
    } catch (error) {
        console.error('Error updating profile picture:', error);
        showNotification('Failed to update profile picture', 'error');
    }
}

async function loadUserActivities() {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData || !userData.user_id) {
            throw new Error('User not found');
        }

        const response = await fetch(`${BASE_URL}/BRACULA/api/users/get_user_activities.php?user_id=${userData.user_id}`);
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
        let url = '';
        let clickable = false;

        switch (activity.activity_type) {
            case 'post':
                icon = '<i class="fas fa-pen"></i>';
                activityDescription = `Created a new post${activity.post_caption ? `: "${activity.post_caption}"` : ''}`;
                if (activity.post_id) {
                    url = `feed.html?post_id=${activity.post_id}`;
                    clickable = true;
                }
                break;
            case 'comment':
                icon = '<i class="fas fa-comment"></i>';
                activityDescription = 'Commented on a post';
                if (activity.post_id) {
                    url = `feed.html?post_id=${activity.post_id}&comment_id=${activity.content_id}`;
                    clickable = true;
                }
                break;
            case 'like':
                icon = '<i class="fas fa-heart"></i>';
                activityDescription = 'Liked a post';
                if (activity.post_id) {
                    url = `feed.html?post_id=${activity.post_id}`;
                    clickable = true;
                }
                break;
            case 'share':
                icon = '<i class="fas fa-share"></i>';
                activityDescription = 'Shared a post';
                if (activity.post_id) {
                    url = `feed.html?post_id=${activity.post_id}`;
                    clickable = true;
                }
                break;
            default:
                icon = '<i class="fas fa-circle"></i>';
                activityDescription = 'Unknown activity';
        }

        const cursorClass = clickable ? 'cursor-pointer' : '';
        const onClickAttr = clickable ? `onclick="window.location.href='${url}'"` : '';

        return `
            <div class="activity-item ${cursorClass}" ${onClickAttr}>
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