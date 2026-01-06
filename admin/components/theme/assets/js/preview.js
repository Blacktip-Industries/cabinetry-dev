/**
 * Theme Component - Preview Page JavaScript
 * Handles interactivity on the design system preview page
 */

(function() {
    'use strict';
    
    /**
     * Preview Manager
     */
    const PreviewManager = {
        /**
         * Initialize preview page
         */
        init: function() {
            this.setupThemeSwitcher();
            this.setupCodeExamples();
            this.setupComponentTabs();
        },
        
        /**
         * Setup theme switcher
         */
        setupThemeSwitcher: function() {
            const switcher = document.getElementById('theme-switcher');
            if (switcher) {
                switcher.addEventListener('change', function(e) {
                    const themeId = e.target.value;
                    if (window.ThemeManager) {
                        window.ThemeManager.switchTheme(themeId);
                    }
                });
            }
        },
        
        /**
         * Setup code example toggles
         */
        setupCodeExamples: function() {
            const codeToggles = document.querySelectorAll('.code-toggle');
            codeToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const codeBlock = this.nextElementSibling;
                    if (codeBlock && codeBlock.classList.contains('code-example')) {
                        codeBlock.style.display = codeBlock.style.display === 'none' ? 'block' : 'none';
                    }
                });
            });
        },
        
        /**
         * Setup component section tabs
         */
        setupComponentTabs: function() {
            const tabs = document.querySelectorAll('.component-tab');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetSection = document.getElementById(targetId);
                    
                    if (targetSection) {
                        // Hide all sections
                        document.querySelectorAll('.component-section').forEach(function(section) {
                            section.style.display = 'none';
                        });
                        
                        // Remove active class from all tabs
                        tabs.forEach(function(t) {
                            t.classList.remove('active');
                        });
                        
                        // Show target section
                        targetSection.style.display = 'block';
                        this.classList.add('active');
                    }
                });
            });
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            PreviewManager.init();
        });
    } else {
        PreviewManager.init();
    }
    
    // Export to global scope
    window.PreviewManager = PreviewManager;
})();

