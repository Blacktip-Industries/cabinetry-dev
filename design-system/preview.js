// Design System Preview - JavaScript
// Adds interactivity to the preview page

document.addEventListener('DOMContentLoaded', function() {
    // Button click handlers
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Prevent default if it's a button
            if (this.tagName === 'BUTTON') {
                e.preventDefault();
                console.log('Button clicked:', this.textContent);
                
                // Show a simple alert for demo
                if (this.classList.contains('btn-primary')) {
                    // Could show a toast notification here
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                }
            }
        });
    });

    // Input focus effects
    const inputs = document.querySelectorAll('.input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.01)';
            this.parentElement.style.transition = 'transform 0.2s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Table row hover enhancement
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transition = 'background-color 0.2s ease';
        });
    });

    // Navigation item click handlers
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            navItems.forEach(nav => nav.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            console.log('Navigation clicked:', this.textContent);
        });
    });

    // Pagination click handlers
    const paginationItems = document.querySelectorAll('.pagination-item');
    paginationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            paginationItems.forEach(pag => pag.classList.remove('active'));
            
            // Add active class to clicked item (if it's a number)
            if (!isNaN(parseInt(this.textContent))) {
                this.classList.add('active');
            }
            
            console.log('Pagination clicked:', this.textContent);
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add animation on scroll (optional enhancement)
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards for fade-in effect
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });

    // Console log for design system info
    console.log('%cLarkon Admin Dashboard Design System', 'font-size: 20px; font-weight: bold; color: #ff6c2f;');
    console.log('%cVersion 1.0.0', 'font-size: 12px; color: #5d7186;');
    console.log('All components are interactive. Check the console for interaction logs.');
});

