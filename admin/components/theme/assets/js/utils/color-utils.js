/**
 * Theme Component - Color Utilities
 * Helper functions for color manipulation
 */

(function() {
    'use strict';
    
    /**
     * Color Utilities
     */
    const ColorUtils = {
        /**
         * Convert hex to RGB
         * @param {string} hex Hex color code
         * @returns {object} RGB object with r, g, b properties
         */
        hexToRgb: function(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },
        
        /**
         * Convert RGB to hex
         * @param {number} r Red value
         * @param {number} g Green value
         * @param {number} b Blue value
         * @returns {string} Hex color code
         */
        rgbToHex: function(r, g, b) {
            return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase();
        },
        
        /**
         * Lighten a color
         * @param {string} hex Hex color code
         * @param {number} percent Percentage to lighten (0-100)
         * @returns {string} Lightened hex color code
         */
        lighten: function(hex, percent) {
            const rgb = this.hexToRgb(hex);
            if (!rgb) return hex;
            
            const r = Math.min(255, Math.round(rgb.r + (255 - rgb.r) * (percent / 100)));
            const g = Math.min(255, Math.round(rgb.g + (255 - rgb.g) * (percent / 100)));
            const b = Math.min(255, Math.round(rgb.b + (255 - rgb.b) * (percent / 100)));
            
            return this.rgbToHex(r, g, b);
        },
        
        /**
         * Darken a color
         * @param {string} hex Hex color code
         * @param {number} percent Percentage to darken (0-100)
         * @returns {string} Darkened hex color code
         */
        darken: function(hex, percent) {
            const rgb = this.hexToRgb(hex);
            if (!rgb) return hex;
            
            const r = Math.max(0, Math.round(rgb.r * (1 - percent / 100)));
            const g = Math.max(0, Math.round(rgb.g * (1 - percent / 100)));
            const b = Math.max(0, Math.round(rgb.b * (1 - percent / 100)));
            
            return this.rgbToHex(r, g, b);
        }
    };
    
    // Export to global scope
    window.ColorUtils = ColorUtils;
})();

