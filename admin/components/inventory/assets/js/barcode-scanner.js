/**
 * Inventory Component - Barcode Scanner JavaScript
 * Enhanced barcode scanning functionality
 */

(function() {
    'use strict';
    
    /**
     * Initialize barcode scanner
     */
    function initBarcodeScanner() {
        const barcodeInput = document.getElementById('barcode-input');
        if (!barcodeInput) return;
        
        let lastScanTime = 0;
        let scanBuffer = '';
        
        // Handle barcode scanner input (usually very fast typing)
        barcodeInput.addEventListener('input', function(e) {
            const now = Date.now();
            const timeSinceLastChar = now - lastScanTime;
            
            // If input is very fast (likely from scanner), wait for completion
            if (timeSinceLastChar < 50) {
                scanBuffer += e.data || '';
                clearTimeout(window.scanTimeout);
                
                window.scanTimeout = setTimeout(function() {
                    if (scanBuffer.length > 0) {
                        barcodeInput.value = scanBuffer;
                        scanBuffer = '';
                        
                        // Auto-submit if form exists
                        const form = barcodeInput.closest('form');
                        if (form && form.id === 'scan-form') {
                            form.submit();
                        }
                    }
                }, 100);
            } else {
                scanBuffer = e.data || '';
            }
            
            lastScanTime = now;
        });
        
        // Handle Enter key
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
        
        // Focus on input when page loads
        barcodeInput.focus();
        
        // Clear input on Escape
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.focus();
            }
        });
    }
    
    /**
     * Generate barcode image (placeholder - would need barcode library)
     */
    function generateBarcodeImage(barcodeValue, barcodeType) {
        // This would integrate with a barcode generation library
        // For now, return a placeholder
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }
    
    /**
     * Print barcode
     */
    function printBarcode(barcodeValue, itemName) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Barcode: ${itemName}</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                    .barcode { margin: 20px 0; }
                    .barcode-value { font-size: 18px; font-weight: bold; margin-top: 10px; }
                    .item-name { font-size: 14px; margin-top: 5px; }
                </style>
            </head>
            <body>
                <div class="barcode">
                    <div class="item-name">${itemName}</div>
                    <div class="barcode-value">${barcodeValue}</div>
                </div>
                <script>window.print();</script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBarcodeScanner);
    } else {
        initBarcodeScanner();
    }
    
    // Export functions
    window.BarcodeScanner = {
        generateBarcodeImage: generateBarcodeImage,
        printBarcode: printBarcode
    };
    
})();

