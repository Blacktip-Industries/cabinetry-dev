<?php
/**
 * Layout Component - Uninstaller
 * Fully automated uninstaller with backup support
 */

$configPath = __DIR__ . '/config.php';
$isInstalled = file_exists($configPath);

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;
$isAuto = false;
$createBackup = true;

if ($isCLI) {
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all') {
            $isSilent = true;
        }
        if ($arg === '--auto') {
            $isAuto = true;
        }
        if ($arg === '--no-backup') {
            $createBackup = false;
        }
    }
}

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

/**
 * Create backup of component data
 */
function createBackup($conn) {
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/uninstall_backup_' . $timestamp . '.sql';
    
    $tables = ['layout_config', 'layout_parameters'];
    $sql = "-- Layout Component Uninstall Backup\n";
    $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Check if table exists
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result && $result->num_rows > 0) {
            // Get table data
            $data = $conn->query("SELECT * FROM {$table}");
            if ($data) {
                $sql .= "-- Table: {$table}\n";
                while ($row = $data->fetch_assoc()) {
                    $keys = array_keys($row);
                    $values = array_map(function($v) use ($conn) {
                        return "'" . $conn->real_escape_string($v) . "'";
                    }, array_values($row));
                    $sql .= "INSERT INTO {$table} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
    }
    
    file_put_contents($backupFile, $sql);
    return $backupFile;
}

/**
 * Drop component tables
 */
function dropTables($conn) {
    $tables = ['layout_parameters', 'layout_config']; // Drop in reverse order
    $errors = [];
    
    foreach ($tables as $table) {
        try {
            $conn->query("DROP TABLE IF EXISTS {$table}");
        } catch (Exception $e) {
            $errors[] = "Error dropping table {$table}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

// Main uninstallation process
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' || ($isCLI && $isAuto)) {
    if (!$isInstalled) {
        $uninstallResults['errors'][] = 'Component is not installed';
    } else {
        // Load config
        require_once $configPath;
        
        // Connect to database
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(
                defined('LAYOUT_DB_HOST') ? LAYOUT_DB_HOST : 'localhost',
                defined('LAYOUT_DB_USER') ? LAYOUT_DB_USER : 'root',
                defined('LAYOUT_DB_PASS') ? LAYOUT_DB_PASS : '',
                defined('LAYOUT_DB_NAME') ? LAYOUT_DB_NAME : ''
            );
            $conn->set_charset("utf8mb4");
            
            // Create backup
            if ($createBackup) {
                $backupFile = createBackup($conn);
                $uninstallResults['steps_completed'][] = 'Backup created: ' . basename($backupFile);
            }
            
            // Drop tables
            $dropResult = dropTables($conn);
            if ($dropResult['success']) {
                $uninstallResults['steps_completed'][] = 'Database tables dropped';
            } else {
                $uninstallResults['errors'] = array_merge($uninstallResults['errors'], $dropResult['errors']);
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $uninstallResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
        
        // Delete config.php
        if (file_exists($configPath)) {
            if (unlink($configPath)) {
                $uninstallResults['steps_completed'][] = 'config.php deleted';
            } else {
                $uninstallResults['errors'][] = 'Failed to delete config.php';
            }
        }
    }
    
    // Final result
    if (empty($uninstallResults['errors'])) {
        $uninstallResults['success'] = true;
    }
}

// Output based on mode
if ($isCLI) {
    if ($isSilent || $isAuto) {
        echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
        exit($uninstallResults['success'] ? 0 : 1);
    } else {
        echo "Layout Component Uninstaller\n";
        echo "===========================\n\n";
        
        if ($uninstallResults['success']) {
            echo "✓ Uninstallation completed successfully!\n";
            echo "\nCompleted steps:\n";
            foreach ($uninstallResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
        } else {
            echo "✗ Uninstallation failed!\n";
            echo "\nErrors:\n";
            foreach ($uninstallResults['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    }
    exit(0);
}

// Web mode
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout Component - Uninstallation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        button { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>Layout Component Uninstallation</h1>
    
    <?php if (!$isInstalled): ?>
        <div class="warning">
            <strong>Not Installed:</strong> This component is not installed.
        </div>
    <?php elseif ($uninstallResults['success']): ?>
        <div class="success">
            <strong>Uninstallation Successful!</strong>
            <ul>
                <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                    <li><?php echo htmlspecialchars($step); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (!empty($uninstallResults['errors'])): ?>
        <div class="error">
            <strong>Uninstallation Failed!</strong>
            <ul>
                <?php foreach ($uninstallResults['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="warning">
            <strong>Warning:</strong> This will remove all layout component data and configuration.
            A backup will be created automatically.
        </div>
        <form method="POST">
            <button type="submit" onclick="return confirm('Are you sure you want to uninstall?')">Uninstall Layout Component</button>
        </form>
    <?php endif; ?>
</body>
</html>

