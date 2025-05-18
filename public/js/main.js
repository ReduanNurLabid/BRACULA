// Post overlay functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to post overlays
    document.querySelectorAll('.post-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            const postId = this.closest('.feed-post').dataset.postId;
            // Navigate to post detail page
            window.location.href = `/post/${postId}`;
        });
    });
    
    // Prevent clicks on interactive elements from triggering the overlay
    document.querySelectorAll('.author-name, .post-avatar, .post-community, .action-btn, .vote-btn, .options-btn').forEach(element => {
        element.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Add click handlers for author name and community
    document.querySelectorAll('.author-name').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            window.location.href = `/profile/${userId}`;
        });
    });
    
    document.querySelectorAll('.post-community').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const communityId = this.dataset.communityId;
            window.location.href = `/community/${communityId}`;
        });
    });
}); 