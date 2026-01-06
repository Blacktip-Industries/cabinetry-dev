<?php
/**
 * Add Default Icon System
 * Creates the default icon and parameter for displaying when icons are missing
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Default Icon System');

$scriptUrl = getAdminUrl('setup/add_default_icon_system.php');

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    migrateSetupIconsTable($conn);
    
    // Step 1: Add --icon-default-color parameter
    $parameterName = '--icon-default-color';
    $defaultValue = '#EF4444';
    $description = 'Color for the default icon displayed when an icon is missing';
    $section = 'Icons';
    
    // Check if parameter already exists
    $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
    $checkStmt->bind_param("ss", $section, $parameterName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        $actions[] = [
            'status' => 'skipped',
            'message' => "Parameter '{$parameterName}' already exists in section '{$section}' with value: {$existing['value']}"
        ];
    } else {
        // Add the parameter
        $upsertResult = upsertParameter($section, $parameterName, $defaultValue, $description);
        
        if ($upsertResult) {
            $actions[] = [
                'status' => 'success',
                'message' => "Successfully added parameter '{$parameterName}' to section '{$section}' with value: {$defaultValue}"
            ];
            
            // Configure as color picker
            $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
            $paramStmt->bind_param("ss", $section, $parameterName);
            $paramStmt->execute();
            $paramResult = $paramStmt->get_result();
            $paramRow = $paramResult->fetch_assoc();
            $paramStmt->close();
            
            if ($paramRow) {
                $paramId = $paramRow['id'];
                
                // Check if config already exists
                $configCheckStmt = $conn->prepare("SELECT id FROM settings_parameters_configs WHERE parameter_id = ?");
                $configCheckStmt->bind_param("i", $paramId);
                $configCheckStmt->execute();
                $configResult = $configCheckStmt->get_result();
                $configExists = $configResult->fetch_assoc();
                $configCheckStmt->close();
                
                if (!$configExists) {
                    // Add color picker config
                    $configResult = upsertParameterInputConfig($paramId, 'color', null, null, null, null);
                    if ($configResult) {
                        $actions[] = [
                            'status' => 'success',
                            'message' => "Configured parameter '{$parameterName}' to use color picker input"
                        ];
                    }
                }
            }
        } else {
            $actions[] = [
                'status' => 'error',
                'message' => "Failed to add parameter '{$parameterName}'"
            ];
        }
    }
    
    // Step 2: Create default icon in setup_icons table
    $defaultIconName = '--icon-default';
    $defaultIconCategory = 'Default';
    
    // Check if default icon already exists
    $iconCheckStmt = $conn->prepare("SELECT id FROM setup_icons WHERE name = ?");
    $iconCheckStmt->bind_param("s", $defaultIconName);
    $iconCheckStmt->execute();
    $iconResult = $iconCheckStmt->get_result();
    $existingIcon = $iconResult->fetch_assoc();
    $iconCheckStmt->close();
    
    if ($existingIcon) {
        $actions[] = [
            'status' => 'skipped',
            'message' => "Default icon '{$defaultIconName}' already exists in the database"
        ];
    } else {
        // Create a simple warning/error icon SVG (circle with exclamation mark)
        $defaultIconSVG = '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="currentColor"/>';
        
        // Store viewBox in comment
        $svgPathWithViewBox = '<!--viewBox:0 0 24 24-->' . $defaultIconSVG;
        
        // Insert default icon
        $insertStmt = $conn->prepare("INSERT INTO setup_icons (name, svg_path, description, category, is_active, display_order) VALUES (?, ?, ?, ?, 1, 0)");
        $description = 'Default icon displayed when an icon is missing';
        $insertStmt->bind_param("ssss", $defaultIconName, $svgPathWithViewBox, $description, $defaultIconCategory);
        
        if ($insertStmt->execute()) {
            $actions[] = [
                'status' => 'success',
                'message' => "Successfully created default icon '{$defaultIconName}' in category '{$defaultIconCategory}'"
            ];
        } else {
            $actions[] = [
                'status' => 'error',
                'message' => "Failed to create default icon: " . $insertStmt->error
            ];
        }
        $insertStmt->close();
    }
}
?>

<div class="admin-container">
    <div class="admin-content">
        <h1>Add Default Icon System</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($actions)): ?>
            <div class="actions-list" style="margin-top: 2rem;">
                <h2>Actions Performed:</h2>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($actions as $action): ?>
                        <li style="padding: 0.5rem; margin-bottom: 0.5rem; border-left: 3px solid <?php 
                            echo $action['status'] === 'success' ? '#10b981' : 
                                ($action['status'] === 'error' ? '#ef4444' : '#6b7280'); 
                        ?>; padding-left: 1rem;">
                            <strong><?php echo ucfirst($action['status']); ?>:</strong> 
                            <?php echo htmlspecialchars($action['message']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem;">
            <a href="<?php echo getAdminUrl('settings/parameters.php?section=Icons'); ?>" class="btn btn-primary">View Icons Parameters</a>
            <a href="<?php echo getAdminUrl('setup/icons.php'); ?>" class="btn btn-secondary">Back to Icons</a>
        </div>
    </div>
</div>

<?php endLayout(); ?>

