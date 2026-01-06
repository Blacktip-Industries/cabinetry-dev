<?php
/**
 * Run Setup Scripts and Cleanup
 * Executes specified setup scripts and optionally deletes them afterwards
 * 
 * Usage (CLI): php run_and_cleanup.php
 * Usage (Web): Navigate to /admin/setup/run_and_cleanup.php
 */

// Determine if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    require_once __DIR__ . '/../includes/layout.php';
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../../config/database.php';
    startLayout('Run Setup Scripts and Cleanup');
}

require_once __DIR__ . '/../../config/database.php';

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];
$deletedFiles = [];

// List of setup scripts to run (in order)
$setupScripts = [
    'add_timezones_table.php',
    'add_system_timezone_parameters.php',
    'update_timezone_parameter_dropdown.php'
];

// Optional: Scripts to delete after running (set to true to enable deletion)
$deleteAfterRun = true;

// Optional: Delete this cleanup script itself after running
$deleteSelf = true;

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    foreach ($setupScripts as $script) {
        $scriptPath = __DIR__ . '/' . $script;
        
        if (!file_exists($scriptPath)) {
            $actions[] = [
                'status' => 'warning',
                'message' => "Script not found: {$script} (skipped)"
            ];
            continue;
        }
        
        $actions[] = [
            'status' => 'info',
            'message' => "Running: {$script}..."
        ];
        
        // Capture output
        ob_start();
        
        try {
            // Include the script
            include $scriptPath;
            
            $output = ob_get_clean();
            
            $actions[] = [
                'status' => 'success',
                'message' => "Completed: {$script}"
            ];
            
            // Delete the script if requested
            if ($deleteAfterRun) {
                if (unlink($scriptPath)) {
                    $deletedFiles[] = $script;
                    $actions[] = [
                        'status' => 'info',
                        'message' => "Deleted: {$script}"
                    ];
                } else {
                    $actions[] = [
                        'status' => 'warning',
                        'message' => "Could not delete: {$script} (file may be locked or permissions issue)"
                    ];
                }
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            $actions[] = [
                'status' => 'error',
                'message' => "Error running {$script}: " . $e->getMessage()
            ];
            $error = "Failed to run {$script}";
        }
    }
    
    if (empty($error) && !empty($deletedFiles)) {
        $success = "Successfully ran " . count($setupScripts) . " setup script(s) and deleted " . count($deletedFiles) . " file(s).";
    } elseif (empty($error)) {
        $success = "Successfully ran " . count($setupScripts) . " setup script(s).";
    }
}

if ($isCLI) {
    // CLI output
    echo "\n=== Setup Scripts Runner ===\n\n";
    
    if ($error) {
        echo "ERROR: {$error}\n\n";
    }
    
    if ($success) {
        echo "SUCCESS: {$success}\n\n";
    }
    
    echo "Actions Performed:\n";
    foreach ($actions as $action) {
        $status = $action['status'];
        $icon = '•';
        if ($status === 'success') {
            $icon = '✓';
        } elseif ($status === 'error') {
            $icon = '✗';
        } elseif ($status === 'warning') {
            $icon = '⚠';
        }
        echo "  {$icon} {$action['message']}\n";
    }
    
    if (!empty($deletedFiles)) {
        echo "\nDeleted Files:\n";
        foreach ($deletedFiles as $file) {
            echo "  - {$file}\n";
        }
    }
    
    // Delete self if requested
    if ($deleteSelf) {
        $selfPath = __FILE__;
        echo "\nDeleting cleanup script itself...\n";
        if (unlink($selfPath)) {
            echo "✓ Cleanup script deleted.\n";
        } else {
            echo "⚠ Could not delete cleanup script (may need manual deletion).\n";
        }
    }
    
    echo "\n";
    
} else {
    // Web output
    ?>
    
    <div class="page-header">
        <h2>Run Setup Scripts and Cleanup</h2>
        <p>Executes setup scripts and optionally deletes them afterwards.</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error" style="background: var(--bg-error, #fee2e2); color: var(--text-error, #991b1b); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success" style="background: var(--bg-success, #d1fae5); color: var(--text-success, #065f46); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($actions)): ?>
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-body">
                <h3 style="margin-top: 0;">Actions Performed</h3>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($actions as $action): ?>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-default, #e5e7eb);">
                            <?php 
                            $status = $action['status'];
                            $icon = '•';
                            $color = 'var(--text-secondary, #6b7280)';
                            
                            if ($status === 'success') {
                                $icon = '✓';
                                $color = 'var(--text-success, #065f46)';
                            } elseif ($status === 'error') {
                                $icon = '✗';
                                $color = 'var(--text-error, #991b1b)';
                            } elseif ($status === 'warning') {
                                $icon = '⚠';
                                $color = 'var(--text-warning, #92400e)';
                            } elseif ($status === 'info') {
                                $icon = '•';
                                $color = 'var(--text-secondary, #6b7280)';
                            }
                            ?>
                            <span style="color: <?php echo $color; ?>;"><?php echo $icon; ?></span>
                            <span style="margin-left: 0.5rem;"><?php echo htmlspecialchars($action['message']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($deletedFiles)): ?>
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-body">
                <h3 style="margin-top: 0;">Deleted Files</h3>
                <ul>
                    <?php foreach ($deletedFiles as $file): ?>
                        <li><?php echo htmlspecialchars($file); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <h3>Scripts Run</h3>
            <ul>
                <?php foreach ($setupScripts as $script): ?>
                    <li><?php echo htmlspecialchars($script); ?></li>
                <?php endforeach; ?>
            </ul>
            
            <p style="margin-top: 1rem;"><strong>Note:</strong> Setup scripts are designed to be run once. After successful execution, they are automatically deleted.</p>
        </div>
    </div>
    
    <?php
    endLayout();
    
    // Delete self if requested (after page output)
    if ($deleteSelf) {
        // Use JavaScript to delete after page load, or schedule deletion
        // Note: This won't work in web context due to file locking
        // Better to delete manually or use a background process
        register_shutdown_function(function() use ($deleteSelf) {
            $selfPath = __FILE__;
            // Try to delete after a short delay
            if (function_exists('sleep')) {
                sleep(1);
            }
            @unlink($selfPath);
        });
    }
}

