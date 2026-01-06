<?php
/**
 * Add Icon Page Count Parameter
 * Creates the --icon-page-count parameter for configuring icons per page
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Icon Page Count Parameter');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Check if parameter already exists
    $checkStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = '--icon-page-count'");
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        $success = 'Parameter --icon-page-count already exists';
    } else {
        // Create the parameter
        $section = 'Icons';
        $parameterName = '--icon-page-count';
        $description = 'Number of icons to display per page in the icon library';
        $defaultValue = '20';
        
        if (upsertParameter($section, $parameterName, $defaultValue, $description)) {
            // Get the parameter ID
            $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE parameter_name = ?");
            $paramStmt->bind_param("s", $parameterName);
            $paramStmt->execute();
            $paramResult = $paramStmt->get_result();
            $paramRow = $paramResult->fetch_assoc();
            $paramStmt->close();
            
            if ($paramRow) {
                $parameterId = $paramRow['id'];
                
                // Create dropdown config with options
                $inputType = 'dropdown';
                $options = ['10', '20', '30', '40', '50'];
                $optionsJson = json_encode(['options' => $options]);
                $helpText = 'Select how many icons to display per page. Options can be customized in the parameters settings.';
                
                if (upsertParameterInputConfig($parameterId, $inputType, $optionsJson, null, $helpText)) {
                    $success = 'Parameter --icon-page-count created successfully with dropdown options: ' . implode(', ', $options);
                } else {
                    $error = 'Parameter created but failed to add input configuration';
                }
            } else {
                $error = 'Parameter created but could not retrieve ID';
            }
        } else {
            $error = 'Failed to create parameter';
        }
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Icon Page Count Parameter</h2>
        <p class="text-muted">Creates the --icon-page-count parameter for configuring icons per page</p>
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
        <p>This script creates the <code>--icon-page-count</code> parameter in the Icons section.</p>
        <p>The parameter allows you to configure how many icons are displayed per page in the icon library.</p>
        <p><strong>Default options:</strong> 10, 20, 30, 40, 50</p>
        <p>You can customize these options in the <a href="../settings/parameters.php?section=Icons">Parameters settings page</a>.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="../settings/parameters.php?section=Icons" class="btn btn-primary btn-medium">View Icons Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

