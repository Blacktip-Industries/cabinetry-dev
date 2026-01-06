<?php
/**
 * Theme Component - Device Preview Page
 * Preview website on different devices with advanced features
 */

// Security check - admin only
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/device-preview-manager.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/theme-loader.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    if ($action === 'get_presets') {
        $presets = device_preview_get_presets(true);
        echo json_encode(['success' => true, 'presets' => $presets]);
        exit;
    }
    
    if ($action === 'get_preset' && isset($_GET['id'])) {
        $preset = device_preview_get_preset((int)$_GET['id']);
        echo json_encode(['success' => $preset !== null, 'preset' => $preset]);
        exit;
    }
    
    if ($action === 'get_frontend_pages') {
        $pages = device_preview_get_frontend_pages();
        echo json_encode(['success' => true, 'pages' => $pages]);
        exit;
    }
    
    // Validate preset ID for get_preset
    if ($action === 'get_preset' && isset($_GET['id'])) {
        $presetId = (int)$_GET['id'];
        if ($presetId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid preset ID']);
            exit;
        }
    }
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    if (function_exists('startLayout')) {
        startLayout('Device Preview', true, 'theme_device_preview');
    }
}

if (!$hasBaseLayout || !function_exists('startLayout')) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Device Preview - Theme Component</title>
        <?php echo theme_load_assets(true); ?>
        <link rel="stylesheet" href="<?php echo theme_get_css_url(); ?>/device-preview.css">
    </head>
    <body>
    <?php
}

// Get frontend pages for URL selector
$frontendPages = device_preview_get_frontend_pages();
?>

<div class="device-preview-container">
    <!-- Toolbar -->
    <div class="device-preview-toolbar">
        <div class="device-preview-toolbar__group">
            <label class="device-preview-toolbar__label" for="preview-mode-select">Mode:</label>
            <select id="preview-mode-select" class="device-preview-toolbar__select">
                <option value="frontend">Frontend Pages</option>
                <option value="design-system">Design System</option>
            </select>
        </div>
        
        <div class="device-preview-toolbar__group" id="url-selector-group">
            <label class="device-preview-toolbar__label" for="preview-url-select">URL:</label>
            <select id="preview-url-select" class="device-preview-toolbar__select">
                <?php foreach ($frontendPages as $page): ?>
                    <?php if (device_preview_validate_url($page['url'])): ?>
                        <option value="<?php echo htmlspecialchars($page['url']); ?>">
                            <?php echo htmlspecialchars($page['title']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <input type="text" id="preview-url-input" class="device-preview-toolbar__input" 
                   placeholder="Custom URL..." style="display: none;">
        </div>
        
        <div class="device-preview-toolbar__group">
            <label class="device-preview-toolbar__label" for="device-preset-select">Device:</label>
            <select id="device-preset-select" class="device-preview-toolbar__select">
                <option value="">Loading...</option>
            </select>
        </div>
        
        <div class="device-preview-toolbar__group">
            <button id="preview-refresh" class="device-preview-toolbar__button" title="Refresh Preview">
                🔄 Refresh
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="device-preview-main">
        <!-- Preview Area -->
        <div class="device-preview-content">
            <div class="device-preview-frame">
                <div class="device-preview-iframe-container">
                    <iframe id="preview-iframe" class="device-preview-iframe" src="about:blank"></iframe>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Controls -->
        <div class="device-preview-sidebar">
            <!-- Orientation Control -->
            <div class="device-preview-controls">
                <div class="device-preview-controls__section">
                    <div class="device-preview-controls__title">Orientation</div>
                    <button id="orientation-toggle" class="device-preview-controls__button">
                        Rotate to <span id="orientation-target">Landscape</span>
                    </button>
                    <div style="margin-top: var(--spacing-sm, 8px); font-size: var(--font-size-small, 14px); color: var(--text-secondary, #6b7280);">
                        Current: <span id="current-orientation">Portrait</span>
                    </div>
                </div>
            </div>
            
            <!-- Network Throttling -->
            <div class="device-preview-controls">
                <div class="device-preview-controls__section">
                    <div class="device-preview-controls__title">Network</div>
                    <select id="network-throttle-select" class="device-preview-toolbar__select" style="width: 100%; margin-bottom: var(--spacing-sm, 8px);">
                        <option value="wifi">WiFi</option>
                        <option value="4g">4G</option>
                        <option value="fast-3g">Fast 3G</option>
                        <option value="slow-3g">Slow 3G</option>
                        <option value="offline">Offline</option>
                    </select>
                    <div style="font-size: var(--font-size-small, 14px); color: var(--text-secondary, #6b7280);">
                        Status: <span id="network-throttle-indicator">WiFi</span>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="device-preview-controls">
                <div class="device-preview-controls__section">
                    <div class="device-preview-controls__title">Performance Metrics</div>
                    <div class="device-preview-metrics">
                        <div class="device-preview-metrics__item">
                            <span class="device-preview-metrics__label">Load Time:</span>
                            <span class="device-preview-metrics__value" id="metric-load-time">-</span>
                        </div>
                        <div class="device-preview-metrics__item">
                            <span class="device-preview-metrics__label">DOM Ready:</span>
                            <span class="device-preview-metrics__value" id="metric-dom-ready">-</span>
                        </div>
                        <div class="device-preview-metrics__item">
                            <span class="device-preview-metrics__label">First Paint:</span>
                            <span class="device-preview-metrics__value" id="metric-fcp">-</span>
                        </div>
                        <div class="device-preview-metrics__item">
                            <span class="device-preview-metrics__label">Resources:</span>
                            <span class="device-preview-metrics__value" id="metric-resources">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="device-preview-controls">
                <div class="device-preview-controls__section">
                    <div class="device-preview-controls__title">Actions</div>
                    <button id="screenshot-capture" class="device-preview-controls__button">
                        📸 Capture Screenshot
                    </button>
                    <a href="device-presets.php" class="device-preview-controls__button" style="text-decoration: none; text-align: center; display: block;">
                        ⚙️ Manage Presets
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo theme_load_js(false); ?>
<link rel="stylesheet" href="<?php echo theme_get_css_url(); ?>/device-preview.css">
<script src="<?php echo theme_get_js_url(); ?>/device-preview.js"></script>

<script>
// Update orientation toggle text based on current orientation
document.addEventListener('DOMContentLoaded', function() {
    const orientationToggle = document.getElementById('orientation-toggle');
    const orientationTarget = document.getElementById('orientation-target');
    
    if (orientationToggle && orientationTarget) {
        // This will be updated by DevicePreview when device is loaded
        orientationToggle.addEventListener('click', function() {
            setTimeout(() => {
                const current = document.getElementById('current-orientation').textContent;
                orientationTarget.textContent = current === 'Portrait' ? 'Portrait' : 'Landscape';
            }, 100);
        });
    }
});
</script>

<?php
if ($hasBaseLayout && function_exists('endLayout')) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

