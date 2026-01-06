<?php
/**
 * Remove Menu Divider Parameters
 * Removes all menu section divider parameters from the database
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Remove Menu Divider Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/remove_menu_divider_parameters.php');

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
    
    // List of divider parameters to remove
    $dividerParameters = [
        '--menu-section-divider-color',
        '--menu-section-divider-width',
        '--menu-section-divider-style',
        '--menu-section-divider-margin-top',
        '--menu-section-divider-margin-bottom',
        '--menu-section-divider-margin-left',
        '--menu-section-divider-margin-right'
    ];
    
    foreach ($dividerParameters as $paramName) {
        // Find the parameter
        $findStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = ? AND section = 'Menu'");
        $findStmt->bind_param("s", $paramName);
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
                $actions[] = 'Removed parameter: ' . htmlspecialchars($paramName);
            } else {
                $error = 'Failed to remove parameter ' . htmlspecialchars($paramName) . ': ' . $deleteStmt->error;
                break;
            }
            $deleteStmt->close();
        } else {
            $actions[] = 'Parameter not found (already removed): ' . htmlspecialchars($paramName);
        }
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Remove Menu Divider Parameters</h2>
        <p class="text-muted">Removes all menu section divider parameters from the database</p>
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
        <p>This script removes the following menu divider parameters:</p>
        <ul>
            <li><code>--menu-section-divider-color</code></li>
            <li><code>--menu-section-divider-width</code></li>
            <li><code>--menu-section-divider-style</code></li>
            <li><code>--menu-section-divider-margin-top</code></li>
            <li><code>--menu-section-divider-margin-bottom</code></li>
            <li><code>--menu-section-divider-margin-left</code></li>
            <li><code>--menu-section-divider-margin-right</code></li>
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

