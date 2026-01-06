<?php
/**
 * Rename Indent Parameters
 * Renames --indent-description to --indent-parameter-helper-text
 * and --indent-range-info to --indent-parameter-range-info
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Rename Indent Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/rename_indent_parameters.php');

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
    
    // Step 1: Rename --indent-description to --indent-parameter-helper-text
    $checkOldDescStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE parameter_name = '--indent-description'");
    $checkOldDescStmt->execute();
    $result = $checkOldDescStmt->get_result();
    $oldDescParam = $result->fetch_assoc();
    $checkOldDescStmt->close();
    
    if ($oldDescParam) {
        // Check if new parameter already exists
        $checkNewDescStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--indent-parameter-helper-text'");
        $checkNewDescStmt->execute();
        $newDescResult = $checkNewDescStmt->get_result();
        $newDescParam = $newDescResult->fetch_assoc();
        $checkNewDescStmt->close();
        
        if ($newDescParam) {
            // New parameter exists, delete old one
            $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteStmt->bind_param("i", $oldDescParam['id']);
            if ($deleteStmt->execute()) {
                $actions[] = 'Removed old parameter --indent-description (new parameter already exists)';
            } else {
                $error = 'Failed to remove old parameter: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        } else {
            // Rename the parameter
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET parameter_name = '--indent-parameter-helper-text' WHERE id = ?");
            $updateStmt->bind_param("i", $oldDescParam['id']);
            if ($updateStmt->execute()) {
                $actions[] = 'Renamed --indent-description to --indent-parameter-helper-text';
            } else {
                $error = 'Failed to rename parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        $actions[] = 'Old parameter --indent-description not found (may have been renamed already)';
    }
    
    // Step 2: Rename --indent-range-info to --indent-parameter-range-info
    $checkOldRangeStmt = $conn->prepare("SELECT id, section, parameter_name, value, description FROM settings_parameters WHERE parameter_name = '--indent-range-info'");
    $checkOldRangeStmt->execute();
    $result = $checkOldRangeStmt->get_result();
    $oldRangeParam = $result->fetch_assoc();
    $checkOldRangeStmt->close();
    
    if ($oldRangeParam) {
        // Check if new parameter already exists
        $checkNewRangeStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--indent-parameter-range-info'");
        $checkNewRangeStmt->execute();
        $newRangeResult = $checkNewRangeStmt->get_result();
        $newRangeParam = $newRangeResult->fetch_assoc();
        $checkNewRangeStmt->close();
        
        if ($newRangeParam) {
            // New parameter exists, delete old one
            $deleteStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteStmt->bind_param("i", $oldRangeParam['id']);
            if ($deleteStmt->execute()) {
                $actions[] = 'Removed old parameter --indent-range-info (new parameter already exists)';
            } else {
                $error = 'Failed to remove old parameter: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        } else {
            // Rename the parameter
            $updateStmt = $conn->prepare("UPDATE settings_parameters SET parameter_name = '--indent-parameter-range-info' WHERE id = ?");
            $updateStmt->bind_param("i", $oldRangeParam['id']);
            if ($updateStmt->execute()) {
                $actions[] = 'Renamed --indent-range-info to --indent-parameter-range-info';
            } else {
                $error = 'Failed to rename parameter: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        $actions[] = 'Old parameter --indent-range-info not found (may have been renamed already)';
    }
    
    if (empty($error) && !empty($actions)) {
        $success = implode('<br>', $actions);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Rename Indent Parameters</h2>
        <p class="text-muted">Rename indent parameters to match new naming convention</p>
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
            <li>Renames <code>--indent-description</code> to <code>--indent-parameter-helper-text</code></li>
            <li>Renames <code>--indent-range-info</code> to <code>--indent-parameter-range-info</code></li>
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

