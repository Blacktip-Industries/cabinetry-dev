/**
 * Theme Component - Global Device Preview Button
 * Floating button accessible from any page
 */

(function() {
    'use strict';
    
    /**
     * Global Device Preview Button Manager
     */
    const GlobalPreviewButton = {
        button: null,
        modal: null,
        isOpen: false,
        
        /**
         * Initialize global button
         */
        init: function() {
            // Only show on admin pages (not on preview pages themselves)
            if (window.location.pathname.includes('device-preview')) {
                return;
            }
            
            this.createButton();
            this.createModal();
            this.setupKeyboardShortcut();
        },
        
        /**
         * Create floating button
         */
        createButton: function() {
            this.button = document.createElement('button');
            this.button.className = 'device-preview-global-button';
            this.button.innerHTML = '📱';
            this.button.setAttribute('title', 'Device Preview (Ctrl+Shift+P)');
            this.button.setAttribute('aria-label', 'Open Device Preview');
            
            this.button.addEventListener('click', () => {
                this.openModal();
            });
            
            document.body.appendChild(this.button);
        },
        
        /**
         * Create preview modal
         */
        createModal: function() {
            this.modal = document.createElement('div');
            this.modal.className = 'device-preview-modal';
            this.modal.innerHTML = `
                <div class="device-preview-modal__content">
                    <div class="device-preview-modal__header">
                        <h2 class="device-preview-modal__title">Device Preview</h2>
                        <button class="device-preview-modal__close" aria-label="Close">&times;</button>
                    </div>
                    <div class="device-preview-modal__body">
                        <iframe src="/admin/components/theme/admin/device-preview.php" 
                                style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
            `;
            
            // Close button
            const closeBtn = this.modal.querySelector('.device-preview-modal__close');
            closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
            
            // Close on backdrop click
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });
            
            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.closeModal();
                }
            });
            
            document.body.appendChild(this.modal);
        },
        
        /**
         * Open preview modal
         */
        openModal: function() {
            if (!this.modal) {
                this.createModal();
            }
            
            this.modal.classList.add('show');
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },
        
        /**
         * Close preview modal
         */
        closeModal: function() {
            if (this.modal) {
                this.modal.classList.remove('show');
                this.isOpen = false;
                document.body.style.overflow = '';
            }
        },
        
        /**
         * Setup keyboard shortcut (Ctrl+Shift+P)
         */
        setupKeyboardShortcut: function() {
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                    e.preventDefault();
                    if (this.isOpen) {
                        this.closeModal();
                    } else {
                        this.openModal();
                    }
                }
            });
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            GlobalPreviewButton.init();
        });
    } else {
        GlobalPreviewButton.init();
    }
    
    // Export to global scope
    window.GlobalPreviewButton = GlobalPreviewButton;
})();

