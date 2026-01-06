/**
 * Inventory Component - Main JavaScript
 * Core frontend functionality
 */

(function() {
    'use strict';
    
    /**
     * Initialize inventory component
     */
    function initInventory() {
        // Auto-submit forms on Enter key
        document.querySelectorAll('.inventory__form input[type="text"], .inventory__form input[type="number"]').forEach(function(input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            });
        });
        
        // Confirm delete actions
        document.querySelectorAll('a[onclick*="confirm"], a[href*="delete"]').forEach(function(link) {
            if (!link.onclick) {
                link.addEventListener('click', function(e) {
                    if (this.href.includes('delete') && !confirm('Are you sure you want to delete this item?')) {
                        e.preventDefault();
                    }
                });
            }
        });
        
        // Auto-focus search inputs
        const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="Search"], input[type="text"][placeholder*="scan"]');
        if (searchInputs.length > 0) {
            searchInputs[0].focus();
        }
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount, currency = 'USD') {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'AUD': 'A$',
            'CAD': 'C$'
        };
        
        const symbol = symbols[currency] || currency + ' ';
        return symbol + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return parseFloat(num).toLocaleString();
    }
    
    /**
     * Show alert message
     */
    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'inventory__alert inventory__alert--' + type;
        alertDiv.textContent = message;
        
        const page = document.querySelector('.inventory__page');
        if (page) {
            page.insertBefore(alertDiv, page.firstChild);
            
            setTimeout(function() {
                alertDiv.remove();
            }, 5000);
        }
    }
    
    /**
     * Validate form
     */
    function validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('inventory__input--error');
            } else {
                field.classList.remove('inventory__input--error');
            }
        });
        
        return isValid;
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInventory);
    } else {
        initInventory();
    }
    
    // Export functions for use in other scripts
    window.Inventory = {
        formatCurrency: formatCurrency,
        formatNumber: formatNumber,
        showAlert: showAlert,
        validateForm: validateForm
    };
    
})();

