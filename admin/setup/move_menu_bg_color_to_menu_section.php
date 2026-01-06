<?php
/**
 * Move Menu Background Color Parameter
 * Moves --menu-bg-color from Backgrounds section to Menu section
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Move Menu Background Color Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/move_menu_bg_color_to_menu_section.php');

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
    
    // Step 1: Find the parameter --menu-bg-color
    $findStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE parameter_name = '--menu-bg-color'");
    $findStmt->execute();
    $result = $findStmt->get_result();
    $param = $result->fetch_assoc();
    $findStmt->close();
    
    if ($param) {
        // Check if it's already in Menu section
        if ($param['section'] === 'Menu') {
            $actions[] = 'Parameter --menu-bg-color is already in Menu section. No action needed.';
        } else {
            // Move the parameter to Menu section
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET section = 'Menu' WHERE id = ?");
            $updateStmt->bind_param("i", $param['id']);
            if ($updateStmt->execute()) {
                $actions[] = 'Moved --menu-bg-color from ' . htmlspecialchars($param['section']) . ' section to Menu section';
            } else {
                $error = 'Failed to move parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        $error = 'Parameter --menu-bg-color not found in database. Please create it first.';
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Move Menu Background Color Parameter</h2>
        <p class="text-muted">Moves --menu-bg-color from Backgrounds section to Menu section</p>
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
            <li>Moves <code>--menu-bg-color</code> from Backgrounds section to Menu section</li>
            <li>Preserves the parameter value and all configurations</li>
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

