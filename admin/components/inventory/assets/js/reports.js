/**
 * Inventory Component - Reports JavaScript
 * Report generation and export functionality
 */

(function() {
    'use strict';
    
    /**
     * Export table to CSV
     */
    function exportTableToCSV(tableId, filename) {
        const table = document.getElementById(tableId) || document.querySelector('.inventory__table');
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(function(row) {
            const cols = row.querySelectorAll('th, td');
            const rowData = [];
            
            cols.forEach(function(col) {
                let text = col.textContent.trim();
                // Remove badge HTML and get text only
                text = text.replace(/\s+/g, ' ');
                // Escape quotes and wrap in quotes if contains comma
                if (text.includes(',') || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                rowData.push(text);
            });
            
            csv.push(rowData.join(','));
        });
        
        // Download CSV
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename || 'inventory-report-' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Export table to PDF (placeholder - would need PDF library)
     */
    function exportTableToPDF(tableId, title) {
        // This would integrate with a PDF generation library like jsPDF
        alert('PDF export requires additional library. Please use CSV export for now.');
    }
    
    /**
     * Print report
     */
    function printReport() {
        window.print();
    }
    
    /**
     * Filter table rows
     */
    function filterTable(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId) || document.querySelector('.inventory__table');
        
        if (!input || !table) return;
        
        const filter = input.value.toUpperCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            const text = row.textContent.toUpperCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }
    
    /**
     * Sort table by column
     */
    function sortTable(tableId, columnIndex, ascending = true) {
        const table = document.getElementById(tableId) || document.querySelector('.inventory__table');
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            // Try to parse as number
            const aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
            const bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return ascending ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison
            return ascending ? aText.localeCompare(bText) : bText.localeCompare(aText);
        });
        
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });
    }
    
    // Initialize report enhancements
    function initReports() {
        // Add export buttons if not present
        const exportButtons = document.querySelectorAll('[href*="export=csv"]');
        exportButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                // Let server handle CSV export
                // This is just for client-side enhancement
            });
        });
        
        // Add print functionality
        const printButtons = document.querySelectorAll('.inventory__button[onclick*="print"]');
        printButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                printReport();
            });
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReports);
    } else {
        initReports();
    }
    
    // Export functions
    window.InventoryReports = {
        exportTableToCSV: exportTableToCSV,
        exportTableToPDF: exportTableToPDF,
        printReport: printReport,
        filterTable: filterTable,
        sortTable: sortTable
    };
    
})();

