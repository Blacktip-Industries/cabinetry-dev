/**
 * Authentication Helpers
 * Form validation and authentication-related JavaScript
 */

(function() {
    'use strict';
    
    // User menu dropdown toggle
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenu = document.getElementById('userMenu');
    
    if (userMenuToggle && userMenu) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    }
    
    // Form validation helpers
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePassword(password) {
        // Minimum 6 characters
        return password.length >= 6;
    }
    
    // Login form validation
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let isValid = true;
            
            // Reset previous errors
            email.classList.remove('error');
            password.classList.remove('error');
            
            // Validate email
            if (!email.value || !validateEmail(email.value)) {
                email.classList.add('error');
                isValid = false;
            }
            
            // Validate password
            if (!password.value || !validatePassword(password.value)) {
                password.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
    
    // Expose validation functions globally
    window.validateEmail = validateEmail;
    window.validatePassword = validatePassword;
})();

