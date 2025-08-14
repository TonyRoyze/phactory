/**
 * AJAX Module for Dynamic Content Loading
 * Provides centralized AJAX functionality with loading states and error handling
 */

class AjaxManager {
    constructor() {
        this.activeRequests = new Map();
        this.loadingStates = new Map();
        this.retryAttempts = new Map();
        this.maxRetries = 3;
        this.retryDelay = 1000;
        this.autoCSRF = true;
    }
    
    /**
     * Get CSRF token from page
     */
    getCSRFToken() {
        return document.querySelector('input[name="csrf_token"]')?.value || 
               document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
               null;
    }
    
    /**
     * Add CSRF token to request data
     */
    addCSRFToken(data, options = {}) {
        if (options.autoCSRF !== false && this.autoCSRF) {
            const token = this.getCSRFToken();
            if (token) {
                if (data instanceof FormData) {
                    data.append('csrf_token', token);
                } else if (typeof data === 'object' && data !== null) {
                    data.csrf_token = token;
                } else if (typeof data === 'string') {
                    try {
                        const parsed = JSON.parse(data);
                        parsed.csrf_token = token;
                        return JSON.stringify(parsed);
                    } catch (e) {
                        // If not JSON, treat as form data
                        return data + (data.includes('&') ? '&' : '') + 'csrf_token=' + encodeURIComponent(token);
                    }
                }
            }
        }
        return data;
    }

    /**
     * Make an AJAX request with loading states and error handling
     */
    async request(url, options = {}) {
        const requestId = this.generateRequestId();
        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        };
        
        // Add CSRF token to POST requests
        if (config.method === 'POST' && config.body) {
            config.body = this.addCSRFToken(config.body, options);
        }

        // Show loading state if element provided
        if (options.loadingElement) {
            this.showLoadingState(options.loadingElement, options.loadingMessage);
        }

        try {
            // Cancel any existing request with same ID
            if (options.requestId && this.activeRequests.has(options.requestId)) {
                this.activeRequests.get(options.requestId).abort();
            }

            // Create abort controller for request cancellation
            const controller = new AbortController();
            config.signal = controller.signal;

            // Store active request
            const activeRequestId = options.requestId || requestId;
            this.activeRequests.set(activeRequestId, controller);

            // Make the request
            const response = await fetch(url, config);

            // Remove from active requests
            this.activeRequests.delete(activeRequestId);

            // Handle HTTP errors
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Parse JSON response
            const data = await response.json();

            // Handle API errors
            if (data.success === false) {
                throw new Error(data.error || 'API request failed');
            }

            // Clear retry attempts on success
            this.retryAttempts.delete(activeRequestId);

            return data;

        } catch (error) {
            // Handle retry logic
            if (options.retry !== false && !error.name === 'AbortError') {
                const retryCount = this.retryAttempts.get(requestId) || 0;
                
                if (retryCount < this.maxRetries) {
                    this.retryAttempts.set(requestId, retryCount + 1);
                    
                    // Wait before retry
                    await this.delay(this.retryDelay * (retryCount + 1));
                    
                    // Retry the request
                    return this.request(url, { ...options, requestId });
                }
            }

            // Handle different error types
            if (error.name === 'AbortError') {
                console.log('Request aborted:', url);
                return null;
            }

            console.error('AJAX request failed:', error);
            
            // Show error message if element provided
            if (options.errorElement) {
                this.showErrorState(options.errorElement, error.message);
            }

            throw error;

        } finally {
            // Hide loading state
            if (options.loadingElement) {
                this.hideLoadingState(options.loadingElement);
            }
        }
    }

    /**
     * GET request wrapper
     */
    async get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    }

    /**
     * POST request wrapper
     */
    async post(url, data, options = {}) {
        const config = {
            ...options,
            method: 'POST'
        };

        if (data instanceof FormData) {
            // Don't set Content-Type for FormData, let browser set it
            delete config.headers['Content-Type'];
            config.body = data;
        } else {
            config.body = JSON.stringify(data);
        }

        return this.request(url, config);
    }

    /**
     * Show loading state on element
     */
    showLoadingState(element, message = 'Loading...') {
        if (!element) return;

        const loadingId = this.generateRequestId();
        
        // Store original content
        const originalContent = {
            innerHTML: element.innerHTML,
            disabled: element.disabled,
            style: {
                opacity: element.style.opacity,
                pointerEvents: element.style.pointerEvents,
                cursor: element.style.cursor
            }
        };

        this.loadingStates.set(element, originalContent);

        // Apply loading state
        if (element.tagName === 'BUTTON') {
            element.disabled = true;
            element.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
        } else {
            element.style.opacity = '0.6';
            element.style.pointerEvents = 'none';
            element.style.cursor = 'wait';
            
            // Add loading overlay for containers
            if (element.classList.contains('card') || element.classList.contains('widget')) {
                this.addLoadingOverlay(element, message);
            }
        }

        // Add loading class
        element.classList.add('ajax-loading');
    }

    /**
     * Hide loading state from element
     */
    hideLoadingState(element) {
        if (!element || !this.loadingStates.has(element)) return;

        const originalContent = this.loadingStates.get(element);

        // Restore original content
        element.innerHTML = originalContent.innerHTML;
        element.disabled = originalContent.disabled;
        element.style.opacity = originalContent.style.opacity;
        element.style.pointerEvents = originalContent.style.pointerEvents;
        element.style.cursor = originalContent.style.cursor;

        // Remove loading class
        element.classList.remove('ajax-loading');

        // Remove loading overlay
        this.removeLoadingOverlay(element);

        // Clean up
        this.loadingStates.delete(element);
    }

    /**
     * Show error state on element
     */
    showErrorState(element, message) {
        if (!element) return;

        element.innerHTML = `
            <div class="ajax-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="error-message">${this.escapeHtml(message)}</span>
                <button class="retry-btn" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> Retry
                </button>
            </div>
        `;
        element.classList.add('ajax-error-state');
    }

    /**
     * Add loading overlay to container elements
     */
    addLoadingOverlay(element, message) {
        const overlay = document.createElement('div');
        overlay.className = 'ajax-loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <i class="fas fa-spinner fa-spin"></i>
                <span class="loading-message">${this.escapeHtml(message)}</span>
            </div>
        `;

        // Style the overlay
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: inherit;
        `;

        // Make parent relative if not already positioned
        const computedStyle = window.getComputedStyle(element);
        if (computedStyle.position === 'static') {
            element.style.position = 'relative';
        }

        element.appendChild(overlay);
    }

    /**
     * Remove loading overlay from element
     */
    removeLoadingOverlay(element) {
        const overlay = element.querySelector('.ajax-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Cancel active request
     */
    cancelRequest(requestId) {
        if (this.activeRequests.has(requestId)) {
            this.activeRequests.get(requestId).abort();
            this.activeRequests.delete(requestId);
        }
    }

    /**
     * Cancel all active requests
     */
    cancelAllRequests() {
        this.activeRequests.forEach(controller => controller.abort());
        this.activeRequests.clear();
    }

    /**
     * Generate unique request ID
     */
    generateRequestId() {
        return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Delay utility for retries
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global AJAX manager instance
window.ajaxManager = new AjaxManager();

/**
 * Real-time Content Updates Manager
 */
class RealTimeUpdates {
    constructor() {
        this.updateIntervals = new Map();
        this.isVisible = true;
        this.setupVisibilityHandling();
    }

    /**
     * Start real-time updates for an element
     */
    startUpdates(elementId, updateFunction, interval = 30000) {
        // Clear existing interval
        this.stopUpdates(elementId);

        // Start new interval
        const intervalId = setInterval(() => {
            if (this.isVisible) {
                updateFunction();
            }
        }, interval);

        this.updateIntervals.set(elementId, intervalId);
    }

    /**
     * Stop real-time updates for an element
     */
    stopUpdates(elementId) {
        if (this.updateIntervals.has(elementId)) {
            clearInterval(this.updateIntervals.get(elementId));
            this.updateIntervals.delete(elementId);
        }
    }

    /**
     * Stop all real-time updates
     */
    stopAllUpdates() {
        this.updateIntervals.forEach(intervalId => clearInterval(intervalId));
        this.updateIntervals.clear();
    }

    /**
     * Setup page visibility handling to pause updates when tab is not active
     */
    setupVisibilityHandling() {
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
        });

        // Pause updates when window loses focus
        window.addEventListener('blur', () => {
            this.isVisible = false;
        });

        window.addEventListener('focus', () => {
            this.isVisible = true;
        });
    }
}

// Create global real-time updates manager
window.realTimeUpdates = new RealTimeUpdates();

/**
 * Enhanced Statistics Updates
 */
function initializeRealTimeStats() {
    const statsWidget = document.querySelector('.stats-widget');
    if (!statsWidget) return;

    // Update stats every 2 minutes
    realTimeUpdates.startUpdates('community-stats', async () => {
        try {
            const data = await ajaxManager.get('api/stats.php', {
                requestId: 'stats-update'
            });

            if (data && data.success) {
                updateStatNumbers(data.stats);
            }
        } catch (error) {
            console.error('Failed to update stats:', error);
        }
    }, 120000);
}

/**
 * Enhanced Like Button with Real-time Updates
 */
function initializeEnhancedLikeButton() {
    const likeButtons = document.querySelectorAll('.like-btn[data-post-id]');
    
    likeButtons.forEach(button => {
        if (button.tagName !== 'BUTTON') return; // Skip non-interactive buttons

        button.addEventListener('click', async function(e) {
            e.preventDefault();

            const postId = parseInt(this.dataset.postId);
            const isLiked = this.dataset.liked === 'true';

            try {
                const data = await ajaxManager.post('api/like-post.php', {
                    post_id: postId,
                    action: isLiked ? 'unlike' : 'like'
                }, {
                    loadingElement: this,
                    loadingMessage: isLiked ? 'Unliking...' : 'Liking...'
                });

                if (data.success) {
                    // Update button state
                    this.dataset.liked = (!isLiked).toString();
                    
                    // Update button appearance
                    if (!isLiked) {
                        this.classList.add('liked');
                    } else {
                        this.classList.remove('liked');
                    }
                    
                    // Update like count with animation
                    const likeCount = this.querySelector('.like-count');
                    if (likeCount) {
                        animateNumberChange(likeCount, parseInt(likeCount.textContent.replace(/,/g, '')), data.like_count);
                    }
                    
                    // Update like text
                    const likeText = this.querySelector('.like-text');
                    if (likeText) {
                        likeText.textContent = !isLiked ? 'Liked' : 'Like';
                    }
                    
                    // Add success animation
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }

            } catch (error) {
                // Show user-friendly error message
                this.showToast('Failed to update like status. Please try again.', 'error');
            }
        });
    });
}

/**
 * Dynamic Comment Loading
 */
function initializeDynamicComments() {
    const commentsSection = document.getElementById('comments');
    if (!commentsSection) return;

    // Load more comments button
    const loadMoreBtn = document.getElementById('load-more-comments');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async function() {
            const postId = new URLSearchParams(window.location.search).get('id');
            const currentComments = document.querySelectorAll('.comment-item').length;

            try {
                const data = await ajaxManager.get(`api/comments.php?post_id=${postId}&offset=${currentComments}&limit=10`, {
                    loadingElement: this,
                    loadingMessage: 'Loading comments...'
                });

                if (data.success && data.comments.length > 0) {
                    appendComments(data.comments);
                    
                    // Hide button if no more comments
                    if (data.comments.length < 10) {
                        this.style.display = 'none';
                    }
                } else {
                    this.style.display = 'none';
                }

            } catch (error) {
                console.error('Failed to load more comments:', error);
            }
        });
    }

    // Real-time comment updates
    const postId = new URLSearchParams(window.location.search).get('id');
    if (postId) {
        realTimeUpdates.startUpdates('post-comments', async () => {
            try {
                const lastCommentTime = getLastCommentTime();
                const data = await ajaxManager.get(`api/new-comments.php?post_id=${postId}&since=${lastCommentTime}`);

                if (data.success && data.comments.length > 0) {
                    prependComments(data.comments);
                    updateCommentCount(data.comments.length);
                }
            } catch (error) {
                console.error('Failed to check for new comments:', error);
            }
        }, 30000);
    }
}

/**
 * Enhanced Search with Auto-complete
 */
function initializeEnhancedSearch() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    
    if (!searchInput) return;

    let searchTimeout;
    let currentQuery = '';

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            hideSearchResults();
            return;
        }

        if (query === currentQuery) return;
        currentQuery = query;

        // Debounce search
        searchTimeout = setTimeout(async () => {
            try {
                const data = await ajaxManager.get(`api/search.php?q=${encodeURIComponent(query)}&limit=5`, {
                    requestId: 'search-autocomplete'
                });

                if (data.success) {
                    showSearchResults(data.results);
                }

            } catch (error) {
                console.error('Search failed:', error);
            }
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            hideSearchResults();
        }
    });
}

/**
 * Infinite Scroll for Posts
 */
function initializeInfiniteScroll() {
    const postsContainer = document.querySelector('.posts-list, .forum-posts');
    if (!postsContainer) return;

    let isLoading = false;
    let hasMorePosts = true;
    let currentPage = 1;

    const loadMorePosts = async () => {
        if (isLoading || !hasMorePosts) return;

        isLoading = true;
        currentPage++;

        try {
            const url = new URL(window.location);
            url.searchParams.set('page', currentPage);
            url.searchParams.set('ajax', '1');

            const data = await ajaxManager.get(url.toString(), {
                requestId: 'infinite-scroll'
            });

            if (data.success && data.posts.length > 0) {
                appendPosts(data.posts);
                hasMorePosts = data.has_more;
            } else {
                hasMorePosts = false;
            }

        } catch (error) {
            console.error('Failed to load more posts:', error);
            currentPage--; // Revert page increment
        } finally {
            isLoading = false;
        }
    };

    // Intersection Observer for infinite scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMorePosts();
            }
        });
    }, {
        rootMargin: '100px'
    });

    // Create and observe sentinel element
    const sentinel = document.createElement('div');
    sentinel.className = 'scroll-sentinel';
    sentinel.style.height = '1px';
    postsContainer.appendChild(sentinel);
    observer.observe(sentinel);
}

/**
 * Toast Notification System
 */
function createToastSystem() {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;
        document.body.appendChild(toastContainer);
    }

    // Add showToast method to all elements
    Element.prototype.showToast = function(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#007bff'};
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            pointer-events: auto;
            max-width: 300px;
            word-wrap: break-word;
        `;

        const icon = type === 'error' ? 'fas fa-exclamation-circle' : 
                    type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';

        toast.innerHTML = `
            <i class="${icon}"></i>
            <span style="margin-left: 8px;">${ajaxManager.escapeHtml(message)}</span>
        `;

        toastContainer.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);

        // Auto remove
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);

        // Click to dismiss
        toast.addEventListener('click', () => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        });
    };
}

// Initialize all AJAX functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    createToastSystem();
    initializeRealTimeStats();
    initializeEnhancedLikeButton();
    initializeDynamicComments();
    initializeEnhancedSearch();
    initializeInfiniteScroll();
    
    console.log('AJAX functionality initialized');
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    ajaxManager.cancelAllRequests();
    realTimeUpdates.stopAllUpdates();
});