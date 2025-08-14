/**
 * Login Form Client-Side Validation and Handling
 * Provides form validation and submission handling for user login
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    // Form fields
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    // Error display elements
    const usernameError = document.getElementById('username-error');
    const passwordError = document.getElementById('password-error');
    
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
     * Validate username/email field
     */
    function validateUsername() {
        const username = usernameField.value.trim();
        
        if (!username) {
            showError(usernameField, usernameError, 'Username or email is required');
            return false;
        }
        
        showSuccess(usernameField, usernameError);
        return true;
    }
    
    /**
     * Validate password field
     */
    function validatePassword() {
        const password = passwordField.value;
        
        if (!password) {
            showError(passwordField, passwordError, 'Password is required');
            return false;
        }
        
        showSuccess(passwordField, passwordError);
        return true;
    }
    
    /**
     * Validate entire form
     */
    function validateForm() {
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        
        return isUsernameValid && isPasswordValid;
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
        } else {
            submitBtn.classList.remove('loading');
            btnText.classList.remove('hidden');
            loadingSpinner.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }
    
    // Real-time validation event listeners
    usernameField.addEventListener('blur', validateUsername);
    usernameField.addEventListener('input', function() {
        if (usernameField.classList.contains('error')) {
            validateUsername();
        }
    });
    
    passwordField.addEventListener('blur', validatePassword);
    passwordField.addEventListener('input', function() {
        if (passwordField.classList.contains('error')) {
            validatePassword();
        }
    });
    
    // Form submission handler
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
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                // Login successful, redirect
                window.location.href = response.url;
                return;
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
                    if (errorMessages) {
                        errorMessages.innerHTML = newErrorMessages.innerHTML;
                        errorMessages.style.display = 'block';
                    }
                    
                    // Scroll to top to show errors
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            alert('An error occurred during login. Please try again.');
        })
        .finally(() => {
            setLoadingState(false);
        });
    });
    
    // Clear validation states when user starts typing after an error
    [usernameField, passwordField].forEach(field => {
        field.addEventListener('focus', function() {
            if (field.classList.contains('error')) {
                const errorElement = document.getElementById(field.id + '-error');
                if (errorElement) {
                    clearValidation(field, errorElement);
                }
            }
        });
    });
    
    // Handle Enter key in form fields
    [usernameField, passwordField].forEach(field => {
        field.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });
    });
    
    // Auto-focus username field if it's empty
    if (!usernameField.value.trim()) {
        usernameField.focus();
    }
});