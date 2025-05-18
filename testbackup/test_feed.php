<?php
require_once __DIR__ . '/setup_test_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Post.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Feature Test</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/BRACULA/test/index.php">BRACULA Tests</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="/BRACULA/test/test_feed.php">Feed Test</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_rideshare.php">Rideshare Test</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_feed_breakable.php">Feed Unit Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BRACULA/test/test_rideshare_breakable.php">Rideshare Unit Tests</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <h1>Feed Feature Test</h1>
    <p>This test verifies the functionality of the Post model and feed-related controllers.</p>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test 1: Create Post</h3>
        </div>
        <div class="card-body">
            <h4>Create a new post</h4>
            <form id="createPostForm">
                <div class="form-group mb-3">
                    <label for="title">Title:</label>
                    <input type="text" id="title" class="form-control" value="Test Post Title" required>
                </div>
                <div class="form-group mb-3">
                    <label for="content">Content:</label>
                    <textarea id="content" class="form-control" rows="4" required>This is a test post created for testing the feed feature.</textarea>
                </div>
                <div class="form-group mb-3">
                    <label for="category">Category:</label>
                    <select id="category" class="form-control">
                        <option value="general">General</option>
                        <option value="academic">Academic</option>
                        <option value="events">Events</option>
                        <option value="question">Question</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create Post</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="createPostResponse" class="bg-light p-3">No response yet</pre>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Test 2: Get Posts (Feed)</h3>
        </div>
        <div class="card-body">
            <h4>Retrieve posts for the feed</h4>
            <form id="getPostsForm">
                <div class="form-group mb-3">
                    <label for="limit">Number of posts:</label>
                    <input type="number" id="limit" class="form-control" value="5" min="1" max="20">
                </div>
                <div class="form-group mb-3">
                    <label for="categoryFilter">Filter by category (optional):</label>
                    <select id="categoryFilter" class="form-control">
                        <option value="">All Categories</option>
                        <option value="general">General</option>
                        <option value="academic">Academic</option>
                        <option value="events">Events</option>
                        <option value="question">Question</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Get Posts</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="getPostsResponse" class="bg-light p-3">No response yet</pre>
            </div>
            <div id="postsContainer" class="mt-3"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0">Test 3: Post Voting</h3>
        </div>
        <div class="card-body">
            <h4>Vote on a post</h4>
            <form id="voteForm">
                <div class="form-group mb-3">
                    <label for="postId">Post ID:</label>
                    <input type="number" id="postId" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>Vote Type:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="voteType" id="upvote" value="up" checked>
                        <label class="form-check-label" for="upvote">Upvote</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="voteType" id="downvote" value="down">
                        <label class="form-check-label" for="downvote">Downvote</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-info">Vote</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="voteResponse" class="bg-light p-3">No response yet</pre>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0">Test 4: Search Posts</h3>
        </div>
        <div class="card-body">
            <h4>Search for posts</h4>
            <form id="searchForm">
                <div class="form-group mb-3">
                    <label for="keywords">Search Keywords:</label>
                    <input type="text" id="keywords" class="form-control" value="test" required>
                </div>
                <button type="submit" class="btn btn-warning">Search</button>
            </form>
            <div class="mt-3">
                <h5>Response:</h5>
                <pre id="searchResponse" class="bg-light p-3">No response yet</pre>
            </div>
            <div id="searchResultsContainer" class="mt-3"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">Test 5: MVC Analysis</h3>
        </div>
        <div class="card-body">
            <h4>MVC Implementation Analysis</h4>
            <p>The feed feature follows the MVC pattern:</p>
            <ul>
                <li><strong>Model (Post.php):</strong> Handles data operations, business logic, and validation</li>
                <li><strong>Controllers (api/posts/):</strong> Process user requests, coordinate with the model, and prepare data for the view</li>
                <li><strong>Views (html/feed/):</strong> Present data to the user in a readable format</li>
            </ul>
            <p>Request flow:</p>
            <ol>
                <li>User request hits an endpoint like <code>api/get_posts.php</code></li>
                <li>This file redirects to the controller implementation in <code>api/posts/get_posts.php</code></li>
                <li>The controller instantiates the Post model and calls appropriate methods</li>
                <li>The controller processes the model's response and formats it for the view</li>
                <li>The view displays the feed data to the user</li>
            </ol>
        </div>
    </div>
</div>

<script>
document.getElementById('createPostForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
        title: document.getElementById('title').value,
        content: document.getElementById('content').value,
        category: document.getElementById('category').value
    };
    
    document.getElementById('createPostResponse').innerText = 'Sending request...';
    
    try {
        const response = await fetch('../api/create_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const responseText = await response.text();
        
        // Try to parse as JSON
        try {
            const data = JSON.parse(responseText);
            document.getElementById('createPostResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        } catch (e) {
            document.getElementById('createPostResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('createPostResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('getPostsForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const limit = document.getElementById('limit').value;
    const category = document.getElementById('categoryFilter').value;
    
    let url = `../api/get_posts.php?limit=${limit}&offset=0`;
    if (category) {
        url += `&category=${category}`;
    }
    
    document.getElementById('getPostsResponse').innerText = 'Fetching posts...';
    document.getElementById('postsContainer').innerHTML = '';
    
    try {
        const response = await fetch(url);
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            document.getElementById('getPostsResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
            
            // Display posts in a user-friendly format
            if (data.status === 'success' && data.posts) {
                const postsHtml = data.posts.map(post => `
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>${post.title}</strong> - by ${post.full_name}
                        </div>
                        <div class="card-body">
                            <p>${post.content}</p>
                            <small class="text-muted">Category: ${post.category} | Votes: ${post.votes} | Post ID: ${post.post_id}</small>
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('postsContainer').innerHTML = postsHtml;
            }
        } catch (e) {
            document.getElementById('getPostsResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('getPostsResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('voteForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const formData = {
        post_id: document.getElementById('postId').value,
        vote_type: document.querySelector('input[name="voteType"]:checked').value
    };
    
    document.getElementById('voteResponse').innerText = 'Sending vote...';
    
    try {
        const response = await fetch('../api/vote_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            document.getElementById('voteResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        } catch (e) {
            document.getElementById('voteResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('voteResponse').innerText = 'Error: ' + error.message;
    }
});

document.getElementById('searchForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const keywords = document.getElementById('keywords').value;
    
    document.getElementById('searchResponse').innerText = 'Searching...';
    document.getElementById('searchResultsContainer').innerHTML = '';
    
    try {
        const response = await fetch(`../api/search_posts.php?keywords=${encodeURIComponent(keywords)}`);
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            document.getElementById('searchResponse').innerText = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
            
            // Display search results
            if (data.status === 'success' && data.posts) {
                const postsHtml = data.posts.map(post => `
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>${post.title}</strong> - by ${post.full_name}
                        </div>
                        <div class="card-body">
                            <p>${post.content}</p>
                            <small class="text-muted">Category: ${post.category} | Votes: ${post.votes} | Post ID: ${post.post_id}</small>
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('searchResultsContainer').innerHTML = postsHtml;
            }
        } catch (e) {
            document.getElementById('searchResponse').innerText = 'Status: ' + response.status + '\n\nNon-JSON Response:\n' + responseText;
        }
    } catch (error) {
        document.getElementById('searchResponse').innerText = 'Error: ' + error.message;
    }
});

// Load first few posts on page load to populate the Post ID field
window.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch('../api/get_posts.php?limit=1');
        const data = await response.json();
        
        if (data.status === 'success' && data.posts && data.posts.length > 0) {
            document.getElementById('postId').value = data.posts[0].post_id;
        }
    } catch (error) {
        console.error('Error fetching initial post:', error);
    }
});
</script>

<footer class="bg-light py-3 mt-5">
    <div class="container">
        <p class="text-center text-muted">BRACULA Test Environment</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 