<?php
/**
 * Remove Auto-Generation Parameters
 * Removes the unused --icon-auto-generate-* parameters from the database
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Remove Auto-Generation Parameters');

$scriptUrl = getAdminUrl('setup/remove_auto_generate_parameters.php');

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Parameters to delete
    $parametersToDelete = [
        '--icon-auto-generate-menu',
        '--icon-auto-generate-frontend',
        '--icon-auto-generate-page'
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
        $success = "Successfully removed {$successCount} auto-generation parameter(s)";
    }
}
?>

<div class="admin-container">
    <div class="admin-content">
        <h1>Remove Auto-Generation Parameters</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($actions)): ?>
            <div class="actions-list" style="margin-top: 2rem;">
                <h2>Actions Performed:</h2>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($actions as $action): ?>
                        <li style="padding: 0.5rem; margin-bottom: 0.5rem; border-left: 3px solid <?php 
                            echo $action['status'] === 'success' ? '#10b981' : 
                                ($action['status'] === 'error' ? '#ef4444' : '#6b7280'); 
                        ?>; padding-left: 1rem;">
                            <strong><?php echo ucfirst($action['status']); ?>:</strong> 
                            <?php echo htmlspecialchars($action['message']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem;">
            <a href="<?php echo getAdminUrl('settings/parameters.php?section=Icons'); ?>" class="btn btn-primary">View Icons Parameters</a>
            <a href="<?php echo getAdminUrl('setup/icons.php'); ?>" class="btn btn-secondary">Back to Icons</a>
        </div>
    </div>
</div>

<?php endLayout(); ?>

