<?php
/**
 * Add Script Retention Parameters
 * Adds retention day parameters for each script type
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Script Retention Parameters');

$scriptUrl = getAdminUrl('setup/add_script_retention_parameters.php');

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Define retention parameters
    $retentionParameters = [
        [
            'parameter_name' => '--setup-script-retention-days-global',
            'default_value' => '30',
            'description' => 'Default number of days to keep archived setup scripts before auto-deletion (used when script-specific or type-specific retention is not set)'
        ],
        [
            'parameter_name' => '--setup-script-retention-days-setup',
            'default_value' => '30',
            'description' => 'Retention period in days for setup scripts before auto-deletion'
        ],
        [
            'parameter_name' => '--setup-script-retention-days-migration',
            'default_value' => '90',
            'description' => 'Retention period in days for migration scripts before auto-deletion'
        ],
        [
            'parameter_name' => '--setup-script-retention-days-cleanup',
            'default_value' => '7',
            'description' => 'Retention period in days for cleanup scripts before auto-deletion'
        ],
        [
            'parameter_name' => '--setup-script-retention-days-data_import',
            'default_value' => '60',
            'description' => 'Retention period in days for data import scripts before auto-deletion'
        ],
        [
            'parameter_name' => '--setup-script-retention-days-parameter',
            'default_value' => '30',
            'description' => 'Retention period in days for parameter creation scripts before auto-deletion'
        ]
    ];
    
    $section = 'Setup';
    
    foreach ($retentionParameters as $param) {
        $parameterName = $param['parameter_name'];
        $defaultValue = $param['default_value'];
        $description = $param['description'];
        
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
                
                // Configure as number input
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
                        // Add number input config
                        $configResult = upsertParameterInputConfig($paramId, 'number', null, null, null, null);
                        if ($configResult) {
                            $actions[] = [
                                'status' => 'success',
                                'message' => "Configured parameter '{$parameterName}' to use number input"
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
}
?>

<div class="admin-container">
    <div class="admin-content">
        <h1>Add Script Retention Parameters</h1>
        
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
            <a href="<?php echo getAdminUrl('settings/parameters.php?section=Setup'); ?>" class="btn btn-primary">View Setup Parameters</a>
            <a href="<?php echo getAdminUrl('scripts/archive.php'); ?>" class="btn btn-secondary">View Script Archive</a>
        </div>
    </div>
</div>

<?php endLayout(); ?>

