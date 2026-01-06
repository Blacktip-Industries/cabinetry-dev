/**
 * Product Options Component - Main JavaScript
 * Client-side logic, conditional evaluation, modal handlers, form validation
 */

(function() {
    'use strict';
    
    const ProductOptions = {
        formValues: {},
        options: {},
        
        init: function() {
            this.bindEvents();
            this.evaluateConditions();
        },
        
        bindEvents: function() {
            // Bind change events to all option inputs
            document.querySelectorAll('.product-option input, .product-option select, .product-option textarea').forEach(function(input) {
                input.addEventListener('change', function() {
                    ProductOptions.updateFormValue(this);
                    ProductOptions.evaluateConditions();
                });
            });
            
            // Modal popup handlers
            document.querySelectorAll('.modal-thumbnail').forEach(function(thumbnail) {
                thumbnail.addEventListener('click', function() {
                    ProductOptions.selectThumbnail(this);
                });
            });
            
            // Range slider value display
            document.querySelectorAll('.product-option-range-slider input[type="range"]').forEach(function(slider) {
                slider.addEventListener('input', function() {
                    const valueDisplay = this.closest('.product-option-range-slider').querySelector('.slider-value');
                    if (valueDisplay) {
                        valueDisplay.textContent = this.value;
                    }
                    ProductOptions.updateFormValue(this);
                });
            });
        },
        
        updateFormValue: function(input) {
            const optionId = input.closest('.product-option').dataset.optionId;
            const optionSlug = input.name.replace('product_options[', '').replace(']', '');
            
            if (input.type === 'checkbox') {
                this.formValues[optionSlug] = input.checked ? input.value : input.nextElementSibling.value;
            } else {
                this.formValues[optionSlug] = input.value;
            }
            
            // Store in hidden field if exists
            const hiddenField = input.closest('.product-option').querySelector('input[type="hidden"][name*="_value"]');
            if (hiddenField) {
                hiddenField.value = this.formValues[optionSlug];
            }
        },
        
        evaluateConditions: function() {
            // This would make an AJAX call to evaluate conditions server-side
            // For now, basic client-side evaluation
            document.querySelectorAll('.product-option').forEach(function(optionEl) {
                const optionId = optionEl.dataset.optionId;
                // Basic show/hide logic would go here
            });
        },
        
        selectThumbnail: function(thumbnail) {
            // Remove selected class from all thumbnails in modal
            thumbnail.closest('.modal-body').querySelectorAll('.modal-thumbnail').forEach(function(t) {
                t.classList.remove('selected');
            });
            
            // Add selected class to clicked thumbnail
            thumbnail.classList.add('selected');
            
            // Update hidden input
            const modal = thumbnail.closest('.modal');
            const optionId = modal.id.replace('po_modal_', '');
            const hiddenInput = document.getElementById('po_option_' + optionId);
            if (hiddenInput) {
                hiddenInput.value = thumbnail.dataset.value;
            }
        },
        
        validateForm: function() {
            let isValid = true;
            const errors = [];
            
            document.querySelectorAll('.product-option').forEach(function(optionEl) {
                const required = optionEl.querySelector('[required]');
                if (required && !required.value) {
                    isValid = false;
                    errors.push({
                        field: required.name,
                        message: 'This field is required'
                    });
                }
            });
            
            return {
                valid: isValid,
                errors: errors
            };
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ProductOptions.init();
        });
    } else {
        ProductOptions.init();
    }
    
    // Export to global scope
    window.ProductOptions = ProductOptions;
})();

