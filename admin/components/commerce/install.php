<?php
/**
 * Commerce Component - Installer
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
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all') {
            $isSilent = true;
        }
        if ($arg === '--auto') {
            $isAuto = true;
        }
        if (strpos($arg, '--db-host=') === 0) {
            $_POST['db_host'] = substr($arg, 10);
        }
        if (strpos($arg, '--db-user=') === 0) {
            $_POST['db_user'] = substr($arg, 10);
        }
        if (strpos($arg, '--db-pass=') === 0) {
            $_POST['db_pass'] = substr($arg, 10);
        }
        if (strpos($arg, '--db-name=') === 0) {
            $_POST['db_name'] = substr($arg, 10);
        }
    }
}

// Load installer helper functions
require_once __DIR__ . '/install/checks.php';
require_once __DIR__ . '/install/default-parameters.php';
require_once __DIR__ . '/install/default-menu-links.php';

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
            
            if (!empty($detected['name'])) {
                break;
            }
        }
    }
    
    // Override with POST data if provided
    if (isset($_POST['db_host'])) {
        $detected['host'] = $_POST['db_host'];
        $detected['source'] = 'user_input';
    }
    if (isset($_POST['db_user'])) {
        $detected['user'] = $_POST['db_user'];
    }
    if (isset($_POST['db_pass'])) {
        $detected['pass'] = $_POST['db_pass'];
    }
    if (isset($_POST['db_name'])) {
        $detected['name'] = $_POST['db_name'];
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
    
    $base = preg_replace('/\/admin\/components\/commerce.*$/', '', $dir);
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
    
    $sqlFile = __DIR__ . '/install/database.sql';
    if (!file_exists($sqlFile)) {
        return ['success' => false, 'error' => 'Database SQL file not found'];
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
 * Generate config.php file
 */
function generateConfigFile($detected) {
    $configTemplate = file_get_contents(__DIR__ . '/config.example.php');
    
    $config = str_replace("define('COMMERCE_DB_HOST', '');", "define('COMMERCE_DB_HOST', '" . addslashes($detected['db']['host']) . "');", $configTemplate);
    $config = str_replace("define('COMMERCE_DB_USER', '');", "define('COMMERCE_DB_USER', '" . addslashes($detected['db']['user']) . "');", $config);
    $config = str_replace("define('COMMERCE_DB_PASS', '');", "define('COMMERCE_DB_PASS', '" . addslashes($detected['db']['pass']) . "');", $config);
    $config = str_replace("define('COMMERCE_DB_NAME', '');", "define('COMMERCE_DB_NAME', '" . addslashes($detected['db']['name']) . "');", $config);
    $config = str_replace("define('COMMERCE_BASE_URL', '');", "define('COMMERCE_BASE_URL', '" . addslashes($detected['base_url']) . "');", $config);
    $config = str_replace("define('COMMERCE_ENCRYPTION_KEY', '');", "define('COMMERCE_ENCRYPTION_KEY', '" . addslashes(bin2hex(random_bytes(32))) . "');", $config);
    
    return $config;
}

// Auto-detect everything
$detected = [
    'db' => detectDatabaseConfig(),
    'paths' => detectPaths(),
    'base_url' => detectBaseUrl(),
    'base_system_info' => [
        'php_version' => PHP_VERSION,
        'detected_at' => date('Y-m-d H:i:s')
    ]
];

$installResults['detected_info'] = $detected;

// Perform installation if requested
if (($isCLI && ($isAuto || $isSilent)) || (!$isCLI && isset($_POST['install']))) {
    
    // Step 1: Run compatibility checks
    $checks = commerce_run_checks();
    if (!$checks['all_passed']) {
        $installResults['warnings'][] = 'Some compatibility checks failed, but continuing...';
    } else {
        $installResults['steps_completed'][] = 'Compatibility checks passed';
    }
    
    // Step 2: Test database connection
    $testResult = testDatabaseConnection(
        $detected['db']['host'],
        $detected['db']['user'],
        $detected['db']['pass'],
        $detected['db']['name']
    );
    
    if (!$testResult['success']) {
        $installResults['errors'][] = 'Database connection failed: ' . ($testResult['error'] ?? 'Unknown error');
    } else {
        $installResults['steps_completed'][] = 'Database connection successful';
        
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(
                $detected['db']['host'],
                $detected['db']['user'],
                $detected['db']['pass'],
                $detected['db']['name']
            );
            $conn->set_charset("utf8mb4");
            
            // Step 3: Create tables
            $tableResult = createDatabaseTables($conn);
            if ($tableResult['success']) {
                $installResults['steps_completed'][] = 'Database tables created';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $tableResult['errors']);
            }
            
            // Step 4: Insert default parameters
            if (empty($installResults['errors'])) {
                $paramResult = commerce_insert_default_parameters($conn);
                if ($paramResult['success']) {
                    $installResults['steps_completed'][] = 'Default parameters inserted (' . $paramResult['inserted'] . ' parameters)';
                } else {
                    $installResults['errors'] = array_merge($installResults['errors'], $paramResult['errors']);
                }
            }
            
            // Step 5: Store installation info in config table
            if (empty($installResults['errors'])) {
                $version = trim(file_get_contents(__DIR__ . '/VERSION'));
                $stmt = $conn->prepare("INSERT INTO commerce_config (config_key, config_value) VALUES ('version', ?), ('installed_at', ?), ('base_system_info', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                $installedAt = date('Y-m-d H:i:s');
                $baseSystemInfo = json_encode($detected['base_system_info']);
                $stmt->bind_param("sss", $version, $installedAt, $baseSystemInfo);
                $stmt->execute();
                $stmt->close();
                $installResults['steps_completed'][] = 'Installation metadata stored';
            }
            
            // Step 6: Create menu links (if menu_system is installed)
            if (empty($installResults['errors'])) {
                $menuResult = commerce_create_menu_links($conn, 'commerce', $detected['base_url'] . '/admin');
                if ($menuResult['success']) {
                    $installResults['steps_completed'][] = 'Menu links created (' . count($menuResult['menu_ids']) . ' items)';
                } else {
                    $installResults['warnings'][] = 'Menu links not created: ' . ($menuResult['error'] ?? 'Unknown error');
                }
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $installResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Step 7: Generate config.php
    if (empty($installResults['errors'])) {
        $configContent = generateConfigFile($detected);
        if (file_put_contents($configPath, $configContent) === false) {
            $installResults['errors'][] = 'Failed to write config.php file';
        } else {
            $installResults['steps_completed'][] = 'config.php generated';
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
        echo json_encode($installResults, JSON_PRETTY_PRINT) . "\n";
        exit($installResults['success'] ? 0 : 1);
    } else {
        echo "Commerce Component Installer\n";
        echo "============================\n\n";
        
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
} else {
    // Web mode - simple HTML output
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Commerce Component - Installer</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #0056b3; }
            input { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
            label { display: block; margin-top: 10px; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Commerce Component Installer</h1>
        
        <?php if ($installResults['success']): ?>
            <div class="success">
                <h2>✓ Installation Completed Successfully!</h2>
                <p>Completed steps:</p>
                <ul>
                    <?php foreach ($installResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (!empty($installResults['errors'])): ?>
            <div class="error">
                <h2>✗ Installation Failed</h2>
                <ul>
                    <?php foreach ($installResults['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <form method="POST">
                <h2>Database Configuration</h2>
                <label>Database Host:</label>
                <input type="text" name="db_host" value="<?php echo htmlspecialchars($detected['db']['host']); ?>" required>
                
                <label>Database User:</label>
                <input type="text" name="db_user" value="<?php echo htmlspecialchars($detected['db']['user']); ?>" required>
                
                <label>Database Password:</label>
                <input type="password" name="db_pass" value="<?php echo htmlspecialchars($detected['db']['pass']); ?>">
                
                <label>Database Name:</label>
                <input type="text" name="db_name" value="<?php echo htmlspecialchars($detected['db']['name']); ?>" required>
                
                <button type="submit" name="install">Install Commerce Component</button>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($installResults['warnings'])): ?>
            <div class="warning">
                <h3>Warnings:</h3>
                <ul>
                    <?php foreach ($installResults['warnings'] as $warning): ?>
                        <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

