/**
 * Post Page JavaScript
 * Handles like/unlike functionality, comment interactions, and related posts
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize post page functionality
    initializeLikeButton();
    initializeCommentForm();
    initializeShareButton();
    initializeDeleteCommentButtons();
    loadRelatedPosts();
    
    console.log('Post page initialized');
});

/**
 * Initialize like/unlike button functionality
 */
function initializeLikeButton() {
    const likeBtn = document.querySelector('.like-btn');
    
    if (!likeBtn || likeBtn.tagName === 'A') {
        // Not logged in or not a button
        return;
    }
    
    // Use the enhanced AJAX like button from ajax.js
    // The enhanced version is automatically initialized
    
    // Add keyboard support
    likeBtn.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
}

/**
 * Initialize comment form functionality
 */
function initializeCommentForm() {
    const commentForm = document.getElementById('comment-form');
    const commentTextarea = document.getElementById('comment_content');
    const commentCounter = document.getElementById('comment-counter');
    const commentError = document.getElementById('comment-error');
    const submitBtn = document.getElementById('comment-submit-btn');
    
    if (!commentForm) return;
    
    // Character counter
    function updateCommentCounter() {
        const length = commentTextarea.value.length;
        commentCounter.textContent = length;
        
        if (length > 900) {
            commentCounter.style.color = 'var(--warning-orange)';
        } else if (length > 800) {
            commentCounter.style.color = 'var(--text-light)';
        } else {
            commentCounter.style.color = 'var(--success-green)';
        }
    }
    
    // Validation
    function validateComment() {
        const content = commentTextarea.value.trim();
        
        if (!content) {
            showCommentError('Comment content is required');
            return false;
        }
        
        if (content.length < 3) {
            showCommentError('Comment must be at least 3 characters long');
            return false;
        }
        
        if (content.length > 1000) {
            showCommentError('Comment must be less than 1000 characters');
            return false;
        }
        
        clearCommentError();
        return true;
    }
    
    function showCommentError(message) {
        commentTextarea.classList.add('error');
        commentError.textContent = message;
        commentError.style.display = 'block';
    }
    
    function clearCommentError() {
        commentTextarea.classList.remove('error');
        commentError.textContent = '';
        commentError.style.display = 'none';
    }
    
    function setCommentLoadingState(loading) {
        const btnText = submitBtn.querySelector('.btn-text');
        const spinner = submitBtn.querySelector('.spinner');
        
        if (loading) {
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            spinner.classList.remove('hidden');
            commentTextarea.disabled = true;
        } else {
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            spinner.classList.add('hidden');
            commentTextarea.disabled = false;
        }
    }
    
    // Event listeners
    commentTextarea.addEventListener('input', function() {
        updateCommentCounter();
        if (commentTextarea.classList.contains('error')) {
            validateComment();
        }
    });
    
    commentTextarea.addEventListener('blur', validateComment);
    
    // Form submission
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateComment()) {
            return;
        }
        
        setCommentLoadingState(true);
        
        // Submit form via AJAX
        const formData = new FormData(commentForm);
        
        fetch(commentForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                // Comment added successfully, redirect
                window.location.href = response.url;
                return null;
            }
            return response.text();
        })
        .then(html => {
            if (html) {
                // Parse response to check for errors
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const errorMessages = doc.querySelector('.alert-error');
                
                if (errorMessages) {
                    // Show server-side errors
                    const existingErrors = document.querySelector('.add-comment-form .alert-error');
                    if (existingErrors) {
                        existingErrors.innerHTML = errorMessages.innerHTML;
                        existingErrors.style.display = 'block';
                    } else {
                        // Create error messages element
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-error';
                        errorDiv.innerHTML = errorMessages.innerHTML;
                        commentForm.parentNode.insertBefore(errorDiv, commentForm);
                    }
                    
                    // Scroll to show errors
                    commentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        })
        .catch(error => {
            console.error('Comment submission error:', error);
            alert('An error occurred while posting your comment. Please try again.');
        })
        .finally(() => {
            setCommentLoadingState(false);
        });
    });
    
    // Auto-resize textarea
    commentTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
    
    // Initialize counter
    updateCommentCounter();
}

/**
 * Initialize share button functionality
 */
function initializeShareButton() {
    const shareBtn = document.querySelector('.share-btn');
    
    if (!shareBtn) return;
    
    shareBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const postId = this.dataset.postId;
        const postUrl = window.location.href;
        const postTitle = document.querySelector('.post-title').textContent;
        
        // Check if Web Share API is supported
        if (navigator.share) {
            navigator.share({
                title: postTitle,
                url: postUrl
            }).catch(error => {
                console.log('Share cancelled or failed:', error);
            });
        } else {
            // Fallback: copy to clipboard
            copyToClipboard(postUrl);
            
            // Show feedback
            const originalText = this.querySelector('.share-text').textContent;
            const shareText = this.querySelector('.share-text');
            shareText.textContent = 'Copied!';
            
            setTimeout(() => {
                shareText.textContent = originalText;
            }, 2000);
        }
    });
}

/**
 * Initialize delete comment buttons
 */
function initializeDeleteCommentButtons() {
    const deleteButtons = document.querySelectorAll('.delete-comment-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const commentId = this.dataset.commentId;
            
            if (!confirm('Are you sure you want to delete this comment?')) {
                return;
            }
            
            // Disable button
            this.disabled = true;
            const originalIcon = this.querySelector('i').className;
            this.querySelector('i').className = 'fas fa-spinner fa-spin';
            
            // Make delete request
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || 
                             document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch('api/delete-comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    comment_id: parseInt(commentId),
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove comment from DOM
                    const commentItem = this.closest('.comment-item');
                    commentItem.style.opacity = '0';
                    commentItem.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        commentItem.remove();
                        
                        // Update comment count
                        updateCommentCount(-1);
                        
                        // Check if no comments left
                        const remainingComments = document.querySelectorAll('.comment-item');
                        if (remainingComments.length === 0) {
                            showEmptyCommentsState();
                        }
                    }, 300);
                } else {
                    console.error('Delete comment failed:', data.error);
                    alert('Failed to delete comment. Please try again.');
                }
            })
            .catch(error => {
                console.error('Delete comment error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Restore button state
                this.disabled = false;
                this.querySelector('i').className = originalIcon;
            });
        });
    });
}

/**
 * Load related posts
 */
async function loadRelatedPosts() {
    const relatedPostsContainer = document.getElementById('related-posts');
    
    if (!relatedPostsContainer) return;
    
    // Get post data from the page
    const postId = new URLSearchParams(window.location.search).get('id');
    
    try {
        const data = await ajaxManager.get(`api/related-posts.php?post_id=${postId}&limit=3`, {
            loadingElement: relatedPostsContainer,
            loadingMessage: 'Loading related posts...'
        });
        
        if (data.success && data.posts.length > 0) {
            displayRelatedPosts(data.posts);
        } else {
            relatedPostsContainer.innerHTML = '<p class="empty-message">No related posts found.</p>';
        }
    } catch (error) {
        console.error('Error loading related posts:', error);
        relatedPostsContainer.innerHTML = '<p class="error-message">Failed to load related posts.</p>';
    }
}

/**
 * Display related posts
 */
function displayRelatedPosts(posts) {
    const relatedPostsContainer = document.getElementById('related-posts');
    
    const postsHtml = posts.map(post => `
        <div class="related-post-item">
            <h5 class="related-post-title">
                <a href="post.php?id=${post.id}">${escapeHtml(post.title)}</a>
            </h5>
            <div class="related-post-meta">
                <span class="post-author">by ${escapeHtml(post.username)}</span>
                <span class="post-time">${timeAgo(post.created_at)}</span>
            </div>
            <div class="related-post-stats">
                <span class="stat-item">
                    <i class="fas fa-heart"></i> ${post.likes_count}
                </span>
                <span class="stat-item">
                    <i class="fas fa-comment"></i> ${post.comments_count}
                </span>
            </div>
        </div>
    `).join('');
    
    relatedPostsContainer.innerHTML = postsHtml;
}

/**
 * Update comment count in the UI
 */
function updateCommentCount(delta) {
    // Update comment count in post actions
    const commentCountSpan = document.querySelector('.comment-count');
    if (commentCountSpan) {
        const currentCount = parseInt(commentCountSpan.textContent.replace(/,/g, ''));
        const newCount = Math.max(0, currentCount + delta);
        commentCountSpan.textContent = newCount.toLocaleString();
    }
    
    // Update comments section title
    const commentsTitle = document.querySelector('.comments-title');
    if (commentsTitle) {
        const currentText = commentsTitle.textContent;
        const currentCount = parseInt(currentText.match(/\((\d+)\)/)[1]);
        const newCount = Math.max(0, currentCount + delta);
        commentsTitle.innerHTML = `<i class="fas fa-comments"></i> Comments (${newCount})`;
    }
}

/**
 * Show empty comments state
 */
function showEmptyCommentsState() {
    const commentsList = document.querySelector('.comments-list');
    commentsList.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-comment-slash empty-icon"></i>
            <h3 class="empty-title">No comments yet</h3>
            <p class="empty-description">Be the first to share your thoughts!</p>
        </div>
    `;
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
}

/**
 * Utility functions
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
    return Math.floor(diffInSeconds / 2592000) + ' months ago';
}

/**
 * Smooth scroll to comments when comment button is clicked
 */
document.addEventListener('DOMContentLoaded', function() {
    const commentBtn = document.querySelector('.comment-btn');
    
    if (commentBtn) {
        commentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const commentsSection = document.getElementById('comments');
            if (commentsSection) {
                commentsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Focus on comment textarea if user is logged in
                const commentTextarea = document.getElementById('comment_content');
                if (commentTextarea) {
                    setTimeout(() => {
                        commentTextarea.focus();
                    }, 500);
                }
            }
        });
    }
});

/**
 * Handle URL hash for direct comment linking
 */
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#comments') {
        const commentsSection = document.getElementById('comments');
        if (commentsSection) {
            setTimeout(() => {
                commentsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        }
    }
});
/**
 *
 Helper functions for AJAX functionality
 */

/**
 * Append comments to the comments list
 */
function appendComments(comments) {
    const commentsList = document.querySelector('.comments-list');
    if (!commentsList) return;
    
    comments.forEach(comment => {
        const commentHtml = createCommentHTML(comment);
        commentsList.insertAdjacentHTML('beforeend', commentHtml);
    });
}

/**
 * Prepend new comments to the comments list
 */
function prependComments(comments) {
    const commentsList = document.querySelector('.comments-list');
    if (!commentsList) return;
    
    comments.forEach(comment => {
        const commentHtml = createCommentHTML(comment);
        commentsList.insertAdjacentHTML('afterbegin', commentHtml);
        
        // Add animation
        const newComment = commentsList.firstElementChild;
        newComment.classList.add('fade-in');
    });
}

/**
 * Create HTML for a comment
 */
function createCommentHTML(comment) {
    const currentUserId = window.currentUserId || 0;
    const canDelete = currentUserId == comment.user_id;
    
    return `
        <div class="comment-item" data-comment-id="${comment.id}">
            <div class="comment-header">
                <div class="comment-author">
                    <i class="fas fa-user-circle"></i>
                    <span class="author-name">${comment.username}</span>
                </div>
                <div class="comment-actions">
                    <span class="comment-time">${comment.time_ago}</span>
                    ${canDelete ? `
                        <button class="delete-comment-btn" data-comment-id="${comment.id}" title="Delete comment">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
            <div class="comment-content">
                <p>${comment.content}</p>
            </div>
        </div>
    `;
}

/**
 * Get the timestamp of the last comment
 */
function getLastCommentTime() {
    const comments = document.querySelectorAll('.comment-item');
    if (comments.length === 0) return new Date().toISOString();
    
    // Get the most recent comment's timestamp
    const lastComment = comments[comments.length - 1];
    const timeElement = lastComment.querySelector('.comment-time');
    
    // This would need to be enhanced to store actual timestamps
    // For now, return current time minus 1 minute
    const oneMinuteAgo = new Date(Date.now() - 60000);
    return oneMinuteAgo.toISOString();
}

/**
 * Show search results dropdown
 */
function showSearchResults(results) {
    let searchResults = document.getElementById('search-results');
    
    if (!searchResults) {
        const searchContainer = document.querySelector('.search-container');
        if (!searchContainer) return;
        
        searchResults = document.createElement('div');
        searchResults.id = 'search-results';
        searchResults.className = 'search-results';
        searchContainer.appendChild(searchResults);
    }
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">No results found</div>';
    } else {
        const resultsHtml = results.map(result => {
            if (result.type === 'post') {
                return `
                    <div class="search-result-item" onclick="window.location.href='${result.url}'">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-meta">
                            <span class="search-result-type">Post</span>
                            <span>by ${result.username}</span>
                            <span>in ${result.category}</span>
                            <span>${result.likes_count} likes</span>
                        </div>
                    </div>
                `;
            } else if (result.type === 'user') {
                return `
                    <div class="search-result-item" onclick="window.location.href='${result.url}'">
                        <div class="search-result-title">${result.username}</div>
                        <div class="search-result-meta">
                            <span class="search-result-type">User</span>
                        </div>
                    </div>
                `;
            }
        }).join('');
        
        searchResults.innerHTML = resultsHtml;
    }
    
    searchResults.classList.add('show');
}

/**
 * Hide search results dropdown
 */
function hideSearchResults() {
    const searchResults = document.getElementById('search-results');
    if (searchResults) {
        searchResults.classList.remove('show');
    }
}

/**
 * Append posts for infinite scroll
 */
function appendPosts(posts) {
    const postsContainer = document.querySelector('.posts-list, .forum-posts');
    if (!postsContainer) return;
    
    posts.forEach(post => {
        const postHtml = createPostHTML(post);
        postsContainer.insertAdjacentHTML('beforeend', postHtml);
    });
    
    // Re-initialize interactions for new posts
    initializePostCards();
}

/**
 * Create HTML for a post (simplified version)
 */
function createPostHTML(post) {
    return `
        <div class="post-card" tabindex="0">
            <div class="post-header">
                <div class="post-author">
                    <i class="fas fa-user-circle"></i>
                    <span class="author-name">${post.username}</span>
                </div>
                <div class="post-category">
                    <span class="category-tag" style="background-color: ${post.category_color || '#007bff'}">
                        ${post.category_name}
                    </span>
                </div>
            </div>
            <div class="post-content">
                <h3 class="post-title">
                    <a href="post.php?id=${post.id}" class="post-link">${post.title}</a>
                </h3>
                <p class="post-excerpt">${post.excerpt || ''}</p>
            </div>
            <div class="post-footer">
                <div class="post-meta">
                    <span class="post-time">${timeAgo(post.created_at)}</span>
                </div>
                <div class="post-stats">
                    <span class="stat-item">
                        <i class="fas fa-heart"></i> ${post.likes_count || 0}
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-comment"></i> ${post.comments_count || 0}
                    </span>
                </div>
            </div>
        </div>
    `;
}

/**
 * Enhanced delete comment functionality
 */
function initializeEnhancedDeleteComments() {
    // Re-initialize delete buttons for dynamically added comments
    const deleteButtons = document.querySelectorAll('.delete-comment-btn:not([data-initialized])');
    
    deleteButtons.forEach(button => {
        button.setAttribute('data-initialized', 'true');
        
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const commentId = this.dataset.commentId;
            
            if (!confirm('Are you sure you want to delete this comment?')) {
                return;
            }
            
            try {
                const data = await ajaxManager.post('api/delete-comment.php', {
                    comment_id: parseInt(commentId)
                }, {
                    loadingElement: this,
                    loadingMessage: 'Deleting...'
                });
                
                if (data.success) {
                    // Remove comment from DOM with animation
                    const commentItem = this.closest('.comment-item');
                    commentItem.classList.add('fade-out');
                    
                    setTimeout(() => {
                        commentItem.remove();
                        updateCommentCount(-1);
                        
                        // Check if no comments left
                        const remainingComments = document.querySelectorAll('.comment-item');
                        if (remainingComments.length === 0) {
                            showEmptyCommentsState();
                        }
                    }, 300);
                    
                    // Show success message
                    this.showToast('Comment deleted successfully', 'success');
                }
                
            } catch (error) {
                console.error('Delete comment error:', error);
                this.showToast('Failed to delete comment', 'error');
            }
        });
    });
}

// Initialize enhanced delete comments when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancedDeleteComments();
    
    // Re-initialize when new comments are added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                initializeEnhancedDeleteComments();
            }
        });
    });
    
    const commentsList = document.querySelector('.comments-list');
    if (commentsList) {
        observer.observe(commentsList, { childList: true, subtree: true });
    }
});