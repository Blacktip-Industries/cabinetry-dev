<?php
/**
 * Add Icon Delete Preview Size Parameter
 * Adds parameter for icon preview size in the Delete Icons modal
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Icon Delete Preview Size Parameter');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_icon_delete_preview_size_parameter.php');

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
    
    $parameter = [
        'section' => 'Icons',
        'parameter_name' => '--icon-delete-preview-size',
        'value' => '32px',
        'description' => 'Size of the icon preview in the Delete Icons modal',
        'input_type' => 'text'
    ];
    
    // Check if parameter already exists
    $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
    $checkStmt->bind_param("ss", $parameter['section'], $parameter['parameter_name']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        $actions[] = [
            'status' => 'skipped',
            'message' => "Parameter '{$parameter['parameter_name']}' already exists in section '{$parameter['section']}' with value: {$existing['value']}"
        ];
    } else {
        // Add the parameter
        $upsertResult = upsertParameter($parameter['section'], $parameter['parameter_name'], $parameter['value'], $parameter['description']);
        
        if ($upsertResult) {
            $actions[] = [
                'status' => 'success',
                'message' => "Successfully added parameter '{$parameter['parameter_name']}' to section '{$parameter['section']}' with value: {$parameter['value']}"
            ];
            
            // Configure as text input
            $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
            $paramStmt->bind_param("ss", $parameter['section'], $parameter['parameter_name']);
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
                    // Add text input config
                    $configResult = upsertParameterInputConfig($paramId, 'text', null, null, null, null);
                    if ($configResult) {
                        $actions[] = [
                            'status' => 'success',
                            'message' => "Configured parameter '{$parameter['parameter_name']}' to use text input"
                        ];
                    }
                }
            }
        } else {
            $actions[] = [
                'status' => 'error',
                'message' => "Failed to add parameter '{$parameter['parameter_name']}'"
            ];
        }
    }
}

?>
<div style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div style="background: var(--bg-card, #ffffff); border-radius: var(--radius-default, 0.75rem); padding: 2rem; box-shadow: var(--shadow-default, 0px 3px 4px 0px rgba(0, 0, 0, 0.03));">
        <h1 style="margin-bottom: 1.5rem; color: var(--text-primary, #313b5e); font-size: 2rem; font-weight: 600;">
            Add Icon Delete Preview Size Parameter
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
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-primary, #313b5e);">Parameter Added</h3>
            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                <div><strong>Section:</strong> Icons</div>
                <div><strong>--icon-delete-preview-size:</strong> 32px (Size of the icon preview in the Delete Icons modal)</div>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-default, #eaedf1);">
            <p style="margin-bottom: 1rem;">
                <strong>Migration Script URL:</strong><br>
                <a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" style="color: var(--color-primary, #ff6c2f); text-decoration: underline; word-break: break-all;">
                    <?php echo htmlspecialchars($scriptUrl); ?>
                </a>
            </p>
            <a href="<?php echo getAdminUrl('settings/parameters.php'); ?>" class="btn btn-primary btn-medium">Go to Parameters Page</a>
        </div>
    </div>
</div>

<?php
endLayout();
?>

