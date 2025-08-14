/**
 * Mobile Navigation and Touch Interactions
 * Handles responsive navigation, mobile menu, and touch-friendly interactions
 */

class MobileNavigation {
    constructor() {
        this.mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        this.mobileNav = document.querySelector('.mobile-nav');
        this.mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
        this.mobileNavClose = document.querySelector('.mobile-nav-close');
        this.body = document.body;
        this.isOpen = false;
        this.touchStartY = 0;
        this.touchStartX = 0;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupTouchInteractions();
        this.setupKeyboardNavigation();
        this.setupResponsiveHandling();
    }

    setupEventListeners() {
        // Mobile menu toggle
        if (this.mobileMenuToggle) {
            this.mobileMenuToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMobileMenu();
            });
        }

        // Mobile menu close
        if (this.mobileNavClose) {
            this.mobileNavClose.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeMobileMenu();
            });
        }

        // Overlay click to close
        if (this.mobileNavOverlay) {
            this.mobileNavOverlay.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        }

        // Close menu when clicking nav links
        const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
        mobileNavItems.forEach(item => {
            if (item.tagName === 'A') {
                item.addEventListener('click', () => {
                    this.closeMobileMenu();
                });
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMobileMenu();
            }
        });
    }

    setupTouchInteractions() {
        // Swipe to close mobile menu
        if (this.mobileNav) {
            this.mobileNav.addEventListener('touchstart', (e) => {
                this.touchStartX = e.touches[0].clientX;
                this.touchStartY = e.touches[0].clientY;
            }, { passive: true });

            this.mobileNav.addEventListener('touchmove', (e) => {
                if (!this.isOpen) return;

                const touchX = e.touches[0].clientX;
                const touchY = e.touches[0].clientY;
                const deltaX = touchX - this.touchStartX;
                const deltaY = Math.abs(touchY - this.touchStartY);

                // Swipe left to close (only if horizontal swipe is dominant)
                if (deltaX < -50 && deltaY < 100) {
                    this.closeMobileMenu();
                }
            }, { passive: true });
        }

        // Enhanced touch interactions for cards and buttons
        this.setupTouchFeedback();
    }

    setupTouchFeedback() {
        const touchElements = document.querySelectorAll('.category-card, .post-card, .btn, .nav-item');
        
        touchElements.forEach(element => {
            let touchTimeout;
            
            element.addEventListener('touchstart', (e) => {
                // Add touch feedback
                element.classList.add('touch-active');
                
                // Long press detection
                touchTimeout = setTimeout(() => {
                    this.handleLongPress(element, e);
                }, 500);
            }, { passive: true });

            element.addEventListener('touchend', () => {
                // Remove touch feedback
                setTimeout(() => {
                    element.classList.remove('touch-active');
                }, 150);
                
                clearTimeout(touchTimeout);
            }, { passive: true });

            element.addEventListener('touchcancel', () => {
                element.classList.remove('touch-active');
                clearTimeout(touchTimeout);
            }, { passive: true });
        });
    }

    setupKeyboardNavigation() {
        // Trap focus in mobile menu when open
        if (this.mobileNav) {
            this.mobileNav.addEventListener('keydown', (e) => {
                if (!this.isOpen) return;

                const focusableElements = this.mobileNav.querySelectorAll(
                    'a, button, input, [tabindex]:not([tabindex="-1"])'
                );
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        // Shift + Tab
                        if (document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        // Tab
                        if (document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
        }
    }

    setupResponsiveHandling() {
        // Handle responsive breakpoints
        const mediaQuery = window.matchMedia('(max-width: 768px)');
        
        const handleBreakpointChange = (e) => {
            if (!e.matches && this.isOpen) {
                // Desktop view, close mobile menu
                this.closeMobileMenu();
            }
        };

        mediaQuery.addListener(handleBreakpointChange);
        handleBreakpointChange(mediaQuery);
    }

    toggleMobileMenu() {
        if (this.isOpen) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }

    openMobileMenu() {
        if (this.isOpen) return;

        this.isOpen = true;
        this.body.classList.add('mobile-menu-open');
        this.mobileNav.classList.add('open');
        this.mobileNavOverlay.classList.add('open');
        this.mobileMenuToggle.setAttribute('aria-expanded', 'true');

        // Focus first focusable element
        const firstFocusable = this.mobileNav.querySelector('a, button, input');
        if (firstFocusable) {
            setTimeout(() => firstFocusable.focus(), 100);
        }

        // Prevent body scroll
        this.body.style.overflow = 'hidden';
    }

    closeMobileMenu() {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.body.classList.remove('mobile-menu-open');
        this.mobileNav.classList.remove('open');
        this.mobileNavOverlay.classList.remove('open');
        this.mobileMenuToggle.setAttribute('aria-expanded', 'false');

        // Restore body scroll
        this.body.style.overflow = '';

        // Return focus to toggle button
        this.mobileMenuToggle.focus();
    }

    handleResize() {
        // Close mobile menu on resize to desktop
        if (window.innerWidth > 768 && this.isOpen) {
            this.closeMobileMenu();
        }
    }

    handleLongPress(element, event) {
        // Handle long press interactions
        if (element.classList.contains('post-card')) {
            this.showPostContextMenu(element, event);
        } else if (element.classList.contains('category-card')) {
            this.showCategoryContextMenu(element, event);
        }
    }

    showPostContextMenu(postCard, event) {
        // Create context menu for posts
        const contextMenu = this.createContextMenu([
            { text: 'Open Post', action: () => this.openPost(postCard) },
            { text: 'Share Post', action: () => this.sharePost(postCard) },
            { text: 'Copy Link', action: () => this.copyPostLink(postCard) }
        ]);

        this.showContextMenu(contextMenu, event.touches[0]);
    }

    showCategoryContextMenu(categoryCard, event) {
        // Create context menu for categories
        const contextMenu = this.createContextMenu([
            { text: 'View Category', action: () => this.openCategory(categoryCard) },
            { text: 'New Post in Category', action: () => this.createPostInCategory(categoryCard) }
        ]);

        this.showContextMenu(contextMenu, event.touches[0]);
    }

    createContextMenu(items) {
        const menu = document.createElement('div');
        menu.className = 'context-menu';
        
        items.forEach(item => {
            const menuItem = document.createElement('button');
            menuItem.className = 'context-menu-item';
            menuItem.textContent = item.text;
            menuItem.addEventListener('click', () => {
                item.action();
                this.hideContextMenu();
            });
            menu.appendChild(menuItem);
        });

        return menu;
    }

    showContextMenu(menu, touch) {
        // Remove existing context menu
        this.hideContextMenu();

        // Position and show menu
        menu.style.position = 'fixed';
        menu.style.left = touch.clientX + 'px';
        menu.style.top = touch.clientY + 'px';
        menu.style.zIndex = '9999';

        document.body.appendChild(menu);

        // Hide menu when clicking outside
        setTimeout(() => {
            document.addEventListener('click', this.hideContextMenu.bind(this), { once: true });
        }, 100);
    }

    hideContextMenu() {
        const existingMenu = document.querySelector('.context-menu');
        if (existingMenu) {
            existingMenu.remove();
        }
    }

    // Context menu actions
    openPost(postCard) {
        const postLink = postCard.querySelector('.post-link');
        if (postLink) {
            window.location.href = postLink.href;
        }
    }

    sharePost(postCard) {
        const postLink = postCard.querySelector('.post-link');
        const postTitle = postCard.querySelector('.post-title');
        
        if (postLink && postTitle) {
            if (navigator.share) {
                navigator.share({
                    title: postTitle.textContent,
                    url: postLink.href
                });
            } else {
                this.copyPostLink(postCard);
            }
        }
    }

    copyPostLink(postCard) {
        const postLink = postCard.querySelector('.post-link');
        if (postLink) {
            const url = new URL(postLink.href, window.location.origin).href;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    this.showToast('Link copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showToast('Link copied to clipboard!', 'success');
            }
        }
    }

    openCategory(categoryCard) {
        const categoryLink = categoryCard.querySelector('.category-link');
        if (categoryLink) {
            window.location.href = categoryLink.href;
        }
    }

    createPostInCategory(categoryCard) {
        const categoryId = categoryCard.dataset.categoryId;
        if (categoryId) {
            window.location.href = `create-post.php?category=${categoryId}`;
        }
    }

    showToast(message, type = 'info') {
        // Use the toast system from ajax.js if available
        if (document.body.showToast) {
            document.body.showToast(message, type);
        } else {
            // Fallback alert
            alert(message);
        }
    }
}

/**
 * Smooth Scrolling and Transitions
 */
class SmoothInteractions {
    constructor() {
        this.init();
    }

    init() {
        this.setupSmoothScrolling();
        this.setupPageTransitions();
        this.setupAnimationObserver();
    }

    setupSmoothScrolling() {
        // Smooth scroll for anchor links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (!link) return;

            const targetId = link.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                e.preventDefault();
                this.smoothScrollTo(targetElement);
            }
        });

        // Smooth scroll to top button
        this.createScrollToTopButton();
    }

    setupPageTransitions() {
        // Add page transition effects
        document.body.classList.add('page-loaded');

        // Handle form submissions with loading states
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', () => {
                document.body.classList.add('page-loading');
            });
        });
    }

    setupAnimationObserver() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // Observe elements that should animate in
        const animateElements = document.querySelectorAll('.category-card, .post-card, .widget');
        animateElements.forEach(el => {
            el.classList.add('animate-on-scroll');
            observer.observe(el);
        });
    }

    smoothScrollTo(element, offset = 0) {
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }

    createScrollToTopButton() {
        const scrollButton = document.createElement('button');
        scrollButton.className = 'scroll-to-top';
        scrollButton.innerHTML = '<i class="fas fa-chevron-up"></i>';
        scrollButton.setAttribute('aria-label', 'Scroll to top');
        scrollButton.style.display = 'none';

        document.body.appendChild(scrollButton);

        // Show/hide button based on scroll position
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollButton.style.display = 'flex';
            } else {
                scrollButton.style.display = 'none';
            }
        });

        // Scroll to top when clicked
        scrollButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * Enhanced Mobile Search
 */
class MobileSearch {
    constructor() {
        this.mobileSearchInput = document.querySelector('.mobile-search-input');
        this.mobileSearchBtn = document.querySelector('.mobile-search-btn');
        this.desktopSearchInput = document.querySelector('#search-input');
        
        this.init();
    }

    init() {
        this.setupMobileSearch();
        this.syncSearchInputs();
    }

    setupMobileSearch() {
        if (this.mobileSearchBtn) {
            this.mobileSearchBtn.addEventListener('click', () => {
                this.performSearch();
            });
        }

        if (this.mobileSearchInput) {
            this.mobileSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch();
                }
            });

            // Auto-complete for mobile
            this.mobileSearchInput.addEventListener('input', () => {
                this.handleSearchInput();
            });
        }
    }

    syncSearchInputs() {
        // Sync mobile and desktop search inputs
        if (this.mobileSearchInput && this.desktopSearchInput) {
            this.mobileSearchInput.addEventListener('input', (e) => {
                this.desktopSearchInput.value = e.target.value;
            });

            this.desktopSearchInput.addEventListener('input', (e) => {
                this.mobileSearchInput.value = e.target.value;
            });
        }
    }

    performSearch() {
        const query = this.mobileSearchInput?.value.trim();
        if (query) {
            window.location.href = `forum.php?search=${encodeURIComponent(query)}`;
        }
    }

    handleSearchInput() {
        // Trigger the same search functionality as desktop
        if (window.ajaxManager && this.mobileSearchInput.value.length >= 2) {
            // Use the enhanced search from ajax.js
            const event = new Event('input');
            if (this.desktopSearchInput) {
                this.desktopSearchInput.dispatchEvent(event);
            }
        }
    }
}

// Initialize mobile functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile navigation
    window.mobileNav = new MobileNavigation();
    
    // Initialize smooth interactions
    window.smoothInteractions = new SmoothInteractions();
    
    // Initialize mobile search
    window.mobileSearch = new MobileSearch();
    
    console.log('Mobile navigation and interactions initialized');
});

// Handle orientation change
window.addEventListener('orientationchange', function() {
    // Close mobile menu on orientation change
    if (window.mobileNav && window.mobileNav.isOpen) {
        setTimeout(() => {
            window.mobileNav.closeMobileMenu();
        }, 100);
    }
});

// Prevent zoom on double tap for iOS
document.addEventListener('touchend', function(e) {
    const now = new Date().getTime();
    const timeSince = now - (window.lastTouchEnd || 0);
    
    if (timeSince < 300 && timeSince > 0) {
        e.preventDefault();
    }
    
    window.lastTouchEnd = now;
}, false);