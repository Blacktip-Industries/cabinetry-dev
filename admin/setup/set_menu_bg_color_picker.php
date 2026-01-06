<?php
/**
 * Set Menu Section Header Background Color to Use Color Picker
 * Updates the --menu-section-header-background-color parameter to use color picker input type
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Set Menu Background Color Picker');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/set_menu_bg_color_picker.php');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Ensure tables exist
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Find the parameter
    $findStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-section-header-background-color' AND section = 'Menu'");
    $findStmt->execute();
    $result = $findStmt->get_result();
    $param = $result->fetch_assoc();
    $findStmt->close();
    
    if ($param) {
        $parameterId = $param['id'];
        
        // Update or insert the input config to use color picker
        if (upsertParameterInputConfig($parameterId, 'color', null, null, null, null)) {
            $success = 'Successfully set --menu-section-header-background-color parameter to use color picker input type.';
        } else {
            $error = 'Failed to update parameter input type.';
        }
    } else {
        $error = 'Parameter --menu-section-header-background-color not found in Menu section. Please run add_menu_section_parameters.php first.';
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Set Menu Background Color Picker</h2>
        <p class="text-muted">Configure menu section header background color to use color picker</p>
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
        <p>This script configures the <code>--menu-section-header-background-color</code> parameter to use a color picker input instead of a text field.</p>
        <p><strong>Step 1: Execute this script by clicking the link below:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">This script executes automatically when you load the page. Click the link above to open in a new tab and run it.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Menu')); ?>" class="btn btn-secondary btn-medium">View Menu Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

