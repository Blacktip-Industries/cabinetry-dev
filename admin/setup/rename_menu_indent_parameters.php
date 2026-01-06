<?php
/**
 * Rename Menu Indent Parameters
 * Renames --menu-section-header-indent-left to --indent-menu-section-header
 * and creates new --indent-menu and --indent-submenu parameters
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Rename Menu Indent Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/rename_menu_indent_parameters.php');

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
    
    // Step 1: Rename --menu-section-header-indent-left to --indent-menu-section-header
    $checkOldStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE parameter_name = '--menu-section-header-indent-left'");
    $checkOldStmt->execute();
    $result = $checkOldStmt->get_result();
    $oldParam = $result->fetch_assoc();
    $checkOldStmt->close();
    
    if ($oldParam) {
        // Check if new parameter already exists
        $checkNewStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--indent-menu-section-header'");
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        $newParam = $newResult->fetch_assoc();
        $checkNewStmt->close();
        
        if ($newParam) {
            // New parameter exists, delete old one
            $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteStmt->bind_param("i", $oldParam['id']);
            if ($deleteStmt->execute()) {
                $actions[] = 'Removed old parameter --menu-section-header-indent-left (new parameter already exists)';
            } else {
                $error = 'Failed to remove old parameter: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        } else {
            // Rename the parameter
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET parameter_name = '--indent-menu-section-header' WHERE id = ?");
            $updateStmt->bind_param("i", $oldParam['id']);
            if ($updateStmt->execute()) {
                $actions[] = 'Renamed --menu-section-header-indent-left to --indent-menu-section-header';
            } else {
                $error = 'Failed to rename parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        $actions[] = 'Old parameter --menu-section-header-indent-left not found (may have been renamed already)';
    }
    
    // Step 2: Create --indent-menu parameter if it doesn't exist
    $checkMenuStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--indent-menu'");
    $checkMenuStmt->execute();
    $menuResult = $checkMenuStmt->get_result();
    $menuParam = $menuResult->fetch_assoc();
    $checkMenuStmt->close();
    
    if (!$menuParam) {
        if (upsertParameter('Indents', '--indent-menu', '25px', 'Left indent for menu items')) {
            $actions[] = 'Created --indent-menu parameter';
        } else {
            $error = 'Failed to create --indent-menu parameter';
        }
    } else {
        $actions[] = 'Parameter --indent-menu already exists';
    }
    
    // Step 3: Create --indent-submenu parameter if it doesn't exist
    $checkSubmenuStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--indent-submenu'");
    $checkSubmenuStmt->execute();
    $submenuResult = $checkSubmenuStmt->get_result();
    $submenuParam = $submenuResult->fetch_assoc();
    $checkSubmenuStmt->close();
    
    if (!$submenuParam) {
        if (upsertParameter('Indents', '--indent-submenu', '59px', 'Left indent for submenu items')) {
            $actions[] = 'Created --indent-submenu parameter';
        } else {
            $error = 'Failed to create --indent-submenu parameter';
        }
    } else {
        $actions[] = 'Parameter --indent-submenu already exists';
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Rename Menu Indent Parameters</h2>
        <p class="text-muted">Rename menu indent parameter and create new menu/submenu indent parameters</p>
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
            <li>Renames <code>--menu-section-header-indent-left</code> to <code>--indent-menu-section-header</code></li>
            <li>Creates <code>--indent-menu</code> parameter for menu items (default: 25px)</li>
            <li>Creates <code>--indent-submenu</code> parameter for submenu items (default: 59px)</li>
        </ul>
        <p><strong>Step 1: Execute this script by clicking the link below:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">This script executes automatically when you load the page. Click the link above to open in a new tab and run it.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Indents')); ?>" class="btn btn-secondary btn-medium">View Indents Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

