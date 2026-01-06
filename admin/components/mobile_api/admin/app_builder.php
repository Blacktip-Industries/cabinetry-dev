<?php
/**
 * Mobile API Component - App Builder
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/app_builder.php';

$pageTitle = 'App Builder';

// Get available features
$features = mobile_api_get_available_features();

// Get default layout
$defaultLayout = mobile_api_get_default_layout();

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
        
        <div class="mobile_api__app-builder">
            <div class="mobile_api__builder-sidebar">
                <h2>Available Features</h2>
                <div class="mobile_api__features-list">
                    <?php foreach ($features as $feature): ?>
                        <div class="mobile_api__feature-item" draggable="true">
                            <h4><?php echo htmlspecialchars($feature['feature_name']); ?></h4>
                            <p><?php echo htmlspecialchars($feature['feature_type']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mobile_api__builder-canvas">
                <h2>App Layout</h2>
                <div id="mobile_api__layout-canvas" class="mobile_api__layout-canvas">
                    <p>Drag features here to build your app layout</p>
                </div>
                <button class="mobile_api__btn mobile_api__btn--primary" onclick="saveLayout()">Save Layout</button>
                <button class="mobile_api__btn" onclick="previewLayout()">Preview</button>
            </div>
        </div>
    </div>
    
    <script>
        // Simple drag and drop implementation
        const features = document.querySelectorAll('.mobile_api__feature-item');
        const canvas = document.getElementById('mobile_api__layout-canvas');
        
        features.forEach(feature => {
            feature.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', feature.innerHTML);
            });
        });
        
        canvas.addEventListener('dragover', (e) => {
            e.preventDefault();
        });
        
        canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            const data = e.dataTransfer.getData('text/plain');
            const div = document.createElement('div');
            div.className = 'mobile_api__layout-item';
            div.innerHTML = data;
            canvas.appendChild(div);
        });
        
        function saveLayout() {
            // Implementation would save layout configuration
            alert('Layout saved!');
        }
        
        function previewLayout() {
            // Implementation would show preview
            alert('Preview feature coming soon!');
        }
    </script>
</body>
</html>

