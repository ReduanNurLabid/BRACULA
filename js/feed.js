// Initialize the state
const STATE = {
    posts: new Map(),
    loading: false,
    page: 1,
    filters: {
        sortBy: 'latest',
        community: 'general',
    },
    currentUser: JSON.parse(localStorage.getItem('user')) || null
};

// Base URL for API calls
const BASE_URL = window.location.origin + '/BRACULA';

// Load Initial Posts
async function loadInitialPosts() {
    try {
        STATE.loading = true;
        const user = JSON.parse(localStorage.getItem('user'));
        
        const queryParams = new URLSearchParams({
            sortBy: STATE.filters.sortBy,
            community: STATE.filters.community,
            page: STATE.page,
            user_id: user ? user.user_id : ''
        });

        const response = await fetch(`${BASE_URL}/api/get_posts.php?${queryParams}`);
        const data = await response.json();

        if (data.status === 'success') {
            // Clear existing posts before adding new ones
            STATE.posts.clear();
            data.data.forEach(post => STATE.posts.set(post.id, post));
            refreshFeed();
        } else {
            throw new Error(data.message || 'Failed to load posts');
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        showNotification('Failed to load posts', 'error');
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
        
        const queryParams = new URLSearchParams({
            sortBy: STATE.filters.sortBy,
            community: STATE.filters.community,
            page: STATE.page
        });

        const response = await fetch(`${BASE_URL}/api/get_posts.php?${queryParams}`);
        const data = await response.json();

        if (data.status === 'success') {
            if (data.data.length === 0) {
                // No more posts to load
                return;
            }

            data.data.forEach(post => {
                if (!STATE.posts.has(post.id)) {
                STATE.posts.set(post.id, post);
                const postElement = createPostElement(post);
                    document.querySelector('.posts-container').appendChild(postElement);
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

// Create Post Element
function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.className = 'feed-post';
    postElement.dataset.postId = post.id;
    
    // Determine vote button classes based on user's vote
    const upvoteClass = post.user_vote === 'up' ? 'vote-btn upvote active' : 'vote-btn upvote';
    const downvoteClass = post.user_vote === 'down' ? 'vote-btn downvote active' : 'vote-btn downvote';
    
    postElement.innerHTML = `
        <div class="vote-section">
            <button class="${upvoteClass}" data-vote="up">
                <i class="fas fa-arrow-up"></i>
            </button>
            <span class="vote-count">${post.votes || 0}</span>
            <button class="${downvoteClass}" data-vote="down">
                <i class="fas fa-arrow-down"></i>
            </button>
        </div>
        <div class="post-content">
            <div class="post-header">
                <img src="${post.avatar_url || 'assets/images/default-avatar.png'}" 
                     alt="${post.author}" class="post-avatar">
                <div class="post-meta">
                    <div class="author-info">
                        <strong>${post.author}</strong>
                        <span class="post-community">${post.community}</span>
                    </div>
                    <div class="post-time">${formatTimestamp(post.timestamp)}</div>
                </div>
            </div>
            ${post.caption ? `<h3 class="post-title">${post.caption}</h3>` : ''}
            <div class="post-text">${post.content}</div>
            <div class="post-actions">
                <button class="action-btn comments-btn">
                    <i class="far fa-comment"></i>
                    <span class="comments-count">${post.commentCount || 0}</span> Comments
                </button>
            </div>
        </div>
    `;
    
    return postElement;
}


// Initialize Feed Page
document.addEventListener('DOMContentLoaded', () => {
    initializeState();
    initializeCreatePostFeature();
    initializePostActions();
    initializeFilters();
    initializeInfiniteScroll();
    initializeRealTimeUpdates();
    loadInitialPosts();
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
            const response = await fetch(`${BASE_URL}/api/update_user_activity.php`, {
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
            const response = await fetch(`${BASE_URL}/api/create_post.php`, {
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
    document.querySelector('.main-feed').addEventListener('click', async (e) => {
        const postElement = e.target.closest('.feed-post');
        if (!postElement) return;

        const postId = postElement.dataset.postId;
        
        // Handle comment button click
        if (e.target.closest('.comments-btn')) {
            // Add your comment handling logic here
            console.log('Comment button clicked for post:', postId);
        }
    });
}

// Initialize Filters
function initializeFilters() {
    const filterButtons = document.querySelectorAll('.post-filter-buttons button');
    const communityItems = document.querySelectorAll('.sidebar-item');

    // Sort filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
            
            // Update state and refresh feed
            STATE.filters.sortBy = button.dataset.sort;
            STATE.page = 1;
            STATE.posts.clear();
            loadInitialPosts();
        });
    });

    // Community filter items
    communityItems.forEach(item => {
        // Set general as active by default
        if (item.dataset.community === 'general') {
            item.classList.add('active');
        }
        
        item.addEventListener('click', () => {
            // Remove active class from all items
            communityItems.forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            item.classList.add('active');
            
            // Update state and refresh feed
            STATE.filters.community = item.dataset.community;
            STATE.page = 1;
            STATE.posts.clear();
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

// Refresh Feed
function refreshFeed() {
    const feedContainer = document.querySelector('.posts-container');
    if (!feedContainer) {
        console.error('Posts container not found');
        return;
    }

    feedContainer.innerHTML = ''; // Clear existing posts

    // Convert Map to Array and sort based on current filter
    const posts = Array.from(STATE.posts.values());
    
    switch(STATE.filters.sortBy) {
        case 'popular':
            posts.sort((a, b) => b.votes - a.votes);
            break;
        case 'discussed':
            posts.sort((a, b) => (b.commentCount || 0) - (a.commentCount || 0));
            break;
        default: // 'latest'
            posts.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
    }
    
    // Create and append post elements
    posts.forEach(post => {
        const postElement = createPostElement(post);
        feedContainer.appendChild(postElement);
    });
    
    // Reattach event listeners
    attachPostEventListeners();
}

// Attach Post Event Listeners
function attachPostEventListeners() {
    document.querySelectorAll('.feed-post').forEach(post => {
        // Vote buttons
        post.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', handleVote);
        });

        // Comment button
        post.querySelector('.comments-btn').addEventListener('click', () => {
            openCommentModal(post.dataset.postId);
        });
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
        const response = await fetch(`${BASE_URL}/api/vote_post.php`, {
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

// Add comment handling functions
async function openCommentModal(postId) {
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
        const response = await fetch(`${BASE_URL}/api/comments.php?post_id=${postId}`);
        const result = await response.json();

        if (result.status === 'success') {
            // Display comments
            commentsContainer.innerHTML = '';
            result.data.forEach(comment => {
                commentsContainer.appendChild(createCommentElement(comment));
            });
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
        };

        window.onclick = (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        };

    } catch (error) {
        console.error('Error opening comment modal:', error);
        showNotification('Failed to load comments', 'error');
    }
}

function createCommentElement(comment) {
    const commentDiv = document.createElement('div');
    commentDiv.className = 'comment';
    commentDiv.dataset.commentId = comment.comment_id;

    commentDiv.innerHTML = `
        <div class="comment-header">
            <img src="${comment.avatar_url || 'assets/images/default-avatar.png'}" 
                 alt="${comment.full_name}" class="comment-avatar">
            <div class="comment-meta">
                <strong>${comment.full_name}</strong>
                <span class="comment-time">${formatTimestamp(comment.created_at)}</span>
            </div>
        </div>
        <div class="comment-content">${comment.content}</div>
    `;

    return commentDiv;
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

        const response = await fetch(`${BASE_URL}/api/comments.php`, {
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
                post.commentCount = (post.commentCount || 0) + 1;
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

// ... rest of the existing code ...