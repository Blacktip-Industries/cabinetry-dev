<?php
/**
 * Email Marketing Component - Installer
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
    
    $possibleAdminPaths = [
        $adminPath,
        $projectRoot . '/admin',
        $projectRoot . '/backend',
        $projectRoot . '/dashboard'
    ];
    
    $detectedAdminPath = $adminPath;
    foreach ($possibleAdminPaths as $path) {
        if (is_dir($path) && file_exists($path . '/index.php')) {
            $detectedAdminPath = $path;
            break;
        }
    }
    
    return [
        'component_path' => $componentPath,
        'project_root' => $projectRoot,
        'admin_path' => $detectedAdminPath
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
    
    $base = preg_replace('/\/admin\/components\/email_marketing.*$/', '', $dir);
    if (empty($base) || $base === '/') {
        $base = '';
    }
    
    return $protocol . '://' . $host . $base;
}

/**
 * Auto-detect CSS variables from base system
 */
function detectCSSVariables() {
    $cssFiles = [
        __DIR__ . '/../../assets/css/admin.css',
        __DIR__ . '/../../../assets/css/admin.css',
        __DIR__ . '/../../assets/css/layout.css',
        __DIR__ . '/../../../assets/css/layout.css'
    ];
    
    $detected = [];
    
    foreach ($cssFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            if (preg_match('/:root\s*\{([^}]+)\}/', $content, $matches)) {
                $rootContent = $matches[1];
                
                if (preg_match_all('/--([^:]+):\s*([^;]+);/', $rootContent, $varMatches, PREG_SET_ORDER)) {
                    foreach ($varMatches as $match) {
                        $varName = trim($match[1]);
                        $varValue = trim($match[2]);
                        if (!isset($detected[$varName])) {
                            $detected[$varName] = $varValue;
                        }
                    }
                }
            }
        }
    }
    
    return $detected;
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
    
    $version = trim(file_get_contents(__DIR__ . '/VERSION'));
    
    $config = str_replace("define('EMAIL_MARKETING_VERSION', '1.0.0');", "define('EMAIL_MARKETING_VERSION', '{$version}');", $configTemplate);
    $config = str_replace("define('EMAIL_MARKETING_INSTALLED_AT', '');", "define('EMAIL_MARKETING_INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_DB_HOST', '');", "define('EMAIL_MARKETING_DB_HOST', '" . addslashes($detected['db']['host']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_DB_USER', '');", "define('EMAIL_MARKETING_DB_USER', '" . addslashes($detected['db']['user']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_DB_PASS', '');", "define('EMAIL_MARKETING_DB_PASS', '" . addslashes($detected['db']['pass']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_DB_NAME', '');", "define('EMAIL_MARKETING_DB_NAME', '" . addslashes($detected['db']['name']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_ROOT_PATH', '');", "define('EMAIL_MARKETING_ROOT_PATH', '" . addslashes($detected['paths']['project_root']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_ADMIN_PATH', '');", "define('EMAIL_MARKETING_ADMIN_PATH', '" . addslashes($detected['paths']['admin_path']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_BASE_URL', '');", "define('EMAIL_MARKETING_BASE_URL', '" . addslashes($detected['base_url']) . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_ADMIN_URL', '');", "define('EMAIL_MARKETING_ADMIN_URL', '" . addslashes($detected['base_url'] . '/admin') . "');", $config);
    $config = str_replace("define('EMAIL_MARKETING_BASE_SYSTEM_INFO', '{}');", "define('EMAIL_MARKETING_BASE_SYSTEM_INFO', '" . addslashes(json_encode($detected['base_system_info'])) . "');", $config);
    
    return $config;
}

/**
 * Generate CSS variables file
 */
function generateCSSVariablesFile($detectedCSSVars) {
    $css = ":root {\n";
    $css .= "    /* Email Marketing Component Variables */\n";
    $css .= "    /* Auto-generated during installation */\n\n";
    
    // Map base system variables
    $mappings = [
        'color-primary' => '--email-marketing-color-primary',
        'color-secondary' => '--email-marketing-color-secondary',
        'spacing-sm' => '--email-marketing-spacing-sm',
        'spacing-md' => '--email-marketing-spacing-md',
        'spacing-lg' => '--email-marketing-spacing-lg',
        'font-primary' => '--email-marketing-font-primary'
    ];
    
    foreach ($mappings as $baseVar => $componentVar) {
        $value = $detectedCSSVars[$baseVar] ?? 'inherit';
        $css .= "    {$componentVar}: var(--{$baseVar}, {$value});\n";
    }
    
    $css .= "}\n";
    
    return $css;
}

// Auto-detect everything
$detected = [
    'db' => detectDatabaseConfig(),
    'paths' => detectPaths(),
    'base_url' => detectBaseUrl(),
    'css_vars' => detectCSSVariables(),
    'base_system_info' => [
        'php_version' => PHP_VERSION,
        'detected_at' => date('Y-m-d H:i:s')
    ]
];

$installResults['detected_info'] = $detected;

// Perform installation if requested
if (($isCLI && ($isAuto || $isSilent)) || (!$isCLI && isset($_POST['install']))) {
    
    // Step 1: Test database connection
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
            
            // Step 2: Create tables
            $tableResult = createDatabaseTables($conn);
            if ($tableResult['success']) {
                $installResults['steps_completed'][] = 'Database tables created';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $tableResult['errors']);
            }
            
            // Step 3: Insert default parameters
            if (empty($installResults['errors'])) {
                $paramResult = email_marketing_insert_default_parameters($conn);
                if ($paramResult['success']) {
                    $installResults['steps_completed'][] = 'Default parameters inserted (' . $paramResult['inserted'] . ' parameters)';
                } else {
                    $installResults['errors'] = array_merge($installResults['errors'], $paramResult['errors']);
                }
            }
            
            // Step 4: Store installation info in config table
            if (empty($installResults['errors'])) {
                $version = trim(file_get_contents(__DIR__ . '/VERSION'));
                $stmt = $conn->prepare("INSERT INTO email_marketing_config (config_key, config_value) VALUES ('version', ?), ('installed_at', ?), ('base_system_info', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                $installedAt = date('Y-m-d H:i:s');
                $baseSystemInfo = json_encode($detected['base_system_info']);
                $stmt->bind_param("sss", $version, $installedAt, $baseSystemInfo);
                $stmt->execute();
                $stmt->close();
                $installResults['steps_completed'][] = 'Installation metadata stored';
            }
            
            // Step 5: Create menu links (if menu_system is installed)
            if (empty($installResults['errors'])) {
                $menuResult = email_marketing_create_menu_links($conn, 'email_marketing', $detected['base_url'] . '/admin');
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
    
    // Step 6: Generate config.php
    if (empty($installResults['errors'])) {
        $configContent = generateConfigFile($detected);
        if (file_put_contents($configPath, $configContent) === false) {
            $installResults['errors'][] = 'Failed to write config.php file';
        } else {
            $installResults['steps_completed'][] = 'config.php generated';
        }
    }
    
    // Step 7: Generate CSS variables file
    if (empty($installResults['errors'])) {
        $cssVarsContent = generateCSSVariablesFile($detected['css_vars']);
        $cssVarsPath = __DIR__ . '/assets/css/variables.css';
        if (file_put_contents($cssVarsPath, $cssVarsContent) === false) {
            $installResults['warnings'][] = 'Failed to write CSS variables file (non-critical)';
        } else {
            $installResults['steps_completed'][] = 'CSS variables file generated';
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
        echo "Email Marketing Component Installer\n";
        echo "====================================\n\n";
        
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
    // Web mode
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Marketing Component - Installer</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #0056b3; }
            .detected-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>Email Marketing Component - Installer</h1>
        
        <?php if ($isInstalled && !isset($_POST['reinstall'])): ?>
            <div class="warning">
                <strong>Component already installed!</strong><br>
                If you want to reinstall, you must delete config.php first or use the uninstaller.
            </div>
        <?php elseif ($installResults['success']): ?>
            <div class="success">
                <strong>Installation Completed Successfully!</strong>
                <ul>
                    <?php foreach ($installResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (!empty($installResults['warnings'])): ?>
                <div class="warning">
                    <strong>Warnings:</strong>
                    <ul>
                        <?php foreach ($installResults['warnings'] as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="info">
                <strong>Next Steps:</strong>
                <ol>
                    <li>Access the dashboard at: <a href="<?php echo htmlspecialchars($detected['base_url']); ?>/admin/components/email_marketing/admin/dashboard.php">Email Marketing Dashboard</a></li>
                    <li>Configure email settings in Settings</li>
                    <li>Create your first email template</li>
                    <li>Set up your first campaign</li>
                </ol>
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
            <div class="info">
                <strong>Auto-Detected Information:</strong>
                <div class="detected-info">
                    <strong>Database:</strong><br>
                    Host: <?php echo htmlspecialchars($detected['db']['host'] ?? 'Not detected'); ?><br>
                    User: <?php echo htmlspecialchars($detected['db']['user'] ?? 'Not detected'); ?><br>
                    Database: <?php echo htmlspecialchars($detected['db']['name'] ?? 'Not detected'); ?><br>
                    Source: <?php echo htmlspecialchars($detected['db']['source'] ?? 'default'); ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="install" value="1">
                    <button type="submit">Install Email Marketing Component</button>
                </form>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

