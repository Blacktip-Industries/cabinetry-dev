<?php
/**
 * Run Setup Scripts and Cleanup (CLI)
 * Executes setup scripts and deletes them afterwards
 * 
 * Usage: php run_setup_scripts.php
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];
$deletedFiles = [];

// List of setup scripts to run (in order)
$setupScripts = [
    'admin/setup/add_timezones_table.php',
    'admin/setup/add_system_timezone_parameters.php',
    'admin/setup/update_timezone_parameter_dropdown.php'
];

// Delete scripts after running
$deleteAfterRun = true;

if ($conn === null) {
    $error = 'Database connection failed';
    echo "ERROR: {$error}\n";
    exit(1);
}

echo "\n=== Setup Scripts Runner ===\n\n";
echo "This will run the following scripts and delete them afterwards:\n";
foreach ($setupScripts as $script) {
    echo "  - {$script}\n";
}
echo "\nStarting execution...\n\n";

foreach ($setupScripts as $script) {
    $scriptPath = __DIR__ . '/' . $script;
    
    if (!file_exists($scriptPath)) {
        echo "⚠  Script not found: {$script} (skipped)\n";
        $actions[] = [
            'status' => 'warning',
            'message' => "Script not found: {$script} (skipped)"
        ];
        continue;
    }
    
    echo "→ Running: {$script}...\n";
    
    // Capture output
    ob_start();
    
    try {
        // Include the script
        include $scriptPath;
        
        $output = ob_get_clean();
        
        echo "✓  Completed: {$script}\n";
        $actions[] = [
            'status' => 'success',
            'message' => "Completed: {$script}"
        ];
        
        // Delete the script if requested
        if ($deleteAfterRun) {
            if (unlink($scriptPath)) {
                $deletedFiles[] = $script;
                echo "  → Deleted: {$script}\n";
            } else {
                echo "  ⚠  Could not delete: {$script} (file may be locked or permissions issue)\n";
            }
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "✗  Error running {$script}: " . $e->getMessage() . "\n";
        $actions[] = [
            'status' => 'error',
            'message' => "Error running {$script}: " . $e->getMessage()
        ];
        $error = "Failed to run {$script}";
    } catch (Error $e) {
        ob_end_clean();
        echo "✗  Fatal error running {$script}: " . $e->getMessage() . "\n";
        $actions[] = [
            'status' => 'error',
            'message' => "Fatal error running {$script}: " . $e->getMessage()
        ];
        $error = "Failed to run {$script}";
    }
}

echo "\n=== Summary ===\n\n";

if ($error) {
    echo "ERROR: {$error}\n\n";
} else {
    echo "SUCCESS: All scripts executed.\n\n";
}

if (!empty($deletedFiles)) {
    echo "Deleted Files (" . count($deletedFiles) . "):\n";
    foreach ($deletedFiles as $file) {
        echo "  - {$file}\n";
    }
    echo "\n";
}

echo "Done!\n\n";

// Exit with appropriate code
exit($error ? 1 : 0);

