<?php
/**
 * Example Script Using Helper System
 * This is an example of how to use the new script lifecycle management system
 * 
 * Features demonstrated:
 * - Script registration and tracking
 * - Step tracking
 * - Template rendering
 * - Automatic completion and archiving
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/setup_script_helper.php';

startLayout('Example Script with Helper');

$scriptPath = __DIR__ . '/example_script_with_helper.php';
$scriptType = 'setup'; // Can be: setup, migration, cleanup, data_import, parameter
$oneTimeOnly = false; // Set to true if script should only run once
$canRerun = true; // Set to false if script cannot be rerun
$retentionDays = 30; // Days to keep in archive (null uses type-specific or global default)

// Register script (will update if already exists)
$scriptData = registerSetupScript($scriptPath, $scriptType, $oneTimeOnly, $canRerun, $retentionDays);

if (!$scriptData) {
    echo '<div class="alert alert-error">Failed to register script</div>';
    endLayout();
    exit;
}

// Track script execution
$actions = [];
$error = '';
$success = '';

// Execute script logic
try {
    trackScriptStep('Initialize', 'info', 'Starting script execution');
    
    $conn = getDBConnection();
    if ($conn === null) {
        throw new Exception('Database connection failed');
    }
    
    trackScriptStep('Database Connection', 'success', 'Database connection established');
    
    // Example: Create a table (idempotent)
    $createTableSQL = "CREATE TABLE IF NOT EXISTS example_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($createTableSQL)) {
        trackScriptStep('Create Table', 'success', 'Table example_table created or already exists');
        $actions[] = [
            'status' => 'success',
            'message' => 'Table example_table created successfully'
        ];
    } else {
        throw new Exception('Failed to create table: ' . $conn->error);
    }
    
    // Example: Insert sample data (idempotent)
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM example_table WHERE name = ?");
    $sampleName = 'example_entry';
    $checkStmt->bind_param("s", $sampleName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($row['count'] == 0) {
        $insertStmt = $conn->prepare("INSERT INTO example_table (name, value) VALUES (?, ?)");
        $sampleValue = 'This is a sample entry';
        $insertStmt->bind_param("ss", $sampleName, $sampleValue);
        
        if ($insertStmt->execute()) {
            trackScriptStep('Insert Data', 'success', 'Sample data inserted');
            $actions[] = [
                'status' => 'success',
                'message' => 'Sample data inserted successfully'
            ];
        } else {
            throw new Exception('Failed to insert data: ' . $insertStmt->error);
        }
        $insertStmt->close();
    } else {
        trackScriptStep('Insert Data', 'skipped', 'Sample data already exists');
        $actions[] = [
            'status' => 'skipped',
            'message' => 'Sample data already exists, skipped insertion'
        ];
    }
    
    trackScriptStep('Complete', 'success', 'Script execution completed successfully');
    $success = 'Script executed successfully';
    
    // Mark script as completed (this will also archive it)
    markScriptCompleted($scriptPath, $GLOBALS['script_steps'], [
        'actions_count' => count($actions),
        'success_count' => count(array_filter($actions, fn($a) => $a['status'] === 'success')),
        'skipped_count' => count(array_filter($actions, fn($a) => $a['status'] === 'skipped'))
    ]);
    
} catch (Exception $e) {
    trackScriptStep('Error', 'error', $e->getMessage());
    $error = 'Script execution failed: ' . $e->getMessage();
}

// Get template for rendering (optional - can use default)
$templateType = $scriptType; // Use type-specific template, or 'default' for default template
$template = getScriptTemplate($templateType);

// Prepare data for template
$templateData = [
    'title' => 'Example Script with Helper',
    'description' => 'This script demonstrates how to use the new script lifecycle management system.',
    'steps' => $GLOBALS['script_steps'],
    'results' => [
        'Total Actions' => count($actions),
        'Successful' => count(array_filter($actions, fn($a) => $a['status'] === 'success')),
        'Skipped' => count(array_filter($actions, fn($a) => $a['status'] === 'skipped')),
        'Errors' => count(array_filter($actions, fn($a) => $a['status'] === 'error'))
    ]
];

// Render using template
$renderedOutput = renderScriptTemplate($template, $templateData);
?>

<div class="admin-container">
    <div class="admin-content">
        <h1>Example Script with Helper</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Template-rendered output -->
        <?php echo $renderedOutput; ?>
        
        <!-- Traditional actions list (optional - template may include this) -->
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
            <a href="<?php echo getAdminUrl('scripts/archive.php'); ?>" class="btn btn-primary">View Script Archive</a>
            <a href="<?php echo getAdminUrl('scripts/settings.php'); ?>" class="btn btn-secondary">View Script Settings</a>
        </div>
    </div>
</div>

<?php endLayout(); ?>

