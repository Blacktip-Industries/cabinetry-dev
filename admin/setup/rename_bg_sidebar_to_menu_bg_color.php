<?php
/**
 * Rename Sidebar Background Parameter
 * Renames --bg-sidebar to --menu-bg-color in the database
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Rename Sidebar Background Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/rename_bg_sidebar_to_menu_bg_color.php');

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
    
    // Step 1: Find the old parameter --bg-sidebar
    $findStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE parameter_name = '--bg-sidebar'");
    $findStmt->execute();
    $result = $findStmt->get_result();
    $oldParam = $result->fetch_assoc();
    $findStmt->close();
    
    if ($oldParam) {
        // Check if new parameter already exists
        $checkNewStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-bg-color'");
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        $newParam = $newResult->fetch_assoc();
        $checkNewStmt->close();
        
        if ($newParam) {
            // New parameter exists, delete old one
            $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteStmt->bind_param("i", $oldParam['id']);
            if ($deleteStmt->execute()) {
                $actions[] = 'Removed old parameter --bg-sidebar (new parameter --menu-bg-color already exists)';
            } else {
                $error = 'Failed to remove old parameter: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        } else {
            // Rename the parameter
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET parameter_name = '--menu-bg-color' WHERE id = ?");
            $updateStmt->bind_param("i", $oldParam['id']);
            if ($updateStmt->execute()) {
                $actions[] = 'Renamed --bg-sidebar to --menu-bg-color';
                
                // Also update the input config if it exists (to maintain the color picker setting)
                $configStmt = $conn->prepare("SELECT id FROM settings_parameters_configs WHERE parameter_id = ?");
                $configStmt->bind_param("i", $oldParam['id']);
                $configStmt->execute();
                $configResult = $configStmt->get_result();
                $configExists = $configResult->fetch_assoc();
                $configStmt->close();
                
                if ($configExists) {
                    $actions[] = 'Preserved input configuration (color picker) for --menu-bg-color';
                }
            } else {
                $error = 'Failed to rename parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        // Parameter doesn't exist, check if new one exists
        $checkNewStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-bg-color'");
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        $newParam = $newResult->fetch_assoc();
        $checkNewStmt->close();
        
        if ($newParam) {
            $actions[] = 'Parameter --menu-bg-color already exists';
        } else {
            $actions[] = 'Parameter --bg-sidebar does not exist. No action needed.';
        }
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Rename Sidebar Background Parameter</h2>
        <p class="text-muted">Renames --bg-sidebar to --menu-bg-color</p>
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
        <p>This script:</p>
        <ul>
            <li>Renames <code>--bg-sidebar</code> to <code>--menu-bg-color</code> in the database</li>
            <li>Preserves the parameter value and input configuration</li>
        </ul>
        <p><strong>Step 1: Execute this script by clicking the link below:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">This script executes automatically when you load the page. Click the link above to open in a new tab and run it.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Backgrounds')); ?>" class="btn btn-secondary btn-medium">View Backgrounds Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

