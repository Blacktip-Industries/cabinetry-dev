/**
 * Menu System Component - Icon Picker JavaScript
 * Provides consistent icon picker functionality
 */

/**
 * Toggle icon picker dropdown
 * @param {HTMLElement} buttonElement - The button element that triggered the toggle
 */
function menu_system_toggleIconPicker(buttonElement) {
    const wrapper = buttonElement.closest('.icon-picker-wrapper');
    if (!wrapper) return;
    
    const dropdown = wrapper.querySelector('.icon-picker-dropdown');
    if (!dropdown) return;
    
    const isOpen = dropdown.style.display !== 'none' && dropdown.style.display !== '';
    
    // Close all other icon pickers
    document.querySelectorAll('.icon-picker-dropdown').forEach(dd => {
        if (dd !== dropdown) {
            dd.style.display = 'none';
        }
    });
    
    dropdown.style.display = isOpen ? 'none' : 'grid';
}

/**
 * Select an icon from the picker
 * @param {HTMLElement} optionElement - The option element that was clicked
 * @param {string} iconName - The name of the selected icon (empty string for "No Icon")
 * @param {Object} options - Optional configuration
 */
function menu_system_selectIcon(optionElement, iconName, options = {}) {
    const wrapper = optionElement.closest('.icon-picker-wrapper');
    if (!wrapper) return;
    
    const hiddenInput = wrapper.querySelector('.icon-picker-value');
    const display = wrapper.querySelector('.icon-picker-display');
    const dropdown = wrapper.querySelector('.icon-picker-dropdown');
    
    if (!hiddenInput) return;
    
    // Set value
    hiddenInput.value = iconName || '';
    
    // Get options
    const allIcons = options.allIcons || (typeof allIconsData !== 'undefined' ? allIconsData : []);
    const iconSize = options.iconSize || (typeof iconSizeMenuItemNum !== 'undefined' ? iconSizeMenuItemNum : 24);
    const showText = options.showText || false;
    const onSelect = options.onSelect;
    
    // Update display
    if (iconName && allIcons.length > 0) {
        const icon = allIcons.find(i => i.name === iconName);
        if (icon && icon.svg_path) {
            // Extract viewBox from stored SVG path if present
            let viewBox = '0 0 24 24';
            let svgContent = icon.svg_path;
            
            const vbMatch = svgContent.match(/<!--viewBox:([^>]+)-->/);
            if (vbMatch) {
                viewBox = vbMatch[1].trim();
                svgContent = svgContent.replace(/<!--viewBox:[^>]+-->/, '');
            }
            
            // Ensure paths have fill="currentColor" for visibility
            if (svgContent.indexOf('<path') !== -1) {
                if (svgContent.indexOf('fill=') === -1) {
                    svgContent = svgContent.replace(/<path([^>]*)>/gi, '<path$1 fill="currentColor">');
                } else {
                    svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                    svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                }
            }
            
            // Handle other SVG elements
            if (svgContent.match(/<(circle|ellipse|polygon|polyline|line|g)/i)) {
                svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
            }
            
            let displayHTML = '<svg width="' + iconSize + '" height="' + iconSize + '" viewBox="' + viewBox + '" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">' + svgContent + '</svg>';
            if (showText) {
                displayHTML += '<span>' + (icon.name || '') + '</span>';
            }
            display.innerHTML = displayHTML;
        } else {
            display.innerHTML = showText ? '<span>No Icon</span>' : '';
        }
    } else {
        display.innerHTML = showText ? '<span>No Icon</span>' : '';
    }
    
    // Trigger change event
    const changeEvent = new Event('change', { bubbles: true });
    hiddenInput.dispatchEvent(changeEvent);
    
    // Also trigger input event
    const inputEvent = new Event('input', { bubbles: true });
    hiddenInput.dispatchEvent(inputEvent);
    
    // Call custom callback if provided
    if (onSelect && typeof onSelect === 'function') {
        onSelect(iconName, hiddenInput);
    }
    
    // Close dropdown
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}

/**
 * Close all icon picker dropdowns when clicking outside
 */
function menu_system_initIconPickerCloseOnOutsideClick() {
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.icon-picker-wrapper')) {
            document.querySelectorAll('.icon-picker-dropdown').forEach(dd => {
                dd.style.display = 'none';
            });
        }
    });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', menu_system_initIconPickerCloseOnOutsideClick);
} else {
    menu_system_initIconPickerCloseOnOutsideClick();
}
