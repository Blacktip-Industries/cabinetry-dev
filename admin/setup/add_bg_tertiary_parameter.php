<?php
/**
 * Add Background Tertiary Parameter
 * Adds the --bg-tertiary parameter for icon name badge background color
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Background Tertiary Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_bg_tertiary_parameter.php');

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
    
    // Parameter to add
    $section = 'Backgrounds';
    $parameterName = '--bg-tertiary';
    $defaultValue = '#E8E9EB'; // Slightly darker than #f3f4f6
    $description = 'Background color for tertiary elements (e.g., icon name badges on icons page)';
    
    // Check if parameter already exists
    $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
    $checkStmt->bind_param("ss", $section, $parameterName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        $actions[] = [
            'status' => 'skipped',
            'message' => "Parameter '{$parameterName}' already exists in section '{$section}' with value: {$existing['value']}"
        ];
    } else {
        // Add the parameter
        $upsertResult = upsertParameter($section, $parameterName, $defaultValue, $description);
        
        if ($upsertResult) {
            $actions[] = [
                'status' => 'success',
                'message' => "Successfully added parameter '{$parameterName}' to section '{$section}' with value: {$defaultValue}"
            ];
            
            // Configure as color picker
            $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
            $paramStmt->bind_param("ss", $section, $parameterName);
            $paramStmt->execute();
            $paramResult = $paramStmt->get_result();
            $paramRow = $paramResult->fetch_assoc();
            $paramStmt->close();
            
            if ($paramRow) {
                $paramId = $paramRow['id'];
                
                // Check if config already exists
                $configCheckStmt = $conn->prepare("SELECT id FROM settings_parameters_configs WHERE parameter_id = ?");
                $configCheckStmt->bind_param("i", $paramId);
                $configCheckStmt->execute();
                $configResult = $configCheckStmt->get_result();
                $configExists = $configResult->fetch_assoc();
                $configCheckStmt->close();
                
                if (!$configExists) {
                    // Add color picker config
                    $configResult = upsertParameterInputConfig($paramId, 'color', null, null, null, null);
                    if ($configResult) {
                        $actions[] = [
                            'status' => 'success',
                            'message' => "Configured parameter '{$parameterName}' to use color picker input"
                        ];
                    }
                }
            }
        } else {
            $actions[] = [
                'status' => 'error',
                'message' => "Failed to add parameter '{$parameterName}'"
            ];
        }
    }
}

?>
<div style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div style="background: var(--bg-card, #ffffff); border-radius: var(--radius-default, 0.75rem); padding: 2rem; box-shadow: var(--shadow-default, 0px 3px 4px 0px rgba(0, 0, 0, 0.03));">
        <h1 style="margin-bottom: 1.5rem; color: var(--text-primary, #313b5e); font-size: 2rem; font-weight: 600;">
            Add Background Tertiary Parameter
        </h1>
        
        <?php if ($error): ?>
            <div style="background: var(--color-danger-subtle, #fcdfdf); border: 1px solid var(--color-danger, #ef5f5f); border-radius: var(--radius-sm, 0.5rem); padding: 1rem; margin-bottom: 1.5rem; color: var(--color-danger-text-emphasis, #602626);">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($actions)): ?>
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary, #313b5e);">Actions Performed</h2>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($actions as $action): ?>
                        <div style="
                            padding: 0.75rem 1rem;
                            border-radius: var(--radius-sm, 0.5rem);
                            background: <?php echo $action['status'] === 'success' ? 'var(--color-success-subtle, #d3f3df)' : ($action['status'] === 'error' ? 'var(--color-danger-subtle, #fcdfdf)' : 'var(--bg-tertiary, #f3f4f6)'); ?>;
                            border: 1px solid <?php echo $action['status'] === 'success' ? 'var(--color-success, #22c55e)' : ($action['status'] === 'error' ? 'var(--color-danger, #ef5f5f)' : 'var(--border-default, #eaedf1)'); ?>;
                            color: <?php echo $action['status'] === 'success' ? 'var(--color-success-text-emphasis, #0e4f26)' : ($action['status'] === 'error' ? 'var(--color-danger-text-emphasis, #602626)' : 'var(--text-primary, #313b5e)'); ?>;
                        ">
                            <strong><?php echo ucfirst($action['status']); ?>:</strong> <?php echo htmlspecialchars($action['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="background: var(--bg-secondary, #f8f9fa); border-radius: var(--radius-sm, 0.5rem); padding: 1.5rem; margin-top: 2rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-primary, #313b5e);">Parameter Details</h3>
            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                <div><strong>Section:</strong> Backgrounds</div>
                <div><strong>Parameter Name:</strong> --bg-tertiary</div>
                <div><strong>Default Value:</strong> #E8E9EB (slightly darker than previous #f3f4f6)</div>
                <div><strong>Description:</strong> Background color for tertiary elements (e.g., icon name badges on icons page)</div>
                <div><strong>Input Type:</strong> Color Picker</div>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-default, #eaedf1);">
            <a href="<?php echo getAdminUrl('settings/parameters.php'); ?>" class="btn btn-primary btn-medium">Go to Parameters Page</a>
        </div>
    </div>
</div>

<?php
endLayout();
?>

