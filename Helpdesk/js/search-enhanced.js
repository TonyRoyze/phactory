/**
 * Enhanced Search Functionality
 * Provides auto-complete, advanced search, and result highlighting
 */

class EnhancedSearch {
    constructor(options = {}) {
        this.searchInput = options.searchInput || document.querySelector('.search-input');
        this.searchForm = options.searchForm || document.querySelector('.search-form');
        this.resultsContainer = options.resultsContainer || document.querySelector('.search-results');
        this.suggestionsContainer = options.suggestionsContainer || document.querySelector('.search-suggestions');
        this.advancedToggle = options.advancedToggle || document.querySelector('.advanced-search-toggle');
        this.advancedPanel = options.advancedPanel || document.querySelector('.advanced-search-panel');
        
        this.debounceTimer = null;
        this.currentRequest = null;
        this.selectedSuggestionIndex = -1;
        this.suggestions = [];
        
        this.init();
    }
    
    init() {
        if (!this.searchInput) return;
        
        this.setupEventListeners();
        this.createSuggestionsContainer();
        this.setupAdvancedSearch();
    }
    
    setupEventListeners() {
        // Auto-complete on input
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        // Keyboard navigation for suggestions
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeyNavigation(e);
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.suggestionsContainer.contains(e.target)) {
                this.hideSuggestions();
            }
        });
        
        // Handle search form submission
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
        
        // Focus handling
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= 2) {
                this.showSuggestions();
            }
        });
    }
    
    createSuggestionsContainer() {
        if (this.suggestionsContainer) return;
        
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'search-suggestions';
        this.suggestionsContainer.style.display = 'none';
        
        // Insert after search input
        this.searchInput.parentNode.insertBefore(this.suggestionsContainer, this.searchInput.nextSibling);
    }
    
    setupAdvancedSearch() {
        if (this.advancedToggle && this.advancedPanel) {
            this.advancedToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleAdvancedSearch();
            });
        }
    }
    
    handleSearchInput(value) {
        clearTimeout(this.debounceTimer);
        
        if (value.length < 2) {
            this.hideSuggestions();
            return;
        }
        
        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(value);
        }, 300);
    }
    
    async fetchSuggestions(query) {
        try {
            // Cancel previous request
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            this.currentRequest = new AbortController();
            
            const response = await fetch(`actions/search_autocomplete.php?q=${encodeURIComponent(query)}`, {
                signal: this.currentRequest.signal
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            this.suggestions = data.suggestions || [];
            this.displaySuggestions();
            
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error fetching suggestions:', error);
            }
        }
    }
    
    displaySuggestions() {
        if (this.suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = this.suggestions.map((suggestion, index) => {
            const activeClass = index === this.selectedSuggestionIndex ? 'active' : '';
            return `
                <div class="suggestion-item ${activeClass}" data-index="${index}">
                    <span class="suggestion-icon">${suggestion.icon}</span>
                    <div class="suggestion-content">
                        <div class="suggestion-label">${this.highlightMatch(suggestion.label, this.searchInput.value)}</div>
                        <div class="suggestion-type">${suggestion.type}</div>
                    </div>
                </div>
            `;
        }).join('');
        
        this.suggestionsContainer.innerHTML = html;
        this.showSuggestions();
        
        // Add click handlers to suggestions
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.selectSuggestion(index);
            });
        });
    }
    
    highlightMatch(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    handleKeyNavigation(e) {
        if (!this.suggestionsContainer.style.display || this.suggestionsContainer.style.display === 'none') {
            return;
        }
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedSuggestionIndex = Math.min(this.selectedSuggestionIndex + 1, this.suggestions.length - 1);
                this.updateSuggestionSelection();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedSuggestionIndex = Math.max(this.selectedSuggestionIndex - 1, -1);
                this.updateSuggestionSelection();
                break;
                
            case 'Enter':
                if (this.selectedSuggestionIndex >= 0) {
                    e.preventDefault();
                    this.selectSuggestion(this.selectedSuggestionIndex);
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    updateSuggestionSelection() {
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach((item, index) => {
            item.classList.toggle('active', index === this.selectedSuggestionIndex);
        });
    }
    
    selectSuggestion(index) {
        if (index < 0 || index >= this.suggestions.length) return;
        
        const suggestion = this.suggestions[index];
        
        // Handle different suggestion types
        switch (suggestion.type) {
            case 'ticket':
                // Navigate directly to ticket
                window.location.href = `app.php?view=ticket&id=${suggestion.ticket_id}`;
                break;
                
            case 'customer':
                // Set customer filter and search
                this.setAdvancedFilter('customer_id', suggestion.customer_id);
                this.searchInput.value = suggestion.value;
                this.performSearch();
                break;
                
            case 'category':
                // Set category filter and search
                this.setAdvancedFilter('category', suggestion.category);
                this.searchInput.value = '';
                this.performSearch();
                break;
                
            case 'status':
                // Set status filter and search
                this.setAdvancedFilter('status', suggestion.status);
                this.searchInput.value = '';
                this.performSearch();
                break;
                
            case 'priority':
                // Set priority filter and search
                this.setAdvancedFilter('priority', suggestion.priority);
                this.searchInput.value = '';
                this.performSearch();
                break;
                
            default:
                // Regular search
                this.searchInput.value = suggestion.value;
                this.performSearch();
                break;
        }
        
        this.hideSuggestions();
    }
    
    setAdvancedFilter(filterName, value) {
        const filterInput = document.querySelector(`[name="${filterName}"]`);
        if (filterInput) {
            filterInput.value = value;
        }
    }
    
    showSuggestions() {
        this.suggestionsContainer.style.display = 'block';
        this.selectedSuggestionIndex = -1;
    }
    
    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
        this.selectedSuggestionIndex = -1;
    }
    
    toggleAdvancedSearch() {
        if (!this.advancedPanel) return;
        
        const isVisible = this.advancedPanel.style.display !== 'none';
        this.advancedPanel.style.display = isVisible ? 'none' : 'block';
        
        if (this.advancedToggle) {
            this.advancedToggle.textContent = isVisible ? 'Advanced Search' : 'Hide Advanced';
        }
    }
    
    async performSearch() {
        const searchTerm = this.searchInput.value.trim();
        if (searchTerm.length < 2) return;
        
        // Show loading state
        this.showLoadingState();
        
        try {
            const formData = new FormData();
            formData.append('search', searchTerm);
            
            // Add advanced search parameters
            if (this.advancedPanel && this.advancedPanel.style.display !== 'none') {
                const advancedInputs = this.advancedPanel.querySelectorAll('input, select');
                advancedInputs.forEach(input => {
                    if (input.value) {
                        formData.append(input.name, input.value);
                    }
                });
            }
            
            const response = await fetch('actions/search_tickets.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) throw new Error('Search request failed');
            
            const data = await response.json();
            this.displaySearchResults(data);
            
        } catch (error) {
            console.error('Search error:', error);
            this.showErrorState();
        }
    }
    
    displaySearchResults(data) {
        if (!this.resultsContainer) return;
        
        if (data.results.length === 0) {
            this.displayNoResults(data);
            return;
        }
        
        const html = `
            <div class="search-results-header">
                <h3>Search Results</h3>
                <p>Found ${data.total} ticket${data.total !== 1 ? 's' : ''} for "${data.search_term}"</p>
            </div>
            <div class="search-results-list">
                ${data.results.map(result => this.renderSearchResult(result)).join('')}
            </div>
            ${data.has_more ? '<div class="load-more-container"><button class="btn-load-more">Load More Results</button></div>' : ''}
        `;
        
        this.resultsContainer.innerHTML = html;
        this.resultsContainer.style.display = 'block';
        
        // Add load more functionality
        const loadMoreBtn = this.resultsContainer.querySelector('.btn-load-more');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => this.loadMoreResults(data));
        }
    }
    
    renderSearchResult(result) {
        const statusClass = result.status.toLowerCase().replace(' ', '-');
        const priorityClass = result.priority.toLowerCase();
        
        return `
            <div class="search-result-item">
                <div class="result-header">
                    <h4><a href="app.php?view=ticket&id=${result.ticket_id}">#${result.ticket_id} - ${result.highlighted_title}</a></h4>
                    <div class="result-badges">
                        <span class="badge priority-${priorityClass}">${result.priority}</span>
                        <span class="badge status-${statusClass}">${result.status}</span>
                    </div>
                </div>
                <div class="result-meta">
                    <span class="category">${result.category}</span>
                    <span class="customer">by ${result.customer_name}</span>
                    <span class="date">${this.formatDate(result.created_at)}</span>
                    ${result.assigned_name ? `<span class="assigned">assigned to ${result.assigned_name}</span>` : ''}
                </div>
                <div class="result-description">
                    ${result.highlighted_description}
                </div>
                ${result.reply_content ? `
                    <div class="result-reply">
                        <strong>Reply by ${result.reply_author}:</strong>
                        ${this.highlightMatch(result.reply_content.substring(0, 150) + '...', result.search_term)}
                    </div>
                ` : ''}
                <div class="result-relevance">
                    Relevance: ${result.relevance_score}/10
                </div>
            </div>
        `;
    }
    
    displayNoResults(data) {
        let html = `
            <div class="no-search-results">
                <h3>No Results Found</h3>
                <p>No tickets found for "${data.search_term}"</p>
        `;
        
        if (data.suggestions.length > 0) {
            html += `
                <div class="search-suggestions-section">
                    <h4>Did you mean:</h4>
                    <ul class="suggestion-list">
                        ${data.suggestions.map(suggestion => `
                            <li><a href="#" class="suggestion-link" data-suggestion="${suggestion.text}">${suggestion.text}</a></li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        html += `
                <div class="search-tips">
                    <h4>Search Tips:</h4>
                    <ul>
                        <li>Try different keywords</li>
                        <li>Check your spelling</li>
                        <li>Use broader search terms</li>
                        <li>Try the advanced search options</li>
                    </ul>
                </div>
            </div>
        `;
        
        this.resultsContainer.innerHTML = html;
        this.resultsContainer.style.display = 'block';
        
        // Add click handlers for suggestions
        this.resultsContainer.querySelectorAll('.suggestion-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.searchInput.value = e.target.dataset.suggestion;
                this.performSearch();
            });
        });
    }
    
    showLoadingState() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = '<div class="search-loading">Searching...</div>';
            this.resultsContainer.style.display = 'block';
        }
    }
    
    showErrorState() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = '<div class="search-error">An error occurred while searching. Please try again.</div>';
            this.resultsContainer.style.display = 'block';
        }
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Initialize enhanced search when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize for main search forms
    const searchForms = document.querySelectorAll('.search-form, .admin-search-form');
    searchForms.forEach(form => {
        const searchInput = form.querySelector('input[type="text"]');
        if (searchInput) {
            new EnhancedSearch({
                searchInput: searchInput,
                searchForm: form
            });
        }
    });
});