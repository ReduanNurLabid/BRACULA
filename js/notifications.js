document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const viewAllNotificationsBtn = document.getElementById('viewAllNotificationsBtn');
    const notificationCount = document.getElementById('notificationCount');
    const notificationModal = document.getElementById('notificationModal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const allNotificationsList = document.getElementById('allNotificationsList');
    
    // Notification tabs
    const notificationTabs = document.querySelectorAll('.notification-tab');
    const notificationFilters = document.querySelectorAll('.notification-filter');
    
    // Storage key for notifications in localStorage
    const STORAGE_KEY = 'bracula_notifications';

    // Initialize notifications from localStorage or create empty array
    let notifications = (() => {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored ? JSON.parse(stored) : [];
    })();

    // Save notifications to localStorage
    function saveNotifications() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(notifications));
    }

    // Initialize UI
    updateNotificationCount();
    renderNotifications('all');

    // Toggle notification dropdown
    if (notificationToggle) {
        notificationToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            notificationDropdown.classList.toggle('show');
            
            if (notificationDropdown.classList.contains('show')) {
                renderNotifications('all');
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
        if (notificationDropdown && !notificationDropdown.contains(event.target) && 
            notificationToggle && !notificationToggle.contains(event.target)) {
            notificationDropdown.classList.remove('show');
        }
    });

    // Notification tabs functionality
    notificationTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            notificationTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            tab.classList.add('active');
            
            // Render notifications based on tab
            renderNotifications(tab.dataset.tab);
        });
    });

    // Notification filters in modal
    notificationFilters.forEach(filter => {
        filter.addEventListener('click', () => {
            // Remove active class from all filters
            notificationFilters.forEach(f => f.classList.remove('active'));
            
            // Add active class to clicked filter
            filter.classList.add('active');
            
            // Render all notifications based on filter
            renderAllNotifications(filter.dataset.filter);
        });
    });

    // Mark all as read button
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', () => {
            markAllAsRead();
            renderNotifications('all');
            updateNotificationCount();
        });
    }

    // View all notifications button
    if (viewAllNotificationsBtn && notificationModal) {
        viewAllNotificationsBtn.addEventListener('click', (event) => {
            event.preventDefault();
            notificationModal.style.display = 'block';
            renderAllNotifications('all');
        });
    }

    // Close modal buttons
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === notificationModal) {
            notificationModal.style.display = 'none';
        }
    });

    // Function to render notifications in dropdown
    function renderNotifications(filter) {
        if (!notificationList) return;
        
        let filteredNotifications;
        
        if (filter === 'unread') {
            filteredNotifications = notifications.filter(notification => !notification.read);
        } else {
            filteredNotifications = notifications;
        }
        
        // Limit to 5 notifications in dropdown
        filteredNotifications = filteredNotifications.slice(0, 5);
        
        if (filteredNotifications.length === 0) {
            notificationList.innerHTML = `
                <div class="empty-notification">
                    <i class="fas fa-bell-slash"></i>
                    <p>No ${filter === 'unread' ? 'unread ' : ''}notifications</p>
                </div>
            `;
            return;
        }
        
        notificationList.innerHTML = '';
        
        filteredNotifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.classList.add('notification-item');
            
            if (!notification.read) {
                notificationItem.classList.add('unread');
            }
            
            notificationItem.dataset.id = notification.id;
            
            // Get appropriate icon for notification type
            let iconMarkup;
            if (notification.avatar) {
                iconMarkup = `
                    <div class="notification-avatar">
                        <img src="${notification.avatar}" alt="User Avatar">
                    </div>
                `;
            } else {
                iconMarkup = `
                    <div class="notification-icon-placeholder">
                        <i class="fas ${notification.icon || 'fa-bell'}"></i>
                    </div>
                `;
            }
            
            notificationItem.innerHTML = `
                ${iconMarkup}
                <div class="notification-content">
                    <p class="notification-text">${notification.message}</p>
                    <span class="notification-time">${formatTime(notification.timestamp)}</span>
                </div>
            `;
            
            notificationItem.addEventListener('click', () => {
                markAsRead(notification.id);
                if (notification.link) {
                    window.location.href = notification.link;
                }
            });
            
            notificationList.appendChild(notificationItem);
        });
    }

    // Function to render all notifications in modal
    function renderAllNotifications(filter) {
        if (!allNotificationsList) return;
        
        let filteredNotifications;
        
        switch(filter) {
            case 'unread':
                filteredNotifications = notifications.filter(notification => !notification.read);
                break;
            case 'read':
                filteredNotifications = notifications.filter(notification => notification.read);
                break;
            default:
                filteredNotifications = notifications;
        }
        
        if (filteredNotifications.length === 0) {
            allNotificationsList.innerHTML = `
                <div class="empty-notification">
                    <i class="fas fa-bell-slash"></i>
                    <p>No ${filter !== 'all' ? filter + ' ' : ''}notifications</p>
                </div>
            `;
            return;
        }
        
        allNotificationsList.innerHTML = '';
        
        filteredNotifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.classList.add('notification-item');
            
            if (!notification.read) {
                notificationItem.classList.add('unread');
            }
            
            notificationItem.dataset.id = notification.id;
            
            // Get appropriate icon for notification type
            let iconMarkup;
            if (notification.avatar) {
                iconMarkup = `
                    <div class="notification-avatar">
                        <img src="${notification.avatar}" alt="User Avatar">
                    </div>
                `;
            } else {
                iconMarkup = `
                    <div class="notification-icon-placeholder">
                        <i class="fas ${notification.icon || 'fa-bell'}"></i>
                    </div>
                `;
            }
            
            notificationItem.innerHTML = `
                ${iconMarkup}
                <div class="notification-content">
                    <p class="notification-text">${notification.message}</p>
                    <span class="notification-time">${formatTime(notification.timestamp)}</span>
                </div>
            `;
            
            notificationItem.addEventListener('click', () => {
                markAsRead(notification.id);
                if (notification.link) {
                    window.location.href = notification.link;
                }
            });
            
            allNotificationsList.appendChild(notificationItem);
        });
    }

    // Function to mark notification as read
    function markAsRead(notificationId) {
        const notification = notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.read = true;
            saveNotifications();
            updateNotificationCount();
            return true;
        }
        return false;
    }

    // Function to mark all notifications as read
    function markAllAsRead() {
        notifications.forEach(notification => {
            notification.read = true;
        });
        saveNotifications();
        updateNotificationCount();
    }

    // Function to update notification count
    function updateNotificationCount() {
        if (!notificationCount) return;
        
        const unreadCount = notifications.filter(notification => !notification.read).length;
        
        if (unreadCount > 0) {
            notificationCount.textContent = unreadCount;
            notificationCount.style.display = 'flex';
            
            if (notificationToggle) {
                notificationToggle.classList.add('animate');
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    notificationToggle.classList.remove('animate');
                }, 600);
            }
        } else {
            notificationCount.style.display = 'none';
        }
    }

    // Function to add a new notification programmatically
    function addNotification(notificationData) {
        // Create notification object with defaults
        const notification = {
            id: Date.now(), // Generate unique ID
            type: notificationData.type || 'general',
            title: notificationData.title || 'Notification',
            message: notificationData.message || notificationData.text || '',
            icon: notificationData.icon || getIconForType(notificationData.type),
            avatar: notificationData.avatar || null,
            read: false,
            timestamp: new Date().toISOString(),
            link: notificationData.link || '#',
            data: notificationData.data || {}
        };
        
        // Add to beginning of array to show newest first
        notifications.unshift(notification);
        
        // Save to localStorage
        saveNotifications();
        
        // Update the notification count
        updateNotificationCount();
        
        // Re-render the notification list if it's open
        if (notificationDropdown && notificationDropdown.classList.contains('show')) {
            renderNotifications('all');
        }
        
        // Flash the notification bell
        if (notificationToggle) {
            notificationToggle.classList.add('animate');
            setTimeout(() => {
                notificationToggle.classList.remove('animate');
            }, 1000);
        }
        
        // If it's a ride request notification, dispatch event for UI updates
        if (notificationData.type === 'ride_request') {
            const requestEvent = new CustomEvent('newRideRequest', {
                detail: notificationData.data
            });
            window.dispatchEvent(requestEvent);
        }
        
        return notification.id; // Return the ID of the new notification
    }

    // Helper function to get icon for notification type
    function getIconForType(type) {
        switch(type) {
            case 'ride_request': return 'fa-car';
            case 'ride_accepted': return 'fa-check';
            case 'ride_rejected': return 'fa-times';
            case 'comment': return 'fa-comment';
            case 'like': return 'fa-heart';
            case 'event': return 'fa-calendar';
            case 'message': return 'fa-envelope';
            default: return 'fa-bell';
        }
    }

    // Function to update a notification by ID
    function updateNotificationById(id, updates) {
        const index = notifications.findIndex(n => n.id === id);
        if (index !== -1) {
            notifications[index] = { ...notifications[index], ...updates };
            saveNotifications();
            
            // Re-render if dropdown is open
            if (notificationDropdown && notificationDropdown.classList.contains('show')) {
                renderNotifications('all');
            }
            
            return true;
        }
        return false;
    }

    // Function to show ride acceptance/rejection notification in UI
    function showStatusNotification(status, rideDetails) {
        const container = document.createElement('div');
        container.className = `request-status-notification status-${status}-notification`;
        
        const iconDiv = document.createElement('div');
        iconDiv.className = 'request-response-icon';
        iconDiv.innerHTML = status === 'accepted' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>';
        
        const textDiv = document.createElement('div');
        textDiv.className = 'request-response-text';
        
        const title = document.createElement('h5');
        title.className = 'mb-1';
        title.textContent = status === 'accepted' 
            ? 'Ride Request Accepted!' 
            : 'Ride Request Declined';
        
        const details = document.createElement('p');
        details.className = 'mb-0';
        details.textContent = rideDetails;
        
        textDiv.appendChild(title);
        textDiv.appendChild(details);
        
        container.appendChild(iconDiv);
        container.appendChild(textDiv);
        
        // Add to notifications area or main content
        const notificationArea = document.querySelector('.notification-area') || document.querySelector('.main-content');
        if (notificationArea) {
            notificationArea.appendChild(container);
            
            // Remove after 5 seconds
            setTimeout(() => {
                container.style.opacity = '0';
                setTimeout(() => container.remove(), 300);
            }, 5000);
        }
    }

    // Listen for ride events
    window.addEventListener('rideRequested', function(e) {
        const { user, destination, requestId, rideId } = e.detail;
        
        addNotification({
            type: 'ride_request',
            title: 'New Ride Request',
            message: `${user} has requested a ride to ${destination}`,
            data: {
                requestId: requestId,
                rideId: rideId,
                user: user,
                destination: destination
            }
        });
    });

    window.addEventListener('rideAccepted', function(e) {
        const { requestId, driverName, destination } = e.detail;
        
        addNotification({
            type: 'ride_accepted',
            title: 'Ride Request Accepted',
            message: `${driverName} has accepted your ride request to ${destination}`,
            data: {
                requestId: requestId,
                driverName: driverName,
                destination: destination
            }
        });
        
        showStatusNotification('accepted', `${driverName} will pick you up for your ride to ${destination}`);
    });

    window.addEventListener('rideRejected', function(e) {
        const { requestId, driverName, destination, reason } = e.detail;
        
        addNotification({
            type: 'ride_rejected',
            title: 'Ride Request Declined',
            message: `${driverName} has declined your ride request to ${destination}${reason ? ': ' + reason : ''}`,
            data: {
                requestId: requestId,
                driverName: driverName,
                destination: destination,
                reason: reason
            }
        });
        
        showStatusNotification('rejected', `${driverName} cannot accommodate your ride to ${destination}${reason ? '. Reason: ' + reason : ''}`);
    });

    // Format time helper function
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // difference in seconds
        
        if (diff < 60) {
            return 'Just now';
        } else if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diff < 604800) {
            const days = Math.floor(diff / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else {
            // Format date
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }
    }

    // Add event handler for "notificationTest" event
    document.addEventListener('notificationTest', function(e) {
        const { type, message, title } = e.detail;
        addNotification({
            type: type || 'general',
            title: title || 'Test Notification',
            message: message || 'This is a test notification',
        });
    });

    // Expose functions to window for use in other scripts
    window.addNotification = addNotification;
    window.updateNotificationById = updateNotificationById;
    window.showStatusNotification = showStatusNotification;
    window.markAsRead = markAsRead;
    window.markAllAsRead = markAllAsRead;
}); 