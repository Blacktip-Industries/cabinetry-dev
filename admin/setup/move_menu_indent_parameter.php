<?php
/**
 * Move Menu Section Header Indent Parameter
 * Moves the --indent-menu-section-header parameter from Menu section to Indents section
 * NOTE: This script is deprecated. Use rename_menu_indent_parameters.php instead.
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Move Menu Indent Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/move_menu_indent_parameter.php');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Check if parameter exists in Menu section
    $checkStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE section = 'Menu' AND parameter_name = '--menu-section-header-indent-left'");
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $menuParam = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($menuParam) {
        // Check if parameter already exists in Indents section
        $checkIndentStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = 'Indents' AND parameter_name = '--menu-section-header-indent-left'");
        $checkIndentStmt->execute();
        $indentResult = $checkIndentStmt->get_result();
        $indentParam = $indentResult->fetch_assoc();
        $checkIndentStmt->close();
        
        if ($indentParam) {
            // Parameter already exists in Indents section, just delete the one from Menu
            $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteStmt->bind_param("i", $menuParam['id']);
            if ($deleteStmt->execute()) {
                $success = 'Removed duplicate parameter from Menu section. Parameter already exists in Indents section.';
            } else {
                $error = 'Failed to remove duplicate parameter: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        } else {
            // Move parameter from Menu to Indents section
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET section = 'Indents' WHERE id = ?");
            $updateStmt->bind_param("i", $menuParam['id']);
            if ($updateStmt->execute()) {
                $success = 'Successfully moved --menu-section-header-indent-left parameter from Menu section to Indents section.';
            } else {
                $error = 'Failed to move parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        // Parameter doesn't exist in Menu section, check if it exists in Indents
        $checkIndentStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = 'Indents' AND parameter_name = '--menu-section-header-indent-left'");
        $checkIndentStmt->execute();
        $indentResult = $checkIndentStmt->get_result();
        $indentParam = $indentResult->fetch_assoc();
        $checkIndentStmt->close();
        
        if ($indentParam) {
            $success = 'Parameter already exists in Indents section. No action needed.';
        } else {
            // Parameter doesn't exist at all, create it in Indents section
            if (upsertParameter('Indents', '--menu-section-header-indent-left', '25px', 'Menu section header left indent (aligns with menu items)')) {
                $success = 'Created --menu-section-header-indent-left parameter in Indents section.';
            } else {
                $error = 'Failed to create parameter in Indents section.';
            }
        }
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Move Menu Indent Parameter</h2>
        <p class="text-muted">Move the menu section header indent parameter from Menu to Indents section</p>
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
        <p>This script moves the <code>--indent-menu-section-header</code> parameter from the "Menu" section to the "Indents" section for better organization.</p>
        <p><strong>Note:</strong> This script is deprecated. Use <code>rename_menu_indent_parameters.php</code> instead.</p>
        <p>If the parameter doesn't exist, it will be created in the Indents section.</p>
        <p><strong>This script executes automatically when you load this page.</strong></p>
        <p><strong>Full URL to access this script:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">Copy this URL and paste it in your browser address bar, or click the link above to open in a new tab.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Indents')); ?>" class="btn btn-secondary btn-medium">View Indents Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

