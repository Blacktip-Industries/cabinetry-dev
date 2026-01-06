<?php
/**
 * Add Menu Setup Background Parameters
 * Adds parameters for menu setup page row background colors
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Menu Setup Background Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_menu_setup_bg_parameters.php');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Ensure tables exist
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    $parameters = [
        // Menu Setup Row Background Colors
        ['section' => 'Menu', 'key' => '--menu-setup-bg-section-header', 'value' => '#f5f5f5', 'description' => 'Menu setup page: background color for section heading rows'],
        ['section' => 'Menu', 'key' => '--menu-setup-bg-menu-parent', 'value' => '#ffffff', 'description' => 'Menu setup page: background color for parent menu rows (use transparent for no background)'],
        ['section' => 'Menu', 'key' => '--menu-setup-bg-menu-child', 'value' => '#ffffff', 'description' => 'Menu setup page: background color for child menu rows (use transparent for no background)'],
        ['section' => 'Menu', 'key' => '--menu-setup-bg-table-header', 'value' => '#f8f9fa', 'description' => 'Menu setup page: background color for table header row'],
    ];
    
    $added = 0;
    $skipped = 0;
    $colorPickersConfigured = 0;
    
    // Parameters that should use color pickers
    $colorPickerParams = ['--menu-setup-bg-menu-parent', '--menu-setup-bg-menu-child', '--menu-setup-bg-table-header', '--menu-setup-bg-section-header'];
    
    foreach ($parameters as $param) {
        // Check if parameter already exists and get its ID
        $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
        $checkStmt->bind_param("ss", $param['section'], $param['key']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existing = $result->fetch_assoc();
        $checkStmt->close();
        
        $parameterId = null;
        
        if (!$existing) {
            // Use upsertParameter function to add the parameter
            if (upsertParameter($param['section'], $param['key'], $param['value'], $param['description'])) {
                $added++;
                // Get the ID of the newly added parameter
                $getIdStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
                $getIdStmt->bind_param("ss", $param['section'], $param['key']);
                $getIdStmt->execute();
                $idResult = $getIdStmt->get_result();
                $newParam = $idResult->fetch_assoc();
                $getIdStmt->close();
                if ($newParam) {
                    $parameterId = $newParam['id'];
                }
            }
        } else {
            $skipped++;
            $parameterId = $existing['id'];
            $currentValue = $existing['value'];
            
            // If the value is "transparent", update it to white (#ffffff) so color picker works
            if (strtolower(trim($currentValue)) === 'transparent' && in_array($param['key'], $colorPickerParams)) {
                $updateStmt = $conn->prepare("UPDATE settings_parameters SET value = '#ffffff' WHERE id = ?");
                $updateStmt->bind_param("i", $parameterId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
        
        // Configure color picker for parameters that need it
        if ($parameterId && in_array($param['key'], $colorPickerParams)) {
            // Update or insert the input config to use color picker
            if (upsertParameterInputConfig($parameterId, 'color', null, null, null, null)) {
                $colorPickersConfigured++;
            }
        }
    }
    
    $messages = [];
    if ($added > 0) {
        $messages[] = "Successfully added $added menu setup background parameters.";
    }
    if ($skipped > 0) {
        $messages[] = "$skipped parameters already existed.";
    }
    if ($colorPickersConfigured > 0) {
        $messages[] = "Configured $colorPickersConfigured parameter(s) to use color picker.";
    }
    
    if (empty($messages)) {
        $success = "All menu setup background parameters already exist and are configured.";
    } else {
        $success = implode(' ', $messages);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Menu Setup Background Parameters</h2>
        <p class="text-muted">Add background color parameters for menu setup page rows</p>
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
        
        <p>This script adds parameters for customizing background colors of rows in the menu setup page.</p>
        <p>Parameters will be added under the "Menu" section in Settings/Parameters.</p>
        <p>The <code>--menu-setup-bg-menu-parent</code> and <code>--menu-setup-bg-menu-child</code> parameters will be configured to use color pickers.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Menu')); ?>" class="btn btn-secondary btn-medium">View Menu Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

