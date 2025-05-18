// Base URL for API calls
const BASE_URL = window.location.origin + '/bracula';

// Global state
let state = {
    posts: [],
    currentPost: null,
    loading: true,
    filter: 'all' // Default filter
};

// DOM Elements
const postsContainer = document.getElementById('postsContainer');
const emptyStateMessage = document.getElementById('emptyStateMessage');
const createPostBtn = document.getElementById('createPostBtn');
const createPostModal = document.getElementById('createPostModal');
const closeCreatePostModal = document.getElementById('closeCreatePostModal');
const submitPostBtn = document.getElementById('submitPost');
const postDetailModal = document.getElementById('postDetailModal');
const closePostDetailModal = document.getElementById('closePostDetailModal');
const commentForm = document.getElementById('commentForm');
const filterOptions = document.querySelectorAll('.filter-option');

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    initializePage();
});

// Initialize page components
function initializePage() {
    loadSavedPosts();
    initializeCreatePostFeature();
    initializeFilterOptions();
    
    // Close modals on click outside
    window.addEventListener('click', (e) => {
        if (e.target === createPostModal) {
            createPostModal.style.display = 'none';
        }
        if (e.target === postDetailModal) {
            postDetailModal.style.display = 'none';
        }
    });
    
    // Close post detail modal
    if (closePostDetailModal) {
        closePostDetailModal.addEventListener('click', () => {
            postDetailModal.style.display = 'none';
        });
    }
    
    // Handle comment submission
    if (commentForm) {
        commentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const commentContent = document.getElementById('commentContent').value.trim();
            if (commentContent && state.currentPost) {
                submitComment(state.currentPost.post_id, commentForm);
            }
        });
    }
}

// Initialize filter options
function initializeFilterOptions() {
    if (filterOptions) {
        filterOptions.forEach(option => {
            option.addEventListener('click', () => {
                // Update active class
                filterOptions.forEach(btn => btn.classList.remove('active'));
                option.classList.add('active');
                
                // Update filter state
                state.filter = option.dataset.filter;
                
                // Apply filter
                applyFilter();
            });
        });
    }
}

// Apply current filter to posts
function applyFilter() {
    if (!state.posts || state.posts.length === 0) return;
    
    let filteredPosts = [...state.posts];
    
    switch (state.filter) {
        case 'recent':
            filteredPosts.sort((a, b) => {
                const dateA = new Date(a.created_at);
                const dateB = new Date(b.created_at);
                return dateB - dateA;
            });
            break;
        case 'popular':
            filteredPosts.sort((a, b) => {
                return (b.net_votes || 0) - (a.net_votes || 0);
            });
            break;
        case 'all':
        default:
            // Keep original order
            break;
    }
    
    // Update UI with filtered posts
    renderPosts(filteredPosts);
}

// Render posts to the UI
function renderPosts(posts) {
    postsContainer.innerHTML = '';
    
    if (posts.length === 0) {
        emptyStateMessage.style.display = 'block';
        return;
    }
    
    emptyStateMessage.style.display = 'none';
    
    posts.forEach(post => {
        const postElement = createPostElement(post);
        postsContainer.appendChild(postElement);
    });
    
    attachPostEventListeners();
}

// Initialize the create post feature
function initializeCreatePostFeature() {
    // Open modal on button click
    if (createPostBtn) {
        createPostBtn.addEventListener('click', () => {
            createPostModal.style.display = 'block';
        });
    }
    
    // Close modal on close button click
    if (closeCreatePostModal) {
        closeCreatePostModal.addEventListener('click', () => {
            createPostModal.style.display = 'none';
        });
    }
    
    // Handle post submission
    if (submitPostBtn) {
        submitPostBtn.addEventListener('click', async () => {
            const content = document.getElementById('postContent').value.trim();
            const community = document.getElementById('community').value;
            
            if (!content) {
                showNotification('Please enter post content', 'error');
                return;
            }
            
            if (!community) {
                showNotification('Please select a community', 'error');
                return;
            }
            
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user) {
                    throw new Error('You must be logged in to create a post');
                }
                
                // Make API request to create post (reusing the feed.js functionality)
                const response = await fetch(`${BASE_URL}/api/posts/create_post.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: user.user_id,
                        content: content,
                        community: community
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showNotification('Post created successfully', 'success');
                    createPostModal.style.display = 'none';
                    document.getElementById('postContent').value = '';
                    document.getElementById('community').value = '';
                    
                    // Redirect to feed to see the new post
                    window.location.href = 'feed.html';
                } else {
                    throw new Error(data.message || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error creating post:', error);
                showNotification(error.message, 'error');
            }
        });
    }
}

// Load saved posts from API
async function loadSavedPosts() {
    try {
        state.loading = true;
        updateUI();
        
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to view saved posts');
        }
        
        // Fetch the saved posts
        console.log('Fetching saved posts for user:', user.user_id);
        const response = await fetch(`${BASE_URL}/api/posts/get_saved_posts.php?user_id=${user.user_id}`);
        
        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Check content type to ensure it's JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Response is not JSON:', await response.text());
            throw new Error('Server returned non-JSON response. Please check server configuration.');
        }
        
        const data = await response.json();
        
        console.log('API response:', data);
        
        if (data.status === 'success') {
            // Validate that posts are returned and have post_id values
            if (Array.isArray(data.saved_posts)) {
                console.log('Received', data.saved_posts.length, 'saved posts');
                
                // Validate each post has a post_id
                data.saved_posts.forEach(post => {
                    if (!post.post_id) {
                        console.warn('Post without post_id:', post);
                    }
                });
                
                // Filter out any posts without post_id
                state.posts = data.saved_posts.filter(post => post.post_id);
            } else {
                console.error('Saved posts is not an array:', data.saved_posts);
                state.posts = [];
            }
            
            state.loading = false;
            updateUI();
        } else {
            throw new Error(data.message || 'Failed to load saved posts');
        }
    } catch (error) {
        console.error('Error loading saved posts:', error);
        showNotification(error.message, 'error');
        state.loading = false;
        state.posts = [];
        updateUI();
    }
}

// Update the UI based on the current state
function updateUI() {
    if (state.loading) {
        postsContainer.innerHTML = `
            <div class="loading-indicator">
                <i class="fas fa-spinner fa-pulse"></i>
                <p>Loading your saved posts...</p>
            </div>
        `;
        emptyStateMessage.style.display = 'none';
    } else if (state.posts.length === 0) {
        postsContainer.innerHTML = '';
        emptyStateMessage.style.display = 'block';
    } else {
        applyFilter(); // Apply current filter and render posts
    }
}

// Create a post element
function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.classList.add('feed-post');
    postElement.dataset.postId = post.post_id;
    
    const currentUser = JSON.parse(localStorage.getItem('user'));
    const isCurrentUserPost = currentUser && parseInt(currentUser.user_id) === parseInt(post.user_id);
    
    postElement.innerHTML = `
        <div class="vote-section">
            <button class="vote-btn upvote ${post.user_vote === 'upvote' ? 'active' : ''}" data-vote="upvote">
                <i class="fas fa-arrow-up"></i>
            </button>
            <span class="vote-count">${post.net_votes || 0}</span>
            <button class="vote-btn downvote ${post.user_vote === 'downvote' ? 'active' : ''}" data-vote="downvote">
                <i class="fas fa-arrow-down"></i>
            </button>
        </div>
        <div class="post-content">
            <div class="post-header">
                <img src="${post.avatar_url || 'https://avatar.iran.liara.run/public'}" alt="${post.author}" class="post-avatar" data-user-id="${post.user_id}">
                <div class="post-meta">
                    <div class="post-author">
                        <span class="author-name" data-user-id="${post.user_id}">${post.author}</span>
                        <span class="post-community">in ${post.community}</span>
                    </div>
                    <span class="post-time">${post.created_at}</span>
                </div>
                <div class="post-options">
                    <button class="options-btn">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="options-dropdown dropdown-menu">
                        <button class="save-post-btn" data-post-id="${post.post_id}" data-saved="${post.is_saved ? 'true' : 'false'}">
                            <i class="fas fa-bookmark"></i> Unsave Post
                        </button>
                        ${isCurrentUserPost ? `
                            <button class="edit-post-btn" data-post-id="${post.post_id}">
                                <i class="fas fa-edit"></i> Edit Post
                            </button>
                            <button class="delete-post-btn" data-post-id="${post.post_id}">
                                <i class="fas fa-trash-alt"></i> Delete Post
                            </button>
                        ` : `
                            <button class="report-post-btn" data-post-id="${post.post_id}">
                                <i class="fas fa-flag"></i> Report Post
                            </button>
                        `}
                    </div>
                </div>
            </div>
            <h3 class="post-title">${post.title || ''}</h3>
            <p class="post-text">${post.content}</p>
            ${post.image_url ? `
                <div class="post-image">
                    <img src="${post.image_url}" alt="Post Image">
                </div>
            ` : ''}
            <div class="post-actions">
                <button class="action-btn comments-btn">
                    <i class="far fa-comment"></i>
                    <span class="comments-count">${post.comment_count || 0} Comments</span>
                </button>
            </div>
        </div>
    `;
    
    return postElement;
}

// Attach event listeners to posts
function attachPostEventListeners() {
    document.querySelectorAll('.feed-post').forEach(post => {
        // Make the entire post clickable to redirect to post page
        post.addEventListener('click', (e) => {
            // Only trigger if the click is directly on the post or post-content
            // and not on any interactive elements
            if (!e.target.closest('.vote-btn') && 
                !e.target.closest('.options-btn') && 
                !e.target.closest('.options-dropdown') && 
                !e.target.closest('.action-btn')) {
                const postId = post.dataset.postId;
                if (postId) {
                    // Redirect to feed.html with post_id parameter
                    window.location.href = `${BASE_URL}/html/feed.html?post_id=${postId}`;
                    console.log('Opening post with ID:', postId);
                } else {
                    console.error('Post ID not found in post element');
                    showNotification('Error: Could not open post details', 'error');
                }
            }
        });
        
        // Vote button event listeners
        post.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', handleVote);
        });
        
        // Comment button event listener
        const commentBtn = post.querySelector('.comments-btn');
        if (commentBtn) {
            commentBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent triggering the post click
                const postId = post.dataset.postId;
                if (postId) {
                    // Redirect to feed.html with post_id parameter
                    window.location.href = `${BASE_URL}/html/feed.html?post_id=${postId}`;
                    console.log('Opening post with ID:', postId);
                } else {
                    console.error('Post ID not found in post element');
                    showNotification('Error: Could not open post details', 'error');
                }
            });
        }
        
        // Author name and avatar click event listeners for profile view
        const authorName = post.querySelector('.author-name');
        const authorAvatar = post.querySelector('.post-avatar');
        
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
        
        // Edit post button event listener
        const editPostBtn = post.querySelector('.edit-post-btn');
        if (editPostBtn) {
            editPostBtn.addEventListener('click', () => {
                const postId = editPostBtn.dataset.postId;
                editPost(postId);
            });
        }
        
        // Delete post button event listener
        const deletePostBtn = post.querySelector('.delete-post-btn');
        if (deletePostBtn) {
            deletePostBtn.addEventListener('click', () => {
                const postId = deletePostBtn.dataset.postId;
                deletePost(postId);
            });
        }
        
        // Save button event listener (direct save button in actions)
        const saveBtn = post.querySelector('.save-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent triggering the post click
                const postId = post.dataset.postId;
                const isSaved = (saveBtn.querySelector('i').classList.contains('fas'));
                savePost(postId, isSaved);
            });
        }
        
        // Save post button event listener (in dropdown)
        const savePostBtn = post.querySelector('.save-post-btn');
        if (savePostBtn) {
            savePostBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent triggering the post click
                const postId = savePostBtn.dataset.postId;
                const isSaved = savePostBtn.dataset.saved === 'true';
                savePost(postId, isSaved);
            });
        }
        
        // Report post button event listener
        const reportPostBtn = post.querySelector('.report-post-btn');
        if (reportPostBtn) {
            reportPostBtn.addEventListener('click', () => {
                const postId = reportPostBtn.dataset.postId;
                reportPost(postId);
            });
        }
        
        // Options dropdown toggle
        const dropdownToggle = post.querySelector('.options-btn');
        if (dropdownToggle) {
            dropdownToggle.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event bubbling
                const dropdownMenu = dropdownToggle.nextElementSibling;
                
                // Close all other open dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                    }
                });
                
                dropdownMenu.classList.toggle('show');
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function closeDropdown(e) {
                    if (!dropdownToggle.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            });
        }
    });
}

// Handle voting on posts
async function handleVote(e) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to vote');
        }
        
        const voteButton = e.currentTarget;
        const voteType = voteButton.dataset.vote;
        const postElement = voteButton.closest('.feed-post');
        const postId = postElement.dataset.postId;
        
        // Check if this button is already active (user already voted this way)
        const isActive = voteButton.classList.contains('active');
        
        // If button is active, we're removing the vote. If not, we're adding a vote
        const action = isActive ? 'remove' : 'add';
        
        const response = await fetch(`${BASE_URL}/api/posts/vote_post.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: user.user_id,
                post_id: postId,
                vote_type: voteType,
                action: action
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // Update UI based on the new vote count
            const voteCountElement = postElement.querySelector('.vote-count');
            voteCountElement.textContent = data.net_votes;
            
            // Toggle active state on vote buttons
            const upvoteBtn = postElement.querySelector('.upvote');
            const downvoteBtn = postElement.querySelector('.downvote');
            
            if (action === 'add') {
                // If adding a vote, remove active from the other button first
                if (voteType === 'upvote') {
                    downvoteBtn.classList.remove('active');
                } else {
                    upvoteBtn.classList.remove('active');
                }
                
                // Then add active to this button
                voteButton.classList.add('active');
            } else {
                // If removing a vote, just remove active from this button
                voteButton.classList.remove('active');
            }
        } else {
            throw new Error(data.message || 'Failed to vote');
        }
    } catch (error) {
        console.error('Error voting:', error);
        showNotification(error.message, 'error');
    }
}

// Update the openCommentModal function to fix the post ID validation
async function openCommentModal(postId) {
    try {
        // Ensure postId is valid
        if (!postId || postId === 'undefined' || postId === 'null') {
            throw new Error('Post ID is required');
        }
        
        // Convert postId to integer to ensure consistent comparison
        const numericPostId = parseInt(postId);
        if (isNaN(numericPostId)) {
            throw new Error('Invalid Post ID');
        }
        
        console.log('Opening comment modal for post ID:', numericPostId);
        
        // Find the post in state
        const post = state.posts.find(p => parseInt(p.post_id) === numericPostId);
        if (!post) {
            throw new Error('Post not found');
        }
        
        state.currentPost = post;
        
        // Create post detail element
        const postDetailContainer = document.getElementById('postDetailContainer');
        postDetailContainer.innerHTML = '';
        
        const postDetailElement = createPostElement(post);
        postDetailContainer.appendChild(postDetailElement);
        
        // Load comments
        const commentsContainer = document.getElementById('commentsContainer');
        commentsContainer.innerHTML = '<div class="loading">Loading comments...</div>';
        
        // Use the correct API endpoint path and parameter name
        const url = `${BASE_URL}/api/comments/comments.php?post_id=${numericPostId}`;
        console.log('Fetching comments from:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('Comments API response:', data);
        
        if (data.status === 'success') {
            commentsContainer.innerHTML = '';
            
            // Check for comments in both 'comments' and 'data' properties
            const comments = data.comments || data.data || [];
            
            if (!comments || comments.length === 0) {
                commentsContainer.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
            } else {
                console.log('Found', comments.length, 'comments');
                
                // Sort comments by timestamp (newest first)
                const sortedComments = comments.sort((a, b) => {
                    const dateA = new Date(b.timestamp || b.created_at);
                    const dateB = new Date(a.timestamp || a.created_at);
                    return dateA - dateB;
                });
                
                sortedComments.forEach(comment => {
                    const commentElement = createCommentElement(comment);
                    commentsContainer.appendChild(commentElement);
                });
                
                // Attach event listeners to comments
                attachCommentEventListeners(commentsContainer, numericPostId);
            }
        } else {
            throw new Error(data.message || 'Failed to load comments');
        }
        
        // Show modal
        postDetailModal.style.display = 'block';
        
        // Reset comment form
        document.getElementById('commentContent').value = '';
        
    } catch (error) {
        console.error('Error opening comment modal:', error);
        showNotification(error.message, 'error');
    }
}

// Unsave a post and remove it from the list
async function savePost(postId, isSaved) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to save posts');
        }
        
        // For saved posts page, we're always unsaving
        // Confirm before unsaving
        createConfirmModal(
            'Unsave Post',
            'Are you sure you want to remove this post from your saved items?',
            async () => {
                try {
                    const response = await fetch(`${BASE_URL}/api/posts/save_post.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            post_id: parseInt(postId),
                            user_id: parseInt(user.user_id),
                            action: 'unsave'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        showNotification('Post removed from saved items', 'success');
                        
                        // Remove the post from the state and update UI
                        state.posts = state.posts.filter(p => parseInt(p.post_id) !== parseInt(postId));
                        updateUI();
                    } else {
                        throw new Error(data.message || 'Failed to unsave post');
                    }
                    return true; // Close the modal
                } catch (error) {
                    console.error('Error unsaving post:', error);
                    showNotification(error.message, 'error');
                    return true; // Close the modal anyway
                }
            }
        );
    } catch (error) {
        console.error('Error saving post:', error);
        showNotification(error.message, 'error');
    }
}

// Function to delete a post
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
                    showNotification('Post deleted successfully', 'success');
                    
                    // Remove the post from the state and update UI
                    state.posts = state.posts.filter(p => parseInt(p.post_id) !== parseInt(postId));
                    updateUI();
                } else {
                    throw new Error(data.message || 'Failed to delete post');
                }
                return true; // Close the modal
            } catch (error) {
                console.error('Error deleting post:', error);
                showNotification(error.message, 'error');
                return true; // Close the modal anyway
            }
        }
    );
}

// Create a confirmation modal and return a promise
function createConfirmModal(title, message, confirmAction, cancelAction = null) {
    // Create modal element
    const modal = document.createElement('div');
    modal.classList.add('modal', 'confirm-modal');
    
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
    
    // Add modal to document body
    document.body.appendChild(modal);
    
    // Show modal
    modal.style.display = 'block';
    
    // Handle close button click
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.addEventListener('click', () => {
        if (cancelAction) {
            cancelAction();
        }
        modal.remove();
    });
    
    // Handle cancel button click
    const cancelBtn = modal.querySelector('.cancel-btn');
    cancelBtn.addEventListener('click', () => {
        if (cancelAction) {
            cancelAction();
        }
        modal.remove();
    });
    
    // Handle confirm button click
    const confirmBtn = modal.querySelector('.confirm-btn');
    confirmBtn.addEventListener('click', async () => {
        const shouldClose = await confirmAction();
        if (shouldClose !== false) {
            modal.remove();
        }
    });
    
    // Close on click outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            if (cancelAction) {
                cancelAction();
            }
            modal.remove();
        }
    });
    
    return modal;
}

// Function to view user profile
function viewUserProfile(userId) {
    window.location.href = `profile.html?user_id=${userId}`;
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

// Show a notification
function showNotification(message, type = 'info') {
    const notificationElement = document.createElement('div');
    notificationElement.classList.add('notification', `notification-${type}`);
    notificationElement.textContent = message;
    
    document.body.appendChild(notificationElement);
    
    // Automatically remove notification after 4 seconds
    setTimeout(() => {
        notificationElement.classList.add('fadeOut');
        setTimeout(() => {
            notificationElement.remove();
        }, 500);
    }, 4000);
}

// Create a comment element
function createCommentElement(comment) {
    try {
        if (!comment) {
            console.error('Cannot create element for undefined comment');
            return document.createElement('div');
        }
        
        console.log('Creating comment element for:', comment);
        
        const commentElement = document.createElement('div');
        commentElement.classList.add('comment');
        
        // Handle different API response formats
        const commentId = comment.comment_id || comment.id;
        if (!commentId) {
            console.warn('Comment has no ID:', comment);
        }
        
        commentElement.dataset.commentId = commentId;
        
        // Check if current user is the author of the comment
        const currentUser = JSON.parse(localStorage.getItem('user')) || {};
        const commentUserId = comment.user_id || comment.authorId;
        const isAuthor = currentUser.user_id && commentUserId && 
                         parseInt(currentUser.user_id) === parseInt(commentUserId);
        
        // Determine if this is a reply (has parent_id) and add appropriate class
        if (comment.parent_id) {
            commentElement.classList.add('comment-reply');
        }
        
        // Handle different API response formats
        const authorName = comment.author || comment.full_name || comment.username || 'Unknown User';
        const avatarUrl = comment.avatar_url || comment.avatar || 'https://avatar.iran.liara.run/public';
        const commentTime = comment.created_at || comment.timestamp || new Date().toISOString();
        const commentContent = comment.content || '';
        
        commentElement.innerHTML = `
            <div class="comment-header">
                <img src="${avatarUrl}" alt="${authorName}" class="comment-avatar" data-user-id="${commentUserId}">
                <div class="comment-meta">
                    <strong class="comment-author" data-user-id="${commentUserId}">${authorName}</strong>
                    <span class="comment-time">${commentTime}</span>
                </div>
                ${isAuthor ? `
                    <div class="comment-options">
                        <button class="comment-options-btn">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="comment-options-dropdown dropdown-menu">
                            <button class="edit-comment-btn" data-comment-id="${commentId}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="delete-comment-btn" data-comment-id="${commentId}">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="comment-content">
                <p>${commentContent}</p>
            </div>
            <div class="comment-actions">
                <button class="reply-comment-btn" data-comment-id="${commentId}">
                    <i class="fas fa-reply"></i> Reply
                </button>
            </div>
            <div class="reply-form-container" data-comment-id="${commentId}" style="display: none;">
                <form class="reply-form">
                    <textarea placeholder="Write a reply..." required></textarea>
                    <div class="form-actions">
                        <button type="button" class="cancel-reply-btn">Cancel</button>
                        <button type="submit" class="submit-reply-btn">Reply</button>
                    </div>
                </form>
            </div>
        `;
        
        return commentElement;
    } catch (error) {
        console.error('Error creating comment element:', error);
        return document.createElement('div');
    }
}

// Attach event listeners to comments
function attachCommentEventListeners(container, postId) {
    // Reply button event listeners
    container.querySelectorAll('.reply-comment-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const commentId = btn.dataset.commentId;
            const replyContainer = container.querySelector(`.reply-form-container[data-comment-id="${commentId}"]`);
            
            // Hide all other reply forms first
            container.querySelectorAll('.reply-form-container').forEach(form => {
                if (form !== replyContainer) {
                    form.style.display = 'none';
                }
            });
            
            // Toggle this reply form
            replyContainer.style.display = replyContainer.style.display === 'none' ? 'block' : 'none';
            
            // Focus on textarea if showing
            if (replyContainer.style.display === 'block') {
                replyContainer.querySelector('textarea').focus();
            }
        });
    });
    
    // Cancel reply button event listeners
    container.querySelectorAll('.cancel-reply-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const replyForm = btn.closest('.reply-form-container');
            replyForm.style.display = 'none';
        });
    });
    
    // Reply form submission event listeners
    container.querySelectorAll('.reply-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const commentId = form.closest('.reply-form-container').dataset.commentId;
            await submitReply(postId, commentId, form);
        });
    });
    
    // Comment author and avatar click event listeners
    container.querySelectorAll('.comment-author, .comment-avatar').forEach(el => {
        el.addEventListener('click', () => {
            const userId = el.dataset.userId;
            if (userId) {
                viewUserProfile(userId);
            }
        });
    });
    
    // Comment options dropdown toggle
    container.querySelectorAll('.comment-options-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const dropdown = btn.nextElementSibling;
            
            // Close all other dropdowns
            container.querySelectorAll('.comment-options-dropdown.show').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            
            // Toggle this dropdown
            dropdown.classList.toggle('show');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!btn.contains(e.target)) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        });
    });
    
    // Edit comment button event listeners
    container.querySelectorAll('.edit-comment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const commentId = btn.dataset.commentId;
            editComment(commentId, postId);
        });
    });
    
    // Delete comment button event listeners
    container.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const commentId = btn.dataset.commentId;
            deleteComment(commentId, postId);
        });
    });
}

// Submit a reply to a comment
async function submitReply(postId, parentCommentId, form) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to reply');
        }
        
        const content = form.querySelector('textarea').value.trim();
        if (!content) {
            throw new Error('Reply cannot be empty');
        }
        
        // Convert IDs to integers
        const numericPostId = parseInt(postId);
        const numericParentId = parseInt(parentCommentId);
        
        if (isNaN(numericPostId)) {
            throw new Error('Invalid Post ID');
        }
        
        if (isNaN(numericParentId)) {
            throw new Error('Invalid Parent Comment ID');
        }
        
        // Show loading state
        const submitBtn = form.querySelector('.submit-reply-btn');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        
        console.log('Submitting reply for post ID:', numericPostId, 'parent comment ID:', numericParentId);
        
        // Use the correct API endpoint
        const url = `${BASE_URL}/api/comments/comments.php`;
        console.log('Submitting to URL:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: numericPostId,
                user_id: parseInt(user.user_id),
                content: content,
                parent_id: numericParentId
            })
        });
        
        const data = await response.json();
        console.log('Reply submission response:', data);
        
        if (data.status === 'success') {
            showNotification('Reply added successfully', 'success');
            
            // Refresh comments
            openCommentModal(numericPostId);
        } else {
            throw new Error(data.message || 'Failed to add reply');
        }
    } catch (error) {
        console.error('Error submitting reply:', error);
        showNotification(error.message, 'error');
        
        // Reset submit button
        const submitBtn = form.querySelector('.submit-reply-btn');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Reply';
    }
}

// Submit a comment
async function submitComment(postId, form) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to comment');
        }
        
        const content = document.getElementById('commentContent').value.trim();
        if (!content) {
            throw new Error('Comment cannot be empty');
        }
        
        // Convert postId to integer
        const numericPostId = parseInt(postId);
        if (isNaN(numericPostId)) {
            throw new Error('Invalid Post ID');
        }
        
        // Show loading state
        const submitBtn = form.querySelector('.submit-comment');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        
        console.log('Submitting comment for post ID:', numericPostId);
        
        // Use the correct API endpoint
        const url = `${BASE_URL}/api/comments/comments.php`;
        console.log('Submitting to URL:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: numericPostId,
                user_id: parseInt(user.user_id),
                content: content
            })
        });
        
        const data = await response.json();
        console.log('Comment submission response:', data);
        
        if (data.status === 'success') {
            showNotification('Comment added successfully', 'success');
            
            // Clear form
            document.getElementById('commentContent').value = '';
            
            // Refresh comments
            openCommentModal(numericPostId);
        } else {
            throw new Error(data.message || 'Failed to add comment');
        }
    } catch (error) {
        console.error('Error submitting comment:', error);
        showNotification(error.message, 'error');
    } finally {
        // Reset submit button
        const submitBtn = form.querySelector('.submit-comment');
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText || 'Post Comment';
    }
}

// Edit a comment
async function editComment(commentId, postId) {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            throw new Error('You must be logged in to edit a comment');
        }
        
        // Find the comment element
        const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
        if (!commentElement) {
            throw new Error('Comment not found');
        }
        
        // Get the current comment content
        const commentContentElement = commentElement.querySelector('.comment-content p');
        const currentContent = commentContentElement.textContent;
        
        // Create edit form
        const editForm = document.createElement('form');
        editForm.classList.add('edit-comment-form');
        editForm.innerHTML = `
            <textarea required>${currentContent}</textarea>
            <div class="form-actions">
                <button type="button" class="cancel-edit-btn">Cancel</button>
                <button type="submit" class="submit-edit-btn">Save</button>
            </div>
        `;
        
        // Replace comment content with edit form
        const commentContent = commentElement.querySelector('.comment-content');
        commentContent.innerHTML = '';
        commentContent.appendChild(editForm);
        
        // Focus on textarea
        const textarea = editForm.querySelector('textarea');
        textarea.focus();
        
        // Position cursor at the end of the text
        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        
        // Cancel edit button event listener
        const cancelBtn = editForm.querySelector('.cancel-edit-btn');
        cancelBtn.addEventListener('click', () => {
            commentContent.innerHTML = `<p>${currentContent}</p>`;
        });
        
        // Submit edit form event listener
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const newContent = textarea.value.trim();
            if (!newContent) {
                showNotification('Comment cannot be empty', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = editForm.querySelector('.submit-edit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            try {
                // Use the correct API endpoint
                const url = `${BASE_URL}/api/comments/edit_comment.php`;
                console.log('Submitting to URL:', url);
                
                const response = await fetch(url, {
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
                console.log('Comment edit response:', data);
                
                if (data.status === 'success') {
                    showNotification('Comment updated successfully', 'success');
                    
                    // Update comment content
                    commentContent.innerHTML = `<p>${newContent}</p>`;
                } else {
                    throw new Error(data.message || 'Failed to update comment');
                }
            } catch (error) {
                console.error('Error updating comment:', error);
                showNotification(error.message, 'error');
                
                // Reset submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save';
            }
        });
    } catch (error) {
        console.error('Error editing comment:', error);
        showNotification(error.message, 'error');
    }
}

// Delete a comment
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
                
                // Use the correct API endpoint
                const url = `${BASE_URL}/api/comments/delete_comment.php`;
                console.log('Submitting to URL:', url);
                
                const response = await fetch(url, {
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
                console.log('Comment delete response:', data);
                
                if (data.status === 'success') {
                    showNotification('Comment deleted successfully', 'success');
                    
                    // Refresh comments
                    openCommentModal(postId);
                } else {
                    throw new Error(data.message || 'Failed to delete comment');
                }
                return true; // Close the modal
            } catch (error) {
                console.error('Error deleting comment:', error);
                showNotification(error.message, 'error');
                return true; // Close the modal anyway
            }
        }
    );
} 