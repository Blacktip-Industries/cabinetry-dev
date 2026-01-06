<?php
/**
 * Product Options Component - Visual Builder
 * Drag-and-drop builder with live preview
 */

require_once __DIR__ . '/../../includes/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Visual Builder - Product Options</title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-dashboard">
        <h1>Visual Option Builder</h1>
        <a href="../index.php">Back to Dashboard</a>
        
        <div class="builder-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="builder-panel">
                <h2>Builder</h2>
                <p>Visual drag-and-drop builder interface coming soon.</p>
                <p>For now, use the <a href="create.php">Create Option</a> form.</p>
            </div>
            
            <div class="preview-panel">
                <h2>Live Preview</h2>
                <div id="preview-container">
                    <p>Preview will appear here</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/product-options.js"></script>
</body>
</html>

