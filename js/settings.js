// Base URL for API calls
const BASE_URL = window.location.origin;

document.addEventListener('DOMContentLoaded', () => {
    initializeSettingsForms();
});

function initializeSettingsForms() {
    // Profile Settings Form
    const profileForm = document.getElementById('profileSettingsForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await updateProfile();
        });
    }

    // Account Settings Form
    const accountForm = document.getElementById('accountSettingsForm');
    if (accountForm) {
        accountForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await updateAccount();
        });
    }

    // Privacy Settings Form
    const privacyForm = document.getElementById('privacySettingsForm');
    if (privacyForm) {
        privacyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await updatePrivacy();
        });
    }

    // Profile Picture URL Input
    const profilePicUrlInput = document.getElementById('profilePictureUrl');
    if (profilePicUrlInput) {
        profilePicUrlInput.addEventListener('change', handleProfilePictureUrlChange);
        profilePicUrlInput.addEventListener('input', handleProfilePictureUrlChange);
    }

    // Load existing user data
    loadUserData();
}

// Load user data from localStorage
function loadUserData() {
    const userData = JSON.parse(localStorage.getItem('user'));
    if (userData) {
        document.getElementById('fullName').value = userData.full_name || '';
        document.getElementById('bio').value = userData.bio || '';
        document.getElementById('email').value = userData.email || '';
        if (userData.avatar_url) {
            document.getElementById('profilePicturePreview').src = userData.avatar_url;
            document.getElementById('profilePictureUrl').value = userData.avatar_url;
        }
    }
}

function handleProfilePictureUrlChange(event) {
    const url = event.target.value.trim();
    if (url) {
        const preview = document.getElementById('profilePicturePreview');
        preview.src = url;
    }
}

async function updateProfile() {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData || !userData.user_id) {
            throw new Error('User not logged in');
        }

        const formData = {
            user_id: userData.user_id,
            full_name: document.getElementById('fullName').value,
            bio: document.getElementById('bio').value,
            avatar_url: document.getElementById('profilePictureUrl').value.trim() || 'https://avatar.iran.liara.run/public'
        };

        const response = await fetch(`${BASE_URL}/BRACULA/api/update_profile.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            // Update local storage with new data
            const updatedUserData = { ...userData, ...data.data };
            localStorage.setItem('user', JSON.stringify(updatedUserData));
            alert('Profile updated successfully!');
        } else {
            throw new Error(data.error || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert(error.message || 'An error occurred while updating profile');
    }
}

async function updateAccount() {
    try {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (!currentPassword) {
            alert('Current password is required');
            return;
        }

        if (newPassword && newPassword !== confirmPassword) {
            alert('New passwords do not match');
            return;
        }

        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData || !userData.user_id) {
            throw new Error('User not logged in');
        }

        const formData = {
            user_id: userData.user_id,
            email: document.getElementById('email').value,
            current_password: currentPassword,
            new_password: newPassword || undefined
        };

        const response = await fetch(`${BASE_URL}/BRACULA/api/update_account.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            // Update local storage with new email
            const updatedUserData = { ...userData, email: formData.email };
            localStorage.setItem('user', JSON.stringify(updatedUserData));
            
            // Clear password fields
            document.getElementById('currentPassword').value = '';
            document.getElementById('password').value = '';
            document.getElementById('confirmPassword').value = '';
            
            alert('Account settings updated successfully!');
        } else {
            throw new Error(data.error || 'Failed to update account settings');
        }
    } catch (error) {
        console.error('Error updating account:', error);
        alert(error.message);
    }
}

async function updatePrivacy() {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        const formData = {
            user_id: userData.user_id,
            profile_visibility: document.getElementById('visibility').value,
            activity_visibility: document.getElementById('activityVisibility').value
        };

        const response = await fetch('api/update_privacy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            alert('Privacy settings updated successfully!');
        } else {
            throw new Error(data.error || 'Failed to update privacy settings');
        }
    } catch (error) {
        console.error('Error updating privacy settings:', error);
        alert(error.message);
    }
}

async function deleteAccount() {
    const passwordInput = document.getElementById('deletePassword');

    // Get the user's password
    if (!passwordInput || !passwordInput.value) {
        alert('Please enter your password');
        return;
    }

    // Get the user data from localStorage
    const userData = JSON.parse(localStorage.getItem('user'));
    if (!userData || !userData.user_id) {
        alert('You must be logged in to delete your account');
        return;
    }

    try {
        const response = await fetch('api/delete_account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId: userData.user_id,
                password: passwordInput.value
            })
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Failed to delete account');
        }

        // Clear local storage
        localStorage.clear();
        
        // Redirect to login page
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'An error occurred while deleting your account. Please try again.');
    }
} 