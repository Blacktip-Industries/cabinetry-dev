<?php
/**
 * Setup Script Helper
 * Provides functions for executing and tracking setup scripts with template rendering
 */

require_once __DIR__ . '/../../config/database.php';

// Global variables for tracking script execution
$GLOBALS['script_steps'] = [];
$GLOBALS['script_start_time'] = null;
$GLOBALS['script_results'] = [];

/**
 * Execute a setup script with tracking
 * @param string $scriptPath Full path to the script file
 * @param callable $callback Function to execute (should return array with 'success' and optionally 'results')
 * @param bool $autoComplete Whether to automatically mark as completed on success
 * @return array Execution result
 */
function executeSetupScript($scriptPath, $callback, $autoComplete = true) {
    global $script_steps, $script_start_time, $script_results;
    
    // Initialize tracking
    $script_steps = [];
    $script_start_time = microtime(true);
    $script_results = [];
    
    // Register script (will update if already exists)
    $scriptData = registerSetupScript($scriptPath, 'setup', false, true, null);
    
    if (!$scriptData) {
        return [
            'success' => false,
            'error' => 'Failed to register script'
        ];
    }
    
    // Execute the callback
    try {
        $result = $callback();
        
        // Calculate execution time
        $executionTime = (microtime(true) - $script_start_time) * 1000; // Convert to milliseconds
        
        // Update execution time
        if ($conn = getDBConnection()) {
            $updateStmt = $conn->prepare("UPDATE setup_scripts SET execution_time_ms = ? WHERE id = ?");
            $updateStmt->bind_param("di", $executionTime, $scriptData['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // If successful and auto-complete is enabled, mark as completed
        if ($result['success'] && $autoComplete) {
            markScriptCompleted($scriptPath, $script_steps, $result['results'] ?? []);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Script execution error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Track a script execution step
 * @param string $stepName Name of the step
 * @param string $status Status: 'success', 'error', 'warning', 'info'
 * @param string $message Step message
 */
function trackScriptStep($stepName, $status, $message = '') {
    global $script_steps;
    
    $script_steps[] = [
        'name' => $stepName,
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Get script execution time in milliseconds
 * @return float Execution time in milliseconds
 */
function getScriptExecutionTime() {
    global $script_start_time;
    
    if ($script_start_time === null) {
        return 0;
    }
    
    return (microtime(true) - $script_start_time) * 1000;
}

/**
 * Render script output using template
 * @param array|string $template Template data or template type
 * @param array $data Data to populate template variables
 * @return string Rendered HTML
 */
function renderScriptTemplate($template, $data = []) {
    // If template is a string, get template from database
    if (is_string($template)) {
        $templateData = getScriptTemplate($template);
        if (!$templateData) {
            // Fallback to default template
            $templateData = getScriptTemplate('default');
        }
        if ($templateData && isset($templateData['template_data'])) {
            $template = $templateData['template_data'];
        } else {
            // Use basic fallback template
            return renderBasicTemplate($data);
        }
    }
    
    // Extract template configuration
    $colors = $template['colors'] ?? [];
    $layout = $template['layout'] ?? [];
    $messages = $template['messages'] ?? [];
    $steps = $template['steps'] ?? [];
    $metadata = $template['metadata'] ?? [];
    $actions = $template['actions'] ?? [];
    
    // Build HTML
    $html = '<div style="';
    $html .= 'background-color: ' . ($colors['background'] ?? '#ffffff') . '; ';
    $html .= 'color: ' . ($colors['text'] ?? '#1f2937') . '; ';
    $html .= 'border: 1px solid ' . ($colors['border'] ?? '#e5e7eb') . '; ';
    $html .= 'border-radius: 0.5rem; ';
    $html .= 'padding: var(--card-padding, var(--spacing-xl));';
    $html .= '">';
    
    // Title
    if (isset($data['title'])) {
        $html .= '<h2 style="margin-top: 0; color: ' . ($colors['text'] ?? '#1f2937') . ';">' . htmlspecialchars($data['title']) . '</h2>';
    }
    
    // Description
    if (isset($data['description'])) {
        $html .= '<p style="color: ' . ($colors['text'] ?? '#1f2937') . ';">' . htmlspecialchars($data['description']) . '</p>';
    }
    
    // Steps
    if (isset($data['steps']) && is_array($data['steps']) && !empty($data['steps'])) {
        $html .= '<div style="margin-top: 1.5rem;">';
        $html .= '<h3 style="color: ' . ($colors['text'] ?? '#1f2937') . ';">Execution Steps</h3>';
        
        $displayStyle = $steps['display_style'] ?? 'numbered';
        foreach ($data['steps'] as $index => $step) {
            $statusColor = $step['status'] === 'success' ? ($colors['button_success'] ?? '#22c55e') : 
                          ($step['status'] === 'error' ? ($colors['button_error'] ?? '#ef4444') : 
                          ($colors['text'] ?? '#1f2937'));
            
            if ($displayStyle === 'numbered') {
                $html .= '<div style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 3px solid ' . $statusColor . '; background: rgba(0,0,0,0.02);">';
                $html .= '<strong>' . ($index + 1) . '. ' . htmlspecialchars($step['name']) . '</strong>';
            } else {
                $html .= '<div style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 3px solid ' . $statusColor . '; background: rgba(0,0,0,0.02);">';
                $html .= '<strong>• ' . htmlspecialchars($step['name']) . '</strong>';
            }
            
            if (!empty($step['message'])) {
                $html .= '<br><small style="color: #6b7280;">' . htmlspecialchars($step['message']) . '</small>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    
    // Results
    if (isset($data['results']) && is_array($data['results']) && !empty($data['results'])) {
        $html .= '<div style="margin-top: 1.5rem;">';
        $html .= '<h3 style="color: ' . ($colors['text'] ?? '#1f2937') . ';">Results</h3>';
        $html .= '<ul style="color: ' . ($colors['text'] ?? '#1f2937') . ';">';
        foreach ($data['results'] as $key => $value) {
            $html .= '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</li>';
        }
        $html .= '</ul></div>';
    }
    
    // Metadata
    if ($metadata['show_execution_time'] ?? true) {
        $executionTime = getScriptExecutionTime();
        $html .= '<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid ' . ($colors['border'] ?? '#e5e7eb') . '; color: #6b7280; font-size: 0.875rem;">';
        $html .= 'Execution time: ' . number_format($executionTime, 2) . 'ms';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render basic fallback template
 * @param array $data Data to populate
 * @return string Rendered HTML
 */
function renderBasicTemplate($data) {
    $html = '<div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: var(--card-padding, var(--spacing-xl));">';
    
    if (isset($data['title'])) {
        $html .= '<h2 style="margin-top: 0;">' . htmlspecialchars($data['title']) . '</h2>';
    }
    
    if (isset($data['description'])) {
        $html .= '<p>' . htmlspecialchars($data['description']) . '</p>';
    }
    
    if (isset($data['steps']) && is_array($data['steps'])) {
        $html .= '<div style="margin-top: 1.5rem;"><h3>Steps</h3><ul>';
        foreach ($data['steps'] as $step) {
            $html .= '<li>' . htmlspecialchars($step['name'] ?? 'Step') . '</li>';
        }
        $html .= '</ul></div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

