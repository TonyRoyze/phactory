/**
 * Comprehensive Client-Side Form Validation Library
 * Provides real-time validation, security measures, and user feedback
 */

class FormValidator {
    constructor(formElement, options = {}) {
        this.form = formElement;
        this.options = {
            validateOnInput: true,
            validateOnBlur: true,
            showSuccessStates: true,
            debounceTime: 300,
            ...options
        };

        this.fields = new Map();
        this.validators = new Map();
        this.isValid = false;
        this.debounceTimers = new Map();

        this.init();
    }

    init() {
        this.setupDefaultValidators();
        this.bindEvents();
        this.scanFormFields();
    }

    /**
     * Setup default validation rules
     */
    setupDefaultValidators() {
        // Required field validator
        this.addValidator('required', (value, field) => {
            const trimmedValue = typeof value === 'string' ? value.trim() : value;
            if (!trimmedValue || trimmedValue === '') {
                return { valid: false, message: `${this.getFieldLabel(field)} is required` };
            }
            return { valid: true };
        });

        // Email validator
        this.addValidator('email', (value, field) => {
            if (!value) return { valid: true }; // Let required handle empty values
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(value)) {
                return { valid: false, message: 'Please enter a valid email address' };
            }
            return { valid: true };
        });

        // Username validator
        this.addValidator('username', (value, field) => {
            if (!value) return { valid: true };
            const usernamePattern = /^[a-zA-Z0-9_]{3,20}$/;
            if (!usernamePattern.test(value)) {
                return { valid: false, message: 'Username must be 3-20 characters, letters, numbers, and underscores only' };
            }
            return { valid: true };
        });

        // Password validator
        this.addValidator('password', (value, field) => {
            if (!value) return { valid: true };
            if (value.length < 8) {
                return { valid: false, message: 'Password must be at least 8 characters long' };
            }
            if (!/[A-Za-z]/.test(value)) {
                return { valid: false, message: 'Password must contain at least one letter' };
            }
            if (!/[0-9]/.test(value)) {
                return { valid: false, message: 'Password must contain at least one number' };
            }
            return { valid: true };
        });

        // Password confirmation validator
        this.addValidator('password-confirm', (value, field) => {
            if (!value) return { valid: true };
            const passwordField = this.form.querySelector('[data-validate*="password"]:not([data-validate*="password-confirm"])');
            if (passwordField && value !== passwordField.value) {
                return { valid: false, message: 'Passwords do not match' };
            }
            return { valid: true };
        });

        // Minimum length validator
        this.addValidator('minlength', (value, field, params) => {
            if (!value) return { valid: true };
            const minLength = parseInt(params) || 0;
            if (value.length < minLength) {
                return { valid: false, message: `Must be at least ${minLength} characters long` };
            }
            return { valid: true };
        });

        // Maximum length validator
        this.addValidator('maxlength', (value, field, params) => {
            if (!value) return { valid: true };
            const maxLength = parseInt(params) || 255;
            if (value.length > maxLength) {
                return { valid: false, message: `Must be less than ${maxLength} characters` };
            }
            return { valid: true };
        });

        // URL validator
        this.addValidator('url', (value, field) => {
            if (!value) return { valid: true };
            try {
                new URL(value);
                return { valid: true };
            } catch {
                return { valid: false, message: 'Please enter a valid URL' };
            }
        });

        // Numeric validator
        this.addValidator('numeric', (value, field) => {
            if (!value) return { valid: true };
            if (!/^\d+$/.test(value)) {
                return { valid: false, message: 'Please enter a valid number' };
            }
            return { valid: true };
        });

        // XSS prevention validator
        this.addValidator('no-script', (value, field) => {
            if (!value) return { valid: true };
            const scriptPattern = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
            const onEventPattern = /\bon\w+\s*=/gi;
            const javascriptPattern = /javascript:/gi;

            if (scriptPattern.test(value) || onEventPattern.test(value) || javascriptPattern.test(value)) {
                return { valid: false, message: 'Invalid content detected. Please remove any script tags or event handlers' };
            }
            return { valid: true };
        });
    }

    /**
     * Add custom validator
     */
    addValidator(name, validatorFn) {
        this.validators.set(name, validatorFn);
    }

    /**
     * Scan form for fields with validation attributes
     */
    scanFormFields() {
        const fields = this.form.querySelectorAll('[data-validate]');
        fields.forEach(field => {
            this.addField(field);
        });
    }

    /**
     * Add field for validation
     */
    addField(field) {
        const validationRules = field.dataset.validate.split('|');
        const fieldData = {
            element: field,
            rules: this.parseValidationRules(validationRules),
            errorElement: this.getOrCreateErrorElement(field),
            isValid: false
        };

        this.fields.set(field, fieldData);

        // Add visual indicators
        this.setupFieldIndicators(field);
    }

    /**
     * Parse validation rules from string
     */
    parseValidationRules(rules) {
        return rules.map(rule => {
            const [name, params] = rule.split(':');
            return { name: name.trim(), params: params ? params.trim() : null };
        });
    }

    /**
     * Get or create error display element
     */
    getOrCreateErrorElement(field) {
        const fieldId = field.id || field.name;
        let errorElement = this.form.querySelector(`#${fieldId}-error, .form-error[data-field="${fieldId}"]`);

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            errorElement.id = `${fieldId}-error`;
            errorElement.setAttribute('data-field', fieldId);
            errorElement.style.display = 'none';

            // Insert after the field or its parent form group
            const formGroup = field.closest('.form-group');
            if (formGroup) {
                formGroup.appendChild(errorElement);
            } else {
                field.parentNode.insertBefore(errorElement, field.nextSibling);
            }
        }

        return errorElement;
    }

    /**
     * Setup visual indicators for field
     */
    setupFieldIndicators(field) {
        // Add validation classes
        field.classList.add('validation-field');

        // Create success indicator if enabled
        if (this.options.showSuccessStates) {
            const successIndicator = document.createElement('span');
            successIndicator.className = 'validation-success-indicator';
            successIndicator.innerHTML = '<i class="fas fa-check"></i>';
            successIndicator.style.display = 'none';

            const formGroup = field.closest('.form-group');
            if (formGroup) {
                formGroup.appendChild(successIndicator);
            }
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.validateForm().then(isValid => {
                if (isValid) {
                    this.onFormValid(e);
                } else {
                    this.onFormInvalid(e);
                }
            });
        });

        // Field events
        this.form.addEventListener('input', (e) => {
            if (this.options.validateOnInput && this.fields.has(e.target)) {
                this.debounceValidation(e.target);
            }
        });

        this.form.addEventListener('blur', (e) => {
            if (this.options.validateOnBlur && this.fields.has(e.target)) {
                this.validateField(e.target);
            }
        }, true);

        this.form.addEventListener('focus', (e) => {
            if (this.fields.has(e.target)) {
                this.clearFieldValidation(e.target);
            }
        }, true);
    }

    /**
     * Debounced validation for input events
     */
    debounceValidation(field) {
        const timer = this.debounceTimers.get(field);
        if (timer) {
            clearTimeout(timer);
        }

        this.debounceTimers.set(field, setTimeout(() => {
            this.validateField(field);
        }, this.options.debounceTime));
    }

    /**
     * Validate single field
     */
    async validateField(field) {
        const fieldData = this.fields.get(field);
        if (!fieldData) return true;

        const value = this.getFieldValue(field);
        let isValid = true;
        let errorMessage = '';

        // Run all validation rules
        for (const rule of fieldData.rules) {
            const validator = this.validators.get(rule.name);
            if (validator) {
                const result = await validator(value, field, rule.params);
                if (!result.valid) {
                    isValid = false;
                    errorMessage = result.message;
                    break; // Stop at first error
                }
            }
        }

        // Update field state
        fieldData.isValid = isValid;
        this.updateFieldDisplay(field, isValid, errorMessage);

        return isValid;
    }

    /**
     * Validate entire form
     */
    async validateForm() {
        const validationPromises = Array.from(this.fields.keys()).map(field =>
            this.validateField(field)
        );

        const results = await Promise.all(validationPromises);
        this.isValid = results.every(result => result);

        return this.isValid;
    }

    /**
     * Update field visual state
     */
    updateFieldDisplay(field, isValid, errorMessage) {
        const fieldData = this.fields.get(field);
        const errorElement = fieldData.errorElement;
        const successIndicator = field.parentNode.querySelector('.validation-success-indicator');

        // Clear previous states
        field.classList.remove('error', 'success');

        if (isValid) {
            field.classList.add('success');
            errorElement.style.display = 'none';
            errorElement.textContent = '';

            if (successIndicator && this.options.showSuccessStates) {
                successIndicator.style.display = 'inline';
            }
        } else {
            field.classList.add('error');
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';

            if (successIndicator) {
                successIndicator.style.display = 'none';
            }
        }
    }

    /**
     * Clear field validation state
     */
    clearFieldValidation(field) {
        if (field.classList.contains('error')) {
            const fieldData = this.fields.get(field);
            if (fieldData) {
                field.classList.remove('error', 'success');
                fieldData.errorElement.style.display = 'none';
                fieldData.errorElement.textContent = '';

                const successIndicator = field.parentNode.querySelector('.validation-success-indicator');
                if (successIndicator) {
                    successIndicator.style.display = 'none';
                }
            }
        }
    }

    /**
     * Get field value
     */
    getFieldValue(field) {
        if (field.type === 'checkbox' || field.type === 'radio') {
            return field.checked;
        }
        return field.value;
    }

    /**
     * Get field label for error messages
     */
    getFieldLabel(field) {
        const label = this.form.querySelector(`label[for="${field.id}"]`);
        if (label) {
            return label.textContent.replace('*', '').trim();
        }

        return field.name || field.id || 'Field';
    }

    /**
     * Called when form is valid
     */
    onFormValid(event) {
        // Override this method or listen for custom event
        this.form.dispatchEvent(new CustomEvent('form:valid', { detail: { event } }));
    }

    /**
     * Called when form is invalid
     */
    onFormInvalid(event) {
        // Focus first invalid field
        const firstInvalidField = Array.from(this.fields.entries())
            .find(([field, data]) => !data.isValid)?.[0];

        if (firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        this.form.dispatchEvent(new CustomEvent('form:invalid', { detail: { event } }));
    }

    /**
     * Reset form validation
     */
    reset() {
        this.fields.forEach((fieldData, field) => {
            this.clearFieldValidation(field);
            fieldData.isValid = false;
        });
        this.isValid = false;
    }

    /**
     * Get validation summary
     */
    getValidationSummary() {
        const summary = {
            isValid: this.isValid,
            fields: {},
            errors: []
        };

        this.fields.forEach((fieldData, field) => {
            const fieldName = field.name || field.id;
            summary.fields[fieldName] = fieldData.isValid;

            if (!fieldData.isValid) {
                summary.errors.push({
                    field: fieldName,
                    message: fieldData.errorElement.textContent
                });
            }
        });

        return summary;
    }
}

/**
 * Security utilities for form validation
 */
class SecurityValidator {
    /**
     * Sanitize HTML input to prevent XSS
     */
    static sanitizeHTML(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }

    /**
     * Check for potentially dangerous content
     */
    static containsDangerousContent(input) {
        const dangerousPatterns = [
            /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
            /javascript:/gi,
            /vbscript:/gi,
            /onload\s*=/gi,
            /onerror\s*=/gi,
            /onclick\s*=/gi,
            /onmouseover\s*=/gi,
            /<iframe\b[^>]*>/gi,
            /<object\b[^>]*>/gi,
            /<embed\b[^>]*>/gi,
            /<link\b[^>]*>/gi,
            /<meta\b[^>]*>/gi
        ];

        return dangerousPatterns.some(pattern => pattern.test(input));
    }

    /**
     * Validate CSRF token
     */
    static validateCSRFToken(token) {
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (!metaToken) {
            console.warn('CSRF token meta tag not found');
            return false;
        }

        return token === metaToken.getAttribute('content');
    }

    /**
     * Generate secure random string
     */
    static generateSecureRandom(length = 32) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        const randomArray = new Uint8Array(length);
        crypto.getRandomValues(randomArray);

        for (let i = 0; i < length; i++) {
            result += chars[randomArray[i] % chars.length];
        }

        return result;
    }

    /**
     * Rate limiting check (client-side)
     */
    static checkRateLimit(action, maxAttempts = 5, timeWindow = 60000) {
        const key = `rate_limit_${action}`;
        const now = Date.now();

        let attempts = JSON.parse(localStorage.getItem(key) || '[]');

        // Remove old attempts outside time window
        attempts = attempts.filter(timestamp => now - timestamp < timeWindow);

        if (attempts.length >= maxAttempts) {
            return {
                allowed: false,
                resetTime: Math.ceil((attempts[0] + timeWindow - now) / 1000)
            };
        }

        // Add current attempt
        attempts.push(now);
        localStorage.setItem(key, JSON.stringify(attempts));

        return { allowed: true };
    }
}

/**
 * Auto-initialize validation on forms with data-validate-form attribute
 */
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('[data-validate-form]');

    forms.forEach(form => {
        const options = {};

        // Parse options from data attributes
        if (form.dataset.validateOnInput !== undefined) {
            options.validateOnInput = form.dataset.validateOnInput !== 'false';
        }
        if (form.dataset.validateOnBlur !== undefined) {
            options.validateOnBlur = form.dataset.validateOnBlur !== 'false';
        }
        if (form.dataset.showSuccessStates !== undefined) {
            options.showSuccessStates = form.dataset.showSuccessStates !== 'false';
        }
        if (form.dataset.debounceTime) {
            options.debounceTime = parseInt(form.dataset.debounceTime);
        }

        new FormValidator(form, options);
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormValidator, SecurityValidator };
}

// Global access
window.FormValidator = FormValidator;
window.SecurityValidator = SecurityValidator;