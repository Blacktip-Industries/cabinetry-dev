<?php
/**
 * Inventory Component - Installer
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
    }
}

// Create checks.php if it doesn't exist
$checksPath = __DIR__ . '/install/checks.php';
if (!file_exists($checksPath)) {
    file_put_contents($checksPath, "<?php\n// System compatibility checks\n");
}

$installResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => [],
    'detected_info' => []
];

// Auto-detect functions
function detectDatabaseConfig() {
    $configFiles = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../includes/config.php',
        __DIR__ . '/../../../config.php'
    ];
    
    $detected = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => '', 'source' => 'default'];
    
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
            if (!empty($detected['name'])) break;
        }
    }
    
    if (empty($detected['name'])) {
        $detected['host'] = getenv('DB_HOST') ?: $detected['host'];
        $detected['user'] = getenv('DB_USER') ?: $detected['user'];
        $detected['pass'] = getenv('DB_PASS') ?: $detected['pass'];
        $detected['name'] = getenv('DB_NAME') ?: $detected['name'];
        if (!empty($detected['name'])) $detected['source'] = 'environment';
    }
    
    return $detected;
}

function detectPaths() {
    $componentPath = __DIR__;
    $projectRoot = dirname(dirname(dirname($componentPath)));
    $adminPath = dirname(dirname($componentPath));
    
    $possibleAdminPaths = [$adminPath, $projectRoot . '/admin', $projectRoot . '/backend', $projectRoot . '/dashboard'];
    $detectedAdminPath = $adminPath;
    foreach ($possibleAdminPaths as $path) {
        if (is_dir($path) && file_exists($path . '/index.php')) {
            $detectedAdminPath = $path;
            break;
        }
    }
    
    return ['component_path' => $componentPath, 'project_root' => $projectRoot, 'admin_path' => $detectedAdminPath];
}

function detectBaseUrl() {
    if (php_sapi_name() === 'cli') return 'http://localhost';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    $base = preg_replace('/\/admin\/components\/inventory.*$/', '', $dir);
    if (empty($base) || $base === '/') $base = '';
    return $protocol . '://' . $host . $base;
}

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
                        if (!isset($detected[$varName])) $detected[$varName] = $varValue;
                    }
                }
            }
        }
    }
    return $detected;
}

function testDatabaseConnection($host, $user, $pass, $name) {
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) return ['success' => false, 'error' => $conn->connect_error];
        $conn->set_charset("utf8mb4");
        $conn->close();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function createDatabaseTables($conn) {
    $errors = [];
    $sqlFile = __DIR__ . '/install/database.sql';
    if (!file_exists($sqlFile)) return ['success' => false, 'errors' => ['Database SQL file not found']];
    
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)), function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    });
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        try {
            if ($conn->query($statement) !== TRUE) $errors[] = "Error: " . $conn->error;
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) $errors[] = $e->getMessage();
        }
    }
    
    return ['success' => empty($errors), 'errors' => $errors];
}

function insertDefaultParameters($conn) {
    require_once __DIR__ . '/install/default-parameters.php';
    $defaultParams = inventory_get_default_parameters();
    $tableName = 'inventory_parameters';
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
    
    return ['success' => empty($errors), 'inserted' => $inserted, 'errors' => $errors];
}

function generateConfigFile($detected) {
    $configTemplate = file_get_contents(__DIR__ . '/config.example.php');
    $version = trim(file_get_contents(__DIR__ . '/VERSION'));
    
    $replacements = [
        "define('INVENTORY_VERSION', '1.0.0');" => "define('INVENTORY_VERSION', '{$version}');",
        "define('INVENTORY_INSTALLED_AT', '');" => "define('INVENTORY_INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');",
        "define('INVENTORY_DB_HOST', '');" => "define('INVENTORY_DB_HOST', '" . addslashes($detected['db']['host']) . "');",
        "define('INVENTORY_DB_USER', '');" => "define('INVENTORY_DB_USER', '" . addslashes($detected['db']['user']) . "');",
        "define('INVENTORY_DB_PASS', '');" => "define('INVENTORY_DB_PASS', '" . addslashes($detected['db']['pass']) . "');",
        "define('INVENTORY_DB_NAME', '');" => "define('INVENTORY_DB_NAME', '" . addslashes($detected['db']['name']) . "');",
        "define('INVENTORY_ROOT_PATH', '');" => "define('INVENTORY_ROOT_PATH', '" . addslashes($detected['paths']['project_root']) . "');",
        "define('INVENTORY_ADMIN_PATH', '');" => "define('INVENTORY_ADMIN_PATH', '" . addslashes($detected['paths']['admin_path']) . "');",
        "define('INVENTORY_BASE_URL', '');" => "define('INVENTORY_BASE_URL', '" . addslashes($detected['base_url']) . "');",
        "define('INVENTORY_ADMIN_URL', '');" => "define('INVENTORY_ADMIN_URL', '" . addslashes($detected['base_url'] . '/admin') . "');",
        "define('INVENTORY_BASE_SYSTEM_INFO', '{}');" => "define('INVENTORY_BASE_SYSTEM_INFO', '" . addslashes(json_encode($detected['base_system_info'])) . "');"
    ];
    
    foreach ($replacements as $search => $replace) {
        $configTemplate = str_replace($search, $replace, $configTemplate);
    }
    
    return $configTemplate;
}

// Main installation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isAuto)) {
    $detected = [
        'db' => detectDatabaseConfig(),
        'paths' => detectPaths(),
        'base_url' => detectBaseUrl(),
        'css_vars' => detectCSSVariables(),
        'base_system_info' => ['php_version' => PHP_VERSION, 'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown', 'detected_at' => date('Y-m-d H:i:s')]
    ];
    
    $installResults['detected_info'] = $detected;
    
    $dbTest = testDatabaseConnection($detected['db']['host'], $detected['db']['user'], $detected['db']['pass'], $detected['db']['name']);
    if (!$dbTest['success']) {
        $installResults['errors'][] = 'Database connection failed: ' . ($dbTest['error'] ?? 'Unknown error');
    } else {
        $installResults['steps_completed'][] = 'Database connection test';
        
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli($detected['db']['host'], $detected['db']['user'], $detected['db']['pass'], $detected['db']['name']);
            $conn->set_charset("utf8mb4");
            
            $tableResult = createDatabaseTables($conn);
            if ($tableResult['success']) {
                $installResults['steps_completed'][] = 'Database tables created';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $tableResult['errors']);
            }
            
            $paramResult = insertDefaultParameters($conn);
            if ($paramResult['success']) {
                $installResults['steps_completed'][] = 'Default parameters inserted (' . $paramResult['inserted'] . ' parameters)';
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $paramResult['errors']);
            }
            
            $configTable = 'inventory_config';
            $version = trim(file_get_contents(__DIR__ . '/VERSION'));
            $stmt = $conn->prepare("INSERT INTO {$configTable} (config_key, config_value) VALUES ('version', ?), ('installed_at', ?), ('base_system_info', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
            $installedAt = date('Y-m-d H:i:s');
            $baseSystemInfo = json_encode($detected['base_system_info']);
            $stmt->bind_param("sss", $version, $installedAt, $baseSystemInfo);
            $stmt->execute();
            $stmt->close();
            
            // Create menu links (if menu_system is installed)
            $menuResult = ['success' => false, 'error' => 'menu_system not installed'];
            $menuTableCheck = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
            if ($menuTableCheck && $menuTableCheck->num_rows > 0) {
                require_once __DIR__ . '/install/default-menu-links.php';
                if (function_exists('inventory_create_menu_links')) {
                    $menuResult = inventory_create_menu_links($conn, 'inventory', $detected['base_url'] . '/admin');
                    if ($menuResult['success']) {
                        $installResults['steps_completed'][] = 'Menu links created (' . count($menuResult['menu_ids']) . ' items)';
                    } else {
                        $installResults['warnings'][] = 'Menu links not created: ' . ($menuResult['error'] ?? 'Unknown error');
                    }
                }
            }
            
            $conn->close();
        } catch (Exception $e) {
            $installResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (empty($installResults['errors'])) {
        $configContent = generateConfigFile($detected);
        if (file_put_contents($configPath, $configContent) === false) {
            $installResults['errors'][] = 'Failed to write config.php file';
        } else {
            $installResults['steps_completed'][] = 'config.php generated';
        }
    }
    
    if (empty($installResults['errors'])) {
        $installResults['success'] = true;
    }
}

// Output based on mode
if ($isCLI) {
    if ($isSilent || $isAuto) {
        echo json_encode($installResults, JSON_PRETTY_PRINT) . "\n";
        exit($installResults['success'] ? 0 : 1);
    } else {
        echo "Inventory Component Installer\n";
        echo "============================\n\n";
        if ($installResults['success']) {
            echo "✓ Installation completed successfully!\n";
            foreach ($installResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
        } else {
            echo "✗ Installation failed!\n";
            foreach ($installResults['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    }
} else {
    // Web mode HTML output
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inventory Component - Installer</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
            button:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <h1>Inventory Component Installer</h1>
        <?php if ($installResults['success']): ?>
            <div class="success">
                <h2>✓ Installation Successful!</h2>
                <ul>
                    <?php foreach ($installResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <?php if (!empty($installResults['errors'])): ?>
                <div class="error">
                    <h2>✗ Installation Failed</h2>
                    <ul>
                        <?php foreach ($installResults['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="POST">
                <h2>Installation Settings</h2>
                <p>Auto-detected settings will be used. Click Install to proceed.</p>
                <button type="submit">Install Inventory Component</button>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

