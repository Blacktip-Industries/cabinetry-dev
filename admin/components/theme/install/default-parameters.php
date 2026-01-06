<?php
/**
 * Theme Component - Default Parameters
 * Inserts all default theme parameters from design-system.json
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'inserted' => int, 'errors' => array]
 */
function theme_insert_default_parameters($conn) {
    $tableName = 'theme_parameters';
    $inserted = 0;
    $errors = [];
    
    // Define all default parameters organized by section
    $defaultParams = [
        // ========== COLORS SECTION ==========
        // Primary Colors
        ['section' => 'colors', 'parameter_name' => '--color-primary', 'value' => '#FF6C2F', 'description' => 'Primary color - warm orange for main actions'],
        ['section' => 'colors', 'parameter_name' => '--color-primary-hover', 'value' => '#D95C28', 'description' => 'Primary color hover state'],
        ['section' => 'colors', 'parameter_name' => '--color-primary-subtle', 'value' => '#FFE2D5', 'description' => 'Primary color subtle variant'],
        ['section' => 'colors', 'parameter_name' => '--color-primary-border-subtle', 'value' => '#FFC4AC', 'description' => 'Primary color border subtle'],
        ['section' => 'colors', 'parameter_name' => '--color-primary-text-emphasis', 'value' => '#662B13', 'description' => 'Primary color text emphasis'],
        
        // Secondary Colors
        ['section' => 'colors', 'parameter_name' => '--color-secondary', 'value' => '#5D7186', 'description' => 'Secondary color - muted blue-gray'],
        ['section' => 'colors', 'parameter_name' => '--color-secondary-subtle', 'value' => '#DFE3E7', 'description' => 'Secondary color subtle variant'],
        ['section' => 'colors', 'parameter_name' => '--color-secondary-border-subtle', 'value' => '#BEC6CF', 'description' => 'Secondary color border subtle'],
        ['section' => 'colors', 'parameter_name' => '--color-secondary-text-emphasis', 'value' => '#252D36', 'description' => 'Secondary color text emphasis'],
        
        // Semantic Colors - Success
        ['section' => 'colors', 'parameter_name' => '--color-success', 'value' => '#22C55E', 'description' => 'Success color - fresh green'],
        ['section' => 'colors', 'parameter_name' => '--color-success-subtle', 'value' => '#D3F3DF', 'description' => 'Success color subtle variant'],
        ['section' => 'colors', 'parameter_name' => '--color-success-border-subtle', 'value' => '#A7E8BF', 'description' => 'Success color border subtle'],
        ['section' => 'colors', 'parameter_name' => '--color-success-text-emphasis', 'value' => '#0E4F26', 'description' => 'Success color text emphasis'],
        
        // Semantic Colors - Danger
        ['section' => 'colors', 'parameter_name' => '--color-danger', 'value' => '#EF5F5F', 'description' => 'Danger color - soft red'],
        ['section' => 'colors', 'parameter_name' => '--color-danger-subtle', 'value' => '#FCDEDF', 'description' => 'Danger color subtle variant'],
        ['section' => 'colors', 'parameter_name' => '--color-danger-border-subtle', 'value' => '#F9BFBF', 'description' => 'Danger color border subtle'],
        ['section' => 'colors', 'parameter_name' => '--color-danger-text-emphasis', 'value' => '#602626', 'description' => 'Danger color text emphasis'],
        
        // Semantic Colors - Warning
        ['section' => 'colors', 'parameter_name' => '--color-warning', 'value' => '#F9B931', 'description' => 'Warning color - warm yellow'],
        ['section' => 'colors', 'parameter_name' => '--color-warning-subtle', 'value' => '#FEF1D6', 'description' => 'Warning color subtle variant'],
        ['section' => 'colors', 'parameter_name' => '--color-warning-border-subtle', 'value' => '#FDE3AD', 'description' => 'Warning color border subtle'],
        ['section' => 'colors', 'parameter_name' => '--color-warning-text-emphasis', 'value' => '#644A14', 'description' => 'Warning color text emphasis'],
        
        // Semantic Colors - Info
        ['section' => 'colors', 'parameter_name' => '--color-info', 'value' => '#4ECAC2', 'description' => 'Info color - cyan'],
        ['section' => 'colors', 'parameter_name' => '--color-info-subtle', 'value' => '#DCF4F3', 'description' => 'Info color subtle variant'],
        ['section' => 'colors', 'parameter_name' => '--color-info-border-subtle', 'value' => '#B8EAE7', 'description' => 'Info color border subtle'],
        ['section' => 'colors', 'parameter_name' => '--color-info-text-emphasis', 'value' => '#1F514E', 'description' => 'Info color text emphasis'],
        
        // Neutral Colors
        ['section' => 'colors', 'parameter_name' => '--color-white', 'value' => '#FFFFFF', 'description' => 'White color'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-100', 'value' => '#F8F9FA', 'description' => 'Gray 100 - lightest'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-200', 'value' => '#EEF2F7', 'description' => 'Gray 200'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-300', 'value' => '#D8DFE7', 'description' => 'Gray 300'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-400', 'value' => '#B0B0BB', 'description' => 'Gray 400'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-500', 'value' => '#8486A7', 'description' => 'Gray 500'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-600', 'value' => '#5D7186', 'description' => 'Gray 600'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-700', 'value' => '#424E5A', 'description' => 'Gray 700'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-800', 'value' => '#36404A', 'description' => 'Gray 800'],
        ['section' => 'colors', 'parameter_name' => '--color-gray-900', 'value' => '#323A46', 'description' => 'Gray 900'],
        ['section' => 'colors', 'parameter_name' => '--color-black', 'value' => '#000000', 'description' => 'Black color'],
        
        // Background Colors
        ['section' => 'colors', 'parameter_name' => '--bg-body', 'value' => '#F9F7F7', 'description' => 'Body background color'],
        ['section' => 'colors', 'parameter_name' => '--bg-card', 'value' => '#FFFFFF', 'description' => 'Card background color'],
        ['section' => 'colors', 'parameter_name' => '--bg-topbar', 'value' => '#F9F7F7', 'description' => 'Topbar background color'],
        ['section' => 'colors', 'parameter_name' => '--bg-sidebar', 'value' => '#262D34', 'description' => 'Sidebar background color'],
        ['section' => 'colors', 'parameter_name' => '--bg-input', 'value' => '#EAE8E8', 'description' => 'Input background color'],
        ['section' => 'colors', 'parameter_name' => '--bg-search', 'value' => '#EAE8E8', 'description' => 'Search input background color'],
        ['section' => 'colors', 'parameter_name' => '--bg-hover', 'value' => '#F8F9FA', 'description' => 'Hover background color'],
        
        // Text Colors
        ['section' => 'colors', 'parameter_name' => '--text-primary', 'value' => '#313B5E', 'description' => 'Primary text color'],
        ['section' => 'colors', 'parameter_name' => '--text-secondary', 'value' => '#5D7186', 'description' => 'Secondary text color'],
        ['section' => 'colors', 'parameter_name' => '--text-tertiary', 'value' => '#424E5A', 'description' => 'Tertiary text color'],
        ['section' => 'colors', 'parameter_name' => '--text-muted', 'value' => '#707793', 'description' => 'Muted text color'],
        ['section' => 'colors', 'parameter_name' => '--text-emphasis', 'value' => 'rgba(93, 113, 134, 0.75)', 'description' => 'Text emphasis color'],
        ['section' => 'colors', 'parameter_name' => '--text-on-primary', 'value' => '#FFFFFF', 'description' => 'Text color on primary background'],
        ['section' => 'colors', 'parameter_name' => '--text-on-dark', 'value' => '#FFFFFF', 'description' => 'Text color on dark background'],
        
        // Border Colors
        ['section' => 'colors', 'parameter_name' => '--border-default', 'value' => '#EAEDF1', 'description' => 'Default border color'],
        ['section' => 'colors', 'parameter_name' => '--border-input', 'value' => '#D8DFE7', 'description' => 'Input border color'],
        ['section' => 'colors', 'parameter_name' => '--border-input-focus', 'value' => '#B0B0BB', 'description' => 'Input border color on focus'],
        ['section' => 'colors', 'parameter_name' => '--border-nav', 'value' => '#2F3944', 'description' => 'Navigation border color'],
        
        // Semantic Link Colors
        ['section' => 'colors', 'parameter_name' => '--link-color', 'value' => '#8486A7', 'description' => 'Link color'],
        ['section' => 'colors', 'parameter_name' => '--link-hover', 'value' => '#D95C28', 'description' => 'Link hover color'],
        ['section' => 'colors', 'parameter_name' => '--valid-color', 'value' => '#22C55E', 'description' => 'Valid/positive color'],
        ['section' => 'colors', 'parameter_name' => '--invalid-color', 'value' => '#EF5F5F', 'description' => 'Invalid/error color'],
        
        // ========== TYPOGRAPHY SECTION ==========
        ['section' => 'typography', 'parameter_name' => '--font-primary', 'value' => '"Play", sans-serif', 'description' => 'Primary font family'],
        ['section' => 'typography', 'parameter_name' => '--font-monospace', 'value' => 'SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace', 'description' => 'Monospace font family'],
        
        // Font Sizes
        ['section' => 'typography', 'parameter_name' => '--font-size-base', 'value' => '14px', 'description' => 'Base font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-h1', 'value' => '2.5rem', 'description' => 'H1 font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-h2', 'value' => '2rem', 'description' => 'H2 font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-h3', 'value' => '1.75rem', 'description' => 'H3 font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-h4', 'value' => '1.5rem', 'description' => 'H4 font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-h5', 'value' => '1.25rem', 'description' => 'H5 font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-h6', 'value' => '1rem', 'description' => 'H6 font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-small', 'value' => '12px', 'description' => 'Small font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-lg', 'value' => '16px', 'description' => 'Large font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-xl', 'value' => '18px', 'description' => 'Extra large font size'],
        ['section' => 'typography', 'parameter_name' => '--font-size-caption', 'value' => '10px', 'description' => 'Caption font size'],
        
        // Line Heights
        ['section' => 'typography', 'parameter_name' => '--line-height-base', 'value' => '21px', 'description' => 'Base line height'],
        ['section' => 'typography', 'parameter_name' => '--line-height-h1', 'value' => '1.2', 'description' => 'H1 line height'],
        ['section' => 'typography', 'parameter_name' => '--line-height-h2', 'value' => '1.3', 'description' => 'H2 line height'],
        ['section' => 'typography', 'parameter_name' => '--line-height-h3', 'value' => '1.4', 'description' => 'H3 line height'],
        ['section' => 'typography', 'parameter_name' => '--line-height-h4', 'value' => '1.4', 'description' => 'H4 line height'],
        ['section' => 'typography', 'parameter_name' => '--line-height-h5', 'value' => '1.5', 'description' => 'H5 line height'],
        ['section' => 'typography', 'parameter_name' => '--line-height-h6', 'value' => '1.5', 'description' => 'H6 line height'],
        
        // Font Weights
        ['section' => 'typography', 'parameter_name' => '--font-weight-regular', 'value' => '400', 'description' => 'Regular font weight'],
        ['section' => 'typography', 'parameter_name' => '--font-weight-medium', 'value' => '500', 'description' => 'Medium font weight'],
        ['section' => 'typography', 'parameter_name' => '--font-weight-semibold', 'value' => '600', 'description' => 'Semibold font weight'],
        ['section' => 'typography', 'parameter_name' => '--font-weight-bold', 'value' => '700', 'description' => 'Bold font weight'],
        
        // ========== SPACING SECTION ==========
        ['section' => 'spacing', 'parameter_name' => '--spacing-xs', 'value' => '4px', 'description' => 'Extra small spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-sm', 'value' => '8px', 'description' => 'Small spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-md', 'value' => '12px', 'description' => 'Medium spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-lg', 'value' => '16px', 'description' => 'Large spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-xl', 'value' => '24px', 'description' => 'Extra large spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-2xl', 'value' => '32px', 'description' => '2X large spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-3xl', 'value' => '48px', 'description' => '3X large spacing'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-4xl', 'value' => '64px', 'description' => '4X large spacing'],
        
        // Component Spacing
        ['section' => 'spacing', 'parameter_name' => '--spacing-card-padding', 'value' => '24px', 'description' => 'Card internal padding'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-container-padding', 'value' => '24px', 'description' => 'Container padding'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-section-spacing', 'value' => '24px', 'description' => 'Spacing between sections'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-element-gap', 'value' => '16px', 'description' => 'Gap between elements'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-form-field-gap', 'value' => '16px', 'description' => 'Gap between form fields'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-badge-padding-y', 'value' => '3px', 'description' => 'Badge vertical padding'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-badge-padding-x', 'value' => '6px', 'description' => 'Badge horizontal padding'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-table-cell-vertical', 'value' => '13.6px', 'description' => 'Table cell vertical padding'],
        ['section' => 'spacing', 'parameter_name' => '--spacing-table-cell-horizontal', 'value' => '24px', 'description' => 'Table cell horizontal padding'],
        
        // ========== SHADOWS SECTION ==========
        ['section' => 'shadows', 'parameter_name' => '--shadow-sm', 'value' => '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)', 'description' => 'Small shadow'],
        ['section' => 'shadows', 'parameter_name' => '--shadow-default', 'value' => '0px 3px 4px 0px rgba(0, 0, 0, 0.03)', 'description' => 'Default shadow'],
        ['section' => 'shadows', 'parameter_name' => '--shadow-lg', 'value' => '0 5px 10px rgba(30, 32, 37, 0.12)', 'description' => 'Large shadow'],
        ['section' => 'shadows', 'parameter_name' => '--shadow-inset', 'value' => 'inset 0 1px 2px rgba(0, 0, 0, 0.075)', 'description' => 'Inset shadow'],
        
        // ========== BORDERS SECTION ==========
        ['section' => 'borders', 'parameter_name' => '--border-width', 'value' => '1px', 'description' => 'Default border width'],
        ['section' => 'borders', 'parameter_name' => '--border-width-thick', 'value' => '2px', 'description' => 'Thick border width'],
        ['section' => 'borders', 'parameter_name' => '--radius-sm', 'value' => '0.5rem', 'description' => 'Small border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-default', 'value' => '0.75rem', 'description' => 'Default border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-md', 'value' => '8px', 'description' => 'Medium border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-lg', 'value' => '1rem', 'description' => 'Large border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-xl', 'value' => '1.25rem', 'description' => 'Extra large border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-2xl', 'value' => '2rem', 'description' => '2X large border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-pill', 'value' => '50rem', 'description' => 'Pill border radius'],
        ['section' => 'borders', 'parameter_name' => '--radius-full', 'value' => '50%', 'description' => 'Full circle border radius'],
        
        // ========== TRANSITIONS SECTION ==========
        ['section' => 'transitions', 'parameter_name' => '--transition-default', 'value' => '0.2s ease', 'description' => 'Default transition'],
        ['section' => 'transitions', 'parameter_name' => '--transition-fast', 'value' => '0.15s ease', 'description' => 'Fast transition'],
        ['section' => 'transitions', 'parameter_name' => '--transition-slow', 'value' => '0.3s ease', 'description' => 'Slow transition'],
        
        // ========== BREAKPOINTS SECTION ==========
        ['section' => 'breakpoints', 'parameter_name' => '--breakpoint-xs', 'value' => '0px', 'description' => 'Extra small breakpoint'],
        ['section' => 'breakpoints', 'parameter_name' => '--breakpoint-sm', 'value' => '576px', 'description' => 'Small breakpoint'],
        ['section' => 'breakpoints', 'parameter_name' => '--breakpoint-md', 'value' => '768px', 'description' => 'Medium breakpoint'],
        ['section' => 'breakpoints', 'parameter_name' => '--breakpoint-lg', 'value' => '992px', 'description' => 'Large breakpoint'],
        ['section' => 'breakpoints', 'parameter_name' => '--breakpoint-xl', 'value' => '1200px', 'description' => 'Extra large breakpoint'],
        ['section' => 'breakpoints', 'parameter_name' => '--breakpoint-2xl', 'value' => '1400px', 'description' => '2X large breakpoint'],
        
        // ========== Z-INDEX SECTION ==========
        ['section' => 'z-index', 'parameter_name' => '--z-index-dropdown', 'value' => '1000', 'description' => 'Dropdown z-index'],
        ['section' => 'z-index', 'parameter_name' => '--z-index-sticky', 'value' => '1020', 'description' => 'Sticky element z-index'],
        ['section' => 'z-index', 'parameter_name' => '--z-index-fixed', 'value' => '1030', 'description' => 'Fixed element z-index'],
        ['section' => 'z-index', 'parameter_name' => '--z-index-modal-backdrop', 'value' => '1040', 'description' => 'Modal backdrop z-index'],
        ['section' => 'z-index', 'parameter_name' => '--z-index-modal', 'value' => '1050', 'description' => 'Modal z-index'],
        ['section' => 'z-index', 'parameter_name' => '--z-index-popover', 'value' => '1060', 'description' => 'Popover z-index'],
        ['section' => 'z-index', 'parameter_name' => '--z-index-tooltip', 'value' => '1070', 'description' => 'Tooltip z-index'],
        
        // ========== FOCUS SECTION ==========
        ['section' => 'focus', 'parameter_name' => '--focus-ring-width', 'value' => '0.15rem', 'description' => 'Focus ring width'],
        ['section' => 'focus', 'parameter_name' => '--focus-ring-color', 'value' => 'rgba(255, 108, 47, 0.25)', 'description' => 'Focus ring color'],
        ['section' => 'focus', 'parameter_name' => '--focus-ring-offset', 'value' => '2px', 'description' => 'Focus ring offset'],
        
        // ========== DIMENSIONS SECTION ==========
        // Avatar Sizes
        ['section' => 'dimensions', 'parameter_name' => '--avatar-size-sm', 'value' => '32px', 'description' => 'Small avatar size'],
        ['section' => 'dimensions', 'parameter_name' => '--avatar-size-md', 'value' => '40px', 'description' => 'Medium avatar size'],
        ['section' => 'dimensions', 'parameter_name' => '--avatar-size-lg', 'value' => '56px', 'description' => 'Large avatar size'],
        
        // Icon Sizes
        ['section' => 'dimensions', 'parameter_name' => '--icon-size-sm', 'value' => '22px', 'description' => 'Small icon size'],
        ['section' => 'dimensions', 'parameter_name' => '--icon-size-md', 'value' => '32px', 'description' => 'Medium icon container size'],
        ['section' => 'dimensions', 'parameter_name' => '--icon-font-size-lg', 'value' => '24px', 'description' => 'Large icon font size'],
        
        // Form Element Sizes
        ['section' => 'dimensions', 'parameter_name' => '--checkbox-size', 'value' => '18px', 'description' => 'Checkbox and radio button size'],
        ['section' => 'dimensions', 'parameter_name' => '--textarea-min-height', 'value' => '100px', 'description' => 'Textarea minimum height'],
        
        // Component-Specific Dimensions
        ['section' => 'dimensions', 'parameter_name' => '--empty-state-icon-size', 'value' => '48px', 'description' => 'Empty state icon size'],
        ['section' => 'dimensions', 'parameter_name' => '--modal-max-width', 'value' => '600px', 'description' => 'Modal maximum width'],
        ['section' => 'dimensions', 'parameter_name' => '--modal-width', 'value' => '90%', 'description' => 'Modal width'],
        ['section' => 'dimensions', 'parameter_name' => '--modal-max-height', 'value' => '90vh', 'description' => 'Modal maximum height'],
        ['section' => 'dimensions', 'parameter_name' => '--modal-backdrop-blur', 'value' => '2px', 'description' => 'Modal backdrop blur amount'],
        ['section' => 'dimensions', 'parameter_name' => '--dropdown-min-width', 'value' => '200px', 'description' => 'Dropdown minimum width'],
        
        // Progress Bar Heights
        ['section' => 'dimensions', 'parameter_name' => '--progress-height-sm', 'value' => '4px', 'description' => 'Small progress bar height'],
        ['section' => 'dimensions', 'parameter_name' => '--progress-height', 'value' => '8px', 'description' => 'Default progress bar height'],
        ['section' => 'dimensions', 'parameter_name' => '--progress-height-lg', 'value' => '12px', 'description' => 'Large progress bar height'],
    ];
    
    // Insert each parameter
    foreach ($defaultParams as $param) {
        try {
            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, description, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("ssss", 
                $param['section'],
                $param['parameter_name'],
                $param['description'],
                $param['value']
            );
            
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = "Failed to insert parameter: " . $param['parameter_name'];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

