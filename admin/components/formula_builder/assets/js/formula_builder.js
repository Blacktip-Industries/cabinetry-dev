/**
 * Formula Builder Component - Main JavaScript
 */

(function() {
    'use strict';
    
    /**
     * Initialize Monaco Editor for formula editing
     * @param {string} containerId Container element ID
     * @param {string} textareaId Hidden textarea ID
     * @param {string} initialValue Initial code value
     */
    function initMonacoEditor(containerId, textareaId, initialValue) {
        if (typeof require === 'undefined') {
            console.error('Monaco Editor loader not found');
            return null;
        }
        
        require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/vs' } });
        
        require(['vs/editor/editor.main'], function() {
            const container = document.getElementById(containerId);
            const textarea = document.getElementById(textareaId);
            
            if (!container || !textarea) {
                console.error('Editor container or textarea not found');
                return;
            }
            
            const editor = monaco.editor.create(container, {
                value: initialValue || textarea.value || '',
                language: 'javascript',
                theme: 'vs',
                automaticLayout: true,
                minimap: { enabled: true },
                wordWrap: 'on',
                lineNumbers: 'on',
                scrollBeyondLastLine: false,
                fontSize: 14,
                tabSize: 2,
                insertSpaces: true
            });
            
            // Update hidden textarea when editor content changes
            editor.onDidChangeModelContent(function() {
                textarea.value = editor.getValue();
            });
            
            // Update editor when form is submitted
            const form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    textarea.value = editor.getValue();
                });
            }
            
            return editor;
        });
    }
    
    /**
     * Validate formula syntax in real-time
     * @param {string} formulaCode Formula code
     * @returns {Object} Validation result
     */
    function validateFormulaSyntax(formulaCode) {
        const errors = [];
        
        // Check for return statement
        if (!formulaCode.includes('return')) {
            errors.push('Formula must contain a return statement');
        }
        
        // Check balanced braces
        const openBraces = (formulaCode.match(/{/g) || []).length;
        const closeBraces = (formulaCode.match(/}/g) || []).length;
        if (openBraces !== closeBraces) {
            errors.push('Unbalanced braces');
        }
        
        // Check balanced parentheses
        const openParens = (formulaCode.match(/\(/g) || []).length;
        const closeParens = (formulaCode.match(/\)/g) || []).length;
        if (openParens !== closeParens) {
            errors.push('Unbalanced parentheses');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    /**
     * Format JSON for display
     * @param {Object} obj Object to format
     * @returns {string} Formatted JSON string
     */
    function formatJSON(obj) {
        try {
            return JSON.stringify(obj, null, 2);
        } catch (e) {
            return String(obj);
        }
    }
    
    // Export functions to global scope
    window.FormulaBuilder = {
        initMonacoEditor: initMonacoEditor,
        validateFormulaSyntax: validateFormulaSyntax,
        formatJSON: formatJSON
    };
    
    // Auto-initialize Monaco editors on page load
    document.addEventListener('DOMContentLoaded', function() {
        const editorContainers = document.querySelectorAll('[data-monaco-editor]');
        editorContainers.forEach(function(container) {
            const containerId = container.id;
            const textareaId = container.getAttribute('data-textarea-id');
            const initialValue = container.getAttribute('data-initial-value') || '';
            
            if (containerId && textareaId) {
                initMonacoEditor(containerId, textareaId, initialValue);
            }
        });
    });
})();

