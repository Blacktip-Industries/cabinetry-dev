/**
 * Access Component - JavaScript
 * UI interactions and dynamic form generation
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Initialize conditional field display
        initConditionalFields();
        
        // Initialize form validation
        initFormValidation();
        
        // Initialize dynamic form generation
        initDynamicForms();
    }

    /**
     * Initialize conditional field display
     */
    function initConditionalFields() {
        const forms = document.querySelectorAll('.access-form');
        forms.forEach(form => {
            const fields = form.querySelectorAll('[data-conditional]');
            fields.forEach(field => {
                const conditional = JSON.parse(field.getAttribute('data-conditional'));
                const targetField = form.querySelector('[name="' + conditional.field + '"]');
                
                if (targetField) {
                    // Initial check
                    checkConditionalField(field, targetField, conditional);
                    
                    // Watch for changes
                    targetField.addEventListener('change', function() {
                        checkConditionalField(field, targetField, conditional);
                    });
                }
            });
        });
    }

    /**
     * Check and show/hide conditional field
     */
    function checkConditionalField(field, targetField, conditional) {
        const targetValue = targetField.value;
        const shouldShow = conditional.value === targetValue;
        
        const fieldWrapper = field.closest('.form-group');
        if (fieldWrapper) {
            fieldWrapper.style.display = shouldShow ? '' : 'none';
        }
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('.access-form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    }

    /**
     * Validate form
     */
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                showFieldError(field, 'This field is required');
            } else {
                field.classList.remove('error');
                clearFieldError(field);
            }
        });
        
        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !isValidEmail(field.value)) {
                isValid = false;
                field.classList.add('error');
                showFieldError(field, 'Please enter a valid email address');
            }
        });
        
        // Password confirmation
        const passwordFields = form.querySelectorAll('input[type="password"]');
        if (passwordFields.length >= 2) {
            const password = passwordFields[0].value;
            const passwordConfirm = passwordFields[1].value;
            
            if (password && passwordConfirm && password !== passwordConfirm) {
                isValid = false;
                passwordFields[1].classList.add('error');
                showFieldError(passwordFields[1], 'Passwords do not match');
            }
        }
        
        return isValid;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        clearFieldError(field);
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.color = '#f44336';
        errorElement.style.fontSize = '12px';
        errorElement.style.marginTop = '5px';
        
        field.parentNode.appendChild(errorElement);
    }

    /**
     * Clear field error
     */
    function clearFieldError(field) {
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    /**
     * Validate email
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Initialize dynamic form generation
     */
    function initDynamicForms() {
        // Handle account type selection for registration
        const accountTypeSelect = document.getElementById('account_type_id');
        if (accountTypeSelect) {
            accountTypeSelect.addEventListener('change', function() {
                // Form will reload with selected account type
                // Additional JS can be added here for AJAX loading
            });
        }
    }

    /**
     * Generate field HTML based on field definition
     */
    function generateFieldHTML(field) {
        let html = '<div class="form-group">';
        html += '<label for="field_' + field.field_name + '">';
        html += field.field_label;
        if (field.is_required) {
            html += ' <span class="required">*</span>';
        }
        html += '</label>';
        
        switch (field.field_type) {
            case 'textarea':
                html += '<textarea id="field_' + field.field_name + '" name="field_' + field.field_name + '"';
                if (field.is_required) html += ' required';
                html += '></textarea>';
                break;
                
            case 'select':
                html += '<select id="field_' + field.field_name + '" name="field_' + field.field_name + '"';
                if (field.is_required) html += ' required';
                html += '>';
                html += '<option value="">Select...</option>';
                if (field.options_json) {
                    const options = JSON.parse(field.options_json);
                    options.forEach(option => {
                        html += '<option value="' + escapeHtml(option) + '">' + escapeHtml(option) + '</option>';
                    });
                }
                html += '</select>';
                break;
                
            default:
                html += '<input type="' + field.field_type + '" id="field_' + field.field_name + '" name="field_' + field.field_name + '"';
                if (field.is_required) html += ' required';
                if (field.placeholder) html += ' placeholder="' + escapeHtml(field.placeholder) + '"';
                html += '>';
        }
        
        if (field.help_text) {
            html += '<small>' + escapeHtml(field.help_text) + '</small>';
        }
        
        html += '</div>';
        return html;
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Export functions for use in other scripts
    window.AccessComponent = {
        generateFieldHTML: generateFieldHTML,
        validateForm: validateForm
    };

})();

