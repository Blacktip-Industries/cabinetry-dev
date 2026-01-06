<?php
/**
 * Add Icon Favourite Parameters
 * Adds parameters for icon favourites system including button sizes, icons, and colors
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Icon Favourite Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_icon_favourite_parameters.php');

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
    
    $parameters = [
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-favourite-size',
            'value' => '24px',
            'description' => 'Size of the favourite icon button in the icon preview cards',
            'input_type' => 'text'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-favourite-color-inactive',
            'value' => '#CCCCCC',
            'description' => 'Color of the favourite icon when the icon is not a favourite',
            'input_type' => 'color'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-favourite-color-active',
            'value' => '#FF6C2F',
            'description' => 'Color of the favourite icon when the icon is a favourite',
            'input_type' => 'color'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-favourite-icon',
            'value' => '',
            'description' => 'Icon to use for the favourite button (select from icon picker)',
            'input_type' => 'text'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-edit-size',
            'value' => '24px',
            'description' => 'Size of the edit button icon in the icon preview cards',
            'input_type' => 'text'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-edit-icon',
            'value' => '',
            'description' => 'Icon to use for the edit button (select from icon picker)',
            'input_type' => 'text'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-delete-size',
            'value' => '24px',
            'description' => 'Size of the delete button icon in the icon preview cards',
            'input_type' => 'text'
        ],
        [
            'section' => 'Icons',
            'parameter_name' => '--icon-button-delete-icon',
            'value' => '',
            'description' => 'Icon to use for the delete button (select from icon picker)',
            'input_type' => 'text'
        ]
    ];
    
    foreach ($parameters as $param) {
        // Check if parameter already exists
        $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
        $checkStmt->bind_param("ss", $param['section'], $param['parameter_name']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existing = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($existing) {
            $actions[] = [
                'status' => 'skipped',
                'message' => "Parameter '{$param['parameter_name']}' already exists in section '{$param['section']}' with value: {$existing['value']}"
            ];
        } else {
            // Add the parameter
            $upsertResult = upsertParameter($param['section'], $param['parameter_name'], $param['value'], $param['description']);
            
            if ($upsertResult) {
                $actions[] = [
                    'status' => 'success',
                    'message' => "Successfully added parameter '{$param['parameter_name']}' to section '{$param['section']}' with value: {$param['value']}"
                ];
                
                // Configure input type if needed
                if ($param['input_type'] === 'color') {
                    $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
                    $paramStmt->bind_param("ss", $param['section'], $param['parameter_name']);
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
                                    'message' => "Configured parameter '{$param['parameter_name']}' to use color picker input"
                                ];
                            }
                        }
                    }
                }
            } elseif ($param['input_type'] === 'text' && strpos($param['parameter_name'], '-icon') !== false) {
                // Configure icon picker for icon parameters
                $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
                $paramStmt->bind_param("ss", $param['section'], $param['parameter_name']);
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
                        // Add icon picker config
                        $configResult = upsertParameterInputConfig($paramId, 'icon', null, null, null, null);
                        if ($configResult) {
                            $actions[] = [
                                'status' => 'success',
                                'message' => "Configured parameter '{$param['parameter_name']}' to use icon picker input"
                            ];
                        }
                    }
                }
            } elseif ($param['input_type'] === 'text' && strpos($param['parameter_name'], '-size') !== false) {
                // Explicitly configure size parameters as text inputs
                $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
                $paramStmt->bind_param("ss", $param['section'], $param['parameter_name']);
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
                        // Add text input config (explicitly set to text to prevent heuristic detection issues)
                        $configResult = upsertParameterInputConfig($paramId, 'text', null, null, null, null);
                        if ($configResult) {
                            $actions[] = [
                                'status' => 'success',
                                'message' => "Configured parameter '{$param['parameter_name']}' to use text input"
                            ];
                        }
                    }
                }
            } else {
                $actions[] = [
                    'status' => 'error',
                    'message' => "Failed to add parameter '{$param['parameter_name']}'"
                ];
            }
        }
    }
}

?>
<div style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div style="background: var(--bg-card, #ffffff); border-radius: var(--radius-default, 0.75rem); padding: 2rem; box-shadow: var(--shadow-default, 0px 3px 4px 0px rgba(0, 0, 0, 0.03));">
        <h1 style="margin-bottom: 1.5rem; color: var(--text-primary, #313b5e); font-size: 2rem; font-weight: 600;">
            Add Icon Favourite Parameters
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
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-primary, #313b5e);">Parameters Added</h3>
            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                <div><strong>Section:</strong> Icons</div>
                <div><strong>--icon-button-favourite-size:</strong> 24px (Size of the favourite icon button)</div>
                <div><strong>--icon-button-favourite-color-inactive:</strong> #CCCCCC (Color when not a favourite)</div>
                <div><strong>--icon-button-favourite-color-active:</strong> #FF6C2F (Color when a favourite)</div>
                <div><strong>--icon-button-favourite-icon:</strong> (Icon to use for favourite button)</div>
                <div><strong>--icon-button-edit-size:</strong> 24px (Size of the edit button icon)</div>
                <div><strong>--icon-button-edit-icon:</strong> (Icon to use for edit button)</div>
                <div><strong>--icon-button-delete-size:</strong> 24px (Size of the delete button icon)</div>
                <div><strong>--icon-button-delete-icon:</strong> (Icon to use for delete button)</div>
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

