<?php
/**
 * SEO Manager Component - Automation Engine
 * Handles automated optimization with flexible modes
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/scheduler.php';

/**
 * Get automation mode for a feature
 * @param string $feature Feature name
 * @return string Automation mode
 */
function seo_manager_get_automation_mode($feature = 'general') {
    $mode = seo_manager_get_parameter('General', 'automation_mode', 'hybrid');
    
    // Check for feature-specific mode
    $featureMode = seo_manager_get_parameter('Automation', $feature . '_mode', null);
    if ($featureMode) {
        return $featureMode;
    }
    
    return $mode;
}

/**
 * Check if action should be automated
 * @param string $actionType Action type
 * @param string $feature Feature name
 * @return bool True if should be automated
 */
function seo_manager_should_automate($actionType, $feature = 'general') {
    $mode = seo_manager_get_automation_mode($feature);
    
    if ($mode === 'manual') {
        return false;
    }
    
    if ($mode === 'automated') {
        return true;
    }
    
    if ($mode === 'scheduled') {
        // Check if there's a scheduled task for this
        return false; // Scheduled tasks are handled separately
    }
    
    // Hybrid mode - check action type
    if ($mode === 'hybrid') {
        $safeActions = ['meta_update', 'sitemap_generation'];
        return in_array($actionType, $safeActions);
    }
    
    return false;
}

/**
 * Execute automated action
 * @param string $actionType Action type
 * @param array $data Action data
 * @param string $feature Feature name
 * @return array Result
 */
function seo_manager_execute_automated_action($actionType, $data, $feature = 'general') {
    if (!seo_manager_should_automate($actionType, $feature)) {
        return ['success' => false, 'error' => 'Action not set for automation'];
    }
    
    // Log the action
    seo_manager_log_optimization([
        'action_type' => $actionType,
        'action_description' => 'Automated ' . $actionType,
        'automation_mode' => 'automated',
        'metadata' => $data
    ]);
    
    return ['success' => true, 'message' => 'Action executed'];
}

