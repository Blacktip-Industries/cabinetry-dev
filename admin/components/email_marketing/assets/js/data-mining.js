/**
 * Email Marketing Component - Data Mining JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Data mining run confirmation
    const runLinks = document.querySelectorAll('a[href*="run.php"]');
    runLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Run data mining source? This may take a while.')) {
                e.preventDefault();
            }
        });
    });
});

