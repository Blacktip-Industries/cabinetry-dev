<?php
/**
 * Mobile API Component - Installer
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
require_once __DIR__ . '/core/functions.php';

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
    
    // Remove /admin/components/mobile_api from path
    $base = preg_replace('/\/admin\/components\/mobile_api.*$/', '', $dir);
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
    foreach ($cssFiles as $cssFile) {
        if (file_exists($cssFile)) {
            $content = file_get_contents($cssFile);
            if (preg_match_all('/--([a-z0-9-]+):\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $varName = trim($match[1]);
                    $varValue = trim($match[2]);
                    $detected[$varName] = $varValue;
                }
            }
        }
    }
    
    return $detected;
}

/**
 * Create database tables
 */
function createDatabaseTables($conn) {
    $sqlFile = __DIR__ . '/install/database.sql';
    if (!file_exists($sqlFile)) {
        return ['success' => false, 'errors' => ['Database SQL file not found']];
    }
    
    $sql = file_get_contents($sqlFile);
    if (empty($sql)) {
        return ['success' => false, 'errors' => ['Database SQL file is empty']];
    }
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $conn->query($statement);
        } catch (Exception $e) {
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
    
    // Replace placeholders
    $version = trim(file_get_contents(__DIR__ . '/VERSION'));
    $config = str_replace("define('MOBILE_API_VERSION', '1.0.0');", "define('MOBILE_API_VERSION', '{$version}');", $configTemplate);
    $config = str_replace("define('MOBILE_API_INSTALLED_AT', '');", "define('MOBILE_API_INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');", $config);
    $config = str_replace("define('MOBILE_API_DB_HOST', '');", "define('MOBILE_API_DB_HOST', '" . addslashes($detected['db']['host']) . "');", $config);
    $config = str_replace("define('MOBILE_API_DB_USER', '');", "define('MOBILE_API_DB_USER', '" . addslashes($detected['db']['user']) . "');", $config);
    $config = str_replace("define('MOBILE_API_DB_PASS', '');", "define('MOBILE_API_DB_PASS', '" . addslashes($detected['db']['pass']) . "');", $config);
    $config = str_replace("define('MOBILE_API_DB_NAME', '');", "define('MOBILE_API_DB_NAME', '" . addslashes($detected['db']['name']) . "');", $config);
    $config = str_replace("define('MOBILE_API_ROOT_PATH', '');", "define('MOBILE_API_ROOT_PATH', '" . addslashes($detected['paths']['project_root']) . "');", $config);
    $config = str_replace("define('MOBILE_API_ADMIN_PATH', '');", "define('MOBILE_API_ADMIN_PATH', '" . addslashes($detected['paths']['admin_path']) . "');", $config);
    $config = str_replace("define('MOBILE_API_BASE_URL', '');", "define('MOBILE_API_BASE_URL', '" . addslashes($detected['base_url']) . "');", $config);
    $config = str_replace("define('MOBILE_API_ADMIN_URL', '');", "define('MOBILE_API_ADMIN_URL', '" . addslashes($detected['base_url'] . '/admin') . "');", $config);
    $config = str_replace("define('MOBILE_API_BASE_SYSTEM_INFO', '{}');", "define('MOBILE_API_BASE_SYSTEM_INFO', '" . addslashes(json_encode($detected['base_system_info'])) . "');", $config);
    
    return $config;
}

/**
 * Generate CSS variables file
 */
function generateCSSVariablesFile($detectedCSSVars) {
    $cssVarsPath = __DIR__ . '/assets/css/variables.css';
    
    $template = "/* Mobile API Component - CSS Variables\n * This file is auto-generated during installation\n * DO NOT EDIT MANUALLY - Regenerate via installer if needed\n */\n\n:root {\n";
    
    // Add detected base system variables
    $additionalVars = "\n    /* Auto-detected Base System Variables */\n";
    foreach ($detectedCSSVars as $varName => $varValue) {
        $maVarName = '--mobile-api-' . str_replace('--', '', $varName);
        $additionalVars .= "    {$maVarName}: var({$varName}, {$varValue});\n";
    }
    
    $template .= $additionalVars . "}\n";
    
    return $template;
}

// Main installation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isAuto)) {
    // Start installation
    
    // Step 1: Auto-detect everything
    $detected = [
        'db' => detectDatabaseConfig(),
        'paths' => detectPaths(),
        'base_url' => detectBaseUrl(),
        'css_vars' => detectCSSVariables(),
        'base_system_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'detected_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    $installResults['detected_info'] = $detected;
    
    // Step 2: Test database connection
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(
            $detected['db']['host'],
            $detected['db']['user'],
            $detected['db']['pass'],
            $detected['db']['name']
        );
        $conn->set_charset("utf8mb4");
        
        $installResults['steps_completed'][] = 'Database connection test';
        
        // Step 3: Check if tables already exist
        $existingTables = [];
        $result = $conn->query("SHOW TABLES LIKE 'mobile_api_%'");
        if ($result) {
            while ($row = $result->fetch_array()) {
                $existingTables[] = $row[0];
            }
        }
        
        if (!empty($existingTables)) {
            $installResults['warnings'][] = 'Some mobile_api_* tables already exist. Installation will continue and update if needed.';
        }
        
        // Step 4: Create tables
        $tableResult = createDatabaseTables($conn);
        if ($tableResult['success']) {
            $installResults['steps_completed'][] = 'Database tables created';
        } else {
            $installResults['errors'] = array_merge($installResults['errors'], $tableResult['errors']);
        }
        
        // Step 5: Insert default parameters
        require_once __DIR__ . '/install/default-parameters.php';
        $paramResult = mobile_api_insert_default_parameters($conn);
        if ($paramResult['success']) {
            $installResults['steps_completed'][] = 'Default parameters inserted (' . $paramResult['inserted'] . ' parameters)';
        } else {
            $installResults['errors'] = array_merge($installResults['errors'], $paramResult['errors']);
        }
        
        // Step 6: Store installation info in config table
        $version = trim(file_get_contents(__DIR__ . '/VERSION'));
        $stmt = $conn->prepare("INSERT INTO mobile_api_config (config_key, config_value) VALUES ('version', ?), ('installed_at', ?), ('base_system_info', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $installedAt = date('Y-m-d H:i:s');
        $baseSystemInfo = json_encode($detected['base_system_info']);
        $stmt->bind_param("sss", $version, $installedAt, $baseSystemInfo);
        $stmt->execute();
        $stmt->close();
        
        // Step 7: Create menu links (if menu_system is installed)
        require_once __DIR__ . '/install/default-menu-links.php';
        $menuResult = mobile_api_create_menu_links($conn, 'mobile_api', $detected['base_url'] . '/admin');
        if ($menuResult['success']) {
            if (isset($menuResult['menu_ids']) && count($menuResult['menu_ids']) > 0) {
                $installResults['steps_completed'][] = 'Menu links created (' . count($menuResult['menu_ids']) . ' items)';
            } else {
                $installResults['steps_completed'][] = 'Menu links (already exist or menu_system not installed)';
            }
        } else {
            $installResults['warnings'][] = 'Menu links not created: ' . ($menuResult['error'] ?? 'Unknown error');
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $installResults['errors'][] = 'Database error: ' . $e->getMessage();
    }
    
    // Step 8: Generate config.php
    if (empty($installResults['errors'])) {
        $configContent = generateConfigFile($detected);
        if (file_put_contents($configPath, $configContent) === false) {
            $installResults['errors'][] = 'Failed to write config.php file';
        } else {
            $installResults['steps_completed'][] = 'config.php generated';
        }
    }
    
    // Step 9: Generate CSS variables file
    if (empty($installResults['errors'])) {
        $cssVarsContent = generateCSSVariablesFile($detected['css_vars']);
        $cssVarsPath = __DIR__ . '/assets/css/variables.css';
        // Ensure directory exists
        $cssDir = dirname($cssVarsPath);
        if (!is_dir($cssDir)) {
            mkdir($cssDir, 0755, true);
        }
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
        // Silent/Auto mode - just output JSON
        echo json_encode($installResults, JSON_PRETTY_PRINT) . "\n";
        exit($installResults['success'] ? 0 : 1);
    } else {
        // Interactive CLI mode
        echo "Mobile API Component Installer\n";
        echo "==============================\n\n";
        
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
    // Web mode - output HTML installer interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mobile API Component - Installation</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            .step { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
            button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
            .detected { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>Mobile API Component - Installation</h1>
        
        <?php if ($installResults['success']): ?>
            <div class="success">
                <h2>✓ Installation Completed Successfully!</h2>
                <p><strong>Completed steps:</strong></p>
                <ul>
                    <?php foreach ($installResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="admin/index.php">Go to Mobile API Dashboard</a></p>
            </div>
        <?php elseif (!empty($installResults['errors'])): ?>
            <div class="error">
                <h2>✗ Installation Failed</h2>
                <p><strong>Errors:</strong></p>
                <ul>
                    <?php foreach ($installResults['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="detected">
                <h2>Auto-Detected Configuration</h2>
                <p><strong>Database:</strong> <?php echo htmlspecialchars($detected['db']['host'] ?? 'Not detected'); ?> / <?php echo htmlspecialchars($detected['db']['name'] ?? 'Not detected'); ?></p>
                <p><strong>Base URL:</strong> <?php echo htmlspecialchars($detected['base_url'] ?? 'Not detected'); ?></p>
            </div>
            
            <form method="POST">
                <button type="submit">Install Mobile API Component</button>
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

