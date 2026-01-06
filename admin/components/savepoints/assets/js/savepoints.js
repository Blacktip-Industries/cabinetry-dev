/**
 * Savepoints Component - JavaScript
 * UI interactions and enhancements
 */

(function() {
    'use strict';

    /**
     * Initialize savepoints component
     */
    function init() {
        // Add confirmation dialogs for destructive actions
        initConfirmDialogs();
        
        // Add form validation
        initFormValidation();
        
        // Add auto-save functionality for settings
        initAutoSave();
    }

    /**
     * Initialize confirmation dialogs
     */
    function initConfirmDialogs() {
        // Restore confirmation
        const restoreForms = document.querySelectorAll('.restore-form');
        restoreForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const confirmed = confirm('Are you absolutely sure you want to restore this savepoint? This will overwrite your current filesystem and database!');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });

        // Delete confirmation
        const deleteForms = document.querySelectorAll('form[action*="delete"]');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const confirmed = confirm('Are you sure you want to delete this savepoint?');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // JSON validation for excluded/included directories
        const jsonFields = document.querySelectorAll('textarea[name="excluded_directories"], textarea[name="included_directories"]');
        jsonFields.forEach(field => {
            field.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value) {
                    try {
                        JSON.parse(value);
                        this.classList.remove('error');
                        this.classList.add('valid');
                    } catch (e) {
                        this.classList.add('error');
                        this.classList.remove('valid');
                        showFieldError(this, 'Invalid JSON format');
                    }
                }
            });
        });

        // Message validation
        const messageField = document.querySelector('textarea[name="message"]');
        if (messageField) {
            messageField.addEventListener('input', function() {
                const value = this.value.trim();
                if (value.length < 3) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }
    }

    /**
     * Show field error message
     */
    function showFieldError(field, message) {
        // Remove existing error message
        const existing = field.parentNode.querySelector('.field-error');
        if (existing) {
            existing.remove();
        }

        // Add error message
        const error = document.createElement('div');
        error.className = 'field-error';
        error.style.color = '#dc3545';
        error.style.fontSize = '0.85rem';
        error.style.marginTop = '4px';
        error.textContent = message;
        field.parentNode.appendChild(error);
    }

    /**
     * Initialize auto-save functionality
     */
    function initAutoSave() {
        // Auto-save settings after a delay
        const settingsForm = document.querySelector('.settings-form');
        if (settingsForm) {
            let saveTimeout;
            const inputs = settingsForm.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        // Could implement auto-save here if needed
                        // For now, just show a visual indicator
                        const indicator = document.createElement('span');
                        indicator.className = 'auto-save-indicator';
                        indicator.textContent = ' (unsaved changes)';
                        indicator.style.color = '#f9b931';
                        indicator.style.fontSize = '0.85rem';
                        
                        const label = input.parentNode.querySelector('label');
                        if (label && !label.querySelector('.auto-save-indicator')) {
                            label.appendChild(indicator);
                        }
                    }, 1000);
                });
            });
        }
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Format date/time
     */
    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export functions for use in other scripts
    window.Savepoints = {
        formatFileSize: formatFileSize,
        formatDateTime: formatDateTime
    };
})();

