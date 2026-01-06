<?php
/**
 * Mobile API Component - Maps Configuration
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/maps_integration.php';

$pageTitle = 'Maps Configuration';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_map_settings') {
        // Update all map display settings
        $settings = [
            'map_default_zoom_level' => (int)$_POST['map_default_zoom_level'] ?? 13,
            'map_style' => $_POST['map_style'] ?? 'roadmap',
            'map_show_traffic' => isset($_POST['map_show_traffic']) ? 'yes' : 'no',
            'map_show_transit' => isset($_POST['map_show_transit']) ? 'yes' : 'no',
            'map_controls_enabled' => isset($_POST['map_controls_enabled']) ? 'yes' : 'no',
            'customer_marker_color' => $_POST['customer_marker_color'] ?? '#4285F4',
            'destination_marker_color' => $_POST['destination_marker_color'] ?? '#EA4335',
            'route_line_color' => $_POST['route_line_color'] ?? '#34A853',
            'route_line_weight' => (int)$_POST['route_line_weight'] ?? 5,
            'route_line_opacity' => (float)$_POST['route_line_opacity'] ?? 0.8,
            'show_location_history_trail' => isset($_POST['show_location_history_trail']) ? 'yes' : 'no',
            'location_history_trail_color' => $_POST['location_history_trail_color'] ?? '#FF9800',
            'map_auto_fit_bounds' => isset($_POST['map_auto_fit_bounds']) ? 'yes' : 'no'
        ];
        
        foreach ($settings as $key => $value) {
            mobile_api_set_parameter('Map Display', $key, $value);
        }
        
        $success = true;
    }
}

// Get current settings
$mapConfig = mobile_api_get_map_config();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Mobile API</title>
    <link rel="stylesheet" href="<?php echo mobile_api_get_admin_url(); ?>/assets/css/admin.css">
</head>
<body>
    <div class="mobile_api__container">
        <header class="mobile_api__header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>
        
        <?php if (isset($success)): ?>
            <div class="mobile_api__alert mobile_api__alert--success">
                Map settings saved successfully!
            </div>
        <?php endif; ?>
        
        <div class="mobile_api__maps-config">
            <form method="POST" class="mobile_api__settings-form">
                <input type="hidden" name="action" value="update_map_settings">
                
                <!-- General Map Settings -->
                <div class="mobile_api__section">
                    <h2>General Map Settings</h2>
                    
                    <div class="mobile_api__form-group">
                        <label>Default Zoom Level</label>
                        <input type="number" name="map_default_zoom_level" value="<?php echo htmlspecialchars($mapConfig['default_zoom']); ?>" min="1" max="20" class="mobile_api__input">
                        <small>Zoom level 1-20 (1 = world view, 20 = street level)</small>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Map Style</label>
                        <select name="map_style" class="mobile_api__input">
                            <option value="roadmap" <?php echo $mapConfig['map_style'] === 'roadmap' ? 'selected' : ''; ?>>Roadmap</option>
                            <option value="satellite" <?php echo $mapConfig['map_style'] === 'satellite' ? 'selected' : ''; ?>>Satellite</option>
                            <option value="hybrid" <?php echo $mapConfig['map_style'] === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            <option value="terrain" <?php echo $mapConfig['map_style'] === 'terrain' ? 'selected' : ''; ?>>Terrain</option>
                        </select>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="map_show_traffic" <?php echo $mapConfig['show_traffic'] ? 'checked' : ''; ?>>
                            Show Traffic Layer
                        </label>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="map_show_transit" <?php echo $mapConfig['show_transit'] ? 'checked' : ''; ?>>
                            Show Transit Layer
                        </label>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="map_controls_enabled" <?php echo $mapConfig['controls_enabled'] ? 'checked' : ''; ?>>
                            Enable Map Controls (zoom, pan, etc.)
                        </label>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="map_auto_fit_bounds" <?php echo $mapConfig['auto_fit_bounds'] ? 'checked' : ''; ?>>
                            Auto-fit Map to Show All Markers
                        </label>
                    </div>
                </div>
                
                <!-- Customer Location Marker -->
                <div class="mobile_api__section">
                    <h2>Customer Location Marker</h2>
                    
                    <div class="mobile_api__form-group">
                        <label>Marker Color</label>
                        <input type="color" name="customer_marker_color" value="<?php echo htmlspecialchars($mapConfig['customer_marker_color']); ?>" class="mobile_api__input">
                        <small>Color for customer location marker</small>
                    </div>
                </div>
                
                <!-- Destination Marker -->
                <div class="mobile_api__section">
                    <h2>Destination Marker</h2>
                    
                    <div class="mobile_api__form-group">
                        <label>Marker Color</label>
                        <input type="color" name="destination_marker_color" value="<?php echo htmlspecialchars($mapConfig['destination_marker_color']); ?>" class="mobile_api__input">
                        <small>Color for destination/collection address marker</small>
                    </div>
                </div>
                
                <!-- Route Display -->
                <div class="mobile_api__section">
                    <h2>Route Display</h2>
                    
                    <div class="mobile_api__form-group">
                        <label>Route Line Color</label>
                        <input type="color" name="route_line_color" value="<?php echo htmlspecialchars($mapConfig['route_line_color']); ?>" class="mobile_api__input">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Route Line Weight (pixels)</label>
                        <input type="number" name="route_line_weight" value="<?php echo htmlspecialchars($mapConfig['route_line_weight']); ?>" min="1" max="20" class="mobile_api__input">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Route Line Opacity</label>
                        <input type="number" name="route_line_opacity" value="<?php echo htmlspecialchars($mapConfig['route_line_opacity']); ?>" min="0" max="1" step="0.1" class="mobile_api__input">
                        <small>0.0 (transparent) to 1.0 (opaque)</small>
                    </div>
                </div>
                
                <!-- Location History Trail -->
                <div class="mobile_api__section">
                    <h2>Location History Trail</h2>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="show_location_history_trail" <?php echo $mapConfig['show_location_history_trail'] ? 'checked' : ''; ?>>
                            Show Location History Trail
                        </label>
                        <small>Display a trail showing the customer's path</small>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Trail Color</label>
                        <input type="color" name="location_history_trail_color" value="<?php echo htmlspecialchars($mapConfig['location_history_trail_color']); ?>" class="mobile_api__input">
                        <small>Color for the location history trail line</small>
                    </div>
                </div>
                
                <div class="mobile_api__form-actions">
                    <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

