/**
 * Main JavaScript functionality for CommunityHub
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeCategoryCards();
    initializePostCards();
    initializeTrendingTopics();
    initializeUpcomingEvents();
    initializeCommunityStats();
    initializeResponsiveFeatures();
    
    console.log('CommunityHub initialized');
});

/**
 * Initialize category card interactions
 */
function initializeCategoryCards() {
    const categoryCards = document.querySelectorAll('.category-card');
    
    categoryCards.forEach(card => {
        // Add click handler for entire card
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on a link
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }
            
            const categoryLink = card.querySelector('.category-link');
            if (categoryLink) {
                // Add visual feedback
                card.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    card.style.transform = '';
                    window.location.href = categoryLink.href;
                }, 100);
            }
        });
        
        // Add keyboard navigation
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const categoryLink = card.querySelector('.category-link');
                if (categoryLink) {
                    categoryLink.click();
                }
            }
        });
        
        // Make card focusable for keyboard navigation
        if (!card.hasAttribute('tabindex')) {
            card.setAttribute('tabindex', '0');
        }
        
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            const icon = card.querySelector('.category-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = card.querySelector('.category-icon');
            if (icon) {
                icon.style.transform = '';
            }
        });
    });
}

/**
 * Initialize post card interactions
 */
function initializePostCards() {
    const postCards = document.querySelectorAll('.post-card');
    
    postCards.forEach(card => {
        // Add click handler for entire card
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on a link or button
            if (e.target.tagName === 'A' || e.target.closest('a') || 
                e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                return;
            }
            
            const postLink = card.querySelector('.post-link');
            if (postLink) {
                // Add visual feedback
                card.style.transform = 'translateY(-1px)';
                setTimeout(() => {
                    card.style.transform = '';
                    window.location.href = postLink.href;
                }, 100);
            }
        });
        
        // Add keyboard navigation
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const postLink = card.querySelector('.post-link');
                if (postLink) {
                    postLink.click();
                }
            }
        });
        
        // Make card focusable for keyboard navigation
        if (!card.hasAttribute('tabindex')) {
            card.setAttribute('tabindex', '0');
        }
    });
}

/**
 * Initialize trending topics interactions
 */
function initializeTrendingTopics() {
    const trendingLinks = document.querySelectorAll('.trending-link');
    
    trendingLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the topic from the URL
            const url = new URL(link.href);
            const topic = url.searchParams.get('topic');
            
            if (topic) {
                // Add loading state
                const topicName = link.querySelector('.topic-name');
                const topicCount = link.querySelector('.topic-count');
                const originalTopicText = topicName.textContent;
                const originalCountText = topicCount.textContent;
                
                topicName.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + originalTopicText;
                topicCount.textContent = 'Loading...';
                
                // Navigate to forum page with topic filter
                window.location.href = `forum.php?topic=${encodeURIComponent(topic)}`;
            }
        });
        
        // Add keyboard navigation
        link.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                link.click();
            }
        });
        
        // Add hover effects
        link.addEventListener('mouseenter', function() {
            const topicName = link.querySelector('.topic-name');
            if (topicName) {
                topicName.style.transform = 'translateX(2px)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            const topicName = link.querySelector('.topic-name');
            if (topicName) {
                topicName.style.transform = '';
            }
        });
    });
}

/**
 * Initialize upcoming events interactions
 */
function initializeUpcomingEvents() {
    const eventItems = document.querySelectorAll('.event-item');
    
    eventItems.forEach(item => {
        // Make event items clickable
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get event data from the DOM
            const eventTitle = item.querySelector('.event-title').textContent;
            const eventDate = item.querySelector('.event-date time').getAttribute('datetime');
            const eventTime = item.querySelector('.event-time').textContent;
            const eventOrganizer = item.querySelector('.event-organizer').textContent;
            
            // Show event details modal
            showEventDetails({
                title: eventTitle,
                date: eventDate,
                time: eventTime,
                organizer: eventOrganizer
            });
        });
        
        // Add keyboard navigation
        item.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
        
        // Make event items focusable
        if (!item.hasAttribute('tabindex')) {
            item.setAttribute('tabindex', '0');
        }
        
        // Add hover effects
        item.addEventListener('mouseenter', function() {
            item.style.transform = 'translateY(-2px)';
            item.style.boxShadow = 'var(--shadow-md)';
        });
        
        item.addEventListener('mouseleave', function() {
            item.style.transform = '';
            item.style.boxShadow = '';
        });
    });
}

/**
 * Show event details in a modal
 */
function showEventDetails(event) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('event-modal');
    if (!modal) {
        modal = createEventModal();
    }
    
    // Populate modal with event data
    const modalTitle = modal.querySelector('.modal-title');
    const modalDate = modal.querySelector('.event-modal-date');
    const modalTime = modal.querySelector('.event-modal-time');
    const modalOrganizer = modal.querySelector('.event-modal-organizer');
    
    modalTitle.textContent = event.title;
    modalDate.textContent = formatEventDate(event.date);
    modalTime.textContent = event.time;
    modalOrganizer.textContent = event.organizer;
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Focus on close button for accessibility
    const closeButton = modal.querySelector('.modal-close');
    if (closeButton) {
        closeButton.focus();
    }
}

/**
 * Create event details modal
 */
function createEventModal() {
    const modal = document.createElement('div');
    modal.id = 'event-modal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Event Details</h3>
                <button class="modal-close" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="event-detail-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="event-modal-date"></span>
                </div>
                <div class="event-detail-item">
                    <i class="fas fa-clock"></i>
                    <span class="event-modal-time"></span>
                </div>
                <div class="event-detail-item">
                    <i class="fas fa-user"></i>
                    <span class="event-modal-organizer"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Interested</button>
                <button class="btn btn-outline modal-close-btn">Close</button>
            </div>
        </div>
    `;
    
    // Add modal styles
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;
    
    // Add event listeners
    const closeButtons = modal.querySelectorAll('.modal-close, .modal-close-btn');
    const overlay = modal.querySelector('.modal-overlay');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', () => hideEventModal(modal));
    });
    
    overlay.addEventListener('click', () => hideEventModal(modal));
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            hideEventModal(modal);
        }
    });
    
    document.body.appendChild(modal);
    return modal;
}

/**
 * Hide event modal
 */
function hideEventModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

/**
 * Format event date for display
 */
function formatEventDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Initialize community statistics widget
 */
function initializeCommunityStats() {
    const statsWidget = document.querySelector('.stats-widget');
    if (!statsWidget) return;
    
    // Add refresh functionality
    addStatsRefreshButton(statsWidget);
    
    // Auto-refresh stats every 5 minutes
    setInterval(() => {
        refreshCommunityStats();
    }, 5 * 60 * 1000);
    
    // Add animation to stat numbers
    animateStatNumbers();
}

/**
 * Add refresh button to stats widget
 */
function addStatsRefreshButton(statsWidget) {
    const header = statsWidget.querySelector('.card-header');
    if (!header) return;
    
    const refreshButton = document.createElement('button');
    refreshButton.className = 'stats-refresh-btn';
    refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
    refreshButton.setAttribute('aria-label', 'Refresh statistics');
    refreshButton.title = 'Refresh statistics';
    
    refreshButton.style.cssText = `
        background: none;
        border: none;
        color: var(--text-light);
        cursor: pointer;
        padding: var(--space-xs);
        border-radius: var(--radius-md);
        transition: all 0.2s ease;
        font-size: var(--font-size-sm);
    `;
    
    refreshButton.addEventListener('click', function() {
        refreshCommunityStats();
    });
    
    refreshButton.addEventListener('mouseenter', function() {
        this.style.color = 'var(--primary-blue)';
        this.style.background = 'var(--background-gray)';
    });
    
    refreshButton.addEventListener('mouseleave', function() {
        this.style.color = 'var(--text-light)';
        this.style.background = 'none';
    });
    
    header.style.display = 'flex';
    header.style.justifyContent = 'space-between';
    header.style.alignItems = 'center';
    header.appendChild(refreshButton);
}

/**
 * Refresh community statistics via AJAX
 */
async function refreshCommunityStats() {
    const refreshButton = document.querySelector('.stats-refresh-btn');
    const statsWidget = document.querySelector('.stats-widget');
    
    try {
        const data = await ajaxManager.get('api/stats.php', {
            loadingElement: refreshButton,
            loadingMessage: 'Refreshing...',
            requestId: 'stats-refresh'
        });
        
        if (data && data.success) {
            updateStatNumbers(data.stats);
            
            // Show success feedback
            if (refreshButton) {
                refreshButton.showToast('Statistics updated!', 'success', 2000);
            }
        }
    } catch (error) {
        console.error('Error refreshing stats:', error);
        if (refreshButton) {
            refreshButton.showToast('Failed to refresh statistics', 'error');
        }
    }
}

/**
 * Update stat numbers with animation
 */
function updateStatNumbers(newStats) {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        const statNumber = card.querySelector('.stat-number');
        const statLabel = card.querySelector('.stat-label').textContent.toLowerCase();
        
        let newValue = 0;
        if (statLabel.includes('members')) {
            newValue = newStats.total_members;
        } else if (statLabel.includes('posts')) {
            newValue = newStats.posts_today;
        } else if (statLabel.includes('active')) {
            newValue = newStats.active_users;
        }
        
        // Animate number change
        animateNumberChange(statNumber, parseInt(statNumber.textContent.replace(/,/g, '')), newValue);
    });
}

/**
 * Animate number change
 */
function animateNumberChange(element, fromValue, toValue) {
    const duration = 1000; // 1 second
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Use easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentValue = Math.round(fromValue + (toValue - fromValue) * easeOutQuart);
        
        element.textContent = currentValue.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

/**
 * Animate stat numbers on page load
 */
function animateStatNumbers() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach((statNumber, index) => {
        const finalValue = parseInt(statNumber.textContent.replace(/,/g, ''));
        statNumber.textContent = '0';
        
        // Stagger animations
        setTimeout(() => {
            animateNumberChange(statNumber, 0, finalValue);
        }, index * 200);
    });
}

/**
 * Initialize responsive features
 */
function initializeResponsiveFeatures() {
    // Handle mobile menu if needed
    handleMobileNavigation();
    
    // Handle responsive grid adjustments
    handleResponsiveGrids();
    
    // Handle touch interactions on mobile
    if ('ontouchstart' in window) {
        initializeTouchInteractions();
    }
}

/**
 * Handle mobile navigation
 */
function handleMobileNavigation() {
    // Mobile navigation is now handled by mobile-nav.js
    // This function is kept for backward compatibility
    console.log('Mobile navigation handled by mobile-nav.js');
}

/**
 * Initialize mobile navigation
 */
function initializeMobileNav(nav) {
    // Mobile navigation is now handled by mobile-nav.js
    // This function is kept for backward compatibility
    console.log('Mobile navigation initialized by mobile-nav.js');
}

/**
 * Handle responsive grid adjustments
 */
function handleResponsiveGrids() {
    const categoriesGrid = document.querySelector('.categories-grid');
    if (!categoriesGrid) return;
    
    function adjustGrid() {
        const width = window.innerWidth;
        
        if (width <= 768) {
            categoriesGrid.classList.remove('grid-cols-2');
            categoriesGrid.classList.add('grid-cols-1');
        } else {
            categoriesGrid.classList.remove('grid-cols-1');
            categoriesGrid.classList.add('grid-cols-2');
        }
    }
    
    adjustGrid();
    window.addEventListener('resize', adjustGrid);
}

/**
 * Initialize touch interactions for mobile
 */
function initializeTouchInteractions() {
    // Enhanced touch interactions are now handled by mobile-nav.js
    // This provides basic fallback functionality
    const cards = document.querySelectorAll('.category-card, .post-card');
    
    cards.forEach(card => {
        let touchStartY = 0;
        let touchStartX = 0;
        let touchStartTime = 0;
        
        card.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
            touchStartX = e.touches[0].clientX;
            touchStartTime = Date.now();
            
            // Add touch feedback if not already handled by mobile-nav.js
            if (!card.classList.contains('touch-active')) {
                card.style.transform = 'scale(0.98)';
                card.style.opacity = '0.8';
            }
        }, { passive: true });
        
        card.addEventListener('touchend', function(e) {
            // Remove touch feedback
            card.style.transform = '';
            card.style.opacity = '';
            
            // Check if it was a tap (not a scroll)
            const touchEndY = e.changedTouches[0].clientY;
            const touchEndX = e.changedTouches[0].clientX;
            const touchEndTime = Date.now();
            const deltaY = Math.abs(touchEndY - touchStartY);
            const deltaX = Math.abs(touchEndX - touchStartX);
            const deltaTime = touchEndTime - touchStartTime;
            
            // Only trigger click for quick taps with minimal movement
            if (deltaY < 10 && deltaX < 10 && deltaTime < 300) {
                // Add a small delay to prevent double-tap zoom
                setTimeout(() => {
                    const link = card.querySelector('a');
                    if (link) {
                        link.click();
                    }
                }, 50);
            }
        }, { passive: true });
        
        card.addEventListener('touchcancel', function() {
            card.style.transform = '';
            card.style.opacity = '';
        }, { passive: true });
    });
}

/**
 * Utility function to show loading state
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

/**
 * Utility function for smooth scrolling
 */
function smoothScrollTo(element) {
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Handle flash messages auto-hide
 */
function initializeFlashMessages() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Auto-hide success messages after 5 seconds
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }
        
        // Add close button to alerts
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'alert-close';
        closeButton.style.cssText = `
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        `;
        
        closeButton.addEventListener('click', () => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        });
        
        alert.style.position = 'relative';
        alert.appendChild(closeButton);
    });
}

// Initialize flash messages when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeFlashMessages);

/**
 * Add loading states to forms
 */
function initializeFormLoadingStates() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                const originalText = submitButton.textContent || submitButton.value;
                
                if (submitButton.tagName === 'BUTTON') {
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                } else {
                    submitButton.value = 'Loading...';
                }
                
                // Reset after 10 seconds as fallback
                setTimeout(() => {
                    submitButton.disabled = false;
                    if (submitButton.tagName === 'BUTTON') {
                        submitButton.textContent = originalText;
                    } else {
                        submitButton.value = originalText;
                    }
                }, 10000);
            }
        });
    });
}

// Initialize form loading states
document.addEventListener('DOMContentLoaded', initializeFormLoadingStates);

/**
 * Handle accessibility improvements
 */
function initializeAccessibility() {
    // Add skip link for keyboard navigation
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.textContent = 'Skip to main content';
    skipLink.className = 'sr-only';
    skipLink.style.cssText = `
        position: absolute;
        top: -40px;
        left: 6px;
        background: var(--primary-blue);
        color: white;
        padding: 8px;
        text-decoration: none;
        border-radius: 4px;
        z-index: 1000;
    `;
    
    skipLink.addEventListener('focus', function() {
        this.style.top = '6px';
    });
    
    skipLink.addEventListener('blur', function() {
        this.style.top = '-40px';
    });
    
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Add main content ID if not present
    const mainContent = document.querySelector('main, .main-content-area');
    if (mainContent && !mainContent.id) {
        mainContent.id = 'main-content';
    }
}

// Initialize accessibility features
document.addEventListener('DOMContentLoaded', initializeAccessibility);/**
 * Fo
rum page specific functionality
 */
function initializeForumPage() {
    // Initialize forum post cards
    initializeForumPostCards();
    
    // Initialize forum actions
    initializeForumActions();
}

/**
 * Initialize forum post card interactions
 */
function initializeForumPostCards() {
    const forumPostCards = document.querySelectorAll('.forum-post-card');
    
    forumPostCards.forEach(card => {
        // Add click handler for entire card
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on a link or button
            if (e.target.tagName === 'A' || e.target.closest('a') || 
                e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                return;
            }
            
            const postLink = card.querySelector('.post-link');
            if (postLink) {
                // Add visual feedback
                card.style.transform = 'translateY(-2px)';
                setTimeout(() => {
                    card.style.transform = '';
                    window.location.href = postLink.href;
                }, 100);
            }
        });
        
        // Add keyboard navigation
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const postLink = card.querySelector('.post-link');
                if (postLink) {
                    postLink.click();
                }
            }
        });
        
        // Make card focusable for keyboard navigation
        if (!card.hasAttribute('tabindex')) {
            card.setAttribute('tabindex', '0');
        }
    });
}

/**
 * Initialize forum actions
 */
function initializeForumActions() {
    const newPostButton = document.querySelector('.forum-actions .btn-primary');
    
    if (newPostButton) {
        newPostButton.addEventListener('click', function(e) {
            // Add loading state
            const hideLoading = showLoadingState(this, 'Loading...');
            
            // Reset loading state if navigation doesn't happen
            setTimeout(hideLoading, 3000);
        });
    }
}

/**
 * Change sort order for forum posts
 */
function changeSortOrder(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page'); // Reset to first page when changing sort
    
    // Add loading state to the select
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.disabled = true;
        const originalValue = sortSelect.value;
        
        // Show loading in the posts area
        const postsContainer = document.querySelector('.posts-list');
        if (postsContainer) {
            postsContainer.style.opacity = '0.5';
        }
    }
    
    // Navigate to new URL
    window.location.href = url.toString();
}

// Initialize forum page if we're on it
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.forum-header') || document.querySelector('.forum-posts')) {
        initializeForumPage();
    }
});