<?php
/**
 * Error Monitoring Component - Auto-Initialization
 * Registers handlers and injects UI elements
 */

// Only initialize if component is installed
if (!file_exists(__DIR__ . '/../config.php')) {
    return;
}

// Load component config
require_once __DIR__ . '/../config.php';

// Load core files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/error_handler.php';
require_once __DIR__ . '/../core/component_detector.php';

/**
 * Check if current user is admin
 * Supports both base system and access component
 * @return bool True if admin
 */
function error_monitoring_is_admin_user() {
    // Try access component first
    if (function_exists('access_check_auth') && function_exists('access_user_has_permission')) {
        if (access_check_auth()) {
            return access_user_has_permission('admin_access');
        }
        return false;
    }
    
    // Fallback to base system
    if (function_exists('checkAuth')) {
        return checkAuth();
    }
    
    // Fallback to session check
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Initialize error monitoring
 * @return void
 */
function error_monitoring_init() {
    // Register error handlers
    if (error_monitoring_is_enabled() && error_monitoring_is_installed()) {
        error_monitoring_register_handlers();
    }
}

/**
 * Inject notification bar and floating widget for admin users
 * @return void
 */
function error_monitoring_inject_ui() {
    // Only inject for admin users
    if (!error_monitoring_is_admin_user()) {
        return;
    }
    
    // Check for opt-out flag
    if (isset($_GET['no_error_notifications']) || (defined('ERROR_MONITORING_NO_UI') && ERROR_MONITORING_NO_UI)) {
        return;
    }
    
    // Inject CSS and JS
    $adminUrl = error_monitoring_get_admin_url();
    $baseUrl = error_monitoring_get_base_url();
    
    // Add CSS
    echo '<link rel="stylesheet" href="' . htmlspecialchars($adminUrl) . '/assets/css/error_monitoring.css">' . "\n";
    
    // Add JS
    echo '<script src="' . htmlspecialchars($adminUrl) . '/assets/js/error_monitoring.js"></script>' . "\n";
    echo '<script src="' . htmlspecialchars($adminUrl) . '/assets/js/notification-bar.js"></script>' . "\n";
    echo '<script src="' . htmlspecialchars($adminUrl) . '/assets/js/floating-widget.js"></script>' . "\n";
    
    // Initialize notification bar
    echo '<div id="error_monitoring_notification_bar" class="error_monitoring__notification-bar" style="display: none;"></div>' . "\n";
    
    // Initialize floating widget
    echo '<div id="error_monitoring_floating_widget" class="error_monitoring__floating-widget">' . "\n";
    echo '  <div class="error_monitoring__widget-icon" id="error_monitoring_widget_icon">' . "\n";
    echo '    <span class="error_monitoring__widget-badge" id="error_monitoring_widget_badge" style="display: none;">0</span>' . "\n";
    echo '  </div>' . "\n";
    echo '  <div class="error_monitoring__widget-panel" id="error_monitoring_widget_panel" style="display: none;"></div>' . "\n";
    echo '</div>' . "\n";
    
    // Initialize with API endpoint
    echo '<script>' . "\n";
    echo '  if (typeof ErrorMonitoring !== "undefined") {' . "\n";
    echo '    ErrorMonitoring.init({' . "\n";
    echo '      apiUrl: "' . htmlspecialchars($adminUrl) . '/admin/api/get-unread-count.php",' . "\n";
    echo '      baseUrl: "' . htmlspecialchars($baseUrl) . '"' . "\n";
    echo '    });' . "\n";
    echo '  }' . "\n";
    echo '</script>' . "\n";
}

// Auto-initialize if not in CLI mode
if (php_sapi_name() !== 'cli') {
    // Register shutdown function to inject UI
    register_shutdown_function(function() {
        // Only inject if output buffering is active or we're in admin area
        if (ob_get_level() > 0 || strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false) {
            error_monitoring_inject_ui();
        }
    });
    
    // Initialize error handlers
    error_monitoring_init();
}

