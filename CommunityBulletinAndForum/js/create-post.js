/**
 * Create Post Form Client-Side Validation and Handling
 * Provides form validation, character counting, and AJAX functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-post-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    // Form fields
    const categoryField = document.getElementById('category_id');
    const titleField = document.getElementById('title');
    const contentField = document.getElementById('content');
    
    // Character counters
    const titleCounter = document.getElementById('title-counter');
    const contentCounter = document.getElementById('content-counter');
    
    // Error display elements
    const categoryError = document.getElementById('category-error');
    const titleError = document.getElementById('title-error');
    const contentError = document.getElementById('content-error');
    
    // Sidebar elements
    const categoryInfo = document.getElementById('category-info');
    const categoryIconDisplay = document.getElementById('category-icon-display');
    const categoryNameDisplay = document.getElementById('category-name-display');
    const categoryDescriptionDisplay = document.getElementById('category-description-display');
    const categoryPostsDisplay = document.getElementById('category-posts-display');
    const recentPostsCategory = document.getElementById('recent-posts-category');
    const recentPostsList = document.getElementById('recent-posts-list');
    
    // Category data (will be populated from the page)
    const categories = window.categoryData || [];
    
    /**
     * Show error message for a field
     */
    function showError(field, errorElement, message) {
        field.classList.add('error');
        field.classList.remove('success');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    /**
     * Show success state for a field
     */
    function showSuccess(field, errorElement) {
        field.classList.remove('error');
        field.classList.add('success');
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    /**
     * Clear validation state for a field
     */
    function clearValidation(field, errorElement) {
        field.classList.remove('error', 'success');
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    /**
     * Update character counter
     */
    function updateCharacterCounter(field, counter, maxLength = null) {
        const length = field.value.length;
        counter.textContent = length;
        
        if (maxLength) {
            if (length > maxLength * 0.9) {
                counter.style.color = 'var(--warning-orange)';
            } else if (length > maxLength * 0.8) {
                counter.style.color = 'var(--text-light)';
            } else {
                counter.style.color = 'var(--success-green)';
            }
        }
    }
    
    /**
     * Validate category selection
     */
    function validateCategory() {
        const categoryId = parseInt(categoryField.value);
        
        if (!categoryId || categoryId <= 0) {
            showError(categoryField, categoryError, 'Please select a category for your post');
            return false;
        }
        
        // Check if category exists in our data
        const categoryExists = categories.some(cat => cat.id == categoryId);
        if (!categoryExists) {
            showError(categoryField, categoryError, 'Selected category is not valid');
            return false;
        }
        
        showSuccess(categoryField, categoryError);
        return true;
    }
    
    /**
     * Validate title field
     */
    function validateTitle() {
        const title = titleField.value.trim();
        
        if (!title) {
            showError(titleField, titleError, 'Post title is required');
            return false;
        }
        
        if (title.length < 5) {
            showError(titleField, titleError, 'Title must be at least 5 characters long');
            return false;
        }
        
        if (title.length > 255) {
            showError(titleField, titleError, 'Title must be less than 255 characters');
            return false;
        }
        
        showSuccess(titleField, titleError);
        return true;
    }
    
    /**
     * Validate content field
     */
    function validateContent() {
        const content = contentField.value.trim();
        
        if (!content) {
            showError(contentField, contentError, 'Post content is required');
            return false;
        }
        
        if (content.length < 10) {
            showError(contentField, contentError, 'Content must be at least 10 characters long');
            return false;
        }
        
        showSuccess(contentField, contentError);
        return true;
    }
    
    /**
     * Validate entire form
     */
    function validateForm() {
        const isCategoryValid = validateCategory();
        const isTitleValid = validateTitle();
        const isContentValid = validateContent();
        
        return isCategoryValid && isTitleValid && isContentValid;
    }
    
    /**
     * Set loading state
     */
    function setLoadingState(loading) {
        if (loading) {
            submitBtn.classList.add('loading');
            btnText.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
            submitBtn.disabled = true;
            
            // Disable form fields
            [categoryField, titleField, contentField].forEach(field => {
                field.disabled = true;
            });
        } else {
            submitBtn.classList.remove('loading');
            btnText.classList.remove('hidden');
            loadingSpinner.classList.add('hidden');
            submitBtn.disabled = false;
            
            // Re-enable form fields
            [categoryField, titleField, contentField].forEach(field => {
                field.disabled = false;
            });
        }
    }
    
    /**
     * Update category information display
     */
    function updateCategoryInfo() {
        const categoryId = parseInt(categoryField.value);
        
        if (!categoryId) {
            categoryInfo.style.display = 'none';
            recentPostsCategory.style.display = 'none';
            return;
        }
        
        const category = categories.find(cat => cat.id == categoryId);
        if (!category) return;
        
        // Update category info display
        categoryIconDisplay.className = category.icon;
        categoryIconDisplay.style.color = category.color;
        categoryNameDisplay.textContent = category.name;
        categoryDescriptionDisplay.textContent = category.description;
        categoryPostsDisplay.textContent = category.actual_post_count || category.post_count || 0;
        
        categoryInfo.style.display = 'block';
        
        // Load recent posts for this category
        loadRecentPostsForCategory(categoryId);
    }
    
    /**
     * Load recent posts for selected category
     */
    function loadRecentPostsForCategory(categoryId) {
        recentPostsList.innerHTML = '<div class="loading-message"><i class="fas fa-spinner fa-spin"></i> Loading recent posts...</div>';
        recentPostsCategory.style.display = 'block';
        
        fetch(`api/recent-posts.php?category_id=${categoryId}&limit=3`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.posts.length > 0) {
                    displayRecentPosts(data.posts);
                } else {
                    recentPostsList.innerHTML = '<p class="empty-message">No recent posts in this category yet. Be the first to post!</p>';
                }
            })
            .catch(error => {
                console.error('Error loading recent posts:', error);
                recentPostsList.innerHTML = '<p class="error-message">Failed to load recent posts.</p>';
            });
    }
    
    /**
     * Display recent posts
     */
    function displayRecentPosts(posts) {
        const postsHtml = posts.map(post => `
            <div class="recent-post-item">
                <h5 class="recent-post-title">
                    <a href="post.php?id=${post.id}">${escapeHtml(post.title)}</a>
                </h5>
                <div class="recent-post-meta">
                    <span class="post-author">by ${escapeHtml(post.username)}</span>
                    <span class="post-time">${timeAgo(post.created_at)}</span>
                </div>
            </div>
        `).join('');
        
        recentPostsList.innerHTML = postsHtml;
    }
    
    /**
     * Extract and highlight hashtags in content
     */
    function highlightHashtags() {
        const content = contentField.value;
        const hashtags = content.match(/#[a-zA-Z0-9_]+/g);
        
        if (hashtags && hashtags.length > 0) {
            const uniqueHashtags = [...new Set(hashtags)];
            
            // Show hashtag preview
            showHashtagPreview(uniqueHashtags);
        } else {
            hideHashtagPreview();
        }
    }
    
    /**
     * Show hashtag preview
     */
    function showHashtagPreview(hashtags) {
        let hashtagPreview = document.getElementById('hashtag-preview');
        
        if (!hashtagPreview) {
            hashtagPreview = document.createElement('div');
            hashtagPreview.id = 'hashtag-preview';
            hashtagPreview.className = 'hashtag-preview';
            contentField.parentNode.appendChild(hashtagPreview);
        }
        
        const hashtagsHtml = hashtags.map(tag => 
            `<span class="hashtag-badge">${escapeHtml(tag)}</span>`
        ).join('');
        
        hashtagPreview.innerHTML = `
            <div class="hashtag-preview-header">
                <i class="fas fa-hashtag"></i> Hashtags found:
            </div>
            <div class="hashtag-list">${hashtagsHtml}</div>
        `;
        
        hashtagPreview.style.display = 'block';
    }
    
    /**
     * Hide hashtag preview
     */
    function hideHashtagPreview() {
        const hashtagPreview = document.getElementById('hashtag-preview');
        if (hashtagPreview) {
            hashtagPreview.style.display = 'none';
        }
    }
    
    /**
     * Submit form via AJAX
     */
    function submitFormAjax(formData) {
        return fetch('create-post.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                // Post created successfully, redirect
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
                const newErrorMessages = doc.getElementById('error-messages');
                
                if (newErrorMessages) {
                    // Show server-side errors
                    const errorMessages = document.getElementById('error-messages');
                    if (errorMessages) {
                        errorMessages.innerHTML = newErrorMessages.innerHTML;
                        errorMessages.style.display = 'block';
                    } else {
                        // Create error messages element if it doesn't exist
                        const errorDiv = document.createElement('div');
                        errorDiv.id = 'error-messages';
                        errorDiv.className = 'alert alert-error';
                        errorDiv.innerHTML = newErrorMessages.innerHTML;
                        form.parentNode.insertBefore(errorDiv, form);
                    }
                    
                    // Scroll to top to show errors
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return false;
                }
            }
            return true;
        });
    }
    
    // Event Listeners
    
    // Character counters
    titleField.addEventListener('input', function() {
        updateCharacterCounter(titleField, titleCounter, 255);
        if (titleField.classList.contains('error')) {
            validateTitle();
        }
    });
    
    contentField.addEventListener('input', function() {
        updateCharacterCounter(contentField, contentCounter);
        if (contentField.classList.contains('error')) {
            validateContent();
        }
        
        // Highlight hashtags with debounce
        clearTimeout(contentField.hashtagTimeout);
        contentField.hashtagTimeout = setTimeout(highlightHashtags, 500);
    });
    
    // Category selection
    categoryField.addEventListener('change', function() {
        if (categoryField.classList.contains('error')) {
            validateCategory();
        }
        updateCategoryInfo();
    });
    
    // Real-time validation
    categoryField.addEventListener('blur', validateCategory);
    titleField.addEventListener('blur', validateTitle);
    contentField.addEventListener('blur', validateContent);
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear any existing server error messages
        const errorMessages = document.getElementById('error-messages');
        if (errorMessages) {
            errorMessages.style.display = 'none';
        }
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Set loading state
        setLoadingState(true);
        
        // Submit form
        const formData = new FormData(form);
        
        submitFormAjax(formData)
            .then(success => {
                if (!success) {
                    setLoadingState(false);
                }
            })
            .catch(error => {
                console.error('Post creation error:', error);
                alert('An error occurred while creating the post. Please try again.');
                setLoadingState(false);
            });
    });
    
    // Clear validation states when user starts typing after an error
    [categoryField, titleField, contentField].forEach(field => {
        field.addEventListener('focus', function() {
            if (field.classList.contains('error')) {
                const errorElement = document.getElementById(field.id.replace('_', '-') + '-error');
                if (errorElement) {
                    clearValidation(field, errorElement);
                }
            }
        });
    });
    
    // Auto-save draft functionality (optional enhancement)
    let autoSaveTimeout;
    function autoSaveDraft() {
        const title = titleField.value.trim();
        const content = contentField.value.trim();
        const categoryId = categoryField.value;
        
        if (title || content) {
            const draftData = {
                title: title,
                content: content,
                category_id: categoryId,
                timestamp: Date.now()
            };
            
            localStorage.setItem('post_draft', JSON.stringify(draftData));
        }
    }
    
    // Auto-save every 30 seconds
    [titleField, contentField, categoryField].forEach(field => {
        field.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSaveDraft, 30000);
        });
    });
    
    // Load draft on page load
    function loadDraft() {
        const draftData = localStorage.getItem('post_draft');
        if (draftData) {
            try {
                const draft = JSON.parse(draftData);
                const draftAge = Date.now() - draft.timestamp;
                
                // Only load draft if it's less than 24 hours old
                if (draftAge < 24 * 60 * 60 * 1000) {
                    if (confirm('A draft was found. Would you like to restore it?')) {
                        titleField.value = draft.title || '';
                        contentField.value = draft.content || '';
                        if (draft.category_id) {
                            categoryField.value = draft.category_id;
                            updateCategoryInfo();
                        }
                        
                        // Update character counters
                        updateCharacterCounter(titleField, titleCounter, 255);
                        updateCharacterCounter(contentField, contentCounter);
                        
                        // Highlight hashtags
                        highlightHashtags();
                    }
                }
            } catch (e) {
                console.error('Error loading draft:', e);
            }
        }
    }
    
    // Clear draft when post is successfully created
    window.addEventListener('beforeunload', function() {
        // Only clear if we're navigating to a post page (successful creation)
        if (window.location.href.includes('post.php')) {
            localStorage.removeItem('post_draft');
        }
    });
    
    // Utility functions
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
    
    // Initialize
    updateCharacterCounter(titleField, titleCounter, 255);
    updateCharacterCounter(contentField, contentCounter);
    loadDraft();
    
    // Auto-focus title field if it's empty
    if (!titleField.value.trim()) {
        titleField.focus();
    }
});