// Base URL for API calls
const BASE_URL = window.location.origin;

// Global state
let state = {
    posts: [],
    currentPost: null,
    loading: true
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

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    initializePage();
});

// Initialize page components
function initializePage() {
    loadSavedPosts();
    initializeCreatePostFeature();
    
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
                const response = await fetch(`${BASE_URL}/BRACULA/api/create_post.php`, {
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
        
        const response = await fetch(`${BASE_URL}/BRACULA/api/get_saved_posts.php?user_id=${user.user_id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            state.posts = data.saved_posts;
            state.loading = false;
            updateUI();
        } else {
            throw new Error(data.message || 'Failed to load saved posts');
        }
    } catch (error) {
        console.error('Error loading saved posts:', error);
        showNotification(error.message, 'error');
        state.loading = false;
        updateUI();
    }
}

// Update the UI based on the current state
function updateUI() {
    if (state.loading) {
        postsContainer.innerHTML = `
            <div class="loading-indicator">
                <i class="fas fa-spinner fa-pulse"></i> Loading your saved posts...
            </div>
        `;
        emptyStateMessage.style.display = 'none';
    } else if (state.posts.length === 0) {
        postsContainer.innerHTML = '';
        emptyStateMessage.style.display = 'block';
    } else {
        postsContainer.innerHTML = '';
        emptyStateMessage.style.display = 'none';
        
        state.posts.forEach(post => {
            const postElement = createPostElement(post);
            postsContainer.appendChild(postElement);
        });
        
        attachPostEventListeners();
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
        // Make the entire post clickable to open comment modal
        post.addEventListener('click', (e) => {
            // Only trigger if the click is directly on the post or post-content
            // and not on any interactive elements
            if (!e.target.closest('.vote-btn') && 
                !e.target.closest('.options-btn') && 
                !e.target.closest('.options-dropdown') && 
                !e.target.closest('.action-btn')) {
                const postId = post.dataset.postId;
                openCommentModal(postId);
            }
        });
        
        // Vote button event listeners
        post.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', handleVote);
        });
        
        // Comment button event listener
        const commentBtn = post.querySelector('.comments-btn');
        if (commentBtn) {
            commentBtn.addEventListener('click', () => {
                const postId = post.dataset.postId;
                openCommentModal(postId);
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
        
        const response = await fetch(`${BASE_URL}/BRACULA/api/vote_post.php`, {
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

// Open the comment modal
async function openCommentModal(postId) {
    try {
        // Find the post in state
        const post = state.posts.find(p => parseInt(p.post_id) === parseInt(postId));
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
        
        const response = await fetch(`${BASE_URL}/BRACULA/api/get_comments.php?post_id=${postId}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            commentsContainer.innerHTML = '';
            
            if (data.comments.length === 0) {
                commentsContainer.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
            } else {
                // Sort comments by timestamp (newest first)
                const comments = data.comments.sort((a, b) => 
                    new Date(b.created_at) - new Date(a.created_at)
                );
                
                comments.forEach(comment => {
                    const commentElement = createCommentElement(comment);
                    commentsContainer.appendChild(commentElement);
                });
                
                // Attach event listeners to comments
                attachCommentEventListeners(commentsContainer, postId);
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
                    const response = await fetch(`${BASE_URL}/BRACULA/api/save_post.php`, {
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
                
                const response = await fetch(`${BASE_URL}/BRACULA/api/delete_post.php`, {
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