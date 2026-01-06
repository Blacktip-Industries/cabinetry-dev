/**
 * SEO Manager Component - JavaScript
 */

(function() {
    'use strict';
    
    // Initialize SEO Manager
    document.addEventListener('DOMContentLoaded', function() {
        console.log('SEO Manager initialized');
    });
    
    // API helper functions
    window.SEOManager = {
        optimizeContent: function(url) {
            return fetch('/admin/components/seo_manager/api/optimize-content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ url: url })
            }).then(response => response.json());
        }
    };
})();

