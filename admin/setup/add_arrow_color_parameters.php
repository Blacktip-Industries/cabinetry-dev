<?php
/**
 * Add Arrow Color Parameters
 * Adds parameters for arrow button colors (UP/DOWN arrows in menu setup)
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Arrow Color Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_arrow_color_parameters.php');

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
        // Arrow Button Colors
        ['section' => 'Arrows', 'key' => '--arrow-bg-color-up', 'value' => '#ffffff', 'description' => 'Background color for UP arrow button'],
        ['section' => 'Arrows', 'key' => '--arrow-bg-color-down', 'value' => '#ffffff', 'description' => 'Background color for DOWN arrow button'],
    ];
    
    $added = 0;
    $skipped = 0;
    $colorPickersConfigured = 0;
    
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
        }
        
        // Configure color picker for all arrow parameters
        if ($parameterId) {
            // Update or insert the input config to use color picker
            if (upsertParameterInputConfig($parameterId, 'color', null, null, null, null)) {
                $colorPickersConfigured++;
            }
        }
    }
    
    $messages = [];
    if ($added > 0) {
        $messages[] = "Successfully added $added arrow color parameters.";
    }
    if ($skipped > 0) {
        $messages[] = "$skipped parameters already existed.";
    }
    if ($colorPickersConfigured > 0) {
        $messages[] = "Configured $colorPickersConfigured parameter(s) to use color picker.";
    }
    
    if (empty($messages)) {
        $success = "All arrow color parameters already exist and are configured.";
    } else {
        $success = implode(' ', $messages);
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Arrow Color Parameters</h2>
        <p class="text-muted">Add color parameters for arrow buttons (UP/DOWN arrows)</p>
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
        
        <p>This script adds parameters for customizing colors of arrow buttons (UP/DOWN arrows) in the menu setup page.</p>
        <p>Parameters will be added under the "Arrows" section in Settings/Parameters.</p>
        <p>All parameters will be configured to use color pickers.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Arrows')); ?>" class="btn btn-secondary btn-medium">View Arrow Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

