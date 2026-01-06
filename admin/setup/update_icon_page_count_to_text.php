<?php
/**
 * Update Icon Page Count Parameter to Text Input
 * Changes the --icon-page-count parameter from dropdown to text input
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Update Icon Page Count Parameter');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Get the parameter ID
    $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--icon-page-count'");
    $paramStmt->execute();
    $paramResult = $paramStmt->get_result();
    $paramRow = $paramResult->fetch_assoc();
    $paramStmt->close();
    
    if (!$paramRow) {
        $error = 'Parameter --icon-page-count not found. Please run add_icon_page_count_parameter.php first.';
    } else {
        $parameterId = $paramRow['id'];
        
        // Update to text input type (remove dropdown config)
        $inputType = 'text';
        $optionsJson = null; // Remove dropdown options
        $helpText = 'Enter the number of icons to display per page (e.g., 20).';
        
        if (upsertParameterInputConfig($parameterId, $inputType, $optionsJson, null, $helpText)) {
            $success = 'Parameter --icon-page-count updated successfully to text input. You can now edit it as a simple text value in the Parameters page.';
        } else {
            $error = 'Failed to update parameter input configuration';
        }
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Update Icon Page Count Parameter</h2>
        <p class="text-muted">Changes --icon-page-count from dropdown to text input</p>
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
        <p>This script updates the <code>--icon-page-count</code> parameter to be a simple text input instead of a dropdown.</p>
        <p>After running this, you can edit the value directly in the <a href="../settings/parameters.php?section=Icons">Parameters settings page</a>.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="../settings/parameters.php?section=Icons" class="btn btn-primary btn-medium">View Icons Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

