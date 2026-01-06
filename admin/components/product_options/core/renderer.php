<?php
/**
 * Product Options Component - Renderer
 * HTML rendering functions for all option types and groups
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/datatypes.php';
require_once __DIR__ . '/query_builder.php';
require_once __DIR__ . '/conditional_logic.php';

/**
 * Render an option
 * @param array $option Option data
 * @param mixed $currentValue Current selected value
 * @param array $formValues All form values (for conditional logic)
 * @param array $options Rendering options
 * @return string Rendered HTML
 */
function product_options_render_option($option, $currentValue = null, $formValues = [], $options = []) {
    if (!$option || !$option['is_active']) {
        return '';
    }
    
    // Check if option should be shown
    if (!product_options_should_show($option['id'], $formValues)) {
        return '';
    }
    
    $datatype = product_options_get_datatype($option['datatype_key']);
    if (!$datatype) {
        return '';
    }
    
    // Get render function
    $renderFunction = $datatype['render_function'] ?? 'product_options_render_custom';
    
    // Call appropriate render function
    if (function_exists($renderFunction)) {
        return $renderFunction($option, $currentValue, $formValues, $options);
    }
    
    return product_options_render_custom($option, $currentValue, $formValues, $options);
}

/**
 * Render dropdown option
 */
function product_options_render_dropdown($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $source = $config['source'] ?? 'static';
    $placeholder = $config['placeholder'] ?? 'Select an option';
    $multiple = $config['multiple'] ?? false;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-dropdown" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    
    if ($multiple) {
        $html .= '<select id="' . $optionId . '" name="' . $optionName . '[]" multiple class="form-control">';
    } else {
        $html .= '<select id="' . $optionId . '" name="' . $optionName . '" class="form-control">';
    }
    
    $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
    
    // Get values based on source
    $values = [];
    if ($source === 'static') {
        $values = product_options_get_option_values($option['id']);
    } elseif ($source === 'database' || $source === 'query') {
        // Get from query
        $queryId = $config['query_id'] ?? null;
        if ($queryId) {
            $queryResult = product_options_execute_query_for_dropdown($queryId, $formValues);
            if ($queryResult['success']) {
                $values = $queryResult['data'];
            }
        }
    }
    
    // Filter values based on conditions
    $values = product_options_filter_values($option['id'], $values, $formValues);
    
    foreach ($values as $value) {
        $valueKey = $value['value_key'] ?? $value['value'] ?? '';
        $valueLabel = $value['value_label'] ?? $value['label'] ?? $valueKey;
        $selected = ($currentValue == $valueKey || (is_array($currentValue) && in_array($valueKey, $currentValue))) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($valueKey) . '"' . $selected . '>' . htmlspecialchars($valueLabel) . '</option>';
    }
    
    $html .= '</select>';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render modal popup option
 */
function product_options_render_modal_popup($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $thumbnailSize = $config['thumbnail_size'] ?? '150x150';
    $columns = $config['columns'] ?? 4;
    $showLabels = $config['show_labels'] ?? true;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    $modalId = 'po_modal_' . $option['id'];
    
    // Get values (thumbnails)
    $values = product_options_get_option_values($option['id']);
    
    $html = '<div class="product-option product-option-modal-popup" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label>' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<input type="hidden" id="' . $optionId . '" name="' . $optionName . '" value="' . htmlspecialchars($currentValue ?? '') . '">';
    $html .= '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#' . $modalId . '">Select ' . htmlspecialchars($option['label']) . '</button>';
    
    // Modal
    $html .= '<div class="modal fade" id="' . $modalId . '" tabindex="-1">';
    $html .= '<div class="modal-dialog modal-lg">';
    $html .= '<div class="modal-content">';
    $html .= '<div class="modal-header">';
    $html .= '<h5 class="modal-title">' . htmlspecialchars($option['label']) . '</h5>';
    $html .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
    $html .= '</div>';
    $html .= '<div class="modal-body">';
    $html .= '<div class="row" style="display: grid; grid-template-columns: repeat(' . $columns . ', 1fr); gap: 15px;">';
    
    foreach ($values as $value) {
        $valueKey = $value['value_key'] ?? $value['value'] ?? '';
        $valueLabel = $value['value_label'] ?? $value['label'] ?? '';
        $valueData = $value['value_data'] ?? [];
        $thumbnail = $valueData['thumbnail'] ?? $valueData['image'] ?? '';
        $selected = ($currentValue == $valueKey) ? ' selected' : '';
        
        $html .= '<div class="modal-thumbnail' . $selected . '" data-value="' . htmlspecialchars($valueKey) . '">';
        if ($thumbnail) {
            $html .= '<img src="' . htmlspecialchars($thumbnail) . '" alt="' . htmlspecialchars($valueLabel) . '" style="width: 100%; height: auto;">';
        }
        if ($showLabels) {
            $html .= '<div class="thumbnail-label">' . htmlspecialchars($valueLabel) . '</div>';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="modal-footer">';
    $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>';
    $html .= '<button type="button" class="btn btn-primary" data-dismiss="modal">Select</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render text input
 */
function product_options_render_text($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $placeholder = $config['placeholder'] ?? '';
    $maxLength = $config['max_length'] ?? null;
    $pattern = $config['pattern'] ?? null;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-text" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<input type="text" id="' . $optionId . '" name="' . $optionName . '" class="form-control"';
    $html .= ' value="' . htmlspecialchars($currentValue ?? '') . '"';
    if ($placeholder) $html .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
    if ($maxLength) $html .= ' maxlength="' . (int)$maxLength . '"';
    if ($pattern) $html .= ' pattern="' . htmlspecialchars($pattern) . '"';
    $html .= '>';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render textarea
 */
function product_options_render_textarea($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $placeholder = $config['placeholder'] ?? '';
    $rows = $config['rows'] ?? 4;
    $maxLength = $config['max_length'] ?? null;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-textarea" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<textarea id="' . $optionId . '" name="' . $optionName . '" class="form-control" rows="' . (int)$rows . '"';
    if ($placeholder) $html .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
    if ($maxLength) $html .= ' maxlength="' . (int)$maxLength . '"';
    $html .= '>' . htmlspecialchars($currentValue ?? '') . '</textarea>';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render number input
 */
function product_options_render_number($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $min = $config['min'] ?? null;
    $max = $config['max'] ?? null;
    $step = $config['step'] ?? 1;
    $placeholder = $config['placeholder'] ?? '';
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-number" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<input type="number" id="' . $optionId . '" name="' . $optionName . '" class="form-control"';
    $html .= ' value="' . htmlspecialchars($currentValue ?? '') . '"';
    if ($min !== null) $html .= ' min="' . (float)$min . '"';
    if ($max !== null) $html .= ' max="' . (float)$max . '"';
    $html .= ' step="' . (float)$step . '"';
    if ($placeholder) $html .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
    $html .= '>';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render checkbox
 */
function product_options_render_checkbox($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $checkedValue = $config['checked_value'] ?? '1';
    $uncheckedValue = $config['unchecked_value'] ?? '0';
    $labelPosition = $config['label_position'] ?? 'right';
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    $checked = ($currentValue == $checkedValue) ? ' checked' : '';
    
    $html = '<div class="product-option product-option-checkbox" data-option-id="' . htmlspecialchars($option['id']) . '">';
    
    if ($labelPosition === 'left') {
        $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    }
    
    $html .= '<input type="checkbox" id="' . $optionId . '" name="' . $optionName . '" value="' . htmlspecialchars($checkedValue) . '"' . $checked . '>';
    $html .= '<input type="hidden" name="' . $optionName . '_hidden" value="' . htmlspecialchars($uncheckedValue) . '">';
    
    if ($labelPosition === 'right') {
        $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    }
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render radio buttons
 */
function product_options_render_radio($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $layout = $config['layout'] ?? 'vertical';
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $values = product_options_get_option_values($option['id']);
    
    $html = '<div class="product-option product-option-radio" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label>' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<div class="radio-group radio-' . $layout . '">';
    
    foreach ($values as $value) {
        $valueKey = $value['value_key'] ?? $value['value'] ?? '';
        $valueLabel = $value['value_label'] ?? $value['label'] ?? $valueKey;
        $radioId = $optionId . '_' . $valueKey;
        $checked = ($currentValue == $valueKey) ? ' checked' : '';
        
        $html .= '<div class="radio-item">';
        $html .= '<input type="radio" id="' . $radioId . '" name="' . $optionName . '" value="' . htmlspecialchars($valueKey) . '"' . $checked . '>';
        $html .= '<label for="' . $radioId . '">' . htmlspecialchars($valueLabel) . '</label>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render color picker
 */
function product_options_render_color_picker($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $format = $config['format'] ?? 'hex';
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-color-picker" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<input type="color" id="' . $optionId . '" name="' . $optionName . '" class="form-control"';
    $html .= ' value="' . htmlspecialchars($currentValue ?? '#000000') . '"';
    $html .= ' data-format="' . htmlspecialchars($format) . '">';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render file upload
 */
function product_options_render_file_upload($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $allowedTypes = $config['allowed_types'] ?? ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = $config['max_size'] ?? 5242880;
    $multiple = $config['multiple'] ?? false;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-file-upload" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<input type="file" id="' . $optionId . '" name="' . $optionName . ($multiple ? '[]' : '') . '" class="form-control"';
    if ($multiple) $html .= ' multiple';
    $html .= ' accept="' . htmlspecialchars(implode(',', $allowedTypes)) . '"';
    $html .= ' data-max-size="' . (int)$maxSize . '">';
    
    if ($currentValue) {
        $html .= '<div class="file-preview">';
        $html .= '<img src="' . htmlspecialchars($currentValue) . '" alt="Preview" style="max-width: 200px;">';
        $html .= '</div>';
    }
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render date picker
 */
function product_options_render_date($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $format = $config['format'] ?? 'Y-m-d';
    $minDate = $config['min_date'] ?? null;
    $maxDate = $config['max_date'] ?? null;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    
    $html = '<div class="product-option product-option-date" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    $html .= '<input type="date" id="' . $optionId . '" name="' . $optionName . '" class="form-control"';
    $html .= ' value="' . htmlspecialchars($currentValue ?? '') . '"';
    if ($minDate) $html .= ' min="' . htmlspecialchars($minDate) . '"';
    if ($maxDate) $html .= ' max="' . htmlspecialchars($maxDate) . '"';
    $html .= ' data-format="' . htmlspecialchars($format) . '">';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render range slider
 */
function product_options_render_range_slider($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $min = $config['min'] ?? 0;
    $max = $config['max'] ?? 100;
    $step = $config['step'] ?? 1;
    $showValue = $config['show_value'] ?? true;
    $optionId = 'po_option_' . $option['id'];
    $optionName = 'product_options[' . $option['slug'] . ']';
    $currentValue = $currentValue ?? $min;
    
    $html = '<div class="product-option product-option-range-slider" data-option-id="' . htmlspecialchars($option['id']) . '">';
    $html .= '<label for="' . $optionId . '">' . htmlspecialchars($option['label']) . '</label>';
    
    if ($showValue) {
        $html .= '<div class="slider-value">' . htmlspecialchars($currentValue) . '</div>';
    }
    
    $html .= '<input type="range" id="' . $optionId . '" name="' . $optionName . '" class="form-control-range"';
    $html .= ' value="' . htmlspecialchars($currentValue) . '"';
    $html .= ' min="' . (float)$min . '" max="' . (float)$max . '" step="' . (float)$step . '">';
    $html .= '<input type="hidden" name="' . $optionName . '_value" value="' . htmlspecialchars($currentValue) . '">';
    
    if (!empty($option['description'])) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($option['description']) . '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render custom datatype
 */
function product_options_render_custom($option, $currentValue = null, $formValues = [], $options = []) {
    $config = $option['config'] ?? [];
    $customHandler = $config['custom_handler'] ?? '';
    
    if ($customHandler && function_exists($customHandler)) {
        return call_user_func($customHandler, $option, $currentValue, $formValues, $options);
    }
    
    // Default fallback
    return '<div class="product-option product-option-custom" data-option-id="' . htmlspecialchars($option['id']) . '">';
}

/**
 * Render option group
 * @param int $groupId Group ID
 * @param array $formValues Current form values
 * @param array $options Rendering options
 * @return string Rendered HTML
 */
function product_options_render_group($groupId, $formValues = [], $options = []) {
    $groupOptions = product_options_get_options_by_group($groupId, true);
    
    if (empty($groupOptions)) {
        return '';
    }
    
    $group = null;
    foreach ($groupOptions as $opt) {
        if ($opt['group_id'] == $groupId) {
            // Get group info from first option
            if (!$group) {
                $conn = product_options_get_db_connection();
                if ($conn) {
                    $tableName = product_options_get_table_name('groups');
                    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
                    $stmt->bind_param("i", $groupId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $group = $result->fetch_assoc();
                    $stmt->close();
                }
            }
            break;
        }
    }
    
    $html = '<div class="product-options-group" data-group-id="' . $groupId . '">';
    
    if ($group) {
        $html .= '<h3 class="group-title">' . htmlspecialchars($group['name']) . '</h3>';
        if (!empty($group['description'])) {
            $html .= '<p class="group-description">' . htmlspecialchars($group['description']) . '</p>';
        }
    }
    
    foreach ($groupOptions as $option) {
        $currentValue = $formValues[$option['slug']] ?? null;
        $html .= product_options_render_option($option, $currentValue, $formValues, $options);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Get all rendered options
 * @param array $optionIds Array of option IDs (empty for all)
 * @param array $formValues Current form values
 * @param array $options Rendering options
 * @return string Rendered HTML
 */
function product_options_get_rendered_options($optionIds = [], $formValues = [], $options = []) {
    if (empty($optionIds)) {
        $allOptions = product_options_get_all_options(true);
    } else {
        $allOptions = [];
        foreach ($optionIds as $id) {
            $option = product_options_get_option($id);
            if ($option) {
                $allOptions[] = $option;
            }
        }
    }
    
    $html = '<div class="product-options-container">';
    
    foreach ($allOptions as $option) {
        $currentValue = $formValues[$option['slug']] ?? null;
        $html .= product_options_render_option($option, $currentValue, $formValues, $options);
    }
    
    $html .= '</div>';
    
    return $html;
}

