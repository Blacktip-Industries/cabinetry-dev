<?php
/**
 * Add Color Fail Parameter
 * Adds --color-fail parameter for inactive status button color
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Color Fail Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_color_fail_parameter.php');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Ensure tables exist
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Find the section that contains --color-success
    $sectionStmt = $conn->prepare("SELECT DISTINCT section FROM settings_parameters WHERE parameter_name LIKE '%color-success%' OR parameter_name LIKE '%color-danger%' OR parameter_name LIKE '%color-primary%' LIMIT 1");
    $sectionStmt->execute();
    $sectionResult = $sectionStmt->get_result();
    $sectionRow = $sectionResult->fetch_assoc();
    $sectionStmt->close();
    
    // Use "Colors" section if found, otherwise "Semantic" or default to "Colors"
    $targetSection = $sectionRow ? $sectionRow['section'] : 'Colors';
    
    // If no color parameters found, check for common section names
    if (!$targetSection) {
        $checkStmt = $conn->prepare("SELECT DISTINCT section FROM settings_parameters WHERE section IN ('Colors', 'Semantic', 'Color') LIMIT 1");
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        $checkStmt->close();
        $targetSection = $checkRow ? $checkRow['section'] : 'Colors';
    }
    
    $parameterKey = '--color-fail';
    $parameterValue = '#ef5f5f'; // Red color (similar to danger)
    $parameterDescription = 'Color for inactive status button (Deactivate button when status is inactive)';
    
    // Check if parameter already exists
    $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
    $checkStmt->bind_param("ss", $targetSection, $parameterKey);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    $parameterId = null;
    $added = false;
    
    if (!$existing) {
        // Use upsertParameter function to add the parameter
        if (upsertParameter($targetSection, $parameterKey, $parameterValue, $parameterDescription)) {
            $added = true;
            // Get the ID of the newly added parameter
            $getIdStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
            $getIdStmt->bind_param("ss", $targetSection, $parameterKey);
            $getIdStmt->execute();
            $idResult = $getIdStmt->get_result();
            $newParam = $idResult->fetch_assoc();
            $getIdStmt->close();
            if ($newParam) {
                $parameterId = $newParam['id'];
            }
        }
    } else {
        $parameterId = $existing['id'];
    }
    
    // Configure color picker
    $colorPickerConfigured = false;
    if ($parameterId) {
        // Update or insert the input config to use color picker
        if (upsertParameterInputConfig($parameterId, 'color', null, null, null, null)) {
            $colorPickerConfigured = true;
        }
    }
    
    $messages = [];
    if ($added) {
        $messages[] = "Successfully added --color-fail parameter in '{$targetSection}' section.";
    } else {
        $messages[] = "Parameter --color-fail already exists in '{$targetSection}' section.";
    }
    if ($colorPickerConfigured) {
        $messages[] = "Configured parameter to use color picker.";
    }
    
    $success = implode(' ', $messages);
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Color Fail Parameter</h2>
        <p class="text-muted">Add --color-fail parameter for inactive status button color</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p><strong>SCRIPT URL - CLICK TO RUN:</strong></p>
        <p style="margin-bottom: 1rem;"><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all; font-size: 14px; padding: 12px 24px;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666; margin-bottom: 1.5rem;">This script executes automatically when you load this page. Click the link above to open in a new tab and run it.</p>
        
        <hr style="margin: 1.5rem 0;">
        
        <p>This script adds the --color-fail parameter for customizing the color of inactive status buttons (Deactivate button when status is inactive).</p>
        <p>The parameter will be added in the same section as other color parameters (e.g., Colors or Semantic section).</p>
        <p>The parameter will be configured to use a color picker with a default red color (#ef5f5f).</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php')); ?>" class="btn btn-secondary btn-medium">View Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

