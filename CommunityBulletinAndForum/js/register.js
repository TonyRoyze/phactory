/**
 * Registration Form Client-Side Validation
 * Provides real-time validation feedback and form submission handling
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    // Form fields
    const usernameField = document.getElementById('username');
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    // Error display elements
    const usernameError = document.getElementById('username-error');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');
    
    // Validation patterns
    const usernamePattern = /^[a-zA-Z0-9_]{3,20}$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;
    
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
     * Validate username field
     */
    function validateUsername() {
        const username = usernameField.value.trim();
        
        if (!username) {
            showError(usernameField, usernameError, 'Username is required');
            return false;
        }
        
        if (!usernamePattern.test(username)) {
            showError(usernameField, usernameError, 'Username must be 3-20 characters, letters, numbers, and underscores only');
            return false;
        }
        
        showSuccess(usernameField, usernameError);
        return true;
    }
    
    /**
     * Validate email field
     */
    function validateEmail() {
        const email = emailField.value.trim();
        
        if (!email) {
            showError(emailField, emailError, 'Email is required');
            return false;
        }
        
        if (!emailPattern.test(email)) {
            showError(emailField, emailError, 'Please enter a valid email address');
            return false;
        }
        
        showSuccess(emailField, emailError);
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
        
        if (password.length < 8) {
            showError(passwordField, passwordError, 'Password must be at least 8 characters long');
            return false;
        }
        
        if (!passwordPattern.test(password)) {
            showError(passwordField, passwordError, 'Password must contain both letters and numbers');
            return false;
        }
        
        showSuccess(passwordField, passwordError);
        
        // Re-validate confirm password if it has a value
        if (confirmPasswordField.value) {
            validateConfirmPassword();
        }
        
        return true;
    }
    
    /**
     * Validate confirm password field
     */
    function validateConfirmPassword() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (!confirmPassword) {
            showError(confirmPasswordField, confirmPasswordError, 'Please confirm your password');
            return false;
        }
        
        if (password !== confirmPassword) {
            showError(confirmPasswordField, confirmPasswordError, 'Passwords do not match');
            return false;
        }
        
        showSuccess(confirmPasswordField, confirmPasswordError);
        return true;
    }
    
    /**
     * Validate entire form
     */
    function validateForm() {
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();
        
        return isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid;
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
    
    emailField.addEventListener('blur', validateEmail);
    emailField.addEventListener('input', function() {
        if (emailField.classList.contains('error')) {
            validateEmail();
        }
    });
    
    passwordField.addEventListener('blur', validatePassword);
    passwordField.addEventListener('input', function() {
        if (passwordField.classList.contains('error')) {
            validatePassword();
        }
    });
    
    confirmPasswordField.addEventListener('blur', validateConfirmPassword);
    confirmPasswordField.addEventListener('input', function() {
        if (confirmPasswordField.classList.contains('error')) {
            validateConfirmPassword();
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
        
        fetch('register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                // Registration successful, redirect
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
            console.error('Registration error:', error);
            alert('An error occurred during registration. Please try again.');
        })
        .finally(() => {
            setLoadingState(false);
        });
    });
    
    // Clear validation states when user starts typing after an error
    [usernameField, emailField, passwordField, confirmPasswordField].forEach(field => {
        field.addEventListener('focus', function() {
            if (field.classList.contains('error')) {
                const errorElement = document.getElementById(field.id + '-error');
                if (errorElement) {
                    clearValidation(field, errorElement);
                }
            }
        });
    });
    
    // Password strength indicator (optional enhancement)
    passwordField.addEventListener('input', function() {
        const password = passwordField.value;
        const strengthIndicator = document.getElementById('password-strength');
        
        if (strengthIndicator) {
            let strength = 0;
            let strengthText = '';
            let strengthClass = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    strengthText = 'Weak';
                    strengthClass = 'weak';
                    break;
                case 2:
                case 3:
                    strengthText = 'Medium';
                    strengthClass = 'medium';
                    break;
                case 4:
                case 5:
                    strengthText = 'Strong';
                    strengthClass = 'strong';
                    break;
            }
            
            strengthIndicator.textContent = password ? `Password strength: ${strengthText}` : '';
            strengthIndicator.className = `password-strength ${strengthClass}`;
        }
    });
});