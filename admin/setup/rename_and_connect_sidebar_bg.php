<?php
/**
 * Rename and Connect Sidebar Background Parameter
 * Renames --color-backgrounds-sidebar to --menu-bg-color, moves it to Backgrounds section,
 * and connects it to control the sidebar background color
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Rename and Connect Sidebar Background');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/rename_and_connect_sidebar_bg.php');

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
    
    // Step 1: Find the old parameter in Colors section
    $findStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE parameter_name = '--color-backgrounds-sidebar' AND section = 'Colors'");
    $findStmt->execute();
    $result = $findStmt->get_result();
    $oldParam = $result->fetch_assoc();
    $findStmt->close();
    
    if ($oldParam) {
        // Check if new parameter already exists in Backgrounds section
        $checkNewStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-bg-color' AND section = 'Backgrounds'");
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        $newParam = $newResult->fetch_assoc();
        $checkNewStmt->close();
        
        if ($newParam) {
            // New parameter exists, delete old one
            $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteStmt->bind_param("i", $oldParam['id']);
            if ($deleteStmt->execute()) {
                $actions[] = 'Removed old parameter --color-backgrounds-sidebar from Colors section (new parameter already exists in Backgrounds)';
            } else {
                $error = 'Failed to remove old parameter: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        } else {
            // Rename and move the parameter
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET parameter_name = '--menu-bg-color', section = 'Backgrounds' WHERE id = ?");
            $updateStmt->bind_param("i", $oldParam['id']);
            if ($updateStmt->execute()) {
                $actions[] = 'Renamed --color-backgrounds-sidebar to --menu-bg-color and moved to Backgrounds section';
            } else {
                $error = 'Failed to rename and move parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        // Parameter doesn't exist in Colors, check if it exists in Backgrounds
        $checkBgStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-bg-color' AND section = 'Backgrounds'");
        $checkBgStmt->execute();
        $bgResult = $checkBgStmt->get_result();
        $bgParam = $bgResult->fetch_assoc();
        $checkBgStmt->close();
        
        if (!$bgParam) {
            // Create it in Backgrounds section with the value from old parameter if it exists, otherwise use default
            $defaultValue = $oldParam ? $oldParam['value'] : '#262D34';
            if (upsertParameter('Backgrounds', '--menu-bg-color', $defaultValue, 'Admin sidebar background color')) {
                $actions[] = 'Created --menu-bg-color parameter in Backgrounds section';
            } else {
                $error = 'Failed to create parameter in Backgrounds section';
            }
        } else {
            $actions[] = 'Parameter --menu-bg-color already exists in Backgrounds section';
        }
    }
    
    // Step 2: Set input type to color picker
    $finalParamStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--menu-bg-color' AND section = 'Backgrounds'");
    $finalParamStmt->execute();
    $finalResult = $finalParamStmt->get_result();
    $finalParam = $finalResult->fetch_assoc();
    $finalParamStmt->close();
    
    if ($finalParam && empty($error)) {
        $parameterId = $finalParam['id'];
        if (upsertParameterInputConfig($parameterId, 'color', null, null, null, null)) {
            $actions[] = 'Set --menu-bg-color parameter to use color picker input type';
        } else {
            $error = 'Failed to set color picker input type';
        }
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Rename and Connect Sidebar Background</h2>
        <p class="text-muted">Rename sidebar background parameter and connect it to control the sidebar</p>
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
            <li>Renames <code>--color-backgrounds-sidebar</code> to <code>--menu-bg-color</code></li>
            <li>Moves it from Colors section to Backgrounds section</li>
            <li>Sets it to use color picker input type</li>
            <li>Connects it to control the admin sidebar background color</li>
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

