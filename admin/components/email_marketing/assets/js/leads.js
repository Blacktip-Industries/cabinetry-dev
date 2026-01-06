/**
 * Email Marketing Component - Leads JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Lead approval confirmation
    const approveButtons = document.querySelectorAll('button[name="approve"]');
    approveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to approve this lead?')) {
                e.preventDefault();
            }
        });
    });
    
    // Lead conversion confirmation
    const convertButtons = document.querySelectorAll('button[name="convert"]');
    convertButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Convert this lead to an account? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

