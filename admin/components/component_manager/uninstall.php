<?php
/**
 * Component Manager - Uninstaller
 * Uninstall component manager component
 */

// Load installer helper functions
require_once __DIR__ . '/install/checks.php';
require_once __DIR__ . '/install/default-menu-links.php';

// Determine mode
$isCLI = php_sapi_name() === 'cli';

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

// Main uninstallation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && isset($argv[1]) && $argv[1] === '--yes')) {
    // Step 1: Remove menu links
    $conn = component_manager_get_db_connection();
    if ($conn !== null) {
        $menuResult = component_manager_remove_menu_links($conn, 'component_manager');
        if ($menuResult['success']) {
            $uninstallResults['steps_completed'][] = 'Menu links removed';
        }
    }
    
    // Step 2: Remove config.php
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        if (unlink($configPath)) {
            $uninstallResults['steps_completed'][] = 'config.php removed';
        } else {
            $uninstallResults['warnings'][] = 'Could not remove config.php';
        }
    }
    
    // Note: Database tables are kept for history (optional cleanup)
    $uninstallResults['warnings'][] = 'Database tables preserved for history. Manually remove if needed.';
    
    $uninstallResults['success'] = true;
}

// Output based on mode
if ($isCLI) {
    if ($uninstallResults['success']) {
        echo "Component Manager uninstalled successfully!\n";
        foreach ($uninstallResults['steps_completed'] as $step) {
            echo "  - $step\n";
        }
    } else {
        echo "Uninstallation failed!\n";
        foreach ($uninstallResults['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    exit($uninstallResults['success'] ? 0 : 1);
}

// Web mode
?>
<!DOCTYPE html>
<html>
<head>
    <title>Component Manager - Uninstall</title>
</head>
<body>
    <h1>Component Manager Uninstall</h1>
    <?php if ($uninstallResults['success']): ?>
        <div class="success">Uninstallation completed successfully!</div>
    <?php else: ?>
        <form method="POST">
            <p>Are you sure you want to uninstall Component Manager?</p>
            <button type="submit">Yes, Uninstall</button>
        </form>
    <?php endif; ?>
</body>
</html>

