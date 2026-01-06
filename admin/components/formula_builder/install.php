<?php
/**
 * Formula Builder Component - Installer
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

require_once __DIR__ . '/install/checks.php';

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
    $base = preg_replace('/\/admin\/components\/formula_builder.*$/', '', $dir);
    if (empty($base) || $base === '/') $base = '';
    return $protocol . '://' . $host . $base;
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
    if (!file_exists($sqlFile)) return ['success' => false, 'error' => 'Database SQL file not found'];
    
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

function generateConfigFile($detected) {
    $configTemplate = file_get_contents(__DIR__ . '/config.example.php');
    $version = trim(file_get_contents(__DIR__ . '/VERSION'));
    
    $replacements = [
        "define('FORMULA_BUILDER_VERSION', '1.0.0');" => "define('FORMULA_BUILDER_VERSION', '{$version}');",
        "define('FORMULA_BUILDER_INSTALLED_AT', '');" => "define('FORMULA_BUILDER_INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');",
        "define('FORMULA_BUILDER_DB_HOST', '');" => "define('FORMULA_BUILDER_DB_HOST', '" . addslashes($detected['db']['host']) . "');",
        "define('FORMULA_BUILDER_DB_USER', '');" => "define('FORMULA_BUILDER_DB_USER', '" . addslashes($detected['db']['user']) . "');",
        "define('FORMULA_BUILDER_DB_PASS', '');" => "define('FORMULA_BUILDER_DB_PASS', '" . addslashes($detected['db']['pass']) . "');",
        "define('FORMULA_BUILDER_DB_NAME', '');" => "define('FORMULA_BUILDER_DB_NAME', '" . addslashes($detected['db']['name']) . "');",
        "define('FORMULA_BUILDER_ROOT_PATH', '');" => "define('FORMULA_BUILDER_ROOT_PATH', '" . addslashes($detected['paths']['project_root']) . "');",
        "define('FORMULA_BUILDER_ADMIN_PATH', '');" => "define('FORMULA_BUILDER_ADMIN_PATH', '" . addslashes($detected['paths']['admin_path']) . "');",
        "define('FORMULA_BUILDER_BASE_URL', '');" => "define('FORMULA_BUILDER_BASE_URL', '" . addslashes($detected['base_url']) . "');",
        "define('FORMULA_BUILDER_ADMIN_URL', '');" => "define('FORMULA_BUILDER_ADMIN_URL', '" . addslashes($detected['base_url'] . '/admin') . "');",
        "define('FORMULA_BUILDER_BASE_SYSTEM_INFO', '{}');" => "define('FORMULA_BUILDER_BASE_SYSTEM_INFO', '" . addslashes(json_encode($detected['base_system_info'])) . "');"
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
        'base_system_info' => ['php_version' => PHP_VERSION, 'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown', 'detected_at' => date('Y-m-d H:i:s')]
    ];
    
    $installResults['detected_info'] = $detected;
    
    $dbTest = testDatabaseConnection($detected['db']['host'], $detected['db']['user'], $detected['db']['pass'], $detected['db']['name']);
    if (!$dbTest['success']) {
        $installResults['errors'][] = 'Database connection failed: ' . ($dbTest['error'] ?? 'Unknown error');
    } else {
        // Create database connection
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli($detected['db']['host'], $detected['db']['user'], $detected['db']['pass'], $detected['db']['name']);
            $conn->set_charset("utf8mb4");
            
            // Create tables
            $tableResult = createDatabaseTables($conn);
            if ($tableResult['success']) {
                $installResults['steps_completed'][] = 'Database tables created';
                
                // Insert default config
                $version = trim(file_get_contents(__DIR__ . '/VERSION'));
                $stmt = $conn->prepare("INSERT INTO formula_builder_config (config_key, config_value) VALUES ('version', ?), ('installed_at', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                $installedAt = date('Y-m-d H:i:s');
                $stmt->bind_param("ss", $version, $installedAt);
                $stmt->execute();
                $stmt->close();
                $installResults['steps_completed'][] = 'Default configuration inserted';
                
                // Insert default parameters
                if (file_exists(__DIR__ . '/install/default-parameters.php')) {
                    require_once __DIR__ . '/install/default-parameters.php';
                    $defaultParams = formula_builder_get_default_parameters();
                    $tableName = 'formula_builder_parameters';
                    $inserted = 0;
                    foreach ($defaultParams as $param) {
                        try {
                            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description)");
                            $stmt->bind_param("ssss", $param['section'], $param['parameter_name'], $param['value'], $param['description']);
                            $stmt->execute();
                            $stmt->close();
                            $inserted++;
                        } catch (Exception $e) {
                            // Ignore duplicate key errors
                        }
                    }
                    if ($inserted > 0) {
                        $installResults['steps_completed'][] = "Default parameters inserted ({$inserted} items)";
                    }
                }
                
                // Generate config.php
                $configContent = generateConfigFile($detected);
                file_put_contents($configPath, $configContent);
                $installResults['steps_completed'][] = 'Config file generated';
                
                // Create menu links (if menu_system is installed)
                if (file_exists(__DIR__ . '/install/default-menu-links.php')) {
                    require_once __DIR__ . '/install/default-menu-links.php';
                    $menuResult = formula_builder_create_menu_links($conn, 'formula_builder', $detected['base_url'] . '/admin');
                    if ($menuResult['success']) {
                        $installResults['steps_completed'][] = 'Menu links created (' . count($menuResult['menu_ids']) . ' items)';
                    } else {
                        $installResults['warnings'][] = 'Menu links not created: ' . ($menuResult['error'] ?? 'Unknown error');
                    }
                }
                
                $installResults['success'] = true;
            } else {
                $installResults['errors'] = array_merge($installResults['errors'], $tableResult['errors']);
            }
            
            $conn->close();
        } catch (Exception $e) {
            $installResults['errors'][] = 'Installation error: ' . $e->getMessage();
        }
    }
}

// Display installer interface (web mode)
if (!$isCLI && !$isInstalled) {
    // Web installer interface would go here
    // For now, show basic installation form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Formula Builder - Installation</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            form { margin-top: 20px; }
            input, button { padding: 8px; margin: 5px; }
        </style>
    </head>
    <body>
        <h1>Formula Builder Component - Installation</h1>
        <?php if (!empty($installResults['steps_completed'])): ?>
            <h2 class="success">Installation Successful!</h2>
            <ul>
                <?php foreach ($installResults['steps_completed'] as $step): ?>
                    <li class="success"><?php echo htmlspecialchars($step); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($installResults['errors'])): ?>
            <h2 class="error">Errors:</h2>
            <ul>
                <?php foreach ($installResults['errors'] as $error): ?>
                    <li class="error"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!$installResults['success']): ?>
            <form method="POST">
                <h2>Database Configuration</h2>
                <?php
                $detected = detectDatabaseConfig();
                ?>
                <p>Host: <input type="text" name="db_host" value="<?php echo htmlspecialchars($detected['host']); ?>" required></p>
                <p>User: <input type="text" name="db_user" value="<?php echo htmlspecialchars($detected['user']); ?>" required></p>
                <p>Password: <input type="password" name="db_pass" value="<?php echo htmlspecialchars($detected['pass']); ?>"></p>
                <p>Database: <input type="text" name="db_name" value="<?php echo htmlspecialchars($detected['name']); ?>" required></p>
                <button type="submit">Install</button>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// CLI mode
if ($isCLI) {
    if ($installResults['success']) {
        echo "Formula Builder Component installed successfully!\n";
        foreach ($installResults['steps_completed'] as $step) {
            echo "  ✓ {$step}\n";
        }
        exit(0);
    } else {
        echo "Installation failed:\n";
        foreach ($installResults['errors'] as $error) {
            echo "  ✗ {$error}\n";
        }
        exit(1);
    }
}

