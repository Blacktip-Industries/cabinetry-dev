/**
 * Frontend Authentication Helpers
 * Form validation and authentication-related JavaScript for frontend
 */

(function() {
    'use strict';
    
    // Frontend user menu dropdown toggle
    const frontendUserMenuToggle = document.getElementById('frontendUserMenuToggle');
    const frontendUserMenu = document.getElementById('frontendUserMenu');
    
    if (frontendUserMenuToggle && frontendUserMenu) {
        frontendUserMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            frontendUserMenu.classList.toggle('show');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!frontendUserMenuToggle.contains(e.target) && !frontendUserMenu.contains(e.target)) {
                frontendUserMenu.classList.remove('show');
                // Close all nested submenus
                frontendUserMenu.querySelectorAll('.admin-header__user-menu-submenu.show').forEach(function(submenu) {
                    submenu.classList.remove('show');
                });
            }
        });
        
        // Handle nested dropdowns (parent items with children)
        const parentItems = frontendUserMenu.querySelectorAll('.admin-header__user-menu-item--has-children');
        parentItems.forEach(function(parentItem) {
            const parentLink = parentItem.querySelector('.admin-header__user-menu-link--parent');
            const submenu = parentItem.querySelector('.admin-header__user-menu-submenu');
            
            if (parentLink && submenu) {
                parentLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Close other submenus
                    frontendUserMenu.querySelectorAll('.admin-header__user-menu-submenu.show').forEach(function(otherSubmenu) {
                        if (otherSubmenu !== submenu) {
                            otherSubmenu.classList.remove('show');
                        }
                    });
                    
                    // Toggle current submenu
                    submenu.classList.toggle('show');
                });
            }
        });
    }
    
    // Frontend login form validation
    const frontendLoginForm = document.querySelector('.login-form');
    if (frontendLoginForm) {
        frontendLoginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let isValid = true;
            
            // Reset previous errors
            if (email) email.classList.remove('error');
            if (password) password.classList.remove('error');
            
            // Validate email
            if (!email || !email.value || !validateEmail(email.value)) {
                if (email) email.classList.add('error');
                isValid = false;
            }
            
            // Validate password
            if (!password || !password.value || !validatePassword(password.value)) {
                if (password) password.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Form validation helpers (reuse from backend if available, otherwise define)
    function validateEmail(email) {
        if (window.validateEmail) {
            return window.validateEmail(email);
        }
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePassword(password) {
        if (window.validatePassword) {
            return window.validatePassword(password);
        }
        // Minimum 6 characters
        return password.length >= 6;
    }
    
    // Expose validation functions globally
    window.validateEmail = validateEmail;
    window.validatePassword = validatePassword;
})();

