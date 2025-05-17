// Singleton Notification Manager
class NotificationManager {
    constructor() {
        this.notificationBell = null;
        this.notificationDropdown = null;
        this.notificationList = null;
        this.notificationCount = null;
        this.notifications = [];
        this.isInitialized = false;
    }

    // Singleton pattern implementation
    static getInstance() {
        if (!NotificationManager.instance) {
            NotificationManager.instance = new NotificationManager();
        }
        return NotificationManager.instance;
    }

    // Initialize the notification system
    initialize() {
        if (this.isInitialized) {
            console.log('NotificationManager already initialized');
            return;
        }

        this.notificationBell = document.querySelector('.notification-bell');
        this.notificationDropdown = document.querySelector('#notificationDropdown');
        this.notificationList = document.querySelector('#notificationList');
        this.notificationCount = document.querySelector('#notificationCount');

        if (this.notificationBell && this.notificationDropdown) {
            // Remove any existing event listeners by cloning and replacing the element
            const newBell = this.notificationBell.cloneNode(true);
            this.notificationBell.parentNode.replaceChild(newBell, this.notificationBell);
            this.notificationBell = newBell;
            
            // Add click event to the notification bell
            this.notificationBell.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Notification bell clicked');
                
                // Toggle show class on the dropdown
                this.notificationDropdown.classList.toggle('show');
                
                // If opening, mark notifications as seen
                if (this.notificationDropdown.classList.contains('show')) {
                    this.markNotificationsAsSeen();
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.notificationBell.contains(e.target) && !this.notificationDropdown.contains(e.target)) {
                    this.notificationDropdown.classList.remove('show');
                }
            });
            
            // Prevent dropdown content clicks from closing
            this.notificationDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            
            // Setup mark all as read button
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.markAllAsRead();
                });
            }
            
            // Handle tab switching
            const tabs = document.querySelectorAll('.notification-tab');
            if (tabs.length > 0) {
                tabs.forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        const tabType = tab.dataset.tab;
                        
                        // Update active tab
                        tabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        
                        // Filter notifications based on tab
                        this.renderNotifications(tabType);
                    });
                });
            }
            
            // Load notifications from backend or storage
            this.loadNotifications();
            
            this.isInitialized = true;
            console.log('NotificationManager initialized');
        } else {
            console.warn('Notification elements not found');
        }
    }
    
    // Load notifications from backend or local storage
    loadNotifications() {
        // Try to load from localStorage first (as a cache)
        const storedNotifications = localStorage.getItem('userNotifications');
        if (storedNotifications) {
            this.notifications = JSON.parse(storedNotifications);
            this.renderNotifications();
            this.updateNotificationCount();
        }
        
        // Then try to fetch from backend
        this.fetchNotificationsFromBackend();
    }
    
    // Fetch notifications from backend
    fetchNotificationsFromBackend() {
        const API_BASE_URL = 'http://localhost:8081/BRACULA/api';
        
        // Set a timeout to prevent long-hanging requests
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        fetch(`${API_BASE_URL}/notifications.php`, {
            credentials: 'include',
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                // Convert non-2xx HTTP responses into errors
                return response.text().then(text => {
                    throw new Error(`HTTP error ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.notifications = data.data;
                // Save to localStorage as cache
                localStorage.setItem('userNotifications', JSON.stringify(this.notifications));
                this.renderNotifications();
                this.updateNotificationCount();
            } else {
                console.warn('Unexpected API response format:', data);
                // If the API returns unexpected format, fall back to mock data
                this.useMockNotifications();
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
            
            // Check if the error is a network error or timeout
            if (error.name === 'AbortError') {
                console.warn('Notification request timed out');
            } else if (error.message.includes('Failed to fetch') || 
                       error.message.includes('NetworkError') ||
                       error.message.includes('Network request failed')) {
                console.warn('Network error when fetching notifications');
            }
            
            // If error happens in accommodation.html, avoid constant retries
            const isAccommodationPage = window.location.pathname.includes('accommodation.html');
            if (isAccommodationPage) {
                console.log('On accommodation page, using cached notifications only');
                
                // Only use mock data if we don't have cached data
                const storedNotifications = localStorage.getItem('userNotifications');
                if (!storedNotifications) {
                    this.useMockNotifications();
                }
            } else {
                // On other pages, try to use mock data
                this.useMockNotifications();
            }
        });
    }
    
    // Use mock notifications for testing or when backend is unavailable
    useMockNotifications() {
        this.notifications = [
            {
                id: 1,
                title: 'New accommodation posted',
                message: 'A new apartment is available in Mohakhali',
                created_at: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
                is_read: false
            },
            {
                id: 2,
                title: 'Price drop alert',
                message: 'A room you saved has reduced its price',
                created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
                is_read: false
            },
            {
                id: 3,
                title: 'Message from owner',
                message: 'You have a new message about your inquiry',
                created_at: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
                is_read: true
            }
        ];
        
        // Save mock notifications to localStorage
        localStorage.setItem('userNotifications', JSON.stringify(this.notifications));
        this.renderNotifications();
        this.updateNotificationCount();
    }
    
    // Render notifications in the dropdown
    renderNotifications(tabType = 'all') {
        if (!this.notificationList) return;
        
        // Filter notifications based on tab
        const filteredNotifications = tabType === 'all' 
            ? this.notifications 
            : this.notifications.filter(notif => !notif.is_read);
        
        if (filteredNotifications.length === 0) {
            this.notificationList.innerHTML = `
                <div class="no-notifications">
                    <p>No ${tabType === 'all' ? '' : 'unread'} notifications</p>
                </div>
            `;
            return;
        }
        
        // Sort notifications by date (newest first)
        const sortedNotifications = [...filteredNotifications].sort((a, b) => {
            return new Date(b.created_at) - new Date(a.created_at);
        });
        
        // Build HTML
        this.notificationList.innerHTML = sortedNotifications.map(notification => `
            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                    <div class="notification-text">${this.escapeHtml(notification.message)}</div>
                    <div class="notification-time">${this.formatTimeSince(notification.created_at)}</div>
                </div>
            </div>
        `).join('');
        
        // Add click event to each notification
        this.notificationList.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = parseInt(item.dataset.id);
                this.markAsRead(notificationId);
                // Handle navigation or action based on notification type
                // This would be expanded based on actual notification types
                console.log(`Clicked on notification ${notificationId}`);
            });
        });
    }
    
    // Mark individual notification as read
    markAsRead(notificationId) {
        // Update local state
        this.notifications = this.notifications.map(notif => {
            if (notif.id === notificationId) {
                return {...notif, is_read: true};
            }
            return notif;
        });
        
        // Update UI
        const notificationItem = this.notificationList.querySelector(`.notification-item[data-id="${notificationId}"]`);
        if (notificationItem) {
            notificationItem.classList.remove('unread');
        }
        
        // Update notification count
        this.updateNotificationCount();
        
        // Save to localStorage
        localStorage.setItem('userNotifications', JSON.stringify(this.notifications));
        
        // Send to backend if available
        this.updateNotificationReadStatus(notificationId, true);
    }
    
    // Mark all notifications as read
    markAllAsRead() {
        // Update local state
        this.notifications = this.notifications.map(notif => ({...notif, is_read: true}));
        
        // Update UI
        this.notificationList.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('unread');
        });
        
        // Update notification count
        this.updateNotificationCount();
        
        // Save to localStorage
        localStorage.setItem('userNotifications', JSON.stringify(this.notifications));
        
        // Send to backend if available
        this.updateAllNotificationsReadStatus();
    }
    
    // Mark notifications as seen when opening dropdown (doesn't mark as read)
    markNotificationsAsSeen() {
        // Could implement a "seen but not read" state here
        console.log('Notifications seen');
    }
    
    // Update notification count badge
    updateNotificationCount() {
        if (!this.notificationCount) return;
        
        const unreadCount = this.notifications.filter(notif => !notif.is_read).length;
        this.notificationCount.textContent = unreadCount.toString();
        
        // Hide badge if no unread notifications
        if (unreadCount === 0) {
            this.notificationCount.style.display = 'none';
        } else {
            this.notificationCount.style.display = 'flex';
        }
    }
    
    // Send read status to backend
    updateNotificationReadStatus(notificationId, isRead) {
        const API_BASE_URL = 'http://localhost:8081/BRACULA/api';
        
        // Set a timeout to prevent long-hanging requests
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        // First check if we're on the accommodation page where API might not be available
        if (window.location.pathname.includes('accommodation.html')) {
            // On accommodation page, just log and don't attempt to send
            console.log('On accommodation page, skipping server notification update');
            clearTimeout(timeoutId);
            return;
        }
        
        fetch(`${API_BASE_URL}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_read_status',
                notification_id: notificationId,
                is_read: isRead
            }),
            credentials: 'include',
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification status updated on server:', data);
        })
        .catch(error => {
            console.error('Error updating notification status:', error);
            // The local state is already updated, so user experience is not affected
        });
    }
    
    // Send all read status to backend
    updateAllNotificationsReadStatus() {
        const API_BASE_URL = 'http://localhost:8081/BRACULA/api';
        
        // Set a timeout to prevent long-hanging requests
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        // First check if we're on the accommodation page where API might not be available
        if (window.location.pathname.includes('accommodation.html')) {
            // On accommodation page, just log and don't attempt to send
            console.log('On accommodation page, skipping server notification update');
            clearTimeout(timeoutId);
            return;
        }
        
        fetch(`${API_BASE_URL}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_read'
            }),
            credentials: 'include',
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('All notifications marked as read on server:', data);
        })
        .catch(error => {
            console.error('Error updating all notification status:', error);
            // The local state is already updated, so user experience is not affected
        });
    }
    
    // Add a new notification (can be called from any page)
    addNotification(notification) {
        // Add to local state
        const newNotification = {
            id: Date.now(), // Temporary ID until server sync
            ...notification,
            created_at: new Date().toISOString(),
            is_read: false
        };
        
        this.notifications.unshift(newNotification);
        
        // Update UI
        this.renderNotifications();
        this.updateNotificationCount();
        
        // Save to localStorage
        localStorage.setItem('userNotifications', JSON.stringify(this.notifications));
        
        // Show toast notification
        this.showToastNotification(notification.title, notification.message);
        
        // Send to backend if available
        this.sendNotificationToBackend(newNotification);
    }
    
    // Show toast notification
    showToastNotification(title, message) {
        const toast = document.createElement('div');
        toast.className = 'notification';
        toast.innerHTML = `
            <h4>${this.escapeHtml(title)}</h4>
            <p>${this.escapeHtml(message)}</p>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    // Send notification to backend
    sendNotificationToBackend(notification) {
        const API_BASE_URL = 'http://localhost:8081/BRACULA/api';
        
        // Set a timeout to prevent long-hanging requests
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        // First check if we're on the accommodation page where API might not be available
        if (window.location.pathname.includes('accommodation.html')) {
            // On accommodation page, just log and don't attempt to send
            console.log('On accommodation page, skipping server notification update');
            clearTimeout(timeoutId);
            return;
        }
        
        fetch(`${API_BASE_URL}/notifications.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_notification',
                notification: notification
            }),
            credentials: 'include',
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification sent to server:', data);
            if (data.status === 'success' && data.data && data.data.id) {
                // Update local notification with server-generated ID
                this.updateNotificationId(notification.id, data.data.id);
            }
        })
        .catch(error => {
            console.error('Error sending notification:', error);
            // No need to retry - the notification will remain in local storage
            // and the user experience isn't affected by the failed server sync
        });
    }
    
    // Update temporary notification ID with server-generated ID
    updateNotificationId(tempId, serverId) {
        this.notifications = this.notifications.map(notif => {
            if (notif.id === tempId) {
                return {...notif, id: serverId};
            }
            return notif;
        });
        
        // Save updated notifications to localStorage
        localStorage.setItem('userNotifications', JSON.stringify(this.notifications));
    }
    
    // Format time since notification was created
    formatTimeSince(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffInMinutes = Math.floor((now - date) / (1000 * 60));
        
        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
        if (diffInMinutes < 10080) return `${Math.floor(diffInMinutes / 1440)}d ago`;
        
        return date.toLocaleDateString();
    }
    
    // Escape HTML to prevent XSS
    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
} 