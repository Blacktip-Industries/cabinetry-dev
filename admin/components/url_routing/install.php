<?php
/**
 * URL Routing Component - Installer
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
    
    // Detect project root (go up from admin/components/url_routing)
    $projectRoot = dirname(dirname(dirname($componentPath)));
    
    // Detect admin path
    $adminPath = dirname($componentPath); // admin/components
    $adminPath = dirname($adminPath); // admin
    
    // Try to find actual admin directory
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
    
    // Remove /admin/components/url_routing from path
    $base = preg_replace('/\/admin\/components\/url_routing.*$/', '', $dir);
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
            
            // Extract :root variables
            if (preg_match('/:root\s*\{([^}]+)\}/', $content, $matches)) {
                $rootContent = $matches[1];
                
                // Extract individual variables
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
    
    // Read SQL file
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
 * Insert default parameters
 */
function insertDefaultParameters($conn) {
    $defaultParams = [
        ['section' => 'General', 'parameter_name' => '--404-page', 'value' => '', 'description' => 'Custom 404 page path (optional)'],
        ['section' => 'General', 'parameter_name' => '--enable-caching', 'value' => 'NO', 'description' => 'Enable route caching for performance'],
        ['section' => 'General', 'parameter_name' => '--base-path', 'value' => '', 'description' => 'Base path for routing (leave empty for auto-detect)'],
    ];
    
    $tableName = 'url_routing_parameters';
    $inserted = 0;
    $errors = [];
    
    foreach ($defaultParams as $param) {
        try {
            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description)");
            $stmt->bind_param("ssss", $param['section'], $param['parameter_name'], $param['value'], $param['description']);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

/**
 * Migrate routes from menu_system_menus (optional)
 */
function migrateRoutesFromMenus($conn, $projectRoot) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system_menus table not found'];
    }
    
    // Load functions for slug generation
    require_once __DIR__ . '/../core/database.php';
    require_once __DIR__ . '/../core/functions.php';
    
    // Get all menu items with URLs
    $result = $conn->query("SELECT id, title, url FROM menu_system_menus WHERE url IS NOT NULL AND url != '' AND url != '#' AND is_active = 1");
    
    $migrated = 0;
    $errors = [];
    $routesTable = 'url_routing_routes';
    
    while ($row = $result->fetch_assoc()) {
        $url = $row['url'];
        
        // Skip external URLs
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            continue;
        }
        
        // Remove query string
        $url = preg_replace('/[?#].*$/', '', $url);
        
        // Remove leading slash
        $url = ltrim($url, '/');
        
        // Skip if already a clean URL (no .php)
        if (strpos($url, '.php') === false) {
            continue;
        }
        
        // Generate slug from file path
        $slug = url_routing_generate_slug_from_path($url);
        
        if (empty($slug)) {
            continue;
        }
        
        // Check if route already exists
        $checkStmt = $conn->prepare("SELECT id FROM {$routesTable} WHERE slug = ?");
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if ($exists) {
            continue; // Skip if already exists
        }
        
        // Insert route
        try {
            $stmt = $conn->prepare("INSERT INTO {$routesTable} (slug, file_path, type, title, active, is_static) VALUES (?, ?, 'admin', ?, 1, 0)");
            $type = 'admin';
            $stmt->bind_param("sss", $slug, $url, $row['title']);
            $stmt->execute();
            $stmt->close();
            $migrated++;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error migrating route {$slug}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'migrated' => $migrated,
        'errors' => $errors
    ];
}

/**
 * Generate config.php file
 */
function generateConfigFile($detected) {
    $configTemplate = file_get_contents(__DIR__ . '/config.example.php');
    
    // Replace placeholders
    $config = str_replace("define('URL_ROUTING_VERSION', '1.0.0');", "define('URL_ROUTING_VERSION', '" . trim(file_get_contents(__DIR__ . '/VERSION')) . "');", $configTemplate);
    $config = str_replace("define('URL_ROUTING_INSTALLED_AT', '');", "define('URL_ROUTING_INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');", $config);
    $config = str_replace("define('URL_ROUTING_DB_HOST', '');", "define('URL_ROUTING_DB_HOST', '" . addslashes($detected['db']['host']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_DB_USER', '');", "define('URL_ROUTING_DB_USER', '" . addslashes($detected['db']['user']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_DB_PASS', '');", "define('URL_ROUTING_DB_PASS', '" . addslashes($detected['db']['pass']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_DB_NAME', '');", "define('URL_ROUTING_DB_NAME', '" . addslashes($detected['db']['name']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_ROOT_PATH', '');", "define('URL_ROUTING_ROOT_PATH', '" . addslashes($detected['paths']['project_root']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_ADMIN_PATH', '');", "define('URL_ROUTING_ADMIN_PATH', '" . addslashes($detected['paths']['admin_path']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_BASE_URL', '');", "define('URL_ROUTING_BASE_URL', '" . addslashes($detected['base_url']) . "');", $config);
    $config = str_replace("define('URL_ROUTING_ADMIN_URL', '');", "define('URL_ROUTING_ADMIN_URL', '" . addslashes($detected['base_url'] . '/admin') . "');", $config);
    $config = str_replace("define('URL_ROUTING_BASE_SYSTEM_INFO', '{}');", "define('URL_ROUTING_BASE_SYSTEM_INFO', '" . addslashes(json_encode($detected['base_system_info'])) . "');", $config);
    
    return $config;
}

/**
 * Generate CSS variables file
 */
function generateCSSVariablesFile($detectedCSSVars) {
    $template = ":root {\n    /* URL Routing Component Variables */\n";
    
    // Add detected base system variables
    if (!empty($detectedCSSVars)) {
        $template .= "\n    /* Auto-detected Base System Variables */\n";
        foreach ($detectedCSSVars as $varName => $varValue) {
            $urVarName = '--ur-' . str_replace('--', '', $varName);
            $template .= "    {$urVarName}: var({$varName}, {$varValue});\n";
        }
    }
    
    $template .= "}\n";
    
    return $template;
}

/**
 * Create .htaccess and router.php in project root
 */
function createIntegrationFiles($projectRoot, $basePath) {
    $errors = [];
    $created = [];
    
    // Create .htaccess
    $htaccessPath = $projectRoot . '/.htaccess';
    $htaccessBackup = $projectRoot . '/.htaccess.backup.' . date('Y-m-d_H-i-s');
    
    $htaccessContent = "RewriteEngine On\n";
    if ($basePath) {
        $htaccessContent .= "RewriteBase " . $basePath . "\n";
    } else {
        $htaccessContent .= "RewriteBase /\n";
    }
    $htaccessContent .= "\n# Don't rewrite existing files/directories\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccessContent .= "\n# Route to router\n";
    $htaccessContent .= "RewriteRule ^(.*)$ router.php [QSA,L]\n";
    
    // Backup existing .htaccess if it exists
    if (file_exists($htaccessPath)) {
        if (copy($htaccessPath, $htaccessBackup)) {
            $created[] = 'Backed up existing .htaccess';
        }
    }
    
    // Create or update .htaccess
    if (file_put_contents($htaccessPath, $htaccessContent) === false) {
        $errors[] = 'Failed to create .htaccess file';
    } else {
        $created[] = '.htaccess created';
    }
    
    // Create router.php
    $routerPath = $projectRoot . '/router.php';
    $routerBackup = $projectRoot . '/router.php.backup.' . date('Y-m-d_H-i-s');
    
    $routerContent = "<?php\n";
    $routerContent .= "/**\n";
    $routerContent .= " * URL Routing - Router Entry Point\n";
    $routerContent .= " * Auto-generated by URL Routing Component installer\n";
    $routerContent .= " */\n\n";
    $routerContent .= "require_once __DIR__ . '/admin/components/url_routing/includes/router.php';\n";
    $routerContent .= "url_routing_dispatch();\n";
    
    // Backup existing router.php if it exists
    if (file_exists($routerPath)) {
        if (copy($routerPath, $routerBackup)) {
            $created[] = 'Backed up existing router.php';
        }
    }
    
    // Create router.php
    if (file_put_contents($routerPath, $routerContent) === false) {
        $errors[] = 'Failed to create router.php file';
    } else {
        $created[] = 'router.php created';
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors,
        'created' => $created
    ];
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
            
            // Step 4: Create tables
            $tableResult = createDatabaseTables($conn);
            if ($tableResult['success']) {
                $installResults['steps_completed'][] = 'Database tables created';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $tableResult['errors']);
            }
            
            // Step 5: Insert default parameters
            $paramResult = insertDefaultParameters($conn);
            if ($paramResult['success']) {
                $installResults['steps_completed'][] = 'Default parameters inserted (' . $paramResult['inserted'] . ' parameters)';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $paramResult['errors']);
            }
            
            // Step 6: Optional menu migration
            $migrateMenus = isset($_POST['migrate_menus']) && $_POST['migrate_menus'] === 'yes';
            if ($migrateMenus) {
                $migrateResult = migrateRoutesFromMenus($conn, $detected['paths']['project_root']);
                if ($migrateResult['success']) {
                    $installResults['steps_completed'][] = 'Migrated ' . $migrateResult['migrated'] . ' routes from menu system';
                } else {
                    $installResults['warnings'][] = 'Menu migration: ' . ($migrateResult['error'] ?? 'No routes migrated');
                }
            }
            
            // Step 7: Create menu links (if menu_system is installed)
            if (empty($installResults['errors'])) {
                require_once __DIR__ . '/install/default-menu-links.php';
                $menuResult = url_routing_create_menu_links($conn, 'url_routing', $detected['base_url'] . '/admin');
                if ($menuResult['success']) {
                    $installResults['steps_completed'][] = 'Menu links created (' . count($menuResult['menu_ids']) . ' items)';
                } else {
                    $installResults['warnings'][] = 'Menu links not created: ' . ($menuResult['error'] ?? 'Unknown error');
                }
            }
            
            // Step 8: Store installation info in config table
            $configTable = 'url_routing_config';
            $version = trim(file_get_contents(__DIR__ . '/VERSION'));
            $stmt = $conn->prepare("INSERT INTO {$configTable} (config_key, config_value) VALUES ('version', ?), ('installed_at', ?), ('base_system_info', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
            $installedAt = date('Y-m-d H:i:s');
            $baseSystemInfo = json_encode($detected['base_system_info']);
            $stmt->bind_param("sss", $version, $installedAt, $baseSystemInfo);
            $stmt->execute();
            $stmt->close();
            
            $conn->close();
            
        } catch (Exception $e) {
            $installResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Step 9: Generate config.php
    if (empty($installResults['errors'])) {
        $configContent = generateConfigFile($detected);
        if (file_put_contents($configPath, $configContent) === false) {
            $installResults['errors'][] = 'Failed to write config.php file';
        } else {
            $installResults['steps_completed'][] = 'config.php generated';
        }
    }
    
    // Step 10: Generate CSS variables file
    if (empty($installResults['errors'])) {
        $cssVarsContent = generateCSSVariablesFile($detected['css_vars']);
        $cssVarsPath = __DIR__ . '/assets/css/variables.css';
        if (!is_dir(dirname($cssVarsPath))) {
            mkdir(dirname($cssVarsPath), 0755, true);
        }
        if (file_put_contents($cssVarsPath, $cssVarsContent) === false) {
            $installResults['warnings'][] = 'Failed to write CSS variables file (non-critical)';
        } else {
            $installResults['steps_completed'][] = 'CSS variables file generated';
        }
    }
    
    // Step 11: Create integration files (.htaccess and router.php)
    if (empty($installResults['errors'])) {
        $basePath = '';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/';
        $dir = dirname($script);
        $parts = explode('/', trim($dir, '/'));
        if (count($parts) > 1 && $parts[0] !== 'admin') {
            $basePath = '/' . $parts[0];
        }
        
        $integrationResult = createIntegrationFiles($detected['paths']['project_root'], $basePath);
        if ($integrationResult['success']) {
            foreach ($integrationResult['created'] as $item) {
                $installResults['steps_completed'][] = $item;
            }
        } else {
            $installResults['errors'] = array_merge($installResults['errors'], $integrationResult['errors']);
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
        echo "URL Routing Component Installer\n";
        echo "=================================\n\n";
        
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
        <title>URL Routing Component - Installer</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #0056b3; }
            .detected-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .detected-info pre { margin: 10px 0; }
            .checkbox { margin: 15px 0; }
            .checkbox input { margin-right: 8px; }
        </style>
    </head>
    <body>
        <h1>URL Routing Component - Installer</h1>
        
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
                    <li>Access the route management page at: <a href="<?php echo htmlspecialchars($detected['base_url']); ?>/admin/components/url_routing/admin/routes.php">Route Management</a></li>
                    <li>Create your first routes or use existing ones</li>
                    <li>Test your clean URLs!</li>
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
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="migrate_menus" value="yes">
                            Migrate existing menu items to routes (optional)
                        </label>
                    </div>
                    <button type="submit">Install URL Routing Component</button>
                </form>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

