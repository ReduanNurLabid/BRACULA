// Common functionality across pages
document.addEventListener('DOMContentLoaded', () => {
    initializeProfileDropdown();
    initializeNotificationDropdown();
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

// Notification Dropdown Functionality
function initializeNotificationDropdown() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationToggle && notificationDropdown) {
        // Toggle notification dropdown
        notificationToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('Notification toggle clicked');
            
            // Toggle show class on the dropdown
            notificationDropdown.classList.toggle('show');
            
            // If opened, fetch notifications (commented for now until API is ready)
            if (notificationDropdown.classList.contains('show')) {
                // fetchNotifications();
                loadSampleNotifications();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            // Check if the click was outside the notification area
            if (!notificationToggle.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
        
        // Handle notification tab switching
        const notificationTabs = notificationDropdown.querySelectorAll('.notification-tab');
        if (notificationTabs.length > 0) {
            notificationTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    notificationTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    tab.classList.add('active');
                    
                    // Filter notifications based on tab
                    const tabType = tab.dataset.tab;
                    filterNotifications(tabType);
                });
            });
        }
        
        // Handle "Mark all as read" button
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                markAllNotificationsAsRead();
            });
        }
        
        // Handle "View all" notifications button
        const viewAllBtn = document.getElementById('viewAllNotificationsBtn');
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', () => {
                // Redirect to all notifications page (if implemented)
                console.log('View all notifications clicked');
                // window.location.href = 'notifications.html';
                
                // For now, just close the dropdown
                notificationDropdown.classList.remove('show');
            });
        }
    } else {
        console.warn('Notification dropdown elements not found');
    }
}

// Load sample notifications for demonstration
function loadSampleNotifications() {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    // Clear existing notifications
    notificationList.innerHTML = '';
    
    // Sample notifications data
    const sampleNotifications = [
        {
            id: 1,
            type: 'comment',
            read: false,
            sender: 'Tanvir Ahmed',
            message: 'commented on your post',
            time: '2 hours ago',
            avatar: 'https://avatar.iran.liara.run/public'
        },
        {
            id: 2,
            type: 'like',
            read: true,
            sender: 'Raisa Rahman',
            message: 'liked your accommodation listing',
            time: '1 day ago',
            avatar: 'https://avatar.iran.liara.run/public/21'
        },
        {
            id: 3,
            type: 'inquiry',
            read: false,
            sender: 'Fahmid Khan',
            message: 'sent an inquiry about your accommodation',
            time: '3 days ago',
            avatar: 'https://avatar.iran.liara.run/public/44'
        }
    ];
    
    // Render notifications
    sampleNotifications.forEach(notification => {
        const notificationItem = document.createElement('div');
        notificationItem.className = `notification-item ${notification.read ? '' : 'unread'}`;
        notificationItem.dataset.id = notification.id;
        
        notificationItem.innerHTML = `
            <div class="notification-avatar">
                <img src="${notification.avatar}" alt="${notification.sender}">
            </div>
            <div class="notification-content">
                <div class="notification-text">
                    <strong>${notification.sender}</strong> ${notification.message}
                </div>
                <div class="notification-time">${notification.time}</div>
            </div>
        `;
        
        // Add click event to mark as read
        notificationItem.addEventListener('click', () => {
            markNotificationAsRead(notification.id);
            // Handle navigation to the relevant item if needed
        });
        
        notificationList.appendChild(notificationItem);
    });
    
    // Update notification count
    updateNotificationCount(sampleNotifications.filter(n => !n.read).length);
}

// Update the notification badge count
function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notificationCount');
    if (notificationCount) {
        notificationCount.textContent = count;
        
        // Show/hide the badge based on count
        if (count > 0) {
            notificationCount.style.display = 'flex';
            // Add a subtle animation to the bell icon
            const notificationIcon = document.querySelector('.notification-icon');
            if (notificationIcon) {
                notificationIcon.classList.add('animate');
                setTimeout(() => {
                    notificationIcon.classList.remove('animate');
                }, 1000);
            }
        } else {
            notificationCount.style.display = 'none';
        }
    }
}

// Filter notifications based on tab (all/unread)
function filterNotifications(type) {
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        if (type === 'all') {
            item.style.display = 'flex';
        } else if (type === 'unread') {
            item.style.display = item.classList.contains('unread') ? 'flex' : 'none';
        }
    });
}

// Mark a single notification as read
function markNotificationAsRead(id) {
    const notification = document.querySelector(`.notification-item[data-id="${id}"]`);
    if (notification) {
        notification.classList.remove('unread');
        
        // Update the notification count
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        updateNotificationCount(unreadCount);
        
        // In a real app, you would also update this on the server
        console.log(`Marked notification ${id} as read`);
    }
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
    
    unreadNotifications.forEach(notification => {
        notification.classList.remove('unread');
    });
    
    // Update the notification count
    updateNotificationCount(0);
    
    // In a real app, you would also update this on the server
    console.log('Marked all notifications as read');
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
        case 'my-accommodations':
            console.log('Navigating to my-accommodations page');
            window.location.href = 'my_accommodations.html';
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
        
        // Update all profile images across the site
        const profileImages = document.querySelectorAll('.profile-dropdown-toggle, .profile-picture img, .profile-section img');
        if (profileImages && profileImages.length > 0) {
            profileImages.forEach(img => {
                // Use the stored avatar_url if available, otherwise use default
                img.src = user.avatar_url || 'https://avatar.iran.liara.run/public';
            });
        } else {
            console.warn('No profile images found to update');
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

// Add styles for profile dropdown menu
document.addEventListener('DOMContentLoaded', function() {
    // Add 'show' class to profile dropdown menu for proper styling
    const style = document.createElement('style');
    style.textContent = `
        .profile-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            min-width: 180px;
            z-index: 1000;
        }
        
        .profile-dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item:hover {
            background-color: #f5f5f5;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }
        
        .profile-dropdown-toggle {
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2000;
            min-width: 300px;
            animation: slideIn 0.3s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .navbar {
            z-index: 900;
            position: sticky;
            top: 0;
        }
    `;
    document.head.appendChild(style);
    
    // Handle mobile navigation toggle if it exists
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuToggle && navLinks) {
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('show-mobile');
        });
    }
});
