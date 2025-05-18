// Initialize the state
const STATE = {
    posts: new Map(),
    loading: false,
    page: 1,
    filters: {
        sortBy: 'latest',
        community: 'general',
        search: ''
    },
    currentUser: JSON.parse(localStorage.getItem('user')) || null
};

// Base URL for API calls
const BASE_URL = window.location.origin + '/BRACULA';

// Load Initial Posts
async function loadInitialPosts() {
    try {
        STATE.loading = true;
        STATE.page = 1; // Reset to page 1
        
        const user = JSON.parse(localStorage.getItem('user'));
        
        const queryParams = new URLSearchParams({
            sortBy: STATE.filters.sortBy,
            community: STATE.filters.community,
            page: STATE.page,
            user_id: user ? user.user_id : ''
        });

        console.log(`Loading posts with params: sortBy=${STATE.filters.sortBy}, community=${STATE.filters.community}`);

        // Clear search input field if we're loading initial posts
        const searchInput = document.getElementById('post-search');
        if (searchInput && STATE.filters.search === '') {
            searchInput.value = '';
        }

        const response = await fetch(`${BASE_URL}/api/posts/get_posts.php?${queryParams}`);
        const data = await response.json();

        if (data.status === 'success') {
            console.log(`Loaded ${data.data.length} posts with sort: ${STATE.filters.sortBy}`);
            
            // Clear existing posts before adding new ones
            STATE.posts.clear();
            data.data.forEach(post => STATE.posts.set(post.id, post));
            refreshFeed();
            return true; // Return success
        } else {
            throw new Error(data.message || 'Failed to load posts');
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        showNotification('Failed to load posts', 'error');
        return false; // Return failure
    } finally {
        STATE.loading = false;
    }
}

// Load More Posts
async function loadMorePosts() {
    if (STATE.loading) return;
    
    try {
        STATE.loading = true;
        STATE.page++;
        
        const user = JSON.parse(localStorage.getItem('user'));
        let endpoint = '';
        const queryParams = new URLSearchParams({
            sortBy: STATE.filters.sortBy,
            community: STATE.filters.community,
            page: STATE.page,
            user_id: user ? user.user_id : ''
        });

        // Check if we're in search mode
        if (STATE.filters.search) {
            endpoint = `${BASE_URL}/api/posts/search_posts.php`;
            queryParams.append('query', STATE.filters.search);
        } else {
            endpoint = `${BASE_URL}/api/posts/get_posts.php`;
        }

        const response = await fetch(`${endpoint}?${queryParams}`);
        const data = await response.json();

        if (data.status === 'success') {
            if (data.data.length === 0) {
                // No more posts to load
                return;
            }

            const postsContainer = document.querySelector('.posts-container');

            data.data.forEach(post => {
                if (!STATE.posts.has(post.id)) {
                    STATE.posts.set(post.id, post);
                    const postElement = createPostElement(post);
                    postsContainer.appendChild(postElement);
                }
            });
            
            attachPostEventListeners();
        } else {
            throw new Error(data.message || 'Failed to load more posts');
        }
    } catch (error) {
        console.error('Error loading more posts:', error);
        showNotification('Failed to load more posts', 'error');
        STATE.page--; // Revert page increment on error
    } finally {
        STATE.loading = false;
    }
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Function to create a post element
function createPostElement(post) {
    // Get current user
    const currentUser = JSON.parse(localStorage.getItem('user')) || {};
    const isAuthor = parseInt(currentUser.user_id) === parseInt(post.user_id);
    
    // Create post element
    const postEl = document.createElement('div');
    postEl.className = 'feed-post compact';
    postEl.dataset.postId = post.id;
    
    // Add post overlay for clickable area
    const postOverlay = document.createElement('div');
    postOverlay.className = 'post-overlay';
    postEl.appendChild(postOverlay);
    
    // Create vote section
    const voteSection = document.createElement('div');
    voteSection.className = 'vote-section';
    voteSection.innerHTML = `
        <button class="vote-btn upvote ${post.user_vote === 'up' ? 'active' : ''}" data-vote="up">
                <i class="fas fa-arrow-up"></i>
            </button>
            <span class="vote-count">${post.votes || 0}</span>
        <button class="vote-btn downvote ${post.user_vote === 'down' ? 'active' : ''}" data-vote="down">
                <i class="fas fa-arrow-down"></i>
            </button>
    `;
    
    // Create post content section
    const postContent = document.createElement('div');
    postContent.className = 'post-content';
    postContent.style.position = 'relative'; // Add relative positioning for dropdown positioning
    
    // Create a container for the options button and dropdown - positioned at top right
    const optionsBtnContainer = document.createElement('div');
    optionsBtnContainer.className = 'options-container';
    optionsBtnContainer.style.position = 'absolute';
    optionsBtnContainer.style.top = '10px';
    optionsBtnContainer.style.right = '10px';
    optionsBtnContainer.style.zIndex = '100';
    
    // Add options button
    const optionsBtn = document.createElement('button');
    optionsBtn.className = 'action-btn options-btn';
    optionsBtn.innerHTML = `<i class="fas fa-ellipsis-h"></i>`;
    optionsBtn.style.padding = '5px 10px';
    optionsBtn.style.background = 'transparent';
    optionsBtn.style.border = 'none';
    optionsBtn.style.cursor = 'pointer';
    
    // Add options button to container
    optionsBtnContainer.appendChild(optionsBtn);
    
    // Add options container to post content
    postContent.appendChild(optionsBtnContainer);
    
    // Add post title if exists
    if (post.caption) {
        const postTitle = document.createElement('h3');
        postTitle.className = 'post-title';
        postTitle.textContent = post.caption;
        postContent.appendChild(postTitle);
    }
    
    // Create post meta info section
    const postMeta = document.createElement('div');
    postMeta.className = 'post-meta-info';
    
    // Format post info like Reddit but without the u/ and r/ prefixes
    postMeta.innerHTML = `
        <span class="post-author-prefix">Posted by </span>
        <a class="author-name" data-user-id="${post.user_id}">${post.author || 'Anonymous'}</a>
        <span class="post-community-prefix">in </span>
        <a class="post-community" data-community-id="${post.community_id || post.community}">${post.community || 'general'}</a>
        <span class="post-time-separator">•</span>
        <span class="post-time">${formatTimestamp(post.timestamp)}</span>
    `;
    
    postContent.appendChild(postMeta);
    
    // Add post text
    const postText = document.createElement('div');
    postText.className = 'post-text';
    postText.innerHTML = post.content || '';
    postContent.appendChild(postText);
    
    // Add post image if exists
    if (post.image_url) {
        const postImageContainer = document.createElement('div');
        postImageContainer.className = 'post-image';
        
        const postImage = document.createElement('img');
        postImage.src = post.image_url;
        postImage.alt = 'Post image';
        postImage.loading = 'lazy'; // Lazy load images
        
        postImageContainer.appendChild(postImage);
        postContent.appendChild(postImageContainer);
    }
    
    // Add post actions
    const postActions = document.createElement('div');
    postActions.className = 'post-actions';
    
    const commentsBtn = document.createElement('button');
    commentsBtn.className = 'action-btn comments-btn';
    commentsBtn.innerHTML = `
        <i class="far fa-comment"></i>
        <span class="comments-count">${post.commentCount || 0}</span> Comments
    `;
    
    const saveBtn = document.createElement('button');
    saveBtn.className = 'action-btn save-btn';
    saveBtn.innerHTML = `
        <i class="${post.is_saved ? 'fas' : 'far'} fa-bookmark"></i>
        ${post.is_saved ? 'Saved' : 'Save'}
    `;
    
    // Add button event listeners
    commentsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openCommentModal(post.id);
    });
    
    saveBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        console.log("Save button clicked");
        const isSaved = saveBtn.querySelector('i').classList.contains('fas');
        console.log("Current saved state:", isSaved);
        
        // Call the savePost function without UI update (it will be handled by the function)
        savePost(post.id, isSaved);
    });
    
    // Add buttons to actions
    postActions.appendChild(commentsBtn);
    postActions.appendChild(saveBtn);
    
    // Add actions to content
    postContent.appendChild(postActions);
    
    // Add options dropdown
    const optionsDropdown = document.createElement('div');
    optionsDropdown.className = 'options-dropdown';
    optionsDropdown.style.display = 'none'; // Explicitly set display to none initially
    optionsDropdown.style.position = 'absolute'; // Ensure absolute positioning
    optionsDropdown.style.zIndex = '9999'; // Very high z-index to show above all elements
    optionsDropdown.style.top = '30px'; // Position it below the button
    optionsDropdown.style.right = '0'; // Align to the right of the button
    optionsDropdown.style.width = '150px'; // Set a fixed width
    optionsDropdown.style.minWidth = '120px'; // Ensure minimum width
    optionsDropdown.style.background = '#fff'; // Ensure background color
    optionsDropdown.style.border = '1px solid #ddd'; // Add border
    optionsDropdown.style.borderRadius = '4px'; // Rounded corners
    optionsDropdown.style.padding = '5px 0'; // Add some padding top/bottom only
    optionsDropdown.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)'; // Subtle shadow
    
    // Add appropriate options based on authorship
    if (isAuthor) {
        optionsDropdown.innerHTML = `
            <button class="edit-post-btn" data-post-id="${post.id}">Edit</button>
            <button class="delete-post-btn" data-post-id="${post.id}">Delete</button>
        `;
        
        // Add event listeners directly to the dropdown buttons
        setTimeout(() => {
            const editBtn = optionsDropdown.querySelector('.edit-post-btn');
            if (editBtn) {
                // Add inline styling
                editBtn.style.padding = '8px 16px';
                editBtn.style.margin = '2px 0';
                editBtn.style.width = '100%';
                editBtn.style.textAlign = 'left';
                editBtn.style.display = 'block';
                editBtn.style.cursor = 'pointer';
                editBtn.style.border = 'none';
                editBtn.style.background = 'none';
                editBtn.style.fontSize = '14px';
                editBtn.style.color = '#333';
                
                editBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    editPost(post.id);
                    optionsDropdown.style.display = 'none'; // Hide dropdown after action
                });
            }
            
            const deleteBtn = optionsDropdown.querySelector('.delete-post-btn');
            if (deleteBtn) {
                // Add inline styling
                deleteBtn.style.padding = '8px 16px';
                deleteBtn.style.margin = '2px 0';
                deleteBtn.style.width = '100%';
                deleteBtn.style.textAlign = 'left';
                deleteBtn.style.display = 'block';
                deleteBtn.style.cursor = 'pointer';
                deleteBtn.style.border = 'none';
                deleteBtn.style.background = 'none';
                deleteBtn.style.fontSize = '14px';
                deleteBtn.style.color = '#333';
                
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deletePost(post.id);
                    optionsDropdown.style.display = 'none'; // Hide dropdown after action
                });
            }
        }, 0);
    } else {
        optionsDropdown.innerHTML = `
            <button class="save-post-btn" data-post-id="${post.id}" data-saved="${post.is_saved ? 'true' : 'false'}">
                ${post.is_saved ? 'Unsave' : 'Save'}
                </button>
            <button class="report-post-btn" data-post-id="${post.id}">Report</button>
        `;
        
        // Add event listeners directly to the dropdown buttons
        setTimeout(() => {
            const savePostBtn = optionsDropdown.querySelector('.save-post-btn');
            if (savePostBtn) {
                // Add inline styling
                savePostBtn.style.padding = '8px 16px';
                savePostBtn.style.margin = '2px 0';
                savePostBtn.style.width = '100%';
                savePostBtn.style.textAlign = 'left';
                savePostBtn.style.display = 'block';
                savePostBtn.style.cursor = 'pointer';
                savePostBtn.style.border = 'none';
                savePostBtn.style.background = 'none';
                savePostBtn.style.fontSize = '14px';
                savePostBtn.style.color = '#333';
                
                savePostBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    console.log("Dropdown save button clicked");
                    const isSaved = savePostBtn.dataset.saved === 'true';
                    console.log("Dropdown current saved state:", isSaved);
                    savePost(post.id, isSaved);
                    optionsDropdown.style.display = 'none'; // Hide dropdown after action
                });
            }
            
            const reportBtn = optionsDropdown.querySelector('.report-post-btn');
            if (reportBtn) {
                // Add inline styling
                reportBtn.style.padding = '8px 16px';
                reportBtn.style.margin = '2px 0';
                reportBtn.style.width = '100%';
                reportBtn.style.textAlign = 'left';
                reportBtn.style.display = 'block';
                reportBtn.style.cursor = 'pointer';
                reportBtn.style.border = 'none';
                reportBtn.style.background = 'none';
                reportBtn.style.fontSize = '14px';
                reportBtn.style.color = '#333';
                
                reportBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    reportPost(post.id);
                    optionsDropdown.style.display = 'none'; // Hide dropdown after action
                });
            }
        }, 0);
    }
    
    // Add the dropdown to the options button container
    optionsBtnContainer.appendChild(optionsDropdown);
    
    // Use setupOptionsButton to handle the options button behavior
    setupOptionsButton(optionsBtn, optionsDropdown, post);
    
    // Add vote section and content to post
    postEl.appendChild(voteSection);
    postEl.appendChild(postContent);
    
    // Add click event listener to post for details page
    postEl.addEventListener('click', (e) => {
        // Don't navigate if clicking on buttons or links
        if (e.target.closest('button') || e.target.closest('a')) {
            return;
        }
        
        // Open the comment modal instead of redirecting
        openCommentModal(post.id);
    });
    
    return postEl;
}

// Initialize Feed Page
document.addEventListener('DOMContentLoaded', () => {
    initializeState();
    initializeCreatePostFeature();
    initializePostActions();
    initializeFilters();
    initializeInfiniteScroll();
    initializeRealTimeUpdates();
    initializeSearchFeature();
    
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const postId = urlParams.get('post_id');
    const commentId = urlParams.get('comment_id');
    
    // Load initial posts
    loadInitialPosts().then(() => {
        // If post_id is provided, open that post
        if (postId) {
            console.log(`Opening post with ID: ${postId}`);
            setTimeout(() => {
                const postElement = document.querySelector(`.feed-post[data-post-id="${postId}"]`);
                if (postElement) {
                    // Scroll to the post
                    postElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Highlight the post briefly
                    postElement.classList.add('highlight-post');
                    setTimeout(() => {
                        postElement.classList.remove('highlight-post');
                    }, 2000);
                    
                    // Open the comment modal if needed
                    openCommentModal(postId).then(() => {
                        // If comment_id is provided, scroll to that comment
                        if (commentId) {
                            console.log(`Scrolling to comment with ID: ${commentId}`);
                            setTimeout(() => {
                                const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
                                if (commentElement) {
                                    commentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    commentElement.classList.add('highlight-comment');
                                    setTimeout(() => {
                                        commentElement.classList.remove('highlight-comment');
                                    }, 2000);
                                }
                            }, 500);
                        }
                    });
                } else {
                    console.error(`Post with ID ${postId} not found`);
                    showNotification(`Post not found`, 'error');
                }
            }, 500);
        }
    });
});

// State Management
function initializeState() {
    // Get user data from localStorage
    STATE.currentUser = JSON.parse(localStorage.getItem('user')) || null;
    if (!STATE.currentUser) {
        console.error('No user data found in localStorage');
        return;
    }
}

// Initialize Post Creation and Actions
function initializeCreatePostFeature() {
    const createPostBtn = document.querySelector('.create-post-btn');
    const createPostModal = document.getElementById('createPostModal');
    const createPostForm = document.getElementById('create-post-form');
    const closeModalBtn = createPostModal?.querySelector('.close-modal');
    const submitButton = createPostForm?.querySelector('.submit-button');
    const contentInput = document.getElementById('post-content');
    const communitySelect = document.getElementById('post-community');

    if (!createPostBtn || !createPostModal || !createPostForm || !submitButton || !contentInput || !communitySelect) {
        console.error('Create post elements not found');
        return;
    }

    // Enable/disable submit button based on content
    function updateSubmitButton() {
        const content = contentInput.value.trim();
        const community = communitySelect.value;
        submitButton.disabled = !content || !community;
    }

    // Add input event listeners
    contentInput.addEventListener('input', updateSubmitButton);
    communitySelect.addEventListener('change', updateSubmitButton);

    // Open modal when clicking create post button
    createPostBtn.addEventListener('click', () => {
        createPostModal.style.display = 'block';
        contentInput.focus();
    });

    // Close modal when clicking close button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            createPostModal.style.display = 'none';
            createPostForm.reset();
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === createPostModal) {
            createPostModal.style.display = 'none';
            createPostForm.reset();
        }
    });

    // Track user activity
    async function trackUserActivity(userId, activityType, contentId) {
        try {
            const response = await fetch(`${BASE_URL}/api/users/update_user_activity.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    activity_type: activityType,
                    content_id: contentId
                })
            });

            const data = await response.json();
            if (data.status !== 'success') {
                console.error('Failed to track activity:', data.message);
            }
        } catch (error) {
            console.error('Error tracking activity:', error);
        }
    }

    // Update the createPostForm submit handler
    createPostForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Get form data
        const caption = document.getElementById('post-caption').value.trim();
        const content = contentInput.value.trim();
        const community = communitySelect.value;
        
        // Validate content
        if (!content) {
            showNotification('Post content cannot be empty', 'error');
            return;
        }

        // Disable submit button and show loading state
        submitButton.disabled = true;
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

        try {
            // Get user data from localStorage
            const user = JSON.parse(localStorage.getItem('user'));
            if (!user || !user.user_id) {
                throw new Error('You must be logged in to create a post');
            }

            // Send request to create post
            const response = await fetch(`${BASE_URL}/api/posts/create_post.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: user.user_id,
                    caption,
                    content,
                    community
                })
            });

        const data = await response.json();

        if (data.status === 'success') {
                // Track the activity
                await trackUserActivity(user.user_id, 'post', data.data.id);
                
                // Add new post to state
                if (data.data && data.data.id) {
                    STATE.posts.set(data.data.id, data.data);
            refreshFeed();
                }
                
                // Reset form and close modal
                createPostForm.reset();
                createPostModal.style.display = 'none';
                
                showNotification('Post created successfully!', 'success');
        } else {
                throw new Error(data.message || 'Failed to create post');
        }
    } catch (error) {
            console.error('Error creating post:', error);
            showNotification(error.message, 'error');
    } finally {
            // Reset submit button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });

    // Initial button state
    updateSubmitButton();
}

// Initialize Post Actions
function initializePostActions() {
    // We're now handling most actions directly on the buttons
    // This function remains as a placeholder for potential future global actions
    console.log("Post actions initialized");
}

// Initialize Filters
function initializeFilters() {
    // Sort buttons
    const sortButtons = document.querySelectorAll('.post-filter-buttons button');
    
    sortButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            
            // Remove active class from all buttons
            sortButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update state
            STATE.filters.sortBy = sortBy;
            STATE.page = 1; // Reset to first page
            
            console.log(`Sorting posts by: ${sortBy}`); // Debug log
            
            // Clear search if it exists
            if (STATE.filters.search) {
                STATE.filters.search = '';
                const searchInput = document.getElementById('post-search');
                if (searchInput) searchInput.value = '';
            }
            
            // Reload posts
            loadInitialPosts();
        });
    });
    
    // Community items
    const communityItems = document.querySelectorAll('.sidebar-item');
    
    communityItems.forEach(item => {
        item.addEventListener('click', function() {
            const community = this.dataset.community;
            
            // Remove active class from all items
            communityItems.forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Update state
            STATE.filters.community = community;
            STATE.page = 1; // Reset to first page
            
            console.log(`Filtering posts by community: ${community}`); // Debug log
            
            // Clear search if it exists
            if (STATE.filters.search) {
                STATE.filters.search = '';
                const searchInput = document.getElementById('post-search');
                if (searchInput) searchInput.value = '';
            }
            
            // Reload posts
            loadInitialPosts();
        });
    });
}

// Initialize Infinite Scroll
function initializeInfiniteScroll() {
    const sentinel = document.querySelector('.scroll-sentinel');
    if (!sentinel) return;

    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting && !STATE.loading) {
            loadMorePosts();
        }
    });

    observer.observe(sentinel);
}

// Initialize Real-time Updates
function initializeRealTimeUpdates() {
    // This can be implemented later with WebSocket or Server-Sent Events
    // For now, we can periodically check for new posts
    setInterval(() => {
        if (!STATE.loading && STATE.page === 1) {
            loadInitialPosts();
        }
    }, 60000); // Check every minute
}

// Function to refresh the feed
function refreshFeed() {
    const postsContainer = document.querySelector('.posts-container');
    
    // Clear existing posts
    postsContainer.innerHTML = '';
    
    // Check if there are any posts
    if (STATE.posts.size === 0) {
        const noPostsMessage = document.createElement('div');
        noPostsMessage.className = 'no-posts-message';
        noPostsMessage.innerHTML = `
            <i class="far fa-frown"></i>
            <p>No posts found matching your criteria</p>
        `;
        postsContainer.appendChild(noPostsMessage);
        return;
    }

    // Sort posts based on current filter
    const sortedPosts = [...STATE.posts.values()].sort((a, b) => {
        switch (STATE.filters.sortBy) {
            case 'popular':
                return (b.votes || 0) - (a.votes || 0);
            case 'discussed':
                return (b.commentCount || 0) - (a.commentCount || 0);
            default: // 'latest'
                return new Date(b.timestamp) - new Date(a.timestamp);
        }
    });

    // Create and append post elements
    sortedPosts.forEach(post => {
        const postElement = createPostElement(post);
        postsContainer.appendChild(postElement);
    });
    
    attachPostEventListeners();
}

// Attach Post Event Listeners
function attachPostEventListeners() {
    document.querySelectorAll('.feed-post').forEach(post => {
        // Get the post ID
        const postId = post.dataset.postId;
        
        // Add click handler to the post overlay
        const postOverlay = post.querySelector('.post-overlay');
        if (postOverlay) {
            postOverlay.addEventListener('click', (e) => {
                e.stopPropagation();
                openCommentModal(postId);
            });
        }
        
        // Vote button event listeners
        post.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', handleVote);
        });
        
        // Author click event listener for profile view
        const authorLink = post.querySelector('.author-name');
        if (authorLink) {
            authorLink.addEventListener('click', (e) => {
                e.stopPropagation();
                const userId = e.target.dataset.userId;
                if (userId) {
                    viewUserProfile(userId);
                }
            });
        }
        
        // Community click event listener
        const communityLink = post.querySelector('.post-community');
        if (communityLink) {
            communityLink.addEventListener('click', (e) => {
                e.stopPropagation();
                const community = e.target.dataset.communityId;
                if (community) {
                    // Set community filter and reload posts
                    STATE.filters.community = community;
                    loadInitialPosts();
                    
                    // Update active community in sidebar
                    document.querySelectorAll('.sidebar-item').forEach(item => {
                        item.classList.remove('active');
                        if (item.dataset.community === community) {
                            item.classList.add('active');
                        }
                    });
                }
            });
        }
        
        // We don't need to attach events to these elements anymore since they have direct event listeners:
        // - options-btn
        // - save-btn
        // - comments-btn
        // - edit-post-btn
        // - delete-post-btn
        // - save-post-btn
        // - report-post-btn
    });
}

// Format Timestamp
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
}

// Update handleVote function to use the correct URL
async function handleVote(e) {
    e.preventDefault(); // Prevent any default button behavior
    const voteButton = e.currentTarget;
    const postElement = voteButton.closest('.feed-post');
    const postId = postElement.dataset.postId;
    const voteType = voteButton.dataset.vote;
    const voteCount = postElement.querySelector('.vote-count');
    
    try {
        // Check if user is logged in
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user || !user.user_id) {
            showNotification('Please login to vote', 'error');
            return;
        }

        // Send vote to server
        const response = await fetch(`${BASE_URL}/api/posts/vote_post.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: parseInt(postId),
                user_id: parseInt(user.user_id),
                vote_type: voteType
            })
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        
        if (data.status === 'success') {
            // Update vote count
            voteCount.textContent = data.new_vote_count;
            
            // Update button states
            const upvoteBtn = postElement.querySelector('.upvote');
            const downvoteBtn = postElement.querySelector('.downvote');
            
            upvoteBtn.classList.remove('active');
            downvoteBtn.classList.remove('active');
            
            if (data.user_vote === 'up') {
                upvoteBtn.classList.add('active');
            } else if (data.user_vote === 'down') {
                downvoteBtn.classList.add('active');
            }
            
            showNotification(data.message, 'success');
        } else {
            throw new Error(data.message || 'Failed to vote');
        }
    } catch (error) {
        console.error('Error voting:', error);
        showNotification(error.message, 'error');
        // Remove active state if vote fails
        voteButton.classList.remove('active');
    }
}

// Update openCommentModal function to return a Promise
async function openCommentModal(postId) {
    return new Promise(async (resolve, reject) => {
    const modal = document.getElementById('postDetailModal');
    const postDetailContainer = modal.querySelector('.post-detail-container');
    const commentsContainer = modal.querySelector('.comments-container');
    const commentForm = modal.querySelector('.comment-form');

    try {
        // Get post details
        const post = STATE.posts.get(parseInt(postId));
        if (!post) {
            throw new Error('Post not found');
        }

        // Display post in modal
        postDetailContainer.innerHTML = createPostElement(post).outerHTML;
        
        // Find and set up the options button in the modal
        const modalOptionsBtn = postDetailContainer.querySelector('.options-btn');
        const modalOptionsDropdown = postDetailContainer.querySelector('.options-dropdown');
        if (modalOptionsBtn && modalOptionsDropdown) {
            // Set up the options button again since we lost the event listeners when using innerHTML
            setupOptionsButton(modalOptionsBtn, modalOptionsDropdown, post);
            
            // Get the options container to ensure the dropdown is correctly positioned
            const modalOptionsContainer = postDetailContainer.querySelector('.options-container');
            if (modalOptionsContainer) {
                modalOptionsContainer.style.position = 'absolute';
                modalOptionsContainer.style.top = '10px';
                modalOptionsContainer.style.right = '10px';
                modalOptionsContainer.style.zIndex = '100';
            }
            
            // Re-attach event listeners to the dropdown buttons
            const isAuthor = post.user_id === STATE.currentUser?.user_id;
            
            if (isAuthor) {
                // Set up edit and delete buttons
                const editBtn = modalOptionsDropdown.querySelector('.edit-post-btn');
                if (editBtn) {
                    editBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        editPost(post.id);
                        modalOptionsDropdown.style.display = 'none';
                    });
                }
                
                const deleteBtn = modalOptionsDropdown.querySelector('.delete-post-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        deletePost(post.id);
                        modal.style.display = 'none';
                    });
                }
            } else {
                // Set up save and report buttons
                const saveBtn = modalOptionsDropdown.querySelector('.save-post-btn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isSaved = saveBtn.dataset.saved === 'true';
                        savePost(post.id, isSaved);
                        modalOptionsDropdown.style.display = 'none';
                    });
                }
                
                const reportBtn = modalOptionsDropdown.querySelector('.report-post-btn');
                if (reportBtn) {
                    reportBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        reportPost(post.id);
                        modalOptionsDropdown.style.display = 'none';
                    });
                }
            }
        }
        
        // Reattach event listeners to the save button in the modal
        const modalSaveBtn = postDetailContainer.querySelector('.save-btn');
        if (modalSaveBtn) {
            modalSaveBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log("Modal save button clicked");
                const isSaved = modalSaveBtn.querySelector('i').classList.contains('fas');
                console.log("Modal current saved state:", isSaved);
                savePost(post.id, isSaved);
            });
        }

        // Reattach event listeners to the comments button in the modal
        const modalCommentsBtn = postDetailContainer.querySelector('.comments-btn');
        if (modalCommentsBtn) {
            modalCommentsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Scroll to comments section
                commentsContainer.scrollIntoView({behavior: 'smooth'});
            });
        }

        // Attach vote event listeners to the modal's vote buttons
        const modalVoteButtons = postDetailContainer.querySelectorAll('.vote-btn');
        modalVoteButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                await handleVote(e);
                // Update the main feed's corresponding post after voting
                const mainFeedPost = document.querySelector(`.feed-post[data-post-id="${postId}"]`);
                if (mainFeedPost) {
                    const updatedPost = STATE.posts.get(parseInt(postId));
                    mainFeedPost.outerHTML = createPostElement(updatedPost).outerHTML;
                    attachPostEventListeners();
                }
            });
        });

        // Fetch comments
        const response = await fetch(`${BASE_URL}/api/comments/comments.php?post_id=${postId}`);
        const result = await response.json();

        if (result.status === 'success') {
            // Display comments
            commentsContainer.innerHTML = '';
                
                // Group comments by parent_id for hierarchical display
                const topLevelComments = result.data.filter(comment => !comment.parent_id);
                const replies = result.data.filter(comment => comment.parent_id);
                
                // First add all top-level comments
                topLevelComments.forEach(comment => {
                commentsContainer.appendChild(createCommentElement(comment));
            });
                
                // Then add replies under their parent comments
                replies.forEach(reply => {
                    const parentComment = document.querySelector(`.comment[data-comment-id="${reply.parent_id}"]`);
                    if (parentComment) {
                        const replyElement = createCommentElement(reply);
                        parentComment.insertAdjacentElement('afterend', replyElement);
                    } else {
                        // If parent not found (shouldn't happen), add at the end
                        commentsContainer.appendChild(createCommentElement(reply));
                    }
                });
                
                // Attach event listeners to comment actions
                attachCommentEventListeners(commentsContainer, postId);
        }

        // Show modal
        modal.style.display = 'block';

        // Handle comment submission
        commentForm.onsubmit = async (e) => {
            e.preventDefault();
            await submitComment(postId, commentForm);
        };

        // Handle modal close
        const closeBtn = modal.querySelector('.close-modal');
        closeBtn.onclick = () => {
            modal.style.display = 'none';
                resolve(); // Resolve the promise when modal is closed
        };

        window.onclick = (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                    resolve(); // Resolve the promise when modal is closed
            }
        };
            
            resolve(); // Resolve the promise when everything is loaded

    } catch (error) {
        console.error('Error opening comment modal:', error);
        showNotification('Failed to load comments', 'error');
            reject(error); // Reject the promise on error
    }
    });
}

function createCommentElement(comment) {
    const commentElement = document.createElement('div');
    commentElement.className = 'comment';
    commentElement.dataset.commentId = comment.id;
    
    // Check if current user is the author of the comment
    const currentUser = JSON.parse(localStorage.getItem('user')) || {};
    const isAuthor = currentUser.user_id === comment.user_id;
    
    // Determine if this is a reply (has parent_id) and add appropriate class
    if (comment.parent_id) {
        commentElement.classList.add('comment-reply');
    }
    
    commentElement.innerHTML = `
        <div class="comment-header">
            <img src="${comment.avatar_url || 'assets/images/default-avatar.png'}" alt="${comment.author}" class="comment-avatar" data-user-id="${comment.user_id}">
            <div class="comment-meta">
                <strong class="comment-author" data-user-id="${comment.user_id}">${comment.author}</strong>
                <span class="comment-time">${formatTimestamp(comment.timestamp)}</span>
            </div>
            ${isAuthor ? `
            <div class="comment-actions-dropdown">
                <button class="dropdown-toggle"><i class="fas fa-ellipsis-v"></i></button>
                <div class="dropdown-menu">
                    <button class="edit-comment-btn" data-comment-id="${comment.id}"><i class="fas fa-edit"></i> Edit</button>
                    <button class="delete-comment-btn" data-comment-id="${comment.id}"><i class="fas fa-trash-alt"></i> Delete</button>
                </div>
            </div>` : ''}
        </div>
        <div class="comment-content">${comment.content}</div>
        <div class="comment-actions">
            <button class="reply-comment-btn" data-comment-id="${comment.id}" data-post-id="${comment.post_id}">
                <i class="fas fa-reply"></i> Reply
            </button>
        </div>
        <div class="reply-form-container" id="reply-form-${comment.id}" style="display: none;">
            <form class="reply-form comment-form">
                <textarea placeholder="Write your reply..." required></textarea>
                <div class="form-actions">
                    <button type="button" class="cancel-reply-btn">Cancel</button>
                    <button type="submit" class="submit-reply-btn">Reply</button>
                </div>
            </form>
        </div>
    `;
    
    // Add click event listener to the author name and avatar
    const authorName = commentElement.querySelector('.comment-author');
    const authorAvatar = commentElement.querySelector('.comment-avatar');
    
    if (authorName) {
        authorName.addEventListener('click', (e) => {
            const userId = e.target.dataset.userId;
            if (userId) {
                viewUserProfile(userId);
            }
        });
    }
    
    if (authorAvatar) {
        authorAvatar.addEventListener('click', (e) => {
            const userId = e.target.dataset.userId;
            if (userId) {
                viewUserProfile(userId);
            }
        });
    }
    
    // Add reply button event listener
    const replyBtn = commentElement.querySelector('.reply-comment-btn');
    if (replyBtn) {
        replyBtn.addEventListener('click', (e) => {
            const commentId = e.target.dataset.commentId;
            const replyFormContainer = document.getElementById(`reply-form-${commentId}`);
            if (replyFormContainer) {
                // Hide all other reply forms first
                document.querySelectorAll('.reply-form-container').forEach(form => {
                    form.style.display = 'none';
                });
                replyFormContainer.style.display = 'block';
                
                // Focus on the textarea
                const textarea = replyFormContainer.querySelector('textarea');
                if (textarea) textarea.focus();
                
                // Handle cancel button
                const cancelBtn = replyFormContainer.querySelector('.cancel-reply-btn');
                if (cancelBtn) {
                    cancelBtn.onclick = () => {
                        replyFormContainer.style.display = 'none';
                    };
                }
                
                // Handle form submission
                const replyForm = replyFormContainer.querySelector('.reply-form');
                if (replyForm) {
                    replyForm.onsubmit = async (e) => {
                        e.preventDefault();
                        await submitReply(comment.post_id, commentId, replyForm);
                    };
                }
            }
        });
    }
    
    return commentElement;
}

async function submitComment(postId, form) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('Please login to comment');
        }

        const content = form.querySelector('textarea').value.trim();
        if (!content) {
            throw new Error('Comment cannot be empty');
        }

        const response = await fetch(`${BASE_URL}/api/comments/comments.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                user_id: user.user_id,
                content: content
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Add new comment to the container
            const commentsContainer = document.querySelector('.comments-container');
            commentsContainer.insertBefore(
                createCommentElement(result.data),
                commentsContainer.firstChild
            );

            // Clear form
            form.reset();

            // Update comment count in the post
            const post = STATE.posts.get(parseInt(postId));
            if (post) {
                // Use the comment_count from the API response if available
                post.commentCount = result.comment_count || (post.commentCount || 0) + 1;
                const postElement = document.querySelector(`[data-post-id="${postId}"]`);
                if (postElement) {
                    const commentCount = postElement.querySelector('.comments-count');
                    if (commentCount) {
                        commentCount.textContent = post.commentCount;
                    }
                }
            }

            showNotification('Comment added successfully', 'success');
        } else {
            throw new Error(result.message || 'Failed to add comment');
        }
    } catch (error) {
        console.error('Error submitting comment:', error);
        showNotification(error.message, 'error');
    }
}

// Function to view user profile
function viewUserProfile(userId) {
    window.location.href = `profile.html?user_id=${userId}`;
}

// Function to edit post
async function editPost(postId) {
    try {
        // Get the post data
        const post = STATE.posts.get(parseInt(postId));
        if (!post) {
            throw new Error('Post not found');
        }
        
        // Create edit modal
        const editModal = document.createElement('div');
        editModal.className = 'modal';
        editModal.id = 'editPostModal';
        
        editModal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Post</h3>
                    <span class="close-modal">&times;</span>
                </div>
                <form id="edit-post-form">
                    <div class="form-group">
                        <label for="edit-post-caption">Caption (Optional)</label>
                        <input type="text" id="edit-post-caption" value="${post.caption || ''}">
                    </div>
                    <div class="form-group">
                        <label for="edit-post-content">Content</label>
                        <textarea id="edit-post-content" required>${post.content || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit-post-community">Community</label>
                        <select id="edit-post-community" required>
                            <option value="general" ${post.community === 'general' ? 'selected' : ''}>General</option>
                            <option value="technology" ${post.community === 'technology' ? 'selected' : ''}>Technology</option>
                            <option value="science" ${post.community === 'science' ? 'selected' : ''}>Science</option>
                            <option value="arts" ${post.community === 'arts' ? 'selected' : ''}>Arts</option>
                            <option value="sports" ${post.community === 'sports' ? 'selected' : ''}>Sports</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-button">Update Post</button>
                </form>
            </div>
        `;
        
        // Add modal to the body
        document.body.appendChild(editModal);
        
        // Show modal
        editModal.style.display = 'block';
        
        // Close modal when clicking on X
        const closeBtn = editModal.querySelector('.close-modal');
        closeBtn.onclick = () => {
            editModal.remove();
        };
        
        // Close modal when clicking outside
        window.onclick = (e) => {
            if (e.target === editModal) {
                editModal.remove();
            }
        };
        
        // Handle form submission
        const form = document.getElementById('edit-post-form');
        form.onsubmit = async (e) => {
            e.preventDefault();
            
            const caption = document.getElementById('edit-post-caption').value.trim();
            const content = document.getElementById('edit-post-content').value.trim();
            const community = document.getElementById('edit-post-community').value;
            
            if (!content) {
                showNotification('Content cannot be empty', 'error');
                return;
            }
            
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user) {
                    throw new Error('You must be logged in to edit a post');
                }
                
                // Send request to update post
                const response = await fetch(`${BASE_URL}/api/posts/edit_post.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: parseInt(postId),
                        user_id: parseInt(user.user_id),
                        caption,
                        content,
                        community
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Update post in state
                    STATE.posts.set(parseInt(postId), data.data);
                    
                    // Refresh feed to show updated post
                    refreshFeed();
                    
                    // Close modal
                    editModal.remove();
                    
                    showNotification('Post updated successfully', 'success');
                } else {
                    throw new Error(data.message || 'Failed to update post');
                }
            } catch (error) {
                console.error('Error updating post:', error);
                showNotification(error.message, 'error');
            }
        };
    } catch (error) {
        console.error('Error editing post:', error);
        showNotification(error.message, 'error');
    }
}

// Function to create a styled alert/confirmation modal
function createConfirmModal(title, message, confirmAction, cancelAction = null) {
    const modal = document.createElement('div');
    modal.className = 'modal confirm-modal';
    
    modal.innerHTML = `
        <div class="modal-content confirm-modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn">Cancel</button>
                <button class="confirm-btn">Confirm</button>
            </div>
        </div>
    `;
    
    // Add modal to the body
    document.body.appendChild(modal);
    
    // Show modal
    modal.style.display = 'block';
    
    // Handle close button
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.onclick = () => {
        modal.remove();
        if (cancelAction) cancelAction();
    };
    
    // Handle cancel button
    const cancelBtn = modal.querySelector('.cancel-btn');
    cancelBtn.onclick = () => {
        modal.remove();
        if (cancelAction) cancelAction();
    };
    
    // Handle confirm button
    const confirmBtn = modal.querySelector('.confirm-btn');
    confirmBtn.onclick = () => {
        modal.remove();
        confirmAction();
    };
    
    // Close modal when clicking outside
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.remove();
            if (cancelAction) cancelAction();
        }
    };
}

// Function to delete post
async function deletePost(postId) {
    createConfirmModal(
        'Delete Post',
        'Are you sure you want to delete this post? This action cannot be undone.',
        async () => {
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user) {
                    throw new Error('You must be logged in to delete a post');
                }
                
                // Send request to delete post
                const response = await fetch(`${BASE_URL}/api/posts/delete_post.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: parseInt(postId),
                        user_id: parseInt(user.user_id)
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Remove post from state
                    STATE.posts.delete(parseInt(postId));
                    
                    // Refresh feed to remove deleted post
                    refreshFeed();
                    
                    // Close the post detail modal if it's open
                    const postDetailModal = document.getElementById('postDetailModal');
                    if (postDetailModal && postDetailModal.style.display === 'block') {
                        postDetailModal.style.display = 'none';
                    }
                    
                    showNotification('Post deleted successfully', 'success');
                } else {
                    throw new Error(data.message || 'Failed to delete post');
                }
            } catch (error) {
                console.error('Error deleting post:', error);
                showNotification(error.message, 'error');
            }
        }
    );
}

// Function to edit comment
async function editComment(commentId, postId) {
    try {
        // Get the comment element
        const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
        if (!commentElement) {
            throw new Error('Comment not found');
        }
        
        // Get the current content
        const contentElement = commentElement.querySelector('.comment-content');
        const currentContent = contentElement.textContent;
        
        // Replace content with editable textarea
        contentElement.innerHTML = `
            <form class="edit-comment-form">
                <textarea class="edit-comment-textarea" required>${currentContent}</textarea>
                <div class="form-actions">
                    <button type="button" class="cancel-edit-btn">Cancel</button>
                    <button type="submit" class="save-edit-btn">Save</button>
                </div>
            </form>
        `;
        
        // Focus on textarea
        const textarea = contentElement.querySelector('textarea');
        textarea.focus();
        
        // Handle cancel button
        const cancelBtn = contentElement.querySelector('.cancel-edit-btn');
        cancelBtn.onclick = () => {
            contentElement.textContent = currentContent;
        };
        
        // Handle form submission
        const form = contentElement.querySelector('.edit-comment-form');
        form.onsubmit = async (e) => {
            e.preventDefault();
            
            const newContent = textarea.value.trim();
            if (!newContent) {
                showNotification('Comment cannot be empty', 'error');
                return;
            }
            
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user) {
                    throw new Error('You must be logged in to edit a comment');
                }
                
                // Send request to update comment
                const response = await fetch(`${BASE_URL}/api/comments/edit_comment.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_id: parseInt(commentId),
                        user_id: parseInt(user.user_id),
                        content: newContent
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Update comment content
                    contentElement.textContent = newContent;
                    
                    showNotification('Comment updated successfully', 'success');
                } else {
                    throw new Error(data.message || 'Failed to update comment');
                }
            } catch (error) {
                console.error('Error updating comment:', error);
                showNotification(error.message, 'error');
                contentElement.textContent = currentContent; // Restore original content on error
            }
        };
    } catch (error) {
        console.error('Error editing comment:', error);
        showNotification(error.message, 'error');
    }
}

// Function to delete comment
async function deleteComment(commentId, postId) {
    createConfirmModal(
        'Delete Comment',
        'Are you sure you want to delete this comment? This action cannot be undone.',
        async () => {
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user) {
                    throw new Error('You must be logged in to delete a comment');
                }
                
                // Send request to delete comment
                const response = await fetch(`${BASE_URL}/api/comments/delete_comment.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_id: parseInt(commentId),
                        user_id: parseInt(user.user_id)
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Remove comment element
                    const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
                    if (commentElement) {
                        commentElement.remove();
                    }
                    
                    // Update comment count in the post
                    const post = STATE.posts.get(parseInt(postId));
                    if (post) {
                        post.commentCount = data.comment_count;
                        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
                        if (postElement) {
                            const commentCount = postElement.querySelector('.comments-count');
                            if (commentCount) {
                                commentCount.textContent = post.commentCount;
                            }
                        }
                    }
                    
                    showNotification('Comment deleted successfully', 'success');
                } else {
                    throw new Error(data.message || 'Failed to delete comment');
                }
            } catch (error) {
                console.error('Error deleting comment:', error);
                showNotification(error.message, 'error');
            }
        }
    );
}

// Function to submit a reply to a comment
async function submitReply(postId, parentCommentId, form) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('Please login to reply');
        }

        const content = form.querySelector('textarea').value.trim();
        if (!content) {
            throw new Error('Reply cannot be empty');
        }

        const response = await fetch(`${BASE_URL}/api/comments/reply_comment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: parseInt(postId),
                user_id: parseInt(user.user_id),
                content: content,
                parent_id: parseInt(parentCommentId)
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Add new reply to the container
            const parentComment = document.querySelector(`.comment[data-comment-id="${parentCommentId}"]`);
            if (parentComment) {
                const replyElement = createCommentElement(result.data);
                parentComment.insertAdjacentElement('afterend', replyElement);
            }

            // Clear form and hide it
            form.reset();
            const replyFormContainer = document.getElementById(`reply-form-${parentCommentId}`);
            if (replyFormContainer) {
                replyFormContainer.style.display = 'none';
            }

            // Update comment count in the post
            const post = STATE.posts.get(parseInt(postId));
            if (post) {
                post.commentCount = result.comment_count;
                const postElement = document.querySelector(`[data-post-id="${postId}"]`);
                if (postElement) {
                    const commentCount = postElement.querySelector('.comments-count');
                    if (commentCount) {
                        commentCount.textContent = post.commentCount;
                    }
                }
            }

            showNotification('Reply added successfully', 'success');
        } else {
            throw new Error(result.message || 'Failed to add reply');
        }
    } catch (error) {
        console.error('Error submitting reply:', error);
        showNotification(error.message, 'error');
    }
}

// Function to attach event listeners to comment actions
function attachCommentEventListeners(container, postId) {
    // Edit comment buttons
    container.querySelectorAll('.edit-comment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const commentId = btn.dataset.commentId;
            editComment(commentId, postId);
        });
    });
    
    // Delete comment buttons
    container.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const commentId = btn.dataset.commentId;
            deleteComment(commentId, postId);
        });
    });
    
    // Reply buttons already handled in createCommentElement
    
    // Dropdown toggles
    container.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            const dropdownMenu = toggle.nextElementSibling;
            
            // Close all other open dropdowns first
            container.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                }
            });
            
            dropdownMenu.classList.toggle('show');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!toggle.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        });
    });
}

// Function to save or unsave a post
async function savePost(postId, isSaved) {
    try {
        console.log("Save post called with postId:", postId, "isSaved:", isSaved);
        
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to save posts');
        }
        
        // Determine action based on current state
        const action = isSaved ? 'unsave' : 'save';
        
        // Send request to server
        console.log(`Sending ${action} request to server for post ID: ${postId}`);
        const response = await fetch(`${BASE_URL}/api/posts/save_post.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: parseInt(postId),
                user_id: parseInt(user.user_id),
                action: action
            })
        });
        
        const data = await response.json();
        console.log("Server response:", data);
        
        if (data.status === 'success') {
            // Update post in state
            const post = STATE.posts.get(parseInt(postId));
            if (post) {
                post.is_saved = !isSaved;
            }
            
            // Update UI elements
            const feedPost = document.querySelector(`.feed-post[data-post-id="${postId}"]`);
            if (feedPost) {
                // Update main save button
                const saveBtn = feedPost.querySelector('.save-btn');
                if (saveBtn) {
                    const saveIcon = saveBtn.querySelector('i');
                    if (isSaved) {
                        saveIcon.classList.remove('fas');
                        saveIcon.classList.add('far');
                        saveBtn.innerHTML = '';
                        saveBtn.appendChild(saveIcon);
                        saveBtn.appendChild(document.createTextNode(' Save'));
                    } else {
                        saveIcon.classList.remove('far');
                        saveIcon.classList.add('fas');
                        saveBtn.innerHTML = '';
                        saveBtn.appendChild(saveIcon);
                        saveBtn.appendChild(document.createTextNode(' Saved'));
                    }
                }
                
                // Update dropdown save button if exists
                const dropdownSaveBtn = feedPost.querySelector('.save-post-btn');
                if (dropdownSaveBtn) {
                    dropdownSaveBtn.textContent = isSaved ? 'Save' : 'Unsave';
                    dropdownSaveBtn.dataset.saved = isSaved ? 'false' : 'true';
                }
            }
            
            // Also update the save button in the modal if it exists
            const modal = document.getElementById('postDetailModal');
            if (modal && modal.style.display === 'block') {
                const modalPost = modal.querySelector(`.feed-post[data-post-id="${postId}"]`);
                if (modalPost) {
                    // Update modal save button
                    const modalSaveBtn = modalPost.querySelector('.save-btn');
                    if (modalSaveBtn) {
                        const modalSaveIcon = modalSaveBtn.querySelector('i');
                        if (isSaved) {
                            modalSaveIcon.classList.remove('fas');
                            modalSaveIcon.classList.add('far');
                            modalSaveBtn.innerHTML = '';
                            modalSaveBtn.appendChild(modalSaveIcon);
                            modalSaveBtn.appendChild(document.createTextNode(' Save'));
                        } else {
                            modalSaveIcon.classList.remove('far');
                            modalSaveIcon.classList.add('fas');
                            modalSaveBtn.innerHTML = '';
                            modalSaveBtn.appendChild(modalSaveIcon);
                            modalSaveBtn.appendChild(document.createTextNode(' Saved'));
                        }
                    }
                    
                    // Update modal dropdown save button if exists
                    const modalDropdownSaveBtn = modalPost.querySelector('.save-post-btn');
                    if (modalDropdownSaveBtn) {
                        modalDropdownSaveBtn.textContent = isSaved ? 'Save' : 'Unsave';
                        modalDropdownSaveBtn.dataset.saved = isSaved ? 'false' : 'true';
                    }
                }
            }
            
            // Show success notification
            showNotification(
                isSaved ? 'Post removed from saved items' : 'Post saved successfully', 
                'success'
            );
        } else {
            throw new Error(data.message || 'Failed to save post');
        }
    } catch (error) {
        console.error('Error saving post:', error);
        showNotification(error.message, 'error');
    }
}

// Function to report post
function reportPost(postId) {
    const modalContent = `
        <form id="report-post-form">
            <div class="form-group">
                <label for="report-reason">Reason for reporting</label>
                <select id="report-reason" required>
                    <option value="">Select a reason</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Harassment</option>
                    <option value="violence">Violence</option>
                    <option value="misinformation">Misinformation</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="report-details">Additional details (optional)</label>
                <textarea id="report-details"></textarea>
            </div>
        </form>
    `;
    
    const modal = createConfirmModal(
        'Report Post', 
        modalContent,
        async () => {
            const reason = document.getElementById('report-reason').value;
            const details = document.getElementById('report-details').value.trim();
            
            if (!reason) {
                showNotification('Please select a reason for reporting', 'error');
                return false; // Don't close the modal
            }
            
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user) {
                    throw new Error('You must be logged in to report a post');
                }
                
                // Here you would normally send the report to the server
                // For now, we'll just show a success message
                showNotification('Post reported successfully. Our team will review it.', 'success');
                
                // In a real implementation, you would send a request like this:
                /*
                const response = await fetch(`${BASE_URL}/api/report_post.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: parseInt(postId),
                        user_id: parseInt(user.user_id),
                        reason: reason,
                        details: details
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showNotification('Post reported successfully', 'success');
                    return true; // Close the modal
                } else {
                    throw new Error(data.message || 'Failed to report post');
                }
                */
                
                return true; // Close the modal
            } catch (error) {
                console.error('Error reporting post:', error);
                showNotification(error.message, 'error');
                return false; // Don't close the modal
            }
        },
        () => {
            // Cancel action
            return true; // Close the modal
        }
    );
    
    // Change the confirm button text
    const confirmBtn = modal.querySelector('.confirm-btn');
    if (confirmBtn) {
        confirmBtn.textContent = 'Submit Report';
    }
}

// Search Posts
async function searchPosts() {
    try {
        STATE.loading = true;
        STATE.page = 1; // Reset to first page
        const user = JSON.parse(localStorage.getItem('user'));
        
        const queryParams = new URLSearchParams({
            query: STATE.filters.search,
            sortBy: STATE.filters.sortBy,
            community: STATE.filters.community,
            page: STATE.page,
            user_id: user ? user.user_id : ''
        });

        console.log(`Searching posts with params: query=${STATE.filters.search}, sortBy=${STATE.filters.sortBy}, community=${STATE.filters.community}`);

        const response = await fetch(`${BASE_URL}/api/posts/search_posts.php?${queryParams}`);
        const data = await response.json();

        if (data.status === 'success') {
            console.log(`Found ${data.data.length} posts matching search with sort: ${STATE.filters.sortBy}`);
            
            // Clear existing posts before adding new ones
            STATE.posts.clear();
            data.data.forEach(post => STATE.posts.set(post.id, post));
            refreshFeed();
            return true; // Return success
        } else {
            throw new Error(data.message || 'No posts found matching your search');
        }
    } catch (error) {
        console.error('Error searching posts:', error);
        showNotification(error.message, 'error');
        return false; // Return failure
    } finally {
        STATE.loading = false;
    }
}

// Initialize Search Feature
function initializeSearchFeature() {
    const searchInput = document.getElementById('post-search');
    const searchButton = document.getElementById('search-btn');

    // Handle search button click
    searchButton.addEventListener('click', function() {
        const searchQuery = searchInput.value.trim();
        if (searchQuery !== '') {
            STATE.filters.search = searchQuery;
            searchPosts();
        } else {
            STATE.filters.search = '';
            loadInitialPosts(); // If search is empty, load normal posts
        }
    });

    // Handle enter key in search input
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const searchQuery = searchInput.value.trim();
            if (searchQuery !== '') {
                STATE.filters.search = searchQuery;
                searchPosts();
            } else {
                STATE.filters.search = '';
                loadInitialPosts(); // If search is empty, load normal posts
            }
        }
    });

    // Clear search when input is cleared
    searchInput.addEventListener('input', function() {
        if (this.value.trim() === '' && STATE.filters.search !== '') {
            STATE.filters.search = '';
            loadInitialPosts();
        }
    });
}

// Function to set up options button functionality
function setupOptionsButton(optionsBtn, optionsDropdown, post) {
    // Remove existing event listeners (if any)
    const newOptionsBtn = optionsBtn.cloneNode(true);
    optionsBtn.parentNode.replaceChild(newOptionsBtn, optionsBtn);
    
    newOptionsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault();
        
        // Add visual feedback when clicked
        newOptionsBtn.style.opacity = '0.7';
        setTimeout(() => {
            newOptionsBtn.style.opacity = '1';
        }, 150);
        
        // Toggle dropdown visibility
        if (optionsDropdown.style.display === 'block') {
            optionsDropdown.style.display = 'none';
        } else {
            // Close all open dropdowns first
            document.querySelectorAll('.options-dropdown').forEach(dropdown => {
                if (dropdown !== optionsDropdown) {
                    dropdown.style.display = 'none';
                }
            });
            
            // Ensure proper styling and positioning
            optionsDropdown.style.display = 'block';
            optionsDropdown.style.position = 'absolute';
            optionsDropdown.style.zIndex = '9999';
            optionsDropdown.style.top = '100%'; // Position right below the button
            optionsDropdown.style.right = '0';  // Align to the right edge
            optionsDropdown.style.background = '#fff';
            optionsDropdown.style.border = '1px solid #ddd';
            optionsDropdown.style.borderRadius = '4px';
            optionsDropdown.style.padding = '5px 0';
            optionsDropdown.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            optionsDropdown.style.minWidth = '120px';
            optionsDropdown.style.width = '150px';
            
            // Add click outside handler to close dropdown
            setTimeout(() => {
                const handleOutsideClick = (event) => {
                    if (!newOptionsBtn.contains(event.target) && !optionsDropdown.contains(event.target)) {
                        optionsDropdown.style.display = 'none';
                        document.removeEventListener('click', handleOutsideClick);
                    }
                };
                document.addEventListener('click', handleOutsideClick);
            }, 0);
        }
    });
    
    return newOptionsBtn;
}