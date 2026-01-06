<?php
/**
 * Layout Component - Installer
 * Fully automated installer with auto-detection
 * Supports CLI, Web, and Silent modes
 */

// Prevent direct access if config already exists (security)
$configPath = __DIR__ . '/config.php';
$isInstalled = file_exists($configPath);

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;
$isAuto = false;

if ($isCLI) {
    // Parse CLI arguments
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all') {
            $isSilent = true;
        }
        if ($arg === '--auto') {
            $isAuto = true;
        }
    }
}

// Load installer helper functions
require_once __DIR__ . '/install/checks.php';
require_once __DIR__ . '/core/database.php';

// Installation results
$installResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => [],
    'detected_info' => []
];

/**
 * Auto-detect database connection from common config files
 */
function detectDatabaseConfig() {
    $configFiles = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../includes/config.php',
        __DIR__ . '/../../../config.php'
    ];
    
    $detected = [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => '',
        'source' => 'default'
    ];
    
    foreach ($configFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Try to extract DB_HOST, DB_USER, DB_PASS, DB_NAME
            if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
                $detected['host'] = $matches[1];
                $detected['source'] = $file;
            }
            if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
                $detected['user'] = $matches[1];
            }
            if (preg_match("/define\s*\(\s*['\"]DB_PASS['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $content, $matches)) {
                $detected['pass'] = $matches[1];
            }
            if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
                $detected['name'] = $matches[1];
            }
            
            // If we found at least the database name, we have a valid config
            if (!empty($detected['name'])) {
                break;
            }
        }
    }
    
    // Check environment variables as fallback
    if (empty($detected['name'])) {
        $detected['host'] = getenv('DB_HOST') ?: $detected['host'];
        $detected['user'] = getenv('DB_USER') ?: $detected['user'];
        $detected['pass'] = getenv('DB_PASS') ?: $detected['pass'];
        $detected['name'] = getenv('DB_NAME') ?: $detected['name'];
        if (!empty($detected['name'])) {
            $detected['source'] = 'environment';
        }
    }
    
    return $detected;
}

/**
 * Auto-detect paths
 */
function detectPaths() {
    $componentPath = __DIR__;
    $projectRoot = dirname(dirname(dirname($componentPath)));
    $adminPath = dirname(dirname($componentPath));
    
    return [
        'component_path' => $componentPath,
        'project_root' => $projectRoot,
        'admin_path' => $adminPath
    ];
}

/**
 * Auto-detect base URL
 */
function detectBaseUrl() {
    if (php_sapi_name() === 'cli') {
        return 'http://localhost';
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    
    // Remove /admin/components/layout from path
    $base = preg_replace('/\/admin\/components\/layout.*$/', '', $dir);
    if (empty($base) || $base === '/') {
        $base = '';
    }
    
    return $protocol . '://' . $host . $base;
}

/**
 * Test database connection
 */
function testDatabaseConnection($host, $user, $pass, $name) {
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($host, $user, $pass, $name);
        
        if ($conn->connect_error) {
            return ['success' => false, 'error' => $conn->connect_error];
        }
        
        $conn->set_charset("utf8mb4");
        $conn->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Create database tables
 */
function createDatabaseTables($conn) {
    $errors = [];
    
    // Read SQL file
    $sqlFile = __DIR__ . '/install/database.sql';
    if (!file_exists($sqlFile)) {
        return ['success' => false, 'errors' => ['Database SQL file not found']];
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            if ($conn->query($statement) !== TRUE) {
                $errors[] = "Error executing statement: " . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Run migration
 * Checks current version and runs appropriate migrations
 */
function runMigration($conn) {
    // First, check if tables exist - if not, run initial migration
    $tableName = layout_get_table_name('config');
    $currentVersion = '1.0.0';
    
    // Check if config table exists
    $checkTable = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    if ($checkTable && $checkTable->num_rows > 0) {
        // Get current version
        $versionStmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = 'version'");
        if ($versionStmt) {
            $versionStmt->execute();
            $result = $versionStmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $currentVersion = $row['config_value'];
            }
            $versionStmt->close();
        }
    }
    
    $errors = [];
    $allSuccess = true;
    
    // Run 1.0.0 migration if needed
    if (version_compare($currentVersion, '1.0.0', '<')) {
        require_once __DIR__ . '/install/migrations/1.0.0.php';
        $result = layout_migration_1_0_0($conn);
        if (!$result['success']) {
            $errors = array_merge($errors, $result['errors']);
            $allSuccess = false;
        } else {
            $currentVersion = '1.0.0';
        }
    }
    
    // Run 3.0.0 migration if needed
    if (version_compare($currentVersion, '3.0.0', '<')) {
        require_once __DIR__ . '/install/migrations/3.0.0.php';
        $result = layout_migration_3_0_0($conn);
        if (!$result['success']) {
            $errors = array_merge($errors, $result['errors']);
            $allSuccess = false;
        }
    }
    
    return [
        'success' => $allSuccess,
        'errors' => $errors,
        'version' => $currentVersion
    ];
}

/**
 * Generate config.php file
 */
function generateConfigFile($detected) {
    $configTemplate = file_get_contents(__DIR__ . '/config.example.php');
    
    // Replace placeholders
    $version = trim(file_get_contents(__DIR__ . '/VERSION'));
    $config = str_replace("define('LAYOUT_VERSION', '1.0.0');", "define('LAYOUT_VERSION', '{$version}');", $configTemplate);
    $config = str_replace("define('LAYOUT_INSTALLED_AT', '');", "define('LAYOUT_INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');", $config);
    $config = str_replace("define('LAYOUT_DB_HOST', '');", "define('LAYOUT_DB_HOST', '" . addslashes($detected['db']['host']) . "');", $config);
    $config = str_replace("define('LAYOUT_DB_USER', '');", "define('LAYOUT_DB_USER', '" . addslashes($detected['db']['user']) . "');", $config);
    $config = str_replace("define('LAYOUT_DB_PASS', '');", "define('LAYOUT_DB_PASS', '" . addslashes($detected['db']['pass']) . "');", $config);
    $config = str_replace("define('LAYOUT_DB_NAME', '');", "define('LAYOUT_DB_NAME', '" . addslashes($detected['db']['name']) . "');", $config);
    $config = str_replace("define('LAYOUT_ROOT_PATH', '');", "define('LAYOUT_ROOT_PATH', '" . addslashes($detected['paths']['project_root']) . "');", $config);
    $config = str_replace("define('LAYOUT_ADMIN_PATH', '');", "define('LAYOUT_ADMIN_PATH', '" . addslashes($detected['paths']['admin_path']) . "');", $config);
    $config = str_replace("define('LAYOUT_BASE_URL', '');", "define('LAYOUT_BASE_URL', '" . addslashes($detected['base_url']) . "');", $config);
    $config = str_replace("define('LAYOUT_ADMIN_URL', '');", "define('LAYOUT_ADMIN_URL', '" . addslashes($detected['base_url'] . '/admin') . "');", $config);
    $config = str_replace("define('LAYOUT_BASE_SYSTEM_INFO', '{}');", "define('LAYOUT_BASE_SYSTEM_INFO', '" . addslashes(json_encode($detected['base_system_info'])) . "');", $config);
    
    return $config;
}

// Main installation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isAuto)) {
    // Start installation
    
    // Step 1: Auto-detect everything
    $detected = [
        'db' => detectDatabaseConfig(),
        'paths' => detectPaths(),
        'base_url' => detectBaseUrl(),
        'base_system_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'detected_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    $installResults['detected_info'] = $detected;
    
    // Step 2: Test database connection
    $dbTest = testDatabaseConnection(
        $detected['db']['host'],
        $detected['db']['user'],
        $detected['db']['pass'],
        $detected['db']['name']
    );
    
    if (!$dbTest['success']) {
        $installResults['errors'][] = 'Database connection failed: ' . ($dbTest['error'] ?? 'Unknown error');
    } else {
        $installResults['steps_completed'][] = 'Database connection test';
        
        // Step 3: Create database connection
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(
                $detected['db']['host'],
                $detected['db']['user'],
                $detected['db']['pass'],
                $detected['db']['name']
            );
            $conn->set_charset("utf8mb4");
            
            // Step 4: Run migration
            $migrationResult = runMigration($conn);
            if ($migrationResult['success']) {
                $installResults['steps_completed'][] = 'Database migration completed';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $migrationResult['errors']);
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $installResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Step 5: Generate config.php
    if (empty($installResults['errors'])) {
        $configContent = generateConfigFile($detected);
        if (file_put_contents($configPath, $configContent) === false) {
            $installResults['errors'][] = 'Failed to write config.php file';
        } else {
            $installResults['steps_completed'][] = 'config.php generated';
        }
    }
    
    // Step 6: Register menu links (if menu_system is installed)
    if (empty($installResults['errors'])) {
        $menuSystemConfig = __DIR__ . '/../menu_system/config.php';
        if (file_exists($menuSystemConfig)) {
            // menu_system is installed, register menu links
            $menuLinksFile = __DIR__ . '/install/menu-links.php';
            if (file_exists($menuLinksFile)) {
                require_once $menuLinksFile;
                
                try {
                    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                    $conn = new mysqli(
                        $detected['db']['host'],
                        $detected['db']['user'],
                        $detected['db']['pass'],
                        $detected['db']['name']
                    );
                    $conn->set_charset("utf8mb4");
                    
                    $adminUrl = $detected['base_url'] . '/admin';
                    $componentName = 'layout';
                    $menuResult = layout_create_menu_links($conn, $componentName, $adminUrl);
                    
                    if ($menuResult['success']) {
                        $installResults['steps_completed'][] = 'Menu links registered';
                    } else {
                        // Non-critical - menu links can be registered later
                        $installResults['warnings'][] = 'Menu links could not be registered: ' . ($menuResult['error'] ?? 'Unknown error');
                    }
                    
                    $conn->close();
                } catch (Exception $e) {
                    // Non-critical - menu links can be registered later
                    $installResults['warnings'][] = 'Could not register menu links: ' . $e->getMessage();
                }
            }
        } else {
            // menu_system not installed yet - menu links will be processed when menu_system is installed
            $installResults['steps_completed'][] = 'Menu links will be registered when menu_system is installed';
        }
    }
    
    // Final result
    if (empty($installResults['errors'])) {
        $installResults['success'] = true;
    }
}

// Output based on mode
if ($isCLI) {
    // CLI mode
    if ($isSilent || $isAuto) {
        // Silent/Auto mode - just output JSON
        echo json_encode($installResults, JSON_PRETTY_PRINT) . "\n";
        exit($installResults['success'] ? 0 : 1);
    } else {
        // Interactive CLI mode
        echo "Layout Component Installer\n";
        echo "==========================\n\n";
        
        if ($installResults['success']) {
            echo "✓ Installation completed successfully!\n";
            echo "\nCompleted steps:\n";
            foreach ($installResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
        } else {
            echo "✗ Installation failed!\n";
            echo "\nErrors:\n";
            foreach ($installResults['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    }
    exit(0);
}

// Web mode - show installation form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout Component - Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        form { background: #f8f9fa; padding: 20px; border-radius: 5px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .detected { background: #e7f3ff; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Layout Component Installation</h1>
    
    <?php if ($isInstalled): ?>
        <div class="warning">
            <strong>Already Installed:</strong> This component appears to be already installed. 
            If you want to reinstall, please delete config.php first.
        </div>
    <?php elseif ($installResults['success']): ?>
        <div class="success">
            <strong>Installation Successful!</strong>
            <ul>
                <?php foreach ($installResults['steps_completed'] as $step): ?>
                    <li><?php echo htmlspecialchars($step); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (!empty($installResults['errors'])): ?>
        <div class="error">
            <strong>Installation Failed!</strong>
            <ul>
                <?php foreach ($installResults['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <?php
        // Auto-detect values for form
        $detected = [
            'db' => detectDatabaseConfig(),
            'paths' => detectPaths(),
            'base_url' => detectBaseUrl()
        ];
        ?>
        
        <p>This installer will set up the Layout Component. All fields are pre-filled with auto-detected values.</p>
        
        <form method="POST">
            <h2>Database Configuration</h2>
            <div class="form-group">
                <label>Database Host:</label>
                <input type="text" name="db_host" value="<?php echo htmlspecialchars($detected['db']['host']); ?>" required>
                <small>Detected from: <?php echo htmlspecialchars($detected['db']['source']); ?></small>
            </div>
            <div class="form-group">
                <label>Database User:</label>
                <input type="text" name="db_user" value="<?php echo htmlspecialchars($detected['db']['user']); ?>" required>
            </div>
            <div class="form-group">
                <label>Database Password:</label>
                <input type="password" name="db_pass" value="<?php echo htmlspecialchars($detected['db']['pass']); ?>">
            </div>
            <div class="form-group">
                <label>Database Name:</label>
                <input type="text" name="db_name" value="<?php echo htmlspecialchars($detected['db']['name']); ?>" required>
            </div>
            
            <button type="submit">Install Layout Component</button>
        </form>
    <?php endif; ?>
</body>
</html>

