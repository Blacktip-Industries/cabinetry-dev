<?php
/**
 * Settings Parameters Page
 * Admin page for managing all design system parameters
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/icon_picker.php';
require_once __DIR__ . '/../../config/database.php';

// Get search query and section filter (check both GET and POST to preserve after form submission)
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : (isset($_POST['search_filter']) ? trim($_POST['search_filter']) : '');
$sectionFilter = isset($_GET['section']) ? trim($_GET['section']) : (isset($_POST['section_filter']) ? trim($_POST['section_filter']) : '');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle form submission for updating parameters (BEFORE layout output to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_parameters'])) {
    $updated = 0;
    $errors = [];
    $updatedParamNames = []; // Track which parameters were updated
    
    // Only process changed parameters (those submitted in the form)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'parameter_value_') === 0 && strpos($key, '_hidden') === false) {
            $id = str_replace('parameter_value_', '', $key);
            // Handle multiselect (array)
            if (is_array($value)) {
                $newValue = implode(',', array_map('trim', $value));
            } else {
                $newValue = trim($value);
            }
            $id = intval($id);
            
            if ($id > 0) {
                // Get parameter to validate range and input type
                $stmt = $conn->prepare("SELECT sp.min_range, sp.max_range, sp.parameter_name, sp.value, spc.input_type FROM settings_parameters sp LEFT JOIN settings_parameters_configs spc ON sp.id = spc.parameter_id WHERE sp.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $param = $result->fetch_assoc();
                $stmt->close();
                
                if ($param) {
                    // Handle checkbox - if unchecked, use hidden field value
                    if ($param['input_type'] === 'checkbox') {
                        $hiddenKey = 'parameter_value_' . $id . '_hidden';
                        if (!isset($_POST[$key]) && isset($_POST[$hiddenKey])) {
                            $newValue = 'no';
                        } else {
                            $newValue = 'yes';
                        }
                    }
                    
                    // Only update if value actually changed
                    if ($param['value'] !== $newValue) {
                        // Validate range if applicable
                        $isValid = true;
                        if ($param['min_range'] !== null || $param['max_range'] !== null) {
                            $numValue = floatval($newValue);
                            if ($param['min_range'] !== null && $numValue < $param['min_range']) {
                                $errors[] = $param['parameter_name'] . ": Value must be at least " . $param['min_range'];
                                $isValid = false;
                            } elseif ($param['max_range'] !== null && $numValue > $param['max_range']) {
                                $errors[] = $param['parameter_name'] . ": Value must be at most " . $param['max_range'];
                                $isValid = false;
                            }
                        }
                        
                        if ($isValid) {
                            if (updateParameter($id, $newValue)) {
                                $updated++;
                                $updatedParamNames[] = $param['parameter_name'];
                            } else {
                                $errors[] = $param['parameter_name'] . ": Failed to update";
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Redirect after successful POST to preserve filter state and avoid resubmission
    if ($updated > 0 && empty($errors)) {
        $redirectParams = ['saved' => '1', 'count' => $updated];
        if (!empty($searchQuery)) {
            $redirectParams['search'] = $searchQuery;
        }
        if (!empty($sectionFilter)) {
            $redirectParams['section'] = $sectionFilter;
        }
        // Encode updated parameter names for URL
        if (!empty($updatedParamNames)) {
            $redirectParams['updated'] = base64_encode(json_encode($updatedParamNames));
        }
        $redirectUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($redirectParams);
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    if ($updated > 0) {
        $success = "Successfully updated {$updated} parameter" . ($updated !== 1 ? 's' : '');
        if (!empty($updatedParamNames)) {
            $success .= "<ul style='margin-top: 10px; margin-bottom: 0; padding-left: 20px;'>";
            foreach ($updatedParamNames as $paramName) {
                $success .= "<li>" . htmlspecialchars($paramName) . "</li>";
            }
            $success .= "</ul>";
        }
    } else {
        $success = "No parameters were changed";
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}

// Check for saved success message from redirect
if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $updatedCount = isset($_GET['count']) ? intval($_GET['count']) : 0;
    if ($updatedCount > 0) {
        $success = "Successfully updated {$updatedCount} parameter" . ($updatedCount !== 1 ? 's' : '');
        if (isset($_GET['updated'])) {
            $updatedParamNames = json_decode(base64_decode($_GET['updated']), true);
            if (!empty($updatedParamNames) && is_array($updatedParamNames)) {
                $success .= "<ul style='margin-top: 10px; margin-bottom: 0; padding-left: 20px;'>";
                foreach ($updatedParamNames as $paramName) {
                    $success .= "<li>" . htmlspecialchars($paramName) . "</li>";
                }
                $success .= "</ul>";
            }
        }
    } else {
        $success = "No parameters were changed";
    }
}

// Auto-menu creation removed - menu items should be managed through the Menus page
// syncSettingSectionMenus(); // Disabled to allow full control over menu items

startLayout('Settings - Parameters', true, 'settings_parameters');

// Get all sections for dropdown
$allSections = getAllSections();

// Always load all parameters - JavaScript will handle filtering
// This ensures dropdown changes work correctly after form submission
$parameters = getAllParametersBySection();

// Get all icons for icon picker (if needed)
$allIcons = [];
$iconSortOrder = getParameter('Icons', '--icon-sort-order', 'name');
if ($iconSortOrder === null || $iconSortOrder === '') {
    $iconSortOrder = 'name';
}
$allIcons = getAllIcons($iconSortOrder);
// Apply consistent sorting for icon pickers (Default, Favourites, then categories)
$allIcons = sortIconsForDisplay($allIcons);

// Get icon size for icon picker display
$iconSizeMenuItem = getParameter('Icons', '--icon-size-menu-item', '24px');
$iconSizeMenuItemNum = preg_replace('/[^0-9.]/', '', $iconSizeMenuItem);
if (empty($iconSizeMenuItemNum)) {
    $iconSizeMenuItemNum = '24';
}

// Get indent parameter values
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');
$indentParameterHelperText = getParameter('Indents', '--indent-parameter-helper-text', '0');
$indentParameterRangeInfo = getParameter('Indents', '--indent-parameter-range-info', '0');

/**
 * Render input field based on input_type from database or fallback to heuristics
 * @param array $param Parameter data including input_type, options, etc.
 * @return string HTML for input field
 */
function renderParameterInput($param) {
    $inputType = $param['input_type'] ?? null;
    $options = $param['options'] ?? null;
    $placeholder = $param['placeholder'] ?? null;
    $helpText = $param['help_text'] ?? null;
    $validation = $param['validation'] ?? null;
    
    // Normalize value to string (handle arrays)
    $paramValue = $param['value'];
    if (is_array($paramValue)) {
        // If value is an array, check if it's the options structure or actual values
        if (isset($paramValue['options'])) {
            // This is the options structure, not the actual value - use empty string
            $paramValue = '';
        } else {
            // This is an array of values (e.g., multiselect), convert to comma-separated string
            $paramValue = implode(',', $paramValue);
        }
    } else {
        $paramValue = (string)$paramValue;
    }
    $param['value'] = $paramValue; // Update the param array with normalized value
    
    // Parse options_json structure if options is an array with 'options' key
    if (is_array($options) && isset($options['options']) && is_array($options['options'])) {
        $options = $options['options'];
    }
    
    // If no input_type from database, use heuristics as fallback
    if (!$inputType) {
        $value = trim($paramValue);
        $paramNameLower = strtolower($param['parameter_name']);
        
        // Heuristic detection (fallback)
        $isNumeric = is_numeric($value);
        $isColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $value) || preg_match('/^rgba?\(/', $value);
        $isYesNo = in_array(strtolower($value), ['yes', 'no']);
        $isDisplayHide = in_array(strtoupper($value), ['DISPLAY', 'HIDE']);
        $isFont = strpos($paramNameLower, 'font') !== false && 
                  (strpos($paramNameLower, 'primary') !== false || 
                   strpos($paramNameLower, 'secondary') !== false ||
                   strpos($paramNameLower, 'family') !== false);
        $isIconPicker = (strpos($paramNameLower, '-icon') !== false && 
                        strpos($paramNameLower, '-size') === false && // Exclude size parameters
                        (strpos($paramNameLower, 'button') !== false || 
                         strpos($paramNameLower, 'favourite') !== false ||
                         strpos($paramNameLower, 'edit') !== false ||
                         strpos($paramNameLower, 'delete') !== false));
        
        if ($isColor) {
            $inputType = 'color';
        } elseif ($isYesNo) {
            $inputType = 'dropdown';
            $options = ['yes', 'no'];
        } elseif ($isDisplayHide) {
            $inputType = 'dropdown';
            $options = ['DISPLAY', 'HIDE'];
        } elseif ($isNumeric && ($param['min_range'] !== null || $param['max_range'] !== null)) {
            $inputType = 'number';
            $options = [];
            if ($param['min_range'] !== null) $options['min'] = floatval($param['min_range']);
            if ($param['max_range'] !== null) $options['max'] = floatval($param['max_range']);
            $options['step'] = 0.01;
        } elseif ($isFont) {
            $inputType = 'font'; // Special case
        } elseif ($isIconPicker) {
            $inputType = 'icon'; // Icon picker
        } else {
            $inputType = 'text';
        }
    }
    
    $html = '';
    $paramId = $param['id'];
    $paramValue = htmlspecialchars($paramValue);
    $paramName = htmlspecialchars($param['parameter_name']);
    
    switch ($inputType) {
        case 'color':
            $colorPickerSize = getParameter('Color Picker', '--color-picker-size', '50px');
            $colorPickerBorder = getParameter('Color Picker', '--color-picker-border', '#EAEDF1');
            $colorPickerBorderRadius = getParameter('Color Picker', '--color-picker-border-radius', '8px');
            $colorValue = preg_match('/^#[0-9A-Fa-f]{6}$/', $paramValue) ? $paramValue : '#ffffff';
            $html = '<div style="display: flex; gap: var(--spacing-sm); align-items: center;">';
            $html .= '<input type="color" id="color_' . $paramId . '" value="' . htmlspecialchars($colorValue) . '" data-param-id="' . $paramId . '" style="width: ' . htmlspecialchars($colorPickerSize) . '; height: ' . htmlspecialchars($colorPickerSize) . '; border: 1px solid ' . htmlspecialchars($colorPickerBorder) . '; border-radius: ' . htmlspecialchars($colorPickerBorderRadius) . '; cursor: pointer;">';
            $html .= '<input type="text" id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" class="input param-input" value="' . $paramValue . '" data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '" placeholder="' . htmlspecialchars($placeholder ?: '') . '" style="flex: 1;">';
            $html .= '</div>';
            break;
            
        case 'dropdown':
            $html = '<select id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" class="input param-input" data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '">';
            if ($options && is_array($options)) {
                foreach ($options as $option) {
                    $optionValue = is_array($option) ? ($option['value'] ?? $option) : $option;
                    $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                    $paramValueStr = is_string($paramValue) ? $paramValue : (string)$paramValue;
                    $selected = (strcasecmp(trim($paramValueStr), trim($optionValue)) === 0) ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($optionLabel) . '</option>';
                }
            }
            $html .= '</select>';
            break;
            
        case 'multiselect':
            $selectedValues = is_array($paramValue) ? $paramValue : (strpos($paramValue, ',') !== false ? explode(',', $paramValue) : [$paramValue]);
            $html = '<select id="param_' . $paramId . '" name="parameter_value_' . $paramId . '[]" class="input param-input param-multiselect" multiple data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '" style="min-height: 100px;">';
            if ($options && is_array($options)) {
                foreach ($options as $option) {
                    $optionValue = is_array($option) ? ($option['value'] ?? $option) : $option;
                    $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                    $selected = in_array(trim($optionValue), array_map('trim', $selectedValues)) ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($optionLabel) . '</option>';
                }
            }
            $html .= '</select>';
            break;
            
        case 'checkbox':
            $checked = in_array(strtolower(trim($paramValue)), ['yes', '1', 'true', 'on']) ? ' checked' : '';
            $html = '<label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">';
            $html .= '<input type="checkbox" id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" class="param-input param-checkbox" value="yes" data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '"' . $checked . '>';
            $html .= '<input type="hidden" name="parameter_value_' . $paramId . '_hidden" value="no">';
            $html .= '<span>' . ($checked ? 'Yes' : 'No') . '</span>';
            $html .= '</label>';
            break;
            
        case 'number':
            $min = ($options && isset($options['min'])) ? $options['min'] : ($param['min_range'] ?? null);
            $max = ($options && isset($options['max'])) ? $options['max'] : ($param['max_range'] ?? null);
            $step = ($options && isset($options['step'])) ? $options['step'] : '0.01';
            $html = '<input type="number" id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" class="input param-input" value="' . $paramValue . '" data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '" placeholder="' . htmlspecialchars($placeholder ?: '') . '"';
            if ($min !== null) $html .= ' min="' . htmlspecialchars($min) . '"';
            if ($max !== null) $html .= ' max="' . htmlspecialchars($max) . '"';
            $html .= ' step="' . htmlspecialchars($step) . '">';
            break;
            
        case 'textarea':
            $rows = ($options && isset($options['rows'])) ? $options['rows'] : 4;
            $html = '<textarea id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" class="input param-input" data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '" placeholder="' . htmlspecialchars($placeholder ?: '') . '" rows="' . $rows . '" style="width: 100%; resize: vertical;">' . $paramValue . '</textarea>';
            break;
            
        case 'font':
            // Special font selector (keep existing implementation)
            $currentValue = htmlspecialchars($paramValue);
            $currentFont = '';
            if (preg_match('/"([^"]+)"/', $paramValue, $matches)) {
                $currentFont = $matches[1];
            } elseif (preg_match("/'([^']+)'/", $paramValue, $matches)) {
                $currentFont = $matches[1];
            }
            
            $currentFallback = 'sans-serif';
            if (preg_match('/,\s*(sans-serif|serif|monospace|cursive|fantasy)/i', $paramValue, $matches)) {
                $currentFallback = strtolower(trim($matches[1]));
            }
            
            $googleFonts = ['Play', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Source Sans Pro', 'Raleway', 'Inter', 'Nunito', 'Ubuntu', 'Merriweather', 'Lora', 'PT Serif', 'Playfair Display', 'Oswald', 'Roboto Slab', 'Crimson Text', 'Libre Baskerville', 'Dancing Script'];
            $systemFonts = ['Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Georgia', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Impact'];
            $isCustomFont = !in_array($currentFont, array_merge($googleFonts, $systemFonts)) && !empty($currentFont);
            
            $html = '<div class="font-selector-wrapper" style="display: flex; gap: var(--spacing-sm); align-items: center; flex-wrap: nowrap;">';
            $html .= '<div class="custom-font-dropdown" style="position: relative; flex: 1; min-width: 0;">';
            $html .= '<div class="font-dropdown-trigger input" id="font_trigger_' . $paramId . '" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 8px 12px; width: 100%;" data-param-id="' . $paramId . '">';
            $html .= '<span class="font-display" style="font-family: ' . htmlspecialchars($currentFont ? '"' . $currentFont . '", ' . $currentFallback : 'inherit') . '; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;">' . htmlspecialchars($currentFont ?: 'Select font...') . '</span>';
            $html .= '<span style="color: var(--text-muted); flex-shrink: 0; margin-left: 8px;">▼</span>';
            $html .= '</div>';
            $html .= '<div class="font-dropdown-menu" id="font_menu_' . $paramId . '" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-primary, #ffffff); border: 1px solid var(--border-default, #eaedf1); border-radius: var(--radius-md, 8px); box-shadow: var(--shadow-lg, 0 5px 10px rgba(0,0,0,0.1)); z-index: 1000; max-height: 300px; overflow-y: auto; margin-top: 4px;">';
            $html .= '<div style="padding: 4px;">';
            $html .= '<div class="font-group" style="margin-bottom: 8px;"><div style="padding: 6px 12px; font-size: 11px; font-weight: 600; color: var(--text-muted, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Google Fonts</div>';
            foreach ($googleFonts as $font) {
                $html .= '<div class="font-option" data-font="' . htmlspecialchars($font) . '" data-param-id="' . $paramId . '" style="padding: 10px 12px; cursor: pointer; font-family: \'' . htmlspecialchars($font) . '\', sans-serif; transition: background 0.2s;" onmouseover="this.style.backgroundColor=\'var(--bg-secondary, #f3f4f6)\'" onmouseout="this.style.backgroundColor=\'transparent\'">' . htmlspecialchars($font) . '</div>';
            }
            $html .= '</div>';
            $html .= '<div class="font-group" style="margin-bottom: 8px;"><div style="padding: 6px 12px; font-size: 11px; font-weight: 600; color: var(--text-muted, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">System Fonts</div>';
            foreach ($systemFonts as $font) {
                $html .= '<div class="font-option" data-font="' . htmlspecialchars($font) . '" data-param-id="' . $paramId . '" style="padding: 10px 12px; cursor: pointer; font-family: ' . htmlspecialchars($font) . ', sans-serif; transition: background 0.2s;" onmouseover="this.style.backgroundColor=\'var(--bg-secondary, #f3f4f6)\'" onmouseout="this.style.backgroundColor=\'transparent\'">' . htmlspecialchars($font) . '</div>';
            }
            $html .= '</div>';
            $html .= '<div class="font-option font-option-custom" data-font="__custom__" data-param-id="' . $paramId . '" style="padding: 10px 12px; cursor: pointer; border-top: 1px solid var(--border-default, #eaedf1); margin-top: 4px; transition: background 0.2s;" onmouseover="this.style.backgroundColor=\'var(--bg-secondary, #f3f4f6)\'" onmouseout="this.style.backgroundColor=\'transparent\'"><strong>Custom (manual input)</strong></div>';
            $html .= '</div></div></div>';
            $html .= '<select id="font_fallback_' . $paramId . '" class="input" data-param-id="' . $paramId . '" style="flex: 1; min-width: 0;">';
            foreach (['sans-serif', 'serif', 'monospace', 'cursive', 'fantasy'] as $fallback) {
                $selected = $currentFallback === $fallback ? ' selected' : '';
                $html .= '<option value="' . $fallback . '"' . $selected . '>' . $fallback . '</option>';
            }
            $html .= '</select>';
            $html .= '<input type="hidden" id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" value="' . $currentValue . '" data-original-value="' . $currentValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '" class="param-input">';
            $html .= '<input type="text" id="param_custom_' . $paramId . '" class="input param-input" value="' . $currentValue . '" placeholder="&quot;Font Name&quot;, sans-serif" style="display: ' . ($isCustomFont ? 'block' : 'none') . '; flex: 1; min-width: 0;">';
            $html .= '</div>';
            break;
            
        case 'icon':
            // Icon picker - use reusable component
            global $allIcons, $iconSizeMenuItem, $iconSizeMenuItemNum;
            
            // Get icon size for this specific parameter (check if there's a corresponding size parameter)
            $sizeParamName = str_replace('-icon', '-size', $paramName);
            $iconSize = getParameter('Icons', $sizeParamName, $iconSizeMenuItem);
            $iconSizeNum = preg_replace('/[^0-9.]/', '', $iconSize);
            if (empty($iconSizeNum)) {
                $iconSizeNum = $iconSizeMenuItemNum;
            }
            
            // Use reusable icon picker function
            $html = renderIconPicker([
                'name' => 'parameter_value_' . $paramId,
                'id' => 'param_' . $paramId,
                'value' => $paramValue,
                'allIcons' => $allIcons,
                'iconSize' => $iconSizeNum,
                'onSelectCallback' => 'selectParameterIcon',
                'showText' => false,
                'inputClasses' => 'param-input',
            ]);
            
            // Add data attributes for parameter tracking
            $html = str_replace(
                'class="icon-picker-value',
                'class="icon-picker-value param-input" data-original-value="' . htmlspecialchars($paramValue) . '" data-param-name="' . htmlspecialchars($paramName) . '" data-param-id="' . $paramId,
                $html
            );
            break;
            
        default: // text
            $html = '<input type="text" id="param_' . $paramId . '" name="parameter_value_' . $paramId . '" class="input param-input" value="' . $paramValue . '" data-original-value="' . $paramValue . '" data-param-name="' . $paramName . '" data-param-id="' . $paramId . '" placeholder="' . htmlspecialchars($placeholder ?: '') . '">';
            break;
    }
    
    if ($helpText) {
        global $indentHelperText;
        $html .= '<small class="helper-text" style="display: block; margin-top: 4px; padding-left: ' . htmlspecialchars($indentHelperText) . '; text-indent: 0;">' . htmlspecialchars($helpText) . '</small>';
    }
    
    return $html;
}

// Ensure values have 'px' unit if numeric and not empty
if (!empty($indentLabel)) {
    $indentLabel = trim($indentLabel);
    if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
        $indentLabel = $indentLabel . 'px';
    }
} else {
    $indentLabel = '0px';
}

if (!empty($indentHelperText)) {
    $indentHelperText = trim($indentHelperText);
    if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
        $indentHelperText = $indentHelperText . 'px';
    }
} else {
    $indentHelperText = '0px';
}

if (!empty($indentParameterHelperText)) {
    $indentParameterHelperText = trim($indentParameterHelperText);
    if (is_numeric($indentParameterHelperText) && strpos($indentParameterHelperText, 'px') === false && strpos($indentParameterHelperText, 'em') === false && strpos($indentParameterHelperText, 'rem') === false) {
        $indentParameterHelperText = $indentParameterHelperText . 'px';
    }
} else {
    $indentParameterHelperText = '0px';
}

if (!empty($indentParameterRangeInfo)) {
    $indentParameterRangeInfo = trim($indentParameterRangeInfo);
    if (is_numeric($indentParameterRangeInfo) && strpos($indentParameterRangeInfo, 'px') === false && strpos($indentParameterRangeInfo, 'em') === false && strpos($indentParameterRangeInfo, 'rem') === false) {
        $indentParameterRangeInfo = $indentParameterRangeInfo . 'px';
    }
} else {
    $indentParameterRangeInfo = '0px';
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Settings - Parameters</h2>
        <p class="text-muted">Manage all design system parameters in one place</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-body">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; padding-right: var(--spacing-md); vertical-align: top;">
                    <label for="search-input" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Search Parameters</label>
                    <input 
                        type="text" 
                        id="search-input" 
                        class="input" 
                        placeholder="Search by section, name, description, or value..."
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                        autocomplete="off"
                        style="width: 100%;">
                    <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Search across all parameter fields</small>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <label for="section-filter" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Filter by Section</label>
                    <select id="section-filter" class="input" style="width: 100%;">
                        <option value="">All Sections</option>
                        <?php foreach ($allSections as $section): ?>
                            <option value="<?php echo htmlspecialchars($section); ?>" <?php echo $sectionFilter === $section ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if (empty($parameters)): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted">No parameters found. <?php if (empty($searchQuery) && empty($sectionFilter)): ?>Run the migration script to seed parameters from the design system.<?php else: ?>Try adjusting your search or filter.<?php endif; ?></p>
    </div>
</div>
<?php else: ?>
<form method="POST" id="parameters-form">
    <input type="hidden" name="search_filter" value="<?php echo htmlspecialchars($searchQuery); ?>">
    <input type="hidden" name="section_filter" value="<?php echo htmlspecialchars($sectionFilter); ?>">
    <?php foreach ($parameters as $section => $sectionParams): ?>
        <div class="card section-group" data-section="<?php echo htmlspecialchars($section); ?>" style="margin-bottom: var(--spacing-lg);">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-sm); border-bottom: 1px solid var(--border-default);">
                    <h3 style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($section); ?></h3>
                    <span style="color: var(--text-muted); font-size: 14px;"><?php echo count($sectionParams); ?> parameter<?php echo count($sectionParams) !== 1 ? 's' : ''; ?></span>
                </div>
                
                <div style="display: grid; gap: 0;">
                <?php 
                $paramIndex = 0;
                foreach ($sectionParams as $param): 
                    $paramIndex++;
                    // Determine if this is a Typography parameter that needs preview
                    $isTypographyParam = $section === 'Typography';
                    $paramNameLower = strtolower($param['parameter_name']);
                    $isFontFamily = strpos($paramNameLower, 'font-primary') !== false || strpos($paramNameLower, 'font-family') !== false;
                    $isFontSize = strpos($paramNameLower, 'font-size-') !== false;
                    $isFontWeight = strpos($paramNameLower, 'font-weight-') !== false;
                    $needsPreview = $isTypographyParam && ($isFontFamily || $isFontSize || $isFontWeight);
                    
                    // Alternate background for better row distinction
                    $isEven = $paramIndex % 2 === 0;
                    // Use --bg-secondary parameter if available, fallback to --color-gray-100
                    $bgSecondary = getParameter('Backgrounds', '--bg-secondary', '#f8f9fa');
                    $rowBgColor = $isEven ? $bgSecondary : 'transparent';
                    $rowPadding = 'var(--spacing-md)';
                    // No borders on parameter rows - zebra striping provides visual separation
                    $rowBorderBottom = 'none';
                    $rowBorderTop = 'none';
                ?>
                    <div class="parameter-row" 
                         data-param-name="<?php echo htmlspecialchars(strtolower($param['parameter_name'])); ?>" 
                         data-param-desc="<?php echo htmlspecialchars(strtolower($param['description'] ?? '')); ?>" 
                         data-param-value="<?php echo htmlspecialchars(strtolower($param['value'])); ?>" 
                         data-section="<?php echo htmlspecialchars($section); ?>"
                         style="background-color: <?php echo $rowBgColor; ?>; padding: <?php echo $rowPadding; ?>; border-top: <?php echo $rowBorderTop; ?>; border-bottom: <?php echo $rowBorderBottom; ?>; transition: background-color 0.2s ease;">
                        <!-- Two-Column Card Layout -->
                        <div style="display: grid; grid-template-columns: minmax(0, 40%) minmax(0, 60%); gap: var(--spacing-lg); align-items: start; width: 100%; box-sizing: border-box;">
                            <!-- Left Column: Parameter Info -->
                            <div style="min-width: 0; overflow-wrap: break-word;">
                                <label for="param_<?php echo $param['id']; ?>" class="input-label" style="font-weight: 600; font-size: 15px; margin-bottom: var(--spacing-xs); display: block; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">
                                    <?php echo htmlspecialchars($param['parameter_name']); ?>
                                    <?php if (!$param['input_type']): ?>
                                        <span style="font-size: 11px; color: var(--text-muted); font-weight: normal; margin-left: 8px;">(using fallback)</span>
                                    <?php endif; ?>
                                </label>
                                <?php if ($param['description']): ?>
                                    <small class="helper-text" style="display: block; margin-top: 4px; margin-bottom: 4px; padding-left: <?php echo htmlspecialchars($indentParameterHelperText); ?>; text-indent: 0;">
                                        <?php echo htmlspecialchars($param['description']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($param['min_range'] !== null || $param['max_range'] !== null): ?>
                                    <small class="helper-text" style="display: block; margin-top: 4px; color: var(--text-muted); padding-left: <?php echo htmlspecialchars($indentParameterRangeInfo); ?>; text-indent: 0;">
                                        Range: <?php 
                                            echo $param['min_range'] !== null ? $param['min_range'] : 'no min';
                                            echo ' - ';
                                            echo $param['max_range'] !== null ? $param['max_range'] : 'no max';
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <!-- Right Column: Input Field -->
                            <div style="padding-right: 5px !important; min-width: 0; overflow: visible;">
                                <?php echo renderParameterInput($param); ?>
                            </div>
                        </div>
                        <?php if ($needsPreview): ?>
                            <div class="param-preview" style="display: flex; align-items: center; padding: var(--spacing-md); background: var(--bg-card, #ffffff); border: 1px solid var(--border-default, #eaedf1); border-radius: var(--radius-md, 8px); min-height: 40px; margin-top: var(--spacing-md);">
                                <div id="preview_<?php echo $param['id']; ?>" class="param-preview-text" style="color: var(--text-primary); width: 100%; word-wrap: break-word; overflow-wrap: break-word;">
                                    <?php
                                    $paramNameLower = strtolower($param['parameter_name']);
                                    $isFontFamily = strpos($paramNameLower, 'font-primary') !== false || strpos($paramNameLower, 'font-family') !== false;
                                    $isFontSize = strpos($paramNameLower, 'font-size-') !== false;
                                    $isFontWeight = strpos($paramNameLower, 'font-weight-') !== false;
                                    if ($isFontFamily) {
                                        $previewText = 'The quick brown fox jumps over the lazy dog';
                                        $fontFamily = $param['value'];
                                        echo '<span style="font-family: ' . htmlspecialchars($fontFamily) . ';">' . htmlspecialchars($previewText) . '</span>';
                                    } elseif ($isFontSize) {
                                        $previewText = 'Sample text at this size';
                                        $fontSize = $param['value'];
                                        echo '<span style="font-size: ' . htmlspecialchars($fontSize) . ';">' . htmlspecialchars($previewText) . '</span>';
                                    } elseif ($isFontWeight) {
                                        $previewText = 'Sample text with this weight';
                                        $fontWeight = $param['value'];
                                        echo '<span style="font-weight: ' . htmlspecialchars($fontWeight) . ';">' . htmlspecialchars($previewText) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="hidden" name="parameter_id_<?php echo $param['id']; ?>" value="<?php echo $param['id']; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div style="margin-top: var(--spacing-xl);">
        <button type="submit" name="update_parameters" class="btn btn-primary btn-medium">Save All Changes</button>
        <button type="button" class="btn btn-secondary btn-medium" onclick="location.reload();" style="margin-left: var(--spacing-sm);">Cancel</button>
    </div>
</form>
<?php endif; ?>

<style>
/* Parameter row styling for better visual distinction */
.parameter-row {
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    border-top: none !important; /* Ensure no top border on any parameter row */
}

/* Remove top border from first parameter row in each section group */
.section-group .parameter-row:first-child {
    border-top: none !important;
}

/* Ensure the grid container doesn't add borders */
.section-group > div[style*="grid"] > .parameter-row:first-child {
    border-top: none !important;
}

.parameter-row:hover {
    background-color: var(--bg-hover, var(--color-gray-200, #eef2f7)) !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

/* Ensure preview panels maintain their styling on hover */
.parameter-row:hover .param-preview {
    background: var(--bg-card, #ffffff);
    border-color: var(--border-input-focus, #b0b0bb);
}

/* Color picker styling - remove browser default gray background */
input[type="color"] {
    background: transparent !important;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border: 1px solid var(--color-picker-border, #eaedf1) !important;
    border-radius: var(--color-picker-border-radius, 8px) !important;
    padding: var(--color-picker-padding, 2px) !important;
    cursor: pointer;
}

/* Webkit browsers (Chrome, Safari, Edge) */
input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
    border: none;
    border-radius: calc(var(--color-picker-border-radius, 8px) - var(--color-picker-padding, 2px));
}

input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: calc(var(--color-picker-border-radius, 8px) - var(--color-picker-padding, 2px));
}

/* Firefox */
input[type="color"]::-moz-color-swatch {
    border: none;
    border-radius: calc(var(--color-picker-border-radius, 8px) - var(--color-picker-padding, 2px));
}
</style>

<script>
// Icon picker data (available globally)
const allIconsData = <?php echo json_encode($allIcons); ?>;
const iconSizeMenuItemNum = <?php echo $iconSizeMenuItemNum; ?>;

// Real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    
    const searchInput = document.getElementById('search-input');
    const sectionFilter = document.getElementById('section-filter');
    
    if (!searchInput || !sectionFilter) {
        console.error('Search input or section filter not found');
        return;
    }
    
    function performSearch() {
        // Re-query elements each time to ensure we have the latest DOM
        const parameterRows = document.querySelectorAll('.parameter-row');
        const sectionGroups = document.querySelectorAll('.section-group');
        
        if (parameterRows.length === 0) {
            console.warn('No parameter rows found');
            return;
        }
        
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedSection = sectionFilter.value;
        
        let visibleSections = new Set();
        
        parameterRows.forEach(function(row) {
            const paramName = (row.getAttribute('data-param-name') || '').toLowerCase();
            const paramDesc = (row.getAttribute('data-param-desc') || '').toLowerCase();
            const paramValue = (row.getAttribute('data-param-value') || '').toLowerCase();
            const sectionGroup = row.closest('.section-group');
            const section = sectionGroup ? sectionGroup.getAttribute('data-section') : '';
            
            // Check if row matches search
            const matchesSearch = !searchTerm || 
                paramName.includes(searchTerm) || 
                paramDesc.includes(searchTerm) || 
                paramValue.includes(searchTerm) ||
                section.toLowerCase().includes(searchTerm);
            
            // Check if row matches section filter
            const matchesSection = !selectedSection || section === selectedSection;
            
            if (matchesSearch && matchesSection) {
                row.style.display = '';
                if (section) {
                    visibleSections.add(section);
                }
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide section groups based on visible parameters
        sectionGroups.forEach(function(group) {
            const section = group.getAttribute('data-section');
            const rowsInGroup = group.querySelectorAll('.parameter-row');
            let hasVisibleParams = false;
            
            // Check if any row in this group is visible
            for (let i = 0; i < rowsInGroup.length; i++) {
                const row = rowsInGroup[i];
                const display = row.style.display;
                if (display === '' || display === 'block' || (!display && window.getComputedStyle(row).display !== 'none')) {
                    hasVisibleParams = true;
                    break;
                }
            }
            
            if (hasVisibleParams) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });
    }
    
    // Update URL without reload
    function updateURL() {
        const params = new URLSearchParams();
        if (searchInput.value.trim()) {
            params.set('search', searchInput.value.trim());
        }
        if (sectionFilter.value) {
            params.set('section', sectionFilter.value);
        }
        const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({}, '', newURL);
    }
    
    searchInput.addEventListener('input', function() {
        performSearch();
        updateURL();
    });
    
    sectionFilter.addEventListener('change', function() {
        performSearch();
        updateURL();
    });
    
    // Initialize filter state from URL on page load
    const urlParams = new URLSearchParams(window.location.search);
    const urlSearch = urlParams.get('search') || '';
    const urlSection = urlParams.get('section') || '';
    
    // Set initial values from URL
    if (urlSearch && searchInput.value !== urlSearch) {
        searchInput.value = urlSearch;
    }
    if (urlSection && sectionFilter.value !== urlSection) {
        sectionFilter.value = urlSection;
    }
    
    // Apply filters on page load if URL has parameters OR if there are default values
    if (urlSearch || urlSection || searchInput.value || sectionFilter.value) {
        performSearch();
    }
    
    // Track changed parameters and visual feedback
    const changedParams = new Set();
    
    function markAsChanged(paramId) {
        const input = document.getElementById('param_' + paramId);
        if (!input) {
            return;
        }
        
        const originalValue = input.getAttribute('data-original-value') || '';
        let currentValue = '';
        
        // Handle different input types
        if (input.type === 'checkbox') {
            currentValue = input.checked ? 'yes' : 'no';
        } else if (input.tagName === 'SELECT' && input.multiple) {
            // Multiselect
            const selected = Array.from(input.selectedOptions).map(opt => opt.value);
            currentValue = selected.join(',');
        } else {
            // For hidden inputs and text inputs, use value directly (don't trim empty strings for hidden inputs)
            currentValue = input.type === 'hidden' ? (input.value || '') : input.value.trim();
        }
        
        // Check if value actually changed
        if (currentValue !== originalValue) {
            changedParams.add(paramId);
            // Change background color to indicate modification (only for visible inputs)
            if (input.style && input.type !== 'hidden') {
                input.style.backgroundColor = '#fff3cd'; // Light yellow/orange
                input.style.borderColor = '#ffc107'; // Yellow border
            }
        } else {
            changedParams.delete(paramId);
            // Reset to original styling
            if (input.style && input.type !== 'hidden') {
                input.style.backgroundColor = '';
                input.style.borderColor = '';
            }
        }
        
        updateSaveButton();
    }
    
    function updateSaveButton() {
        const saveButton = document.querySelector('button[name="update_parameters"]');
        if (saveButton) {
            if (changedParams.size > 0) {
                saveButton.textContent = 'Save ' + changedParams.size + ' Change' + (changedParams.size !== 1 ? 's' : '');
                saveButton.disabled = false;
            } else {
                saveButton.textContent = 'Save All Changes';
                saveButton.disabled = false; // Always allow saving (even if nothing changed, let server handle it)
            }
        }
    }
    
    // Attach event listeners to all parameter inputs
    document.querySelectorAll('.param-input').forEach(function(input) {
        const paramId = input.getAttribute('data-param-id');
        if (paramId) {
            input.addEventListener('input', function() {
                markAsChanged(parseInt(paramId));
            });
            input.addEventListener('change', function() {
                markAsChanged(parseInt(paramId));
            });
        }
    });
    
    // Handle multiselect inputs
    document.querySelectorAll('.param-multiselect').forEach(function(select) {
        const paramId = select.getAttribute('data-param-id');
        if (paramId) {
            select.addEventListener('change', function() {
                markAsChanged(parseInt(paramId));
            });
        }
    });
    
    // Handle checkbox inputs
    document.querySelectorAll('.param-checkbox').forEach(function(checkbox) {
        const paramId = checkbox.getAttribute('data-param-id');
        if (paramId) {
            checkbox.addEventListener('change', function() {
                markAsChanged(parseInt(paramId));
                // Update the visible text
                const label = checkbox.closest('label');
                if (label) {
                    const span = label.querySelector('span');
                    if (span) {
                        span.textContent = checkbox.checked ? 'Yes' : 'No';
                    }
                }
            });
        }
    });
    
    // Intercept form submission to only send changed parameters
    const parametersForm = document.getElementById('parameters-form');
    if (parametersForm) {
        parametersForm.addEventListener('submit', function(e) {
            // Disable all parameter inputs that haven't changed
            const allInputs = document.querySelectorAll('[name^="parameter_value_"]');
            allInputs.forEach(function(input) {
                const paramId = input.name.replace('parameter_value_', '');
                if (!changedParams.has(parseInt(paramId))) {
                    // Remove from form submission by disabling
                    input.disabled = true;
                }
            });
        });
    }
    
    // Initialize: check all inputs on page load to set initial state
    document.querySelectorAll('.param-input').forEach(function(input) {
        const paramId = input.getAttribute('data-param-id');
        if (paramId) {
            const originalValue = input.getAttribute('data-original-value') || '';
            if (input.value.trim() !== originalValue) {
                markAsChanged(parseInt(paramId));
            }
        }
    });
    
    // Sync color picker with text input
    document.querySelectorAll('input[type="color"]').forEach(function(colorInput) {
        const paramId = colorInput.getAttribute('data-param-id');
        const textInput = document.getElementById('param_' + paramId);
        
        if (textInput) {
            colorInput.addEventListener('change', function() {
                textInput.value = this.value;
                markAsChanged(parseInt(paramId));
            });
            
            textInput.addEventListener('input', function() {
                const hexMatch = this.value.match(/^#([0-9A-Fa-f]{6})$/);
                if (hexMatch) {
                    colorInput.value = this.value;
                }
            });
        }
    });
    
    // Custom font dropdown functionality
    document.querySelectorAll('.font-dropdown-trigger').forEach(function(trigger) {
        const paramId = trigger.getAttribute('data-param-id');
        const menu = document.getElementById('font_menu_' + paramId);
        const hiddenInput = document.getElementById('param_' + paramId);
        const customInput = document.getElementById('param_custom_' + paramId);
        const fallbackSelect = document.getElementById('font_fallback_' + paramId);
        const fontDisplay = trigger.querySelector('.font-display');
        
        let selectedFont = '';
        
        // Extract current font from hidden input
        const currentValue = hiddenInput.value;
        const fontMatch = currentValue.match(/"([^"]+)"/);
        if (fontMatch) {
            selectedFont = fontMatch[1];
        } else {
            // Try to extract from custom input if it exists
            if (customInput && customInput.style.display !== 'none') {
                const customMatch = customInput.value.match(/"([^"]+)"/);
                if (customMatch) {
                    selectedFont = customMatch[1];
                }
            }
        }
        
        // Toggle dropdown
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = menu.style.display === 'block';
            
            // Close all other dropdowns
            document.querySelectorAll('.font-dropdown-menu').forEach(function(m) {
                m.style.display = 'none';
            });
            
            menu.style.display = isOpen ? 'none' : 'block';
        });
        
        // Handle font selection
        menu.querySelectorAll('.font-option').forEach(function(option) {
            option.addEventListener('click', function() {
                const font = this.getAttribute('data-font');
                selectedFont = font;
                
                if (font === '__custom__') {
                    fontDisplay.textContent = 'Custom (manual input)';
                    fontDisplay.style.fontFamily = 'inherit';
                    customInput.style.display = 'block';
                    trigger.style.display = 'none';
                    menu.style.display = 'none';
                } else {
                    fontDisplay.textContent = font;
                    const fallback = fallbackSelect ? fallbackSelect.value : 'sans-serif';
                    fontDisplay.style.fontFamily = '"' + font + '", ' + fallback;
                    customInput.style.display = 'none';
                    trigger.style.display = 'flex';
                    
                    // Update hidden input
                    hiddenInput.value = '"' + font + '", ' + fallback;
                    markAsChanged(parseInt(paramId));
                    updateParameterPreview(paramId);
                    
                    menu.style.display = 'none';
                }
            });
        });
        
        // Handle fallback change
        if (fallbackSelect) {
            fallbackSelect.addEventListener('change', function() {
                if (selectedFont && selectedFont !== '__custom__') {
                    const fallback = this.value;
                    hiddenInput.value = '"' + selectedFont + '", ' + fallback;
                    fontDisplay.style.fontFamily = '"' + selectedFont + '", ' + fallback;
                    markAsChanged(parseInt(paramId));
                    updateParameterPreview(paramId);
                }
            });
        }
        
        // Handle custom input
        if (customInput) {
            customInput.addEventListener('input', function() {
                hiddenInput.value = this.value;
                markAsChanged(parseInt(paramId));
                updateParameterPreview(paramId);
            });
        }
        
        // Initialize: hide dropdown if custom font is already selected
        if (customInput && customInput.style.display !== 'none') {
            trigger.style.display = 'none';
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    });
    
    // Get primary font family for use in other previews
    function getPrimaryFontFamily() {
        const fontPrimaryInput = document.querySelector('[data-param-name*="font-primary"]');
        if (!fontPrimaryInput) return 'inherit';
        
        const value = fontPrimaryInput.value || fontPrimaryInput.getAttribute('value') || '';
        const fontMatch = value.match(/"([^"]+)"/);
        if (fontMatch) {
            const fallbackMatch = value.match(/,\s*([^,]+)$/);
            const fallback = fallbackMatch ? fallbackMatch[1].trim() : 'sans-serif';
            return '"' + fontMatch[1] + '", ' + fallback;
        }
        const fontMatch2 = value.match(/'([^']+)'/);
        if (fontMatch2) {
            const fallbackMatch = value.match(/,\s*([^,]+)$/);
            const fallback = fallbackMatch ? fallbackMatch[1].trim() : 'sans-serif';
            return '"' + fontMatch2[1] + '", ' + fallback;
        }
        return value || 'inherit';
    }
    
    // Individual Parameter Preview Update Function
    function updateParameterPreview(paramId) {
        const previewElement = document.getElementById('preview_' + paramId);
        if (!previewElement) return;
        
        const input = document.getElementById('param_' + paramId);
        const customInput = document.getElementById('param_custom_' + paramId);
        const paramName = (input ? input.getAttribute('data-param-name') : '') || '';
        const paramNameLower = paramName.toLowerCase();
        
        // Get the current value (check custom input first if it exists and is visible)
        let currentValue = '';
        if (customInput && customInput.style.display !== 'none' && customInput.value) {
            currentValue = customInput.value;
        } else if (input) {
            currentValue = input.value || input.getAttribute('value') || '';
        }
        
        if (!currentValue) return;
        
        // Determine preview text based on parameter type
        let previewText = 'Sample text';
        let previewStyle = {};
        
        if (paramNameLower.includes('font-primary') || paramNameLower.includes('font-family')) {
            // Font family preview
            previewText = 'The quick brown fox jumps over the lazy dog';
            
            // Extract font family from value
            let fontFamily = 'inherit';
            const fontMatch = currentValue.match(/"([^"]+)"/);
            if (fontMatch) {
                const fallbackMatch = currentValue.match(/,\s*([^,]+)$/);
                const fallback = fallbackMatch ? fallbackMatch[1].trim() : 'sans-serif';
                fontFamily = '"' + fontMatch[1] + '", ' + fallback;
            } else {
                const fontMatch2 = currentValue.match(/'([^']+)'/);
                if (fontMatch2) {
                    const fallbackMatch = currentValue.match(/,\s*([^,]+)$/);
                    const fallback = fallbackMatch ? fallbackMatch[1].trim() : 'sans-serif';
                    fontFamily = '"' + fontMatch2[1] + '", ' + fallback;
                } else {
                    fontFamily = currentValue;
                }
            }
            previewStyle.fontFamily = fontFamily;
            
        } else if (paramNameLower.includes('font-size-')) {
            // Font size preview - use primary font family
            previewText = 'Sample text at this size';
            previewStyle.fontSize = currentValue;
            previewStyle.fontFamily = getPrimaryFontFamily();
            
        } else if (paramNameLower.includes('font-weight-')) {
            // Font weight preview - use primary font family
            previewText = 'Sample text with this weight';
            previewStyle.fontWeight = currentValue;
            previewStyle.fontFamily = getPrimaryFontFamily();
        }
        
        // Update the preview element
        previewElement.innerHTML = '<span>' + previewText + '</span>';
        const spanElement = previewElement.querySelector('span');
        if (spanElement) {
            Object.keys(previewStyle).forEach(function(prop) {
                spanElement.style[prop] = previewStyle[prop];
            });
        }
    }
    
    // Update previews for all Typography parameters on page load and when values change
    document.querySelectorAll('[data-section="Typography"] .param-input, [data-section="Typography"] input[type="hidden"]').forEach(function(input) {
        const paramId = input.getAttribute('data-param-id') || input.id.replace('param_', '').replace('param_custom_', '');
        if (paramId) {
            // Initialize preview on load
            updateParameterPreview(paramId);
            
            // Update preview on change
            input.addEventListener('input', function() {
                updateParameterPreview(paramId);
                // If this is the primary font, update all other previews too
                const paramName = input.getAttribute('data-param-name') || '';
                if (paramName.toLowerCase().includes('font-primary')) {
                    document.querySelectorAll('[data-section="Typography"] .param-preview').forEach(function(preview) {
                        const previewId = preview.querySelector('[id^="preview_"]');
                        if (previewId) {
                            const id = previewId.id.replace('preview_', '');
                            updateParameterPreview(id);
                        }
                    });
                }
            });
            input.addEventListener('change', function() {
                updateParameterPreview(paramId);
                // If this is the primary font, update all other previews too
                const paramName = input.getAttribute('data-param-name') || '';
                if (paramName.toLowerCase().includes('font-primary')) {
                    document.querySelectorAll('[data-section="Typography"] .param-preview').forEach(function(preview) {
                        const previewId = preview.querySelector('[id^="preview_"]');
                        if (previewId) {
                            const id = previewId.id.replace('preview_', '');
                            updateParameterPreview(id);
                        }
                    });
                }
            });
        }
    });
    
    // Also update font preview when dropdown selection changes
    document.querySelectorAll('.font-dropdown-trigger').forEach(function(trigger) {
        const paramId = trigger.getAttribute('data-param-id');
        if (paramId) {
            trigger.addEventListener('click', function() {
                // Update preview after a short delay to allow value to update
                setTimeout(function() {
                    updateParameterPreview(paramId);
                }, 100);
            });
        }
    });
    
    // Update preview when fallback selector changes
    document.querySelectorAll('[id^="font_fallback_"]').forEach(function(select) {
        const paramId = select.getAttribute('data-param-id');
        if (paramId) {
            select.addEventListener('change', function() {
                updateParameterPreview(paramId);
            });
        }
    });
    
    // Update save button on page load
    updateSaveButton();
});

// Wrapper function for parameter page to handle parameter-specific logic
function selectParameterIcon(optionElement, iconName) {
    const wrapper = optionElement.closest('.icon-picker-wrapper');
    const hiddenInput = wrapper.querySelector('.icon-picker-value');
    
    if (!hiddenInput) {
        return;
    }
    
    const paramId = hiddenInput.getAttribute('data-param-id');
    
    // Use reusable selectIcon function with callback for parameter-specific logic
    selectIcon(optionElement, iconName, {
        allIcons: allIconsData,
        iconSize: iconSizeMenuItemNum,
        showText: false,
        onSelect: function(selectedIconName, inputElement) {
            // Mark as changed for parameter tracking
            if (paramId && typeof markAsChanged === 'function') {
                markAsChanged(paramId);
            }
            
            // Update icon size if there's a corresponding size parameter
            const paramName = inputElement.getAttribute('data-param-name') || '';
            if (paramName) {
                const sizeParamName = paramName.replace('-icon', '-size');
                const sizeInput = document.querySelector('input[data-param-name="' + sizeParamName + '"]');
                if (sizeInput && sizeInput.value) {
                    const sizeValue = sizeInput.value.trim();
                    const sizeNum = parseFloat(sizeValue.replace(/[^0-9.]/g, ''));
                    if (!isNaN(sizeNum) && sizeNum > 0) {
                        // Update display with correct size
                        const display = wrapper.querySelector('.icon-picker-display');
                        if (display && selectedIconName) {
                            const icon = allIconsData.find(i => i.name === selectedIconName);
                            if (icon && icon.svg_path) {
                                let viewBox = '0 0 24 24';
                                let svgContent = icon.svg_path;
                                
                                const vbMatch = svgContent.match(/<!--viewBox:([^>]+)-->/);
                                if (vbMatch) {
                                    viewBox = vbMatch[1].trim();
                                    svgContent = svgContent.replace(/<!--viewBox:[^>]+-->/, '');
                                }
                                
                                if (svgContent.indexOf('<path') !== -1) {
                                    if (svgContent.indexOf('fill=') === -1) {
                                        svgContent = svgContent.replace(/<path([^>]*)>/gi, '<path$1 fill="currentColor">');
                                    } else {
                                        svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                                        svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                                    }
                                }
                                
                                if (svgContent.match(/<(circle|ellipse|polygon|polyline|line|g)/i)) {
                                    svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                                    svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                                }
                                
                                display.innerHTML = '<svg width="' + sizeNum + '" height="' + sizeNum + '" viewBox="' + viewBox + '" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">' + svgContent + '</svg>';
                            }
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php
endLayout();
?>

