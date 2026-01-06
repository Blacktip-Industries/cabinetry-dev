<?php
/**
 * Remove Menu Active Background Color Parameter
 * Removes the --menu-active-bg-color parameter from the database since we only use text highlighting now
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Remove Menu Active Background Color Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/remove_menu_active_bg_color_parameter.php');

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Ensure tables exist
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Find the parameter --menu-active-bg-color
    $findStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-active-bg-color' AND section = 'Menu'");
    $findStmt->execute();
    $result = $findStmt->get_result();
    $param = $result->fetch_assoc();
    $findStmt->close();
    
    if ($param) {
        $paramId = $param['id'];
        
        // Delete input configs first (if any)
        $deleteConfigStmt = $conn->prepare("DELETE FROM settings_parameters_configs WHERE parameter_id = ?");
        $deleteConfigStmt->bind_param("i", $paramId);
        $deleteConfigStmt->execute();
        $deleteConfigStmt->close();
        
        // Delete the parameter
        $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
        $deleteStmt->bind_param("i", $paramId);
        if ($deleteStmt->execute()) {
            $actions[] = 'Removed parameter: --menu-active-bg-color';
        } else {
            $error = 'Failed to remove parameter: ' . $deleteStmt->error;
        }
        $deleteStmt->close();
    } else {
        $actions[] = 'Parameter --menu-active-bg-color not found (already removed or never existed)';
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Remove Menu Active Background Color Parameter</h2>
        <p class="text-muted">Removes --menu-active-bg-color parameter since we only use text highlighting</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <strong>Success:</strong><br><?php echo $success; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p>This script removes the <code>--menu-active-bg-color</code> parameter from the database since menu items now only use text highlighting (no background highlighting).</p>
        <p><strong>Parameters needed for menu text highlighting:</strong></p>
        <ul>
            <li><code>--menu-active-text-color</code> - Controls the text color of active/selected menu items</li>
        </ul>
        <p><strong>Step 1: Execute this script by clicking the link below:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">This script executes automatically when you load the page. Click the link above to open in a new tab and run it.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Menu')); ?>" class="btn btn-secondary btn-medium">View Menu Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

