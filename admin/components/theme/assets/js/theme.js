/**
 * Theme Component - Main JavaScript
 * Handles theme switching and basic functionality
 */

(function() {
    'use strict';
    
    /**
     * Theme Manager
     */
    const ThemeManager = {
        /**
         * Switch theme
         * @param {string} themeId Theme ID
         */
        switchTheme: function(themeId) {
            // This would typically make an AJAX call to switch themes
            // For now, just update the data attribute
            document.documentElement.setAttribute('data-theme', themeId);
            
            // Store preference
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('theme_preference', themeId);
            }
        },
        
        /**
         * Get current theme
         * @returns {string} Current theme ID
         */
        getCurrentTheme: function() {
            return document.documentElement.getAttribute('data-theme') || 'light';
        },
        
        /**
         * Load saved theme preference
         */
        loadThemePreference: function() {
            if (typeof(Storage) !== "undefined") {
                const saved = localStorage.getItem('theme_preference');
                if (saved) {
                    this.switchTheme(saved);
                }
            }
        },
        
        /**
         * Initialize theme manager
         */
        init: function() {
            this.loadThemePreference();
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ThemeManager.init();
        });
    } else {
        ThemeManager.init();
    }
    
    // Export to global scope
    window.ThemeManager = ThemeManager;
})();

