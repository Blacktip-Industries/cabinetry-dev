<?php
/**
 * Formula Builder Component - Uninstaller
 * Fully automated uninstaller
 */

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;

if ($isCLI) {
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all' || $arg === '--yes') {
            $isSilent = true;
        }
    }
}

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

// Load config if exists
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
    require_once __DIR__ . '/core/database.php';
}

function dropDatabaseTables($conn) {
    $tables = [
        'formula_builder_config',
        'formula_builder_parameters',
        'formula_builder_product_formulas',
        'formula_builder_formula_cache',
        'formula_builder_functions',
        'formula_builder_execution_log',
        'formula_builder_formula_versions',
        'formula_builder_formula_tests',
        'formula_builder_formula_library',
        'formula_builder_template_ratings',
        'formula_builder_query_cache',
        'formula_builder_permissions',
        'formula_builder_analytics',
        'formula_builder_debug_sessions',
        'formula_builder_events',
        'formula_builder_webhooks',
        'formula_builder_notifications',
        'formula_builder_notification_preferences',
        'formula_builder_component_integrations',
        'formula_builder_migrations',
        'formula_builder_api_keys',
        'formula_builder_alert_rules',
        'formula_builder_alerts',
        'formula_builder_collaborations',
        'formula_builder_comments',
        'formula_builder_workspaces',
        'formula_builder_workspace_members',
        'formula_builder_backups',
        'formula_builder_audit_log',
        'formula_builder_consents',
        'formula_builder_data_retention',
        'formula_builder_deployments',
        'formula_builder_feature_flags',
        'formula_builder_quality_reports',
        'formula_builder_cicd_pipelines',
        'formula_builder_cicd_runs',
        'formula_builder_performance_benchmarks',
        'formula_builder_performance_profiles',
        'formula_builder_servers',
        'formula_builder_queue_jobs',
        'formula_builder_ai_suggestions',
        'formula_builder_ai_models'
    ];
    
    $errors = [];
    foreach ($tables as $table) {
        try {
            $conn->query("DROP TABLE IF EXISTS {$table}");
        } catch (Exception $e) {
            $errors[] = "Error dropping {$table}: " . $e->getMessage();
        }
    }
    
    return ['success' => empty($errors), 'errors' => $errors];
}

// Main uninstallation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isSilent)) {
    if (file_exists($configPath)) {
        try {
            $conn = formula_builder_get_db_connection();
            if ($conn) {
                // Drop tables
                $dropResult = dropDatabaseTables($conn);
                if ($dropResult['success']) {
                    $uninstallResults['steps_completed'][] = 'Database tables dropped';
                } else {
                    $uninstallResults['errors'] = array_merge($uninstallResults['errors'], $dropResult['errors']);
                }
                $conn->close();
            }
            
            // Remove menu links
            if (file_exists(__DIR__ . '/install/default-menu-links.php')) {
                require_once __DIR__ . '/install/default-menu-links.php';
                $menuResult = formula_builder_remove_menu_links($conn, 'formula_builder');
                if ($menuResult['success']) {
                    $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
                }
            }
            
            // Remove config file
            if (unlink($configPath)) {
                $uninstallResults['steps_completed'][] = 'Config file removed';
            }
            
            $uninstallResults['success'] = true;
        } catch (Exception $e) {
            $uninstallResults['errors'][] = 'Uninstallation error: ' . $e->getMessage();
        }
    } else {
        $uninstallResults['warnings'][] = 'Component not installed (config.php not found)';
        $uninstallResults['success'] = true;
    }
}

// Display uninstaller interface (web mode)
if (!$isCLI && !$isSilent) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Formula Builder - Uninstallation</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            .btn { display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
            .btn:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <h1>Formula Builder Component - Uninstallation</h1>
        <?php if (!empty($uninstallResults['steps_completed'])): ?>
            <h2 class="success">Uninstallation Successful!</h2>
            <ul>
                <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                    <li class="success"><?php echo htmlspecialchars($step); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($uninstallResults['errors'])): ?>
            <h2 class="error">Errors:</h2>
            <ul>
                <?php foreach ($uninstallResults['errors'] as $error): ?>
                    <li class="error"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($uninstallResults['warnings'])): ?>
            <h2 class="warning">Warnings:</h2>
            <ul>
                <?php foreach ($uninstallResults['warnings'] as $warning): ?>
                    <li class="warning"><?php echo htmlspecialchars($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!$uninstallResults['success']): ?>
            <form method="POST">
                <p><strong>Warning:</strong> This will remove all formulas and data. This action cannot be undone!</p>
                <button type="submit" class="btn">Uninstall</button>
                <a href="../index.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// CLI mode
if ($isCLI) {
    if ($uninstallResults['success']) {
        echo "Formula Builder Component uninstalled successfully!\n";
        foreach ($uninstallResults['steps_completed'] as $step) {
            echo "  ✓ {$step}\n";
        }
        exit(0);
    } else {
        echo "Uninstallation failed:\n";
        foreach ($uninstallResults['errors'] as $error) {
            echo "  ✗ {$error}\n";
        }
        exit(1);
    }
}

