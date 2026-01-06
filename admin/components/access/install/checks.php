<?php
/**
 * Access Component - System Compatibility Checks
 */

class AccessInstallerChecks {
    private $errors = [];
    private $warnings = [];
    private $info = [];
    
    /**
     * Run all compatibility checks
     * @return array ['success' => bool, 'errors' => array, 'warnings' => array, 'info' => array]
     */
    public function runAllChecks() {
        $this->checkPHPVersion();
        $this->checkPHPExtensions();
        $this->checkFilePermissions();
        $this->checkDatabaseConnection();
        
        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'info' => $this->info
        ];
    }
    
    /**
     * Check PHP version (require 7.4+)
     */
    private function checkPHPVersion() {
        $version = PHP_VERSION;
        if (version_compare($version, '7.4.0', '<')) {
            $this->errors[] = "PHP version 7.4 or higher is required. Current version: {$version}";
        } else {
            $this->info[] = "PHP version: {$version} ✓";
        }
    }
    
    /**
     * Check required PHP extensions
     */
    private function checkPHPExtensions() {
        $required = ['mysqli', 'json', 'mbstring'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (!empty($missing)) {
            $this->errors[] = "Missing required PHP extensions: " . implode(', ', $missing);
        } else {
            $this->info[] = "Required PHP extensions: " . implode(', ', $required) . " ✓";
        }
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions() {
        $componentPath = __DIR__ . '/..';
        $projectRoot = dirname(dirname(dirname($componentPath)));
        
        $writablePaths = [
            $componentPath,
            $componentPath . '/assets/css',
        ];
        
        foreach ($writablePaths as $path) {
            if (!is_dir($path)) {
                // Try to create it
                if (!@mkdir($path, 0755, true)) {
                    $this->warnings[] = "Directory does not exist and cannot be created: {$path}";
                }
            } elseif (!is_writable($path)) {
                $this->warnings[] = "Directory not writable: {$path} (may need to set permissions manually)";
            } else {
                $this->info[] = "Writable: {$path} ✓";
            }
        }
    }
    
    /**
     * Check database connection (try to detect from common config files)
     */
    private function checkDatabaseConnection() {
        $detected = $this->detectDatabaseConfig();
        
        if ($detected) {
            $this->info[] = "Database config detected: " . $detected['host'] . " / " . $detected['database'];
            
            // Try to connect
            try {
                $conn = @new mysqli($detected['host'], $detected['user'], $detected['pass'], $detected['database']);
                if ($conn->connect_error) {
                    $this->errors[] = "Database connection failed: " . $conn->connect_error;
                } else {
                    $this->info[] = "Database connection successful ✓";
                    $conn->close();
                }
            } catch (Exception $e) {
                $this->errors[] = "Database connection error: " . $e->getMessage();
            }
        } else {
            $this->warnings[] = "Database configuration not auto-detected. Will need manual input.";
        }
    }
    
    /**
     * Detect database configuration from common config files
     * @return array|null Detected config or null
     */
    public function detectDatabaseConfig() {
        $projectRoot = dirname(dirname(dirname(dirname(__DIR__))));
        $configFiles = [
            $projectRoot . '/config/database.php',
            $projectRoot . '/includes/config.php',
            $projectRoot . '/config.php',
            $projectRoot . '/admin/includes/config.php'
        ];
        
        foreach ($configFiles as $configFile) {
            if (file_exists($configFile)) {
                $config = $this->parseDatabaseConfig($configFile);
                if ($config) {
                    return $config;
                }
            }
        }
        
        // Try environment variables
        if (getenv('DB_HOST')) {
            return [
                'host' => getenv('DB_HOST'),
                'user' => getenv('DB_USER') ?: 'root',
                'pass' => getenv('DB_PASS') ?: '',
                'database' => getenv('DB_NAME') ?: ''
            ];
        }
        
        return null;
    }
    
    /**
     * Parse database configuration from PHP file
     * @param string $configFile Path to config file
     * @return array|null Detected config or null
     */
    private function parseDatabaseConfig($configFile) {
        $content = file_get_contents($configFile);
        if (!$content) {
            return null;
        }
        
        $config = [
            'host' => null,
            'user' => null,
            'pass' => null,
            'database' => null
        ];
        
        // Try to extract DB constants
        if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            $config['host'] = $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            $config['user'] = $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]DB_PASS['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $content, $matches)) {
            $config['pass'] = $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            $config['database'] = $matches[1];
        }
        
        // Check if all required values found
        if ($config['host'] && $config['user'] && $config['database'] !== null) {
            return $config;
        }
        
        return null;
    }
    
    /**
     * Detect base system paths
     * @return array Detected paths
     */
    public function detectPaths() {
        $componentPath = __DIR__ . '/..';
        $projectRoot = dirname(dirname(dirname($componentPath)));
        
        // Try to detect admin directory
        $adminPath = null;
        $possibleAdminDirs = ['admin', 'backend', 'dashboard', 'cp'];
        
        foreach ($possibleAdminDirs as $dir) {
            $testPath = $projectRoot . '/' . $dir;
            if (is_dir($testPath)) {
                $adminPath = $testPath;
                break;
            }
        }
        
        if (!$adminPath) {
            // Default to admin
            $adminPath = $projectRoot . '/admin';
        }
        
        return [
            'component_path' => $componentPath,
            'project_root' => $projectRoot,
            'admin_path' => $adminPath
        ];
    }
    
    /**
     * Detect base system CSS variables
     * @return array Detected variables
     */
    public function detectCSSVariables() {
        $projectRoot = dirname(dirname(dirname(dirname(__DIR__))));
        $cssFiles = [
            $projectRoot . '/admin/assets/css/admin.css',
            $projectRoot . '/admin/assets/css/layout.css',
            $projectRoot . '/assets/css/main.css',
            $projectRoot . '/css/main.css'
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
     * Test database connection
     * @param string $host Database host
     * @param string $user Database user
     * @param string $pass Database password
     * @param string $name Database name
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function testDatabaseConnection($host, $user, $pass, $name) {
        try {
            $conn = @new mysqli($host, $user, $pass, $name);
            if ($conn->connect_error) {
                return ['success' => false, 'error' => $conn->connect_error];
            }
            $conn->close();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

