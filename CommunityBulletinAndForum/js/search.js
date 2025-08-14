/**
 * Search functionality for CommunityHub
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
});

/**
 * Initialize all search functionality
 */
function initializeSearch() {
    initializeHeaderSearch();
    initializeMainSearch();
    initializeSearchFilters();
    initializeSearchAutocomplete();
    
    console.log('Search functionality initialized');
}

/**
 * Initialize header search functionality
 */
function initializeHeaderSearch() {
    const headerSearchInput = document.getElementById('search-input');
    const headerSearchBtn = document.querySelector('.search-btn');
    const headerSearchResults = document.getElementById('search-results');
    
    if (!headerSearchInput) return;
    
    let searchTimeout;
    let currentRequest;
    
    // Handle search input
    headerSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            hideSearchResults(headerSearchResults);
            return;
        }
        
        // Debounce search requests
        searchTimeout = setTimeout(() => {
            performHeaderSearch(query, headerSearchResults);
        }, 300);
    });
    
    // Handle search button click
    if (headerSearchBtn) {
        headerSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const query = headerSearchInput.value.trim();
            if (query.length >= 2) {
                navigateToSearchPage(query);
            }
        });
    }
    
    // Handle Enter key
    headerSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            if (query.length >= 2) {
                navigateToSearchPage(query);
            }
        } else if (e.key === 'Escape') {
            hideSearchResults(headerSearchResults);
            this.blur();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateSearchResults(headerSearchResults, 'down');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateSearchResults(headerSearchResults, 'up');
        }
    });
    
    // Handle focus and blur
    headerSearchInput.addEventListener('focus', function() {
        const query = this.value.trim();
        if (query.length >= 2) {
            showSearchResults(headerSearchResults);
        }
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSearchResults(headerSearchResults);
        }
    });
}

/**
 * Initialize mobile search functionality
 */
function initializeMobileSearch() {
    const mobileSearchInput = document.querySelector('.mobile-search-input');
    const mobileSearchBtn = document.querySelector('.mobile-search-btn');
    
    if (!mobileSearchInput) return;
    
    // Handle mobile search input
    mobileSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            if (query.length >= 2) {
                navigateToSearchPage(query);
            }
        }
    });
    
    // Handle mobile search button
    if (mobileSearchBtn) {
        mobileSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const query = mobileSearchInput.value.trim();
            if (query.length >= 2) {
                navigateToSearchPage(query);
            }
        });
    }
}

/**
 * Initialize main search page functionality
 */
function initializeMainSearch() {
    const mainSearchInput = document.getElementById('main-search-input');
    const searchForm = document.querySelector('.search-form');
    
    if (!mainSearchInput) return;
    
    // Focus on search input when page loads
    mainSearchInput.focus();
    
    // Handle form submission
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const query = mainSearchInput.value.trim();
            if (query.length < 2) {
                e.preventDefault();
                showSearchMessage('Please enter at least 2 characters to search.', 'warning');
                mainSearchInput.focus();
            }
        });
    }
}

/**
 * Initialize search filters functionality
 */
function initializeSearchFilters() {
    const filterSelects = document.querySelectorAll('.filter-select');
    const applyFiltersBtn = document.querySelector('.filter-apply-btn');
    
    // Auto-submit on filter change
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Add visual feedback
            this.style.background = 'var(--light-blue)';
            setTimeout(() => {
                this.style.background = '';
            }, 200);
            
            // Auto-submit form after short delay
            setTimeout(() => {
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }, 100);
        });
    });
    
    // Handle apply filters button
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            const hideLoading = showLoadingState(this, 'Applying...');
            
            // Reset loading state if form doesn't submit
            setTimeout(hideLoading, 3000);
        });
    }
}

/**
 * Initialize search autocomplete functionality
 */
function initializeSearchAutocomplete() {
    const searchInputs = document.querySelectorAll('#main-search-input, #search-input');
    
    searchInputs.forEach(input => {
        const suggestionsContainer = input.parentElement.querySelector('.search-suggestions') || 
                                   document.getElementById('search-suggestions');
        
        if (!suggestionsContainer) return;
        
        let suggestionTimeout;
        
        input.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(suggestionTimeout);
            
            if (query.length < 2) {
                hideSuggestions(suggestionsContainer);
                return;
            }
            
            // Debounce suggestion requests
            suggestionTimeout = setTimeout(() => {
                fetchSuggestions(query, suggestionsContainer, input);
            }, 200);
        });
        
        // Handle suggestion selection
        suggestionsContainer.addEventListener('click', function(e) {
            const suggestion = e.target.closest('.suggestion-item');
            if (suggestion) {
                const text = suggestion.querySelector('.suggestion-text').textContent;
                input.value = text;
                hideSuggestions(suggestionsContainer);
                
                // Navigate to search page or perform search
                if (input.id === 'main-search-input') {
                    input.closest('form').submit();
                } else {
                    navigateToSearchPage(text);
                }
            }
        });
    });
}

/**
 * Perform header search with live results
 */
async function performHeaderSearch(query, resultsContainer) {
    if (!resultsContainer) return;
    
    try {
        showSearchLoading(resultsContainer);
        
        const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}&limit=5`);
        const data = await response.json();
        
        if (data.success && data.results) {
            displayHeaderSearchResults(data.results, resultsContainer, query);
        } else {
            showSearchError(resultsContainer, 'Search failed. Please try again.');
        }
    } catch (error) {
        console.error('Search error:', error);
        showSearchError(resultsContainer, 'Search failed. Please try again.');
    }
}

/**
 * Display header search results
 */
function displayHeaderSearchResults(results, container, query) {
    if (!container) return;
    
    if (results.length === 0) {
        container.innerHTML = `
            <div class="search-no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="no-results-text">
                    <p>No results found for "${escapeHtml(query)}"</p>
                    <a href="search.php?q=${encodeURIComponent(query)}" class="search-all-link">
                        Search all results <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        `;
    } else {
        let html = '<div class="search-results-list">';
        
        results.forEach(result => {
            if (result.type === 'post') {
                html += `
                    <div class="search-result-item" data-url="${escapeHtml(result.url)}">
                        <div class="result-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="result-content">
                            <div class="result-title">${highlightText(result.title, query)}</div>
                            <div class="result-meta">
                                <span class="result-category">${escapeHtml(result.category)}</span>
                                <span class="result-author">by ${escapeHtml(result.username)}</span>
                            </div>
                        </div>
                        <div class="result-stats">
                            <span class="result-likes">
                                <i class="fas fa-heart"></i> ${formatNumber(result.likes_count)}
                            </span>
                        </div>
                    </div>
                `;
            } else if (result.type === 'user') {
                html += `
                    <div class="search-result-item" data-url="${escapeHtml(result.url)}">
                        <div class="result-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="result-content">
                            <div class="result-title">${highlightText(result.username, query)}</div>
                            <div class="result-meta">
                                <span class="result-type">User</span>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        
        html += '</div>';
        
        // Add "View all results" link
        html += `
            <div class="search-footer">
                <a href="search.php?q=${encodeURIComponent(query)}" class="search-all-link">
                    View all results <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Add click handlers to result items
        container.querySelectorAll('.search-result-item[data-url]').forEach(item => {
            item.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    }
    
    showSearchResults(container);
}

/**
 * Fetch search suggestions
 */
async function fetchSuggestions(query, container, input) {
    try {
        const response = await fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success && data.suggestions) {
            displaySuggestions(data.suggestions, container, input);
        }
    } catch (error) {
        console.error('Suggestions error:', error);
    }
}

/**
 * Display search suggestions
 */
function displaySuggestions(suggestions, container, input) {
    if (!container || suggestions.length === 0) {
        hideSuggestions(container);
        return;
    }
    
    let html = '<div class="suggestions-list">';
    
    suggestions.forEach(suggestion => {
        html += `
            <div class="suggestion-item" data-type="${escapeHtml(suggestion.type)}">
                <div class="suggestion-icon">
                    <i class="${escapeHtml(suggestion.icon)}"></i>
                </div>
                <div class="suggestion-text">${escapeHtml(suggestion.text)}</div>
                <div class="suggestion-type">${escapeHtml(suggestion.type)}</div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    showSuggestions(container);
}

/**
 * Navigate to search page with query
 */
function navigateToSearchPage(query) {
    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
}

/**
 * Show search results container
 */
function showSearchResults(container) {
    if (container) {
        container.style.display = 'block';
        container.classList.add('visible');
    }
}

/**
 * Hide search results container
 */
function hideSearchResults(container) {
    if (container) {
        container.style.display = 'none';
        container.classList.remove('visible');
    }
}

/**
 * Show search suggestions
 */
function showSuggestions(container) {
    if (container) {
        container.style.display = 'block';
        container.classList.add('visible');
    }
}

/**
 * Hide search suggestions
 */
function hideSuggestions(container) {
    if (container) {
        container.style.display = 'none';
        container.classList.remove('visible');
    }
}

/**
 * Show search loading state
 */
function showSearchLoading(container) {
    if (container) {
        container.innerHTML = `
            <div class="search-loading">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="loading-text">Searching...</div>
            </div>
        `;
        showSearchResults(container);
    }
}

/**
 * Show search error
 */
function showSearchError(container, message) {
    if (container) {
        container.innerHTML = `
            <div class="search-error">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="error-text">${escapeHtml(message)}</div>
            </div>
        `;
        showSearchResults(container);
    }
}

/**
 * Navigate search results with keyboard
 */
function navigateSearchResults(container, direction) {
    if (!container) return;
    
    const items = container.querySelectorAll('.search-result-item, .suggestion-item');
    const currentActive = container.querySelector('.active');
    let newActiveIndex = -1;
    
    if (currentActive) {
        const currentIndex = Array.from(items).indexOf(currentActive);
        newActiveIndex = direction === 'down' ? currentIndex + 1 : currentIndex - 1;
    } else {
        newActiveIndex = direction === 'down' ? 0 : items.length - 1;
    }
    
    // Remove current active state
    if (currentActive) {
        currentActive.classList.remove('active');
    }
    
    // Set new active state
    if (newActiveIndex >= 0 && newActiveIndex < items.length) {
        items[newActiveIndex].classList.add('active');
        items[newActiveIndex].scrollIntoView({ block: 'nearest' });
    }
}

/**
 * Show search message
 */
function showSearchMessage(message, type = 'info') {
    const messageContainer = document.createElement('div');
    messageContainer.className = `search-message search-message-${type}`;
    messageContainer.innerHTML = `
        <div class="search-message-icon">
            <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
        </div>
        <div class="search-message-text">${escapeHtml(message)}</div>
    `;
    
    // Insert message at the top of the main content
    const mainContent = document.querySelector('.main-content .container');
    if (mainContent) {
        mainContent.insertBefore(messageContainer, mainContent.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageContainer.remove();
        }, 5000);
    }
}

/**
 * Highlight search terms in text
 */
function highlightText(text, query) {
    if (!query || !text) return escapeHtml(text);
    
    const escapedText = escapeHtml(text);
    const terms = query.split(' ').filter(term => term.length >= 2);
    
    let highlightedText = escapedText;
    terms.forEach(term => {
        const regex = new RegExp(`(${escapeRegex(term)})`, 'gi');
        highlightedText = highlightedText.replace(regex, '<mark class="search-highlight">$1</mark>');
    });
    
    return highlightedText;
}

/**
 * Escape HTML characters
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Escape regex characters
 */
function escapeRegex(text) {
    return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Format number for display
 */
function formatNumber(num) {
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k';
    }
    return num.toString();
}

/**
 * Show loading state utility
 */
function showLoadingState(element, message = 'Loading...') {
    const originalContent = element.innerHTML;
    element.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
    element.style.opacity = '0.7';
    element.style.pointerEvents = 'none';
    
    return function hideLoading() {
        element.innerHTML = originalContent;
        element.style.opacity = '';
        element.style.pointerEvents = '';
    };
}

// Initialize mobile search when mobile nav is ready
document.addEventListener('mobileNavReady', function() {
    initializeMobileSearch();
});

// Fallback initialization for mobile search
setTimeout(initializeMobileSearch, 1000);