/**
 * Access Component - Messaging JavaScript
 * Handles messaging interactions
 */

(function() {
    'use strict';

    // Mark message as read when viewed
    document.addEventListener('DOMContentLoaded', function() {
        const messageLinks = document.querySelectorAll('.access-messaging a[href*="view.php"]');
        messageLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Message will be marked as read on the server side
                // This is just for UI feedback
                const row = this.closest('tr');
                if (row) {
                    row.classList.remove('unread');
                }
            });
        });
    });

})();

