<?php
/**
 * Remove Category Icon Parameters
 * Removes the unused --icon-category-* parameters from the database
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Remove Category Icon Parameters');

$scriptUrl = getAdminUrl('setup/remove_category_icon_parameters.php');

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Parameters to delete
    $parametersToDelete = [
        '--icon-category-default-icon',
        '--icon-category-default-size',
        '--icon-category-favourites-icon',
        '--icon-category-favourites-size'
    ];
    
    $section = 'Icons';
    
    foreach ($parametersToDelete as $parameterName) {
        // First, get the parameter ID to delete associated configs
        $checkStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
        $checkStmt->bind_param("ss", $section, $parameterName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $paramRow = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($paramRow) {
            $paramId = $paramRow['id'];
            
            // Delete associated configs first
            $deleteConfigStmt = $conn->prepare("DELETE FROM settings_parameters_configs WHERE parameter_id = ?");
            $deleteConfigStmt->bind_param("i", $paramId);
            if ($deleteConfigStmt->execute()) {
                $actions[] = [
                    'status' => 'success',
                    'message' => "Deleted config for parameter '{$parameterName}'"
                ];
            }
            $deleteConfigStmt->close();
            
            // Delete the parameter
            $deleteParamStmt = $conn->prepare("DELETE FROM settings_parameters WHERE id = ?");
            $deleteParamStmt->bind_param("i", $paramId);
            if ($deleteParamStmt->execute()) {
                $actions[] = [
                    'status' => 'success',
                    'message' => "Deleted parameter '{$parameterName}' from section '{$section}'"
                ];
            } else {
                $actions[] = [
                    'status' => 'error',
                    'message' => "Failed to delete parameter '{$parameterName}': " . $deleteParamStmt->error
                ];
            }
            $deleteParamStmt->close();
        } else {
            $actions[] = [
                'status' => 'skipped',
                'message' => "Parameter '{$parameterName}' not found in section '{$section}' (may have already been deleted)"
            ];
        }
    }
    
    // Check if any parameters were successfully deleted
    $successCount = count(array_filter($actions, function($action) {
        return $action['status'] === 'success' && strpos($action['message'], 'Deleted parameter') !== false;
    }));
    
    if ($successCount > 0) {
        $success = "Successfully removed {$successCount} category icon parameter(s)";
    }
}
?>
<div style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div style="background: var(--bg-card, #ffffff); border-radius: var(--radius-default, 0.75rem); padding: 2rem; box-shadow: var(--shadow-default, 0px 3px 4px 0px rgba(0, 0, 0, 0.03));">
        <h1 style="margin-bottom: 1.5rem; color: var(--text-primary, #313b5e); font-size: 2rem; font-weight: 600;">
            Remove Category Icon Parameters
        </h1>
        
        <?php if ($error): ?>
            <div style="background: var(--color-danger-subtle, #fcdfdf); border: 1px solid var(--color-danger, #ef5f5f); border-radius: var(--radius-sm, 0.5rem); padding: 1rem; margin-bottom: 1.5rem; color: var(--color-danger-text-emphasis, #602626);">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="background: var(--color-success-subtle, #d3f3df); border: 1px solid var(--color-success, #22c55e); border-radius: var(--radius-sm, 0.5rem); padding: 1rem; margin-bottom: 1.5rem; color: var(--color-success-text-emphasis, #0e4f26);">
                <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
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
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-primary, #313b5e);">Parameters to Remove</h3>
            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                <div><strong>--icon-category-default-icon</strong></div>
                <div><strong>--icon-category-default-size</strong></div>
                <div><strong>--icon-category-favourites-icon</strong></div>
                <div><strong>--icon-category-favourites-size</strong></div>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-default, #eaedf1);">
            <p style="margin-bottom: 1rem; color: var(--text-secondary, #6b7280);">
                <strong>Script URL:</strong> <a href="<?php echo $scriptUrl; ?>" target="_blank" style="color: var(--color-primary, #3b82f6); text-decoration: underline;"><?php echo htmlspecialchars($scriptUrl); ?></a>
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="<?php echo getAdminUrl('settings/parameters.php'); ?>" class="btn btn-primary btn-medium">Go to Parameters Page</a>
                <a href="<?php echo getAdminUrl('setup/icons.php'); ?>" class="btn btn-secondary btn-medium">Back to Icons</a>
            </div>
        </div>
    </div>
</div>

<?php
endLayout();
?>

