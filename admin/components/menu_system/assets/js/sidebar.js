/**
 * Menu System Component - Sidebar Toggle Functionality
 * Handles sidebar show/hide on mobile and collapse/expand functionality
 */

(function() {
    'use strict';
    
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarCollapsedKey = 'menu_system_sidebarCollapsed';
    
    // Initialize sidebar state from localStorage
    function initSidebar() {
        if (!sidebar) return;
        
        if (window.innerWidth <= 992) {
            // On mobile, sidebar is hidden by default
            sidebar.classList.remove('show');
        } else {
            // On desktop, check localStorage for collapsed state
            const isCollapsed = localStorage.getItem(sidebarCollapsedKey) === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }
    }
    
    // Toggle sidebar on mobile
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    }
    
    // Toggle sidebar collapse on desktop
    function toggleCollapse() {
        if (sidebar && window.innerWidth > 992) {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem(sidebarCollapsedKey, isCollapsed);
        }
    }
    
    // Handle window resize
    function handleResize() {
        if (!sidebar) return;
        
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('show');
        } else {
            sidebar.classList.remove('show');
            const isCollapsed = localStorage.getItem(sidebarCollapsedKey) === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }
    }
    
    // Close sidebar when clicking outside on mobile
    function handleClickOutside(event) {
        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(event.target) && sidebarToggle && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        }
    }
    
    // Event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    window.addEventListener('resize', handleResize);
    document.addEventListener('click', handleClickOutside);
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
    
    // Expose toggle function globally for potential future use
    window.menu_system_toggleSidebar = toggleSidebar;
    window.menu_system_toggleSidebarCollapse = toggleCollapse;
})();
