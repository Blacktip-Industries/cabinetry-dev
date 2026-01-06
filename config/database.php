<?php
/**
 * Database Configuration
 * Centralized database connection and initialization
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cabinetry_dev');

/**
 * Get database connection
 * @return mysqli|null
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
            // Initialize system timezone if database is available
            // This ensures timezone is set early for all date/time operations
            try {
                // Ensure tables exist first
                createSettingsParametersTable($conn);
                setSystemTimezone();
            } catch (Exception $tzException) {
                // If parameter doesn't exist yet, use default
                date_default_timezone_set('Australia/Brisbane');
            }
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Initialize database tables
 * Creates users and admin_menus tables if they don't exist
 * @return bool
 */
function initializeDatabase() {
    $conn = getDBConnection();
    
    if ($conn === null) {
        return false;
    }
    
    // Create users table
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create admin_menus table
    $menusTable = "CREATE TABLE IF NOT EXISTS admin_menus (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT NULL,
        title VARCHAR(255) NOT NULL,
        icon VARCHAR(50),
        url VARCHAR(255),
        page_identifier VARCHAR(255) NULL,
        menu_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        menu_type ENUM('admin', 'frontend') DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES admin_menus(id) ON DELETE CASCADE,
        INDEX idx_parent (parent_id),
        INDEX idx_type (menu_type),
        INDEX idx_order (menu_order),
        INDEX idx_page_identifier (page_identifier)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create settings table
    $settingsTable = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_section VARCHAR(100),
        setting_label VARCHAR(255),
        setting_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key),
        INDEX idx_section (setting_section)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create page_columns table
    $pageColumnsTable = "CREATE TABLE IF NOT EXISTS page_columns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(255) UNIQUE NOT NULL,
        column_count INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_page (page_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create setup_icons table
    $setupIconsTable = "CREATE TABLE IF NOT EXISTS setup_icons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        svg_path TEXT NOT NULL,
        description TEXT,
        category VARCHAR(50),
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        style VARCHAR(20) DEFAULT NULL,
        fill TINYINT(1) DEFAULT NULL,
        weight INT DEFAULT NULL,
        grade INT DEFAULT NULL,
        opsz INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_category (category),
        INDEX idx_active (is_active),
        INDEX idx_order (display_order),
        INDEX idx_style (style),
        INDEX idx_fill (fill)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create footer_data table
    $footerDataTable = "CREATE TABLE IF NOT EXISTS footer_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(100),
        phone VARCHAR(50),
        email VARCHAR(255),
        fax VARCHAR(50),
        copyright_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create footer_links table
    $footerLinksTable = "CREATE TABLE IF NOT EXISTS footer_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        url VARCHAR(500) NOT NULL,
        icon_name VARCHAR(100),
        display_type ENUM('icon', 'icon_text', 'text') DEFAULT 'text',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create footer_social table
    $footerSocialTable = "CREATE TABLE IF NOT EXISTS footer_social (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(100) NOT NULL,
        url VARCHAR(500) NOT NULL,
        icon_name VARCHAR(100),
        display_type ENUM('icon', 'icon_text', 'text') DEFAULT 'icon_text',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create customers table
    $customersTable = "CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(50),
        company VARCHAR(255),
        address_line1 VARCHAR(255),
        address_line2 VARCHAR(255),
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(100) DEFAULT 'Australia',
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_city (city),
        INDEX idx_postal_code (postal_code),
        INDEX idx_location (latitude, longitude)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $success = true;
    
    if ($conn->query($usersTable) !== TRUE) {
        error_log("Error creating users table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($menusTable) !== TRUE) {
        error_log("Error creating admin_menus table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($settingsTable) !== TRUE) {
        error_log("Error creating settings table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($pageColumnsTable) !== TRUE) {
        error_log("Error creating page_columns table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($setupIconsTable) !== TRUE) {
        error_log("Error creating setup_icons table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($footerDataTable) !== TRUE) {
        error_log("Error creating footer_data table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($footerLinksTable) !== TRUE) {
        error_log("Error creating footer_links table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($footerSocialTable) !== TRUE) {
        error_log("Error creating footer_social table: " . $conn->error);
        $success = false;
    }
    
    if ($conn->query($customersTable) !== TRUE) {
        error_log("Error creating customers table: " . $conn->error);
        $success = false;
    }
    
    // Migrate existing settings table to add new columns if they don't exist
    migrateSettingsTable($conn);
    
    // Migrate admin_menus table to add page_identifier column if it doesn't exist
    migrateAdminMenusTable($conn);
    
    // Migrate setup_icons table - ensure it exists and seed default icons
    migrateSetupIconsTable($conn);
    
    // Migrate script management tables
    migrateSetupScriptsTable($conn);
    migrateScriptsSettingsTable($conn);
    migrateScriptsTemplatesTable($conn);
    migrateScriptsArchiveTable($conn);
    
    // Migrate file protection tables
    migrateProtectedFilesTable($conn);
    migrateFileBackupsTable($conn);
    
    // Migrate customers table
    migrateCustomersTable($conn);
    
    return $success;
}

/**
 * Migrate settings table to add new columns
 * @param mysqli $conn Database connection
 */
function migrateSettingsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    // Check if columns exist and add them if they don't
    $columnsToAdd = [
        'setting_section' => "ALTER TABLE settings ADD COLUMN setting_section VARCHAR(100) AFTER setting_value",
        'setting_label' => "ALTER TABLE settings ADD COLUMN setting_label VARCHAR(255) AFTER setting_section",
        'setting_description' => "ALTER TABLE settings ADD COLUMN setting_description TEXT AFTER setting_label"
    ];
    
    foreach ($columnsToAdd as $columnName => $alterQuery) {
        // Check if column exists
        $checkQuery = "SHOW COLUMNS FROM settings LIKE '$columnName'";
        $result = $conn->query($checkQuery);
        
        if ($result && $result->num_rows == 0) {
            // Column doesn't exist, add it
            if ($conn->query($alterQuery) !== TRUE) {
                error_log("Error adding column $columnName: " . $conn->error);
            }
        }
    }
    
    // Add index for setting_section if it doesn't exist
    $indexCheck = "SHOW INDEX FROM settings WHERE Key_name = 'idx_section'";
    $indexResult = $conn->query($indexCheck);
    if ($indexResult && $indexResult->num_rows == 0) {
        $conn->query("ALTER TABLE settings ADD INDEX idx_section (setting_section)");
    }
    
    // Update the search_bar_length setting with the new metadata
    $searchBarLengthId = getSettingIdByKey('search_bar_length');
    if ($searchBarLengthId) {
        $updateQuery = "UPDATE settings SET 
            setting_section = 'Header',
            setting_label = 'Search Bar Length (px)',
            setting_description = 'Set the maximum width of the search bar in pixels (100-2000px)'
            WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        if ($stmt) {
            $stmt->bind_param("i", $searchBarLengthId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Also update row with id=1 if it exists (for backward compatibility)
    $updateByIdQuery = "UPDATE settings SET 
        setting_section = 'Header',
        setting_label = 'Search Bar Length (px)',
        setting_description = 'Set the maximum width of the search bar in pixels (100-2000px)'
        WHERE id = 1";
    $conn->query($updateByIdQuery);
    
    // Initialize avatar_height setting if it doesn't exist
    $checkAvatarHeight = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'avatar_height'";
    $result = $conn->query($checkAvatarHeight);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertAvatarHeight = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('avatar_height', '30', 'Header', 'Avatar Height (px)', 'Set the height of the username circle icon in pixels (20-100px)')";
        $conn->query($insertAvatarHeight);
    } else {
        // Update existing avatar_height setting with metadata using ID
        $avatarHeightId = getSettingIdByKey('avatar_height');
        if ($avatarHeightId) {
            $updateAvatarHeight = "UPDATE settings SET 
                setting_section = 'Header',
                setting_label = 'Avatar Height (px)',
                setting_description = 'Set the height of the username circle icon in pixels (20-100px)'
                WHERE id = ?";
            $stmt = $conn->prepare($updateAvatarHeight);
            if ($stmt) {
                $stmt->bind_param("i", $avatarHeightId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Initialize menu_admin_width setting if it doesn't exist
    $checkMenuAdminWidth = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'menu_admin_width'";
    $result = $conn->query($checkMenuAdminWidth);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $menuAdminWidth = '280';
        $insertMenuAdminWidth = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('menu_admin_width', '$menuAdminWidth', 'Menu - Admin', 'Admin Menu Width (px)', 'Set the width of the admin sidebar menu in pixels. Current width: {$menuAdminWidth}px')";
        $conn->query($insertMenuAdminWidth);
    }
    
    // Initialize menu_frontend_width setting if it doesn't exist
    $checkMenuFrontendWidth = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'menu_frontend_width'";
    $result = $conn->query($checkMenuFrontendWidth);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $menuFrontendWidth = '280';
        $insertMenuFrontendWidth = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('menu_frontend_width', '$menuFrontendWidth', 'Menu - Frontend', 'Frontend Menu Width (px)', 'Set the width of the frontend sidebar menu in pixels. Current width: {$menuFrontendWidth}px')";
        $conn->query($insertMenuFrontendWidth);
    }
    
    // Initialize header_height setting if it doesn't exist
    $checkHeaderHeight = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'header_height'";
    $result = $conn->query($checkHeaderHeight);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $headerHeight = '100';
        $insertHeaderHeight = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('header_height', '$headerHeight', 'Layout', 'Header Height (px)', 'Set the height of the admin header in pixels. Current height: {$headerHeight}px')";
        $conn->query($insertHeaderHeight);
    }
    
    // Initialize footer_height setting if it doesn't exist
    $checkFooterHeight = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'footer_height'";
    $result = $conn->query($checkFooterHeight);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $footerHeight = '60';
        $insertFooterHeight = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('footer_height', '$footerHeight', 'Layout', 'Footer Height (px)', 'Set the height of the admin footer in pixels. Current height: {$footerHeight}px')";
        $conn->query($insertFooterHeight);
    }
    
    // Menu auto-creation removed - menu items must be manually created via the Menus page
    // syncSettingSectionMenus() call removed to prevent automatic menu creation
    
    // Migrate admin_menus table to add page_identifier column
    migrateAdminMenusTable($conn);
}

/**
 * Migrate admin_menus table to add page_identifier column
 * @param mysqli $conn Database connection
 */
function migrateAdminMenusTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    // Check if page_identifier column exists and add it if it doesn't
    $checkQuery = "SHOW COLUMNS FROM admin_menus LIKE 'page_identifier'";
    $result = $conn->query($checkQuery);
    
    if ($result && $result->num_rows == 0) {
        // Column doesn't exist, add it
        $alterQuery = "ALTER TABLE admin_menus ADD COLUMN page_identifier VARCHAR(255) NULL AFTER url";
        if ($conn->query($alterQuery) !== TRUE) {
            error_log("Error adding page_identifier column: " . $conn->error);
        }
    }
    
    // Check if is_section_heading column exists and add it if it doesn't
    $checkQuery2 = "SHOW COLUMNS FROM admin_menus LIKE 'is_section_heading'";
    $result2 = $conn->query($checkQuery2);
    
    if ($result2 && $result2->num_rows == 0) {
        // Column doesn't exist, add it
        $alterQuery2 = "ALTER TABLE admin_menus ADD COLUMN is_section_heading TINYINT(1) DEFAULT 0 AFTER is_active";
        if ($conn->query($alterQuery2) !== TRUE) {
            error_log("Error adding is_section_heading column: " . $conn->error);
        }
    }
    
    // Check if section_heading_id column exists and add it if it doesn't
    $checkQuery3 = "SHOW COLUMNS FROM admin_menus LIKE 'section_heading_id'";
    $result3 = $conn->query($checkQuery3);
    
    if ($result3 && $result3->num_rows == 0) {
        // Column doesn't exist, add it
        $alterQuery3 = "ALTER TABLE admin_menus ADD COLUMN section_heading_id INT NULL AFTER parent_id";
        if ($conn->query($alterQuery3) !== TRUE) {
            error_log("Error adding section_heading_id column: " . $conn->error);
        } else {
            // Add foreign key constraint if possible
            $fkQuery = "ALTER TABLE admin_menus ADD CONSTRAINT fk_section_heading FOREIGN KEY (section_heading_id) REFERENCES admin_menus(id) ON DELETE SET NULL";
            @$conn->query($fkQuery); // Use @ to suppress error if constraint already exists or fails
        }
    }
    
    // Check if is_pinned column exists and add it if it doesn't
    $checkQuery4 = "SHOW COLUMNS FROM admin_menus LIKE 'is_pinned'";
    $result4 = $conn->query($checkQuery4);
    
    if ($result4 && $result4->num_rows == 0) {
        // Column doesn't exist, add it
        $alterQuery4 = "ALTER TABLE admin_menus ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 AFTER is_section_heading";
        if ($conn->query($alterQuery4) !== TRUE) {
            error_log("Error adding is_pinned column: " . $conn->error);
        }
    }
}

/**
 * Get setting ID by key
 * @param string $key Setting key
 * @return int|null Setting ID or null if not found
 */
function getSettingIdByKey($key) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['id'] : null;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}

/**
 * Get page column ID by page name
 * @param string $pageName Page name
 * @return int|null Page column ID or null if not found
 */
function getPageColumnIdByName($pageName) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id FROM page_columns WHERE page_name = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $pageName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['id'] : null;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}

/**
 * Get admin menu ID by title, parent_id, and menu_type
 * @param string $title Menu title
 * @param int|null $parentId Parent menu ID
 * @param string $menuType Menu type ('admin' or 'frontend')
 * @return int|null Menu ID or null if not found
 */
function getAdminMenuIdByTitle($title, $parentId, $menuType = 'admin') {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        if ($parentId === null) {
            $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = ? AND parent_id IS NULL AND menu_type = ? LIMIT 1");
            $stmt->bind_param("ss", $title, $menuType);
        } else {
            $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = ? AND parent_id = ? AND menu_type = ? LIMIT 1");
            $stmt->bind_param("sis", $title, $parentId, $menuType);
        }
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['id'] : null;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}

/**
 * Get a setting value by key
 * @param string $key Setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed Setting value or default
 */
function getSetting($key, $default = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        // First try the old settings table
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return $row['setting_value'];
        }
        
        // If not found, try the new parameters table (for backward compatibility)
        // Check if parameter_name matches (could be CSS variable format or plain key)
        $stmt = $conn->prepare("SELECT value FROM settings_parameters WHERE parameter_name = ? OR parameter_name = ? LIMIT 1");
        if ($stmt) {
            $cssVarName = '--' . str_replace('_', '-', strtolower($key));
            $stmt->bind_param("ss", $key, $cssVarName);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                return $row['value'];
            }
        }
        
        return $default;
    } catch (mysqli_sql_exception $e) {
        // Try to create the table if it doesn't exist
        try {
            $settingsTable = "CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_section VARCHAR(100),
                setting_label VARCHAR(255),
                setting_description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key (setting_key),
                INDEX idx_section (setting_section)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->query($settingsTable);
            migrateSettingsTable($conn);
        } catch (Exception $createException) {
            // Silently fail if table creation fails
        }
        
        return $default;
    }
}

/**
 * Set a setting value by key
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success
 */
function setSetting($key, $value) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Check if setting exists by getting ID
    $settingId = getSettingIdByKey($key);
    
    if ($settingId) {
        // Update existing setting using ID
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $value, $settingId);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        // Insert new setting
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ss", $key, $value);
        $success = $stmt->execute();
        $stmt->close();
    }
    
    return $success;
}

/**
 * Get all settings grouped by section
 * @return array Settings grouped by section
 */
function getAllSettingsBySection() {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, setting_key, setting_value, setting_section, setting_label, setting_description FROM settings ORDER BY setting_section ASC, setting_label ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = [];
        
        while ($row = $result->fetch_assoc()) {
            $section = $row['setting_section'] ?: 'General';
            if (!isset($settings[$section])) {
                $settings[$section] = [];
            }
            $settings[$section][] = $row;
        }
        
        $stmt->close();
        
        // Sort sections alphabetically
        ksort($settings);
        
        // Sort settings within each section by label
        foreach ($settings as $section => &$sectionSettings) {
            usort($sectionSettings, function($a, $b) {
                $labelA = $a['setting_label'] ?: $a['setting_key'];
                $labelB = $b['setting_label'] ?: $b['setting_key'];
                return strcasecmp($labelA, $labelB);
            });
        }
        unset($sectionSettings);
        
        return $settings;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/**
 * Sync setting sections as sub-menus under Settings
 * This function automatically creates/updates/deletes sub-menu items based on setting sections
 * @return bool Success
 */
function syncSettingSectionMenus() {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Get all unique setting sections
        $stmt = $conn->prepare("SELECT DISTINCT setting_section FROM settings WHERE setting_section IS NOT NULL AND setting_section != '' ORDER BY setting_section ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row['setting_section'];
        }
        $stmt->close();
        
        // Find the Settings parent menu item
        $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = 'Settings' AND menu_type = 'admin' AND parent_id IS NULL LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $settingsParent = $result->fetch_assoc();
        $stmt->close();
        
        if (!$settingsParent) {
            return false; // Settings menu not found
        }
        
        $settingsParentId = $settingsParent['id'];
        
        // First, update any old URLs directly to new paths (catch-all update)
        $urlUpdates = [
            ['old' => '/admin/settings.php?section=Header', 'new' => '/admin/settings/parameters.php?section=Header'],
            ['old' => '/admin/settings.php?section=Layout', 'new' => '/admin/settings/parameters.php?section=Layout'],
            ['old' => '/admin/settings.php?section=Menu', 'new' => '/admin/settings/parameters.php?section=Menu'],
            ['old' => '/admin/settings_header.php', 'new' => '/admin/settings/parameters.php?section=Header'],
            ['old' => '/admin/settings_layout.php', 'new' => '/admin/settings/parameters.php?section=Layout'],
            ['old' => '/admin/settings_menu.php', 'new' => '/admin/settings/parameters.php?section=Menu'],
        ];
        
        foreach ($urlUpdates as $update) {
            // First get the menu ID by url, parent_id, and menu_type
            $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE url = ? AND parent_id = ? AND menu_type = 'admin' LIMIT 1");
            $stmt->bind_param("si", $update['old'], $settingsParentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $menuRow = $result->fetch_assoc();
            $stmt->close();
            
            if ($menuRow) {
                // Update using ID
                $updateStmt = $conn->prepare("UPDATE admin_menus SET url = ? WHERE id = ?");
                $updateStmt->bind_param("si", $update['new'], $menuRow['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
        
        // Also update URLs that contain section parameters
        $stmt = $conn->prepare("SELECT id, url FROM admin_menus WHERE parent_id = ? AND menu_type = 'admin' AND url LIKE '/admin/settings.php?section=%'");
        $stmt->bind_param("i", $settingsParentId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $oldUrl = $row['url'];
            parse_str(parse_url($oldUrl, PHP_URL_QUERY), $params);
            if (isset($params['section'])) {
                $section = $params['section'];
                $newUrl = null;
                if ($section === 'Header') {
                    $newUrl = '/admin/settings/parameters.php?section=Header';
                } elseif ($section === 'Layout') {
                    $newUrl = '/admin/settings/parameters.php?section=Layout';
                } elseif ($section === 'Menu') {
                    $newUrl = '/admin/settings/parameters.php?section=Menu';
                } elseif ($section === 'Menu - Admin') {
                    $newUrl = '/admin/settings/parameters.php?section=Menu - Admin';
                } elseif ($section === 'Menu - Frontend') {
                    $newUrl = '/admin/settings/parameters.php?section=Menu - Frontend';
                }
                if ($newUrl) {
                    $updateStmt = $conn->prepare("UPDATE admin_menus SET url = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $newUrl, $row['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        }
        $stmt->close();
        
        // Get existing setting section sub-menus (both old and new format)
        // This will catch all settings-related menu items under Settings parent
        $stmt = $conn->prepare("SELECT id, title, url FROM admin_menus WHERE parent_id = ? AND menu_type = 'admin'");
        $stmt->bind_param("i", $settingsParentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingMenus = [];
        while ($row = $result->fetch_assoc()) {
            // Only include menus that are settings-related (by URL or by known titles)
            // Exclude Parameters as it's handled separately
            $url = $row['url'];
            $title = $row['title'];
            $isSettingsMenu = (
                (strpos($url, '/admin/settings') !== false || 
                strpos($url, 'settings') !== false ||
                in_array($title, ['Header', 'Layout', 'Menu', 'Menu - Admin', 'Menu - Frontend', 'Footer'])) &&
                $title !== 'Parameters'
            );
            if ($isSettingsMenu) {
                $existingMenus[$title] = $row;
            }
        }
        $stmt->close();
        
        // Map sections to their dedicated pages
        $sectionPageMap = [
            'Footer' => '/admin/settings/footer.php',
        ];
        
        // Add Footer as a special case (it uses footer_data table, not settings table)
        if (!in_array('Footer', $sections)) {
            $sections[] = 'Footer';
        }
        
        // Auto-creation disabled - Parameters menu should be managed through the Menus page
        // Only update existing Parameters menu if it exists
        $parametersUrl = '/admin/settings/parameters.php';
        $parametersTitle = 'Parameters';
        $parametersPageIdentifier = 'settings_parameters';
        
        // Check if Parameters menu already exists
        $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = ? AND parent_id = ? AND menu_type = 'admin' LIMIT 1");
        $stmt->bind_param("si", $parametersTitle, $settingsParentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $parametersMenu = $result->fetch_assoc();
        $stmt->close();
        
        if ($parametersMenu) {
            // Update existing Parameters menu URL only (no auto-creation)
            $parametersMenuId = $parametersMenu['id'];
            $stmt = $conn->prepare("UPDATE admin_menus SET url = ?, page_identifier = ? WHERE id = ?");
            $stmt->bind_param("ssi", $parametersUrl, $parametersPageIdentifier, $parametersMenuId);
            $stmt->execute();
            $stmt->close();
        }
        // Auto-creation removed - menu items should be created manually through the Menus page
        
        // Delete any existing "Layout Table Test" menu item (should not appear in menu)
        $layoutTableTestId = getAdminMenuIdByTitle('Layout Table Test', $settingsParentId, 'admin');
        if ($layoutTableTestId) {
            $deleteStmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ?");
            $deleteStmt->bind_param("i", $layoutTableTestId);
            $deleteStmt->execute();
            $deleteStmt->close();
        }
        
        // Delete any existing "Header" menu item (should not appear in menu, use Parameters instead)
        $headerMenuId = getAdminMenuIdByTitle('Header', $settingsParentId, 'admin');
        if ($headerMenuId) {
            $deleteHeaderStmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ?");
            $deleteHeaderStmt->bind_param("i", $headerMenuId);
            $deleteHeaderStmt->execute();
            $deleteHeaderStmt->close();
        }
        
        // Delete any existing "Layout" menu item (should not appear in menu, use Parameters instead)
        $layoutMenuId = getAdminMenuIdByTitle('Layout', $settingsParentId, 'admin');
        if ($layoutMenuId) {
            $deleteLayoutStmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ?");
            $deleteLayoutStmt->bind_param("i", $layoutMenuId);
            $deleteLayoutStmt->execute();
            $deleteLayoutStmt->close();
        }
        
        // Delete any existing "Menu" menu item (should not appear in menu, use Parameters instead)
        $menuMenuId = getAdminMenuIdByTitle('Menu', $settingsParentId, 'admin');
        if ($menuMenuId) {
            $deleteMenuStmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ?");
            $deleteMenuStmt->bind_param("i", $menuMenuId);
            $deleteMenuStmt->execute();
            $deleteMenuStmt->close();
        }
        
        // Create or update sub-menus for each section
        $menuOrder = 1;
        $processedSections = [];
        foreach ($sections as $section) {
            // Skip "Layout Table Test" section - it should not appear in menu
            if ($section === 'Layout Table Test') {
                continue;
            }
            
            // Skip "Header" section - it should not appear in menu (use Parameters page instead)
            if ($section === 'Header') {
                continue;
            }
            
            // Skip "Layout" section - it should not appear in menu (use Parameters page instead)
            if ($section === 'Layout') {
                continue;
            }
            
            // Skip Menu sections - they should not appear in menu (use Parameters page instead)
            if ($section === 'Menu' || $section === 'Menu - Admin' || $section === 'Menu - Frontend') {
                continue;
            }
            
            // Map section to page URL
            $url = isset($sectionPageMap[$section]) ? $sectionPageMap[$section] : '/admin/settings.php?section=' . urlencode($section);
            
            $title = $section;
            
            $processedSections[] = $title;
            
            if (isset($existingMenus[$title])) {
                // Update existing menu URL only (no auto-creation)
                $menuId = $existingMenus[$title]['id'];
                $stmt = $conn->prepare("UPDATE admin_menus SET url = ? WHERE id = ?");
                $stmt->bind_param("si", $url, $menuId);
                $stmt->execute();
                $stmt->close();
                unset($existingMenus[$title]); // Remove from existing so we know it's still valid
            }
            // Auto-creation removed - new menu items should be created manually through the Menus page
            // Only existing menus are updated with new URLs
            $menuOrder++;
        }
        
        // Auto-deletion removed - menu items should be deleted manually through the Menus page
        // This prevents accidental deletion of menu items that users have customized
        
        return true;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/**
 * Get a single setting with all metadata
 * @param string $key Setting key
 * @return array|null Setting data or null
 */
function getSettingData($key) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, setting_key, setting_value, setting_section, setting_label, setting_description FROM settings WHERE setting_key = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}

/**
 * Get table border styles based on settings
 * @return string CSS style string for table borders
 */
function getTableBorderStyles() {
    $showBorder = getParameter('Layout Table Test', '--test-table-show-border', 'yes');
    $thickness = getParameter('Layout Table Test', '--test-table-border-thickness', '1');
    $color = getParameter('Layout Table Test', '--test-table-border-color', '#000000');
    
    if (strtolower($showBorder) === 'yes') {
        return "border: {$thickness}px solid {$color};";
    }
    return "border: none;";
}

/**
 * Get table border style for table element
 * @return string CSS style string for table element
 */
function getTableElementBorderStyle() {
    $showBorder = getParameter('Layout Table Test', '--test-table-show-border', 'yes');
    $thickness = getParameter('Layout Table Test', '--test-table-border-thickness', '1');
    $color = getParameter('Layout Table Test', '--test-table-border-color', '#000000');
    
    if (strtolower($showBorder) === 'yes') {
        return "border: {$thickness}px solid {$color};";
    }
    return "border: none;";
}

/**
 * Get table cell border style (for th and td)
 * @return string CSS style string for table cells
 */
function getTableCellBorderStyle() {
    $showBorder = getParameter('Layout Table Test', '--test-table-show-border', 'yes');
    $thickness = getParameter('Layout Table Test', '--test-table-border-thickness', '1');
    $color = getParameter('Layout Table Test', '--test-table-border-color', '#000000');
    
    if (strtolower($showBorder) === 'yes') {
        return "border: {$thickness}px solid {$color};";
    }
    return "border: none;";
}

/**
 * Get table cell padding value from settings
 * @return string Padding value in pixels
 */
function getTableCellPadding() {
    return getParameter('Layout Table Test', '--test-table-cellpadding', '8');
}

/**
 * Get column count for a specific page
 * @param string $pageName Page name (filename)
 * @return int Column count (default: 0 means no grid, full width)
 */
function getPageColumnCount($pageName) {
    $conn = getDBConnection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        // Try to create table if it doesn't exist
        $pageColumnsTable = "CREATE TABLE IF NOT EXISTS page_columns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_name VARCHAR(255) UNIQUE NOT NULL,
            column_count INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_page (page_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($pageColumnsTable);
        
        $stmt = $conn->prepare("SELECT column_count FROM page_columns WHERE page_name = ?");
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("s", $pageName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['column_count'] : 0;
    } catch (mysqli_sql_exception $e) {
        return 0;
    }
}

/**
 * Set column count for a specific page
 * @param string $pageName Page name (filename)
 * @param int $columnCount Column count (0-6, where 0 means no grid)
 * @return bool Success
 */
function setPageColumnCount($pageName, $columnCount) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Validate column count (0 means no grid, 1-6 means grid columns)
    $columnCount = max(0, min(6, (int)$columnCount));
    
    try {
        // Try to create table if it doesn't exist
        $pageColumnsTable = "CREATE TABLE IF NOT EXISTS page_columns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_name VARCHAR(255) UNIQUE NOT NULL,
            column_count INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_page (page_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($pageColumnsTable);
        
        $stmt = $conn->prepare("INSERT INTO page_columns (page_name, column_count) VALUES (?, ?) ON DUPLICATE KEY UPDATE column_count = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sii", $pageName, $columnCount, $columnCount);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/**
 * Get all page column settings
 * @return array Array of page column settings
 */
function getAllPageColumns() {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        // Try to create table if it doesn't exist
        $pageColumnsTable = "CREATE TABLE IF NOT EXISTS page_columns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_name VARCHAR(255) UNIQUE NOT NULL,
            column_count INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_page (page_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($pageColumnsTable);
        
        $stmt = $conn->prepare("SELECT id, page_name, column_count FROM page_columns ORDER BY page_name ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $pages = [];
        
        while ($row = $result->fetch_assoc()) {
            $pages[] = $row;
        }
        
        $stmt->close();
        return $pages;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/**
 * Delete page column setting
 * @param string $pageName Page name
 * @return bool Success
 */
function deletePageColumn($pageName) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Get ID by page_name first, then delete by ID
        $pageColumnId = getPageColumnIdByName($pageName);
        if (!$pageColumnId) {
            // If not found, return false
            return false;
        }
        
        $stmt = $conn->prepare("DELETE FROM page_columns WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $pageColumnId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/**
 * Migrate setup_icons table and seed default icons
 * @param mysqli $conn Database connection
 */
function migrateSetupIconsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    // Ensure table exists
    $setupIconsTable = "CREATE TABLE IF NOT EXISTS setup_icons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        svg_path TEXT NOT NULL,
        description TEXT,
        category VARCHAR(50),
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        style VARCHAR(20) DEFAULT NULL,
        fill TINYINT(1) DEFAULT NULL,
        weight INT DEFAULT NULL,
        grade INT DEFAULT NULL,
        opsz INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_category (category),
        INDEX idx_active (is_active),
        INDEX idx_order (display_order),
        INDEX idx_style (style),
        INDEX idx_fill (fill)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($setupIconsTable);
    
    // Add new columns if they don't exist (for existing databases)
    $columnsToAdd = [
        'style' => "ALTER TABLE setup_icons ADD COLUMN style VARCHAR(20) DEFAULT NULL AFTER display_order",
        'fill' => "ALTER TABLE setup_icons ADD COLUMN fill TINYINT(1) DEFAULT NULL AFTER style",
        'weight' => "ALTER TABLE setup_icons ADD COLUMN weight INT DEFAULT NULL AFTER fill",
        'grade' => "ALTER TABLE setup_icons ADD COLUMN grade INT DEFAULT NULL AFTER weight",
        'opsz' => "ALTER TABLE setup_icons ADD COLUMN opsz INT DEFAULT NULL AFTER grade"
    ];
    
    foreach ($columnsToAdd as $columnName => $sql) {
        $checkColumn = $conn->query("SHOW COLUMNS FROM setup_icons LIKE '$columnName'");
        if ($checkColumn->num_rows == 0) {
            $conn->query($sql);
        }
    }
    
    // Add indexes if they don't exist
    $indexesToAdd = [
        'idx_style' => "CREATE INDEX idx_style ON setup_icons(style)",
        'idx_fill' => "CREATE INDEX idx_fill ON setup_icons(fill)"
    ];
    
    foreach ($indexesToAdd as $indexName => $sql) {
        $checkIndex = $conn->query("SHOW INDEX FROM setup_icons WHERE Key_name = '$indexName'");
        if ($checkIndex->num_rows == 0) {
            $conn->query($sql);
        }
    }
}

/**
 * Add social media icons to the database if they don't already exist
 * @param mysqli $conn Database connection
 */
function addSocialMediaIcons($conn) {
    // Icon auto-creation disabled - icons must be manually added via icons.php
    // This function is kept for backward compatibility but no longer inserts icons
    return true;
    
    // Removed icon insertion code - icons must be manually created
    /*
    if ($conn === null) {
        return false;
    }
    
    $socialIcons = [
        ['name' => 'facebook', 'svg_path' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>', 'description' => 'Facebook icon - social media platform', 'category' => 'social', 'display_order' => 27],
        ['name' => 'twitter', 'svg_path' => '<path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>', 'description' => 'Twitter/X icon - social media platform', 'category' => 'social', 'display_order' => 28],
        ['name' => 'x-social', 'svg_path' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>', 'description' => 'X icon - social media platform (formerly Twitter)', 'category' => 'social', 'display_order' => 29],
        ['name' => 'instagram', 'svg_path' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>', 'description' => 'Instagram icon - social media platform', 'category' => 'social', 'display_order' => 30],
        ['name' => 'linkedin', 'svg_path' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle>', 'description' => 'LinkedIn icon - professional social network', 'category' => 'social', 'display_order' => 31],
        ['name' => 'youtube', 'svg_path' => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>', 'description' => 'YouTube icon - video sharing platform', 'category' => 'social', 'display_order' => 32],
        ['name' => 'pinterest', 'svg_path' => '<path d="M12 2C6.48 2 2 6.48 2 12c0 4.84 3.01 8.97 7.26 10.63-.1-.94-.19-2.4.04-3.43.21-1.39 1.35-9.38 1.35-9.38s-.34-.68-.34-1.69c0-1.58.92-2.76 2.06-2.76.97 0 1.44.73 1.44 1.6 0 .98-.62 2.44-.94 3.8-.27 1.14.57 2.07 1.7 2.07 2.04 0 3.61-2.15 3.61-5.25 0-2.74-1.96-4.66-4.76-4.66-3.24 0-5.14 2.43-5.14 4.94 0 .98.38 2.03.85 2.6.09.11.1.21.08.32-.08.33-.26 1.05-.3 1.2-.05.2-.16.24-.37.15-1.39-.65-2.26-2.68-2.26-4.32 0-3.52 2.56-6.76 7.38-6.76 3.87 0 6.88 2.76 6.88 6.45 0 3.85-2.43 6.94-5.8 6.94-1.13 0-2.2-.59-2.57-1.38l-.7 2.67c-.26 1.01-1.95 4.5-2.29 5.36-.18.68-.67 1.52-1 2.04 1.55.48 3.19.74 4.9.74 5.52 0 10-4.48 10-10S17.52 2 12 2z"></path>', 'description' => 'Pinterest icon - social media platform', 'category' => 'social', 'display_order' => 33],
        ['name' => 'tiktok', 'svg_path' => '<path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"></path>', 'description' => 'TikTok icon - video sharing platform', 'category' => 'social', 'display_order' => 34],
        ['name' => 'whatsapp', 'svg_path' => '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>', 'description' => 'WhatsApp icon - messaging platform', 'category' => 'social', 'display_order' => 35],
        ['name' => 'snapchat', 'svg_path' => '<path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.15c-.012.75-.022 1.5.005 2.25.032 1.03.09 2.07.24 3.09.06.39.12.78.24 1.16.12.38.3.74.54 1.06.24.32.54.6.9.82.36.22.78.38 1.2.44.42.06.84.03 1.26-.03.42-.06.84-.15 1.23-.3.39-.15.75-.33 1.08-.54.33-.21.63-.45.9-.72.27-.27.48-.57.66-.9.18-.33.3-.69.36-1.05.06-.36.06-.72 0-1.08-.06-.36-.18-.72-.36-1.05-.18-.33-.39-.63-.66-.9-.27-.27-.57-.51-.9-.72-.33-.21-.69-.39-1.08-.54-.39-.15-.81-.24-1.23-.3-.42-.06-.84-.09-1.26-.03-.42.06-.84.22-1.2.44-.36.22-.66.5-.9.82-.24.32-.42.68-.54 1.06-.12.38-.18.77-.24 1.16-.15 1.02-.208 2.06-.24 3.09-.027.75-.017 1.5-.005 2.25l.003.15c.104 1.628.23 3.654-.299 4.847-1.583 3.545-4.94 3.821-5.93 3.821-.99 0-4.347-.276-5.93-3.821-.529-1.193-.403-3.219-.299-4.847l.003-.15c.012-.75.022-1.5-.005-2.25-.032-1.03-.09-2.07-.24-3.09-.06-.39-.12-.78-.24-1.16-.12-.38-.3-.74-.54-1.06-.24-.32-.54-.6-.9-.82-.36-.22-.78-.38-1.2-.44-.42-.06-.84-.03-1.26.03-.42.06-.84.15-1.23.3-.39.15-.75.33-1.08.54-.33.21-.63.45-.9.72-.27.27-.48.57-.66.9-.18.33-.3.69-.36 1.05-.06.36-.06.72 0 1.08.06.36.18.72.36 1.05.18.33.39.63.66.9.27.27.57.51.9.72.33.21.69.39 1.08.54.39.15.81.24 1.23.3.42.06.84.09 1.26.03.42-.06.84-.22 1.2-.44.36-.22.66-.5.9-.82.24-.32.42-.68.54-1.06.12-.38.18-.77.24-1.16.15-1.02.208-2.06.24-3.09.027-.75.017-1.5.005-2.25l-.003-.15c-.104-1.628-.23-3.654.299-4.847C7.859 1.069 11.216.793 12.206.793z"></path>', 'description' => 'Snapchat icon - social media platform', 'category' => 'social', 'display_order' => 36],
        ['name' => 'github', 'svg_path' => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>', 'description' => 'GitHub icon - code repository platform', 'category' => 'social', 'display_order' => 37],
        ['name' => 'discord', 'svg_path' => '<path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.21-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.336.698.748 1.362 1.21 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"></path>', 'description' => 'Discord icon - communication platform', 'category' => 'social', 'display_order' => 38],
        ['name' => 'reddit', 'svg_path' => '<circle cx="12" cy="12" r="10"></circle><path d="M12 2A10 10 0 0 0 2 12a10 10 0 0 0 10 10 10 10 0 0 0 10-10A10 10 0 0 0 12 2zm5.01 8.74c-.69 0-1.25.56-1.25 1.25a1.25 1.25 0 0 0 2.5 0c0-.69-.56-1.25-1.25-1.25zm-10 0c-.69 0-1.25.56-1.25 1.25a1.25 1.25 0 0 0 2.5 0c0-.69-.56-1.25-1.25-1.25zm4.99 5.5c-1.38 0-2.63-.56-3.54-1.47a.75.75 0 0 1 0-1.06.75.75 0 0 1 1.06 0c.63.63 1.51.98 2.48.98s1.85-.35 2.48-.98a.75.75 0 0 1 1.06 0 .75.75 0 0 1 0 1.06c-.91.91-2.16 1.47-3.54 1.47z"></path>', 'description' => 'Reddit icon - social news platform', 'category' => 'social', 'display_order' => 39],
    ];
    
        $stmt = $conn->prepare("INSERT IGNORE INTO setup_icons (name, svg_path, description, category, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($socialIcons as $icon) {
        $stmt->bind_param("ssssi", $icon['name'], $icon['svg_path'], $icon['description'], $icon['category'], $icon['display_order']);
        $stmt->execute();
    }
    $stmt->close();
    
    return true;
    */
}

/**
 * Sort icons: Default category first, then Favourites (alphabetically), then categories (alphabetically) with icons within each category (alphabetically)
 * @param array $icons Array of icons to sort
 * @return array Sorted array of icons
 */
function sortIconsForDisplay($icons) {
    usort($icons, function($a, $b) {
        // 1. Default category icons first
        $aIsDefault = (isset($a['category']) && strtolower($a['category']) === 'default');
        $bIsDefault = (isset($b['category']) && strtolower($b['category']) === 'default');
        
        if ($aIsDefault && !$bIsDefault) {
            return -1; // a comes first
        }
        if (!$aIsDefault && $bIsDefault) {
            return 1; // b comes first
        }
        if ($aIsDefault && $bIsDefault) {
            // Both are Default - sort alphabetically by name
            $aName = strtolower($a['name'] ?? '');
            $bName = strtolower($b['name'] ?? '');
            return strcmp($aName, $bName);
        }
        
        // 2. Favourites (display_order = 0) sorted alphabetically
        $aIsFavourite = (isset($a['display_order']) && (int)$a['display_order'] === 0);
        $bIsFavourite = (isset($b['display_order']) && (int)$b['display_order'] === 0);
        
        if ($aIsFavourite && !$bIsFavourite) {
            return -1; // a comes first
        }
        if (!$aIsFavourite && $bIsFavourite) {
            return 1; // b comes first
        }
        if ($aIsFavourite && $bIsFavourite) {
            // Both are favourites - sort alphabetically by name
            $aName = strtolower($a['name'] ?? '');
            $bName = strtolower($b['name'] ?? '');
            return strcmp($aName, $bName);
        }
        
        // 3. All other categories sorted alphabetically, with icons within each category sorted alphabetically
        $aCategory = strtolower($a['category'] ?? 'uncategorized');
        $bCategory = strtolower($b['category'] ?? 'uncategorized');
        
        // First compare by category
        $categoryCompare = strcmp($aCategory, $bCategory);
        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }
        
        // Same category - sort alphabetically by name
        $aName = strtolower($a['name'] ?? '');
        $bName = strtolower($b['name'] ?? '');
        return strcmp($aName, $bName);
    });
    
    return $icons;
}

/**
 * Migrate setup_icons table to remove is_active column
 * Deletes inactive icons and drops the is_active column and index
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateSetupIconsRemoveIsActive($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        // Check if is_active column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM setup_icons LIKE 'is_active'");
        if ($checkColumn && $checkColumn->num_rows > 0) {
            // Delete all icons where is_active = 0
            $deleteStmt = $conn->prepare("DELETE FROM setup_icons WHERE is_active = 0");
            if ($deleteStmt) {
                $deleteStmt->execute();
                $deletedCount = $deleteStmt->affected_rows;
                $deleteStmt->close();
                if ($deletedCount > 0) {
                    error_log("Deleted {$deletedCount} inactive icon(s) from setup_icons table");
                }
            }
            
            // Drop the index first (if it exists)
            $checkIndex = $conn->query("SHOW INDEX FROM setup_icons WHERE Key_name = 'idx_active'");
            if ($checkIndex && $checkIndex->num_rows > 0) {
                $conn->query("ALTER TABLE setup_icons DROP INDEX idx_active");
            }
            
            // Drop the column
            $conn->query("ALTER TABLE setup_icons DROP COLUMN is_active");
            
            return true;
        }
        return true; // Column doesn't exist, migration already done
    } catch (mysqli_sql_exception $e) {
        error_log("Error removing is_active from setup_icons: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all icons from database
 * @param string|null $sortOrder Sort order: 'name' for alphabetical by name, 'order' for by display_order then name, null for default
 * @return array Array of icons
 */
function getAllIcons($sortOrder = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        // Migrate setup_icons table to remove is_active column
        migrateSetupIconsRemoveIsActive($conn);
        
        // Ensure table exists
        $setupIconsTable = "CREATE TABLE IF NOT EXISTS setup_icons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            svg_path TEXT NOT NULL,
            description TEXT,
            category VARCHAR(50),
            display_order INT DEFAULT 0,
            style VARCHAR(20) DEFAULT NULL,
            fill TINYINT(1) DEFAULT NULL,
            weight INT DEFAULT NULL,
            grade INT DEFAULT NULL,
            opsz INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_category (category),
            INDEX idx_order (display_order),
            INDEX idx_style (style),
            INDEX idx_fill (fill)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($setupIconsTable);
        
        // Determine ORDER BY clause based on sort order
        $orderBy = "category ASC, display_order ASC, name ASC"; // Default for backward compatibility
        if ($sortOrder === "name") {
            $orderBy = "name ASC";
        } elseif ($sortOrder === "order") {
            $orderBy = "display_order ASC, name ASC";
        }
        
        $stmt = $conn->prepare("SELECT id, name, svg_path, description, category, style, fill, weight, grade, opsz, display_order FROM setup_icons ORDER BY " . $orderBy);
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $icons = [];
        
        while ($row = $result->fetch_assoc()) {
            $icons[] = $row;
        }
        
        $stmt->close();
        return $icons;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/**
 * Get icon by name
 * @param string $name Icon name
 * @return array|null Icon data or null
 */
function getIconByName($name) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        // Migrate setup_icons table to remove is_active column
        migrateSetupIconsRemoveIsActive($conn);
        
        // Ensure table exists
        $setupIconsTable = "CREATE TABLE IF NOT EXISTS setup_icons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            svg_path TEXT NOT NULL,
            description TEXT,
            category VARCHAR(50),
            display_order INT DEFAULT 0,
            style VARCHAR(20) DEFAULT NULL,
            fill TINYINT(1) DEFAULT NULL,
            weight INT DEFAULT NULL,
            grade INT DEFAULT NULL,
            opsz INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_category (category),
            INDEX idx_order (display_order),
            INDEX idx_style (style),
            INDEX idx_fill (fill)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($setupIconsTable);
        
        $stmt = $conn->prepare("SELECT id, name, svg_path, description, category, style, fill, weight, grade, opsz, display_order FROM setup_icons WHERE name = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $icon = $result->fetch_assoc();
        $stmt->close();
        
        return $icon;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}

/**
 * Save or update icon
 * @param array $iconData Icon data (id, name, svg_path, description, category, display_order)
 * @return array ['success' => bool, 'error' => string] Success status and error message
 */
function saveIcon($iconData) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $name = trim($iconData['name'] ?? '');
        $iconId = isset($iconData['id']) && $iconData['id'] > 0 ? (int)$iconData['id'] : 0;
        
        // Check for duplicate name (excluding current icon if editing)
        $checkStmt = $conn->prepare("SELECT id FROM setup_icons WHERE name = ? AND id != ?");
        if (!$checkStmt) {
            return ['success' => false, 'error' => 'Database query preparation failed'];
        }
        
        $checkStmt->bind_param("si", $name, $iconId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'error' => 'An icon with this name already exists. Please choose a different name.'];
        }
        $checkStmt->close();
        
        $style = $iconData['style'] ?? null;
        $fill = isset($iconData['fill']) ? (int)$iconData['fill'] : null;
        $weight = isset($iconData['weight']) ? (int)$iconData['weight'] : null;
        $grade = isset($iconData['grade']) ? (int)$iconData['grade'] : null;
        $opsz = isset($iconData['opsz']) ? (int)$iconData['opsz'] : null;
        
        if ($iconId > 0) {
            // Update existing - use primary key ID
            $stmt = $conn->prepare("UPDATE setup_icons SET name = ?, svg_path = ?, description = ?, category = ?, style = ?, fill = ?, weight = ?, grade = ?, opsz = ?, display_order = ? WHERE id = ?");
            if (!$stmt) {
                return ['success' => false, 'error' => 'Database query preparation failed: ' . $conn->error];
            }
            $stmt->bind_param("sssssiiiiii", $iconData['name'], $iconData['svg_path'], $iconData['description'], $iconData['category'], $style, $fill, $weight, $grade, $opsz, $iconData['display_order'], $iconId);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO setup_icons (name, svg_path, description, category, style, fill, weight, grade, opsz, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                return ['success' => false, 'error' => 'Database query preparation failed: ' . $conn->error];
            }
            $stmt->bind_param("sssssiiiii", $iconData['name'], $iconData['svg_path'], $iconData['description'], $iconData['category'], $style, $fill, $weight, $grade, $opsz, $iconData['display_order']);
        }
        
        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->error ?: $conn->error;
            $stmt->close();
            return ['success' => false, 'error' => 'Database error: ' . $error];
        }
        
        $stmt->close();
        return ['success' => true, 'error' => ''];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => 'Database exception: ' . $e->getMessage()];
    }
}

/**
 * Delete icon
 * @param int $id Icon ID
 * @return bool Success
 */
function deleteIcon($id) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Check if this is the default icon (cannot be deleted)
        $checkStmt = $conn->prepare("SELECT name FROM setup_icons WHERE id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $iconData = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($iconData && $iconData['name'] === '--icon-default') {
            return false; // Cannot delete default icon
        }
        
        $stmt = $conn->prepare("DELETE FROM setup_icons WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/**
 * Move icons from one category to another
 * @param string $oldCategory Old category name
 * @param string $newCategory New category name
 * @return array ['success' => bool, 'count' => int, 'error' => string]
 */
function moveIconsToCategory($oldCategory, $newCategory) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'count' => 0, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("UPDATE setup_icons SET category = ? WHERE category = ?");
        if (!$stmt) {
            return ['success' => false, 'count' => 0, 'error' => 'Database query preparation failed'];
        }
        
        $stmt->bind_param("ss", $newCategory, $oldCategory);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return ['success' => true, 'count' => $affectedRows, 'error' => ''];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'count' => 0, 'error' => 'Database exception: ' . $e->getMessage()];
    }
}

/**
 * Bulk insert icons
 * @param array $icons Array of icon data arrays
 * @return array ['success' => bool, 'inserted' => int, 'errors' => array]
 */
function bulkInsertIcons($icons) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'inserted' => 0, 'errors' => ['Database connection failed']];
    }
    
    $inserted = 0;
    $errors = [];
    
    try {
        $stmt = $conn->prepare("INSERT IGNORE INTO setup_icons (name, svg_path, description, category, style, fill, weight, grade, opsz, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'inserted' => 0, 'errors' => ['Database query preparation failed: ' . $conn->error]];
        }
        
        foreach ($icons as $icon) {
            $name = $icon['name'] ?? '';
            $svgPath = $icon['svg_path'] ?? '';
            $description = $icon['description'] ?? '';
            $category = $icon['category'] ?? null;
            $style = $icon['style'] ?? null;
            $fill = isset($icon['fill']) ? (int)$icon['fill'] : null;
            $weight = isset($icon['weight']) ? (int)$icon['weight'] : null;
            $grade = isset($icon['grade']) ? (int)$icon['grade'] : null;
            $opsz = isset($icon['opsz']) ? (int)$icon['opsz'] : null;
            $displayOrder = isset($icon['display_order']) ? (int)$icon['display_order'] : 0;
            
            $stmt->bind_param("sssssiiiii", $name, $svgPath, $description, $category, $style, $fill, $weight, $grade, $opsz, $displayOrder);
            
            if ($stmt->execute()) {
                // Check if row was actually inserted (INSERT IGNORE returns success even if duplicate)
                if ($stmt->affected_rows > 0) {
                    $inserted++;
                } else {
                    // Duplicate entry - this is expected for some icons, so we don't count it as an error
                    // But we could log it if needed
                }
            } else {
                $errors[] = "Failed to insert icon '{$name}': " . $stmt->error;
            }
        }
        
        $stmt->close();
        return ['success' => true, 'inserted' => $inserted, 'errors' => $errors];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'inserted' => $inserted, 'errors' => array_merge($errors, ['Database exception: ' . $e->getMessage()])];
    }
}

/**
 * Get icon usages - find where icons are referenced in the system
 * @param string $iconName Icon name to search for
 * @return array Array of usage locations
 */
function getIconUsages($iconName) {
    $usages = [];
    $conn = getDBConnection();
    if ($conn === null) {
        return $usages;
    }
    
    // Search in menus table
    try {
        $stmt = $conn->prepare("SELECT id, title, menu_location FROM menus WHERE icon = ?");
        if ($stmt) {
            $stmt->bind_param("s", $iconName);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $usages[] = [
                    'type' => 'menu',
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'location' => $row['menu_location']
                ];
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        // Continue searching other locations
    }
    
    // Add more usage searches as needed (footer, header, etc.)
    
    return $usages;
}

/**
 * Get icons by style
 * @param string $style Style variant (outlined, rounded, sharp)
 * @return array Array of icons
 */
function getIconsByStyle($style) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, name, svg_path, description, category, style, fill, weight, grade, opsz, display_order FROM setup_icons WHERE style = ? ORDER BY display_order ASC, name ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("s", $style);
        $stmt->execute();
        $result = $stmt->get_result();
        $icons = [];
        
        while ($row = $result->fetch_assoc()) {
            $icons[] = $row;
        }
        
        $stmt->close();
        return $icons;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/**
 * Get icon SVG from Iconify API with variable font parameters
 * @param string $iconName Base icon name
 * @param string $style Style variant (outlined, rounded, sharp)
 * @param int $fill Fill value (0 or 1)
 * @param int $weight Weight value (default 400)
 * @param int $grade Grade value (default 0)
 * @param int $opsz Optical size (default 24)
 * @return string|false SVG path content or false on failure
 */
function getIconSVGFromAPI($iconName, $style = 'outlined', $fill = 0, $weight = 400, $grade = 0, $opsz = 24) {
    $prefix = 'material-symbols-' . $style;
    $url = "https://api.iconify.design/{$prefix}/{$iconName}.svg?fill={$fill}&weight={$weight}&grade={$grade}&opticalSize={$opsz}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $svgContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Iconify API error for {$iconName}: HTTP {$httpCode}" . ($curlError ? " - {$curlError}" : ""));
        return false;
    }
    
    if ($svgContent === false || empty(trim($svgContent))) {
        error_log("Iconify API returned empty content for {$iconName}");
        return false;
    }
    
    // Log first API response for debugging
    static $debugLogged = false;
    if (!$debugLogged) {
        error_log("Sample API response for {$iconName}: " . substr($svgContent, 0, 500));
        $debugLogged = true;
    }
    
    // Extract all SVG content (paths, circles, rects, etc.)
    $dom = new DOMDocument();
    $libXmlErrors = libxml_use_internal_errors(true);
    $loaded = @$dom->loadXML($svgContent);
    libxml_use_internal_errors($libXmlErrors);
    
    if ($loaded) {
        $svgElement = $dom->getElementsByTagName('svg')->item(0);
        
        if ($svgElement) {
            // Get all child elements, but filter out background rects
            // Material Icons sometimes have a background rect that we don't want
            $innerHTML = '';
            foreach ($svgElement->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $tagName = strtolower($child->tagName);
                    
                    // Skip ALL rect elements - Material Icons shouldn't have rects for the icon itself
                    if ($tagName === 'rect') {
                        continue; // Skip all rects
                    }
                    
                    // Only include paths, circles, polygons, etc. - actual icon shapes
                    if (in_array($tagName, ['path', 'circle', 'ellipse', 'polygon', 'polyline', 'line', 'g'])) {
                        $innerHTML .= $dom->saveHTML($child);
                    }
                }
            }
            
            // If we got content, return it
            if (!empty(trim($innerHTML))) {
                return $innerHTML;
            }
            
            // Fallback: try to get path data only (paths are the actual icon shapes)
            $paths = $dom->getElementsByTagName('path');
            $pathData = '';
            foreach ($paths as $path) {
                $d = $path->getAttribute('d');
                if (!empty($d)) {
                    // Preserve path attributes if they exist
                    $pathHTML = '<path';
                    foreach ($path->attributes as $attr) {
                        if ($attr->name !== 'd') {
                            $pathHTML .= ' ' . $attr->name . '="' . htmlspecialchars($attr->value) . '"';
                        }
                    }
                    $pathHTML .= ' d="' . htmlspecialchars($d) . '"></path>';
                    $pathData .= $pathHTML;
                }
            }
            
            if (!empty($pathData)) {
                return $pathData;
            }
        }
    } else {
        // If XML parsing failed, try to extract path data directly from SVG string
        // First, remove any rect elements from the string
        $svgContent = preg_replace('/<rect[^>]*>.*?<\/rect>/is', '', $svgContent);
        
        if (preg_match_all('/<path[^>]*d="([^"]+)"[^>]*>/i', $svgContent, $matches)) {
            $pathData = '';
            foreach ($matches[1] as $d) {
                $pathData .= '<path d="' . htmlspecialchars($d) . '"></path>';
            }
            if (!empty($pathData)) {
                return $pathData;
            }
        }
    }
    
    error_log("Failed to extract SVG content for {$iconName}");
    return false;
}

/**
 * Cache icon SVG path
 * @param int $iconId Icon ID
 * @param string $svgPath SVG path content
 * @return bool Success
 */
function cacheIconSVG($iconId, $svgPath) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE setup_icons SET svg_path = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("si", $svgPath, $iconId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/**
 * Get footer data
 * @return array Footer data or empty array with defaults
 */
function getFooterData() {
    $conn = getDBConnection();
    if ($conn === null) {
        return getDefaultFooterData();
    }
    
    // Ensure tables exist
    ensureFooterTableExists($conn);
    
    try {
        // Get footer data
        $stmt = $conn->prepare("SELECT * FROM footer_data ORDER BY id DESC LIMIT 1");
        if (!$stmt) {
            return getDefaultFooterData();
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            $row = getDefaultFooterData();
        }
        
        // Get links
        $linksStmt = $conn->prepare("SELECT id, label, url, icon_name, display_type, display_order, is_visible FROM footer_links ORDER BY display_order ASC, id ASC");
        $linksStmt->execute();
        $linksResult = $linksStmt->get_result();
        $links = [];
        while ($linkRow = $linksResult->fetch_assoc()) {
            $links[] = [
                'id' => $linkRow['id'],
                'label' => $linkRow['label'],
                'url' => $linkRow['url'],
                'icon_name' => $linkRow['icon_name'] ?? '',
                'display_type' => $linkRow['display_type'] ?? 'text',
                'display_order' => $linkRow['display_order'],
                'is_visible' => isset($linkRow['is_visible']) ? (int)$linkRow['is_visible'] : 1
            ];
        }
        $linksStmt->close();
        $row['links'] = $links;
        
        // Get social media
        $socialStmt = $conn->prepare("SELECT id, platform, url, icon_name, display_type, display_order, is_visible FROM footer_social ORDER BY display_order ASC, id ASC");
        $socialStmt->execute();
        $socialResult = $socialStmt->get_result();
        $socialMedia = [];
        while ($socialRow = $socialResult->fetch_assoc()) {
            $socialMedia[] = [
                'id' => $socialRow['id'],
                'platform' => $socialRow['platform'],
                'url' => $socialRow['url'],
                'icon_name' => $socialRow['icon_name'] ?? '',
                'display_type' => $socialRow['display_type'] ?? 'icon_text',
                'display_order' => $socialRow['display_order'],
                'is_visible' => isset($socialRow['is_visible']) ? (int)$socialRow['is_visible'] : 1
            ];
        }
        $socialStmt->close();
        $row['social_media'] = $socialMedia;
        
        // Get column widths (stored as JSON)
        $linkColumnWidths = isset($row['link_column_widths']) && !empty($row['link_column_widths']) ? json_decode($row['link_column_widths'], true) : null;
        $socialColumnWidths = isset($row['social_column_widths']) && !empty($row['social_column_widths']) ? json_decode($row['social_column_widths'], true) : null;
        
        if (!$linkColumnWidths || !is_array($linkColumnWidths)) {
            $linkColumnWidths = ['20%', '40.5%', '12.5%', '12%', '5%', '8%'];
        }
        if (!$socialColumnWidths || !is_array($socialColumnWidths)) {
            $socialColumnWidths = ['20%', '40.5%', '12.5%', '12%', '5%', '8%'];
        }
        
        $row['link_column_widths'] = $linkColumnWidths;
        $row['social_column_widths'] = $socialColumnWidths;
        
        return $row;
    } catch (mysqli_sql_exception $e) {
        return getDefaultFooterData();
    }
}

/**
 * Get default footer data structure
 * @return array Default footer data
 */
function getDefaultFooterData() {
    return [
        'id' => null,
        'company_name' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'country' => '',
        'phone' => '',
        'email' => '',
        'fax' => '',
        'copyright_text' => '&copy; ' . date('Y') . ' Bespoke Cabinetry. All rights reserved.',
        'links' => [],
        'social_media' => [],
        'link_column_widths' => ['20%', '40.5%', '12.5%', '12%', '5%', '8%'],
        'social_column_widths' => ['20%', '40.5%', '12.5%', '12%', '5%', '8%']
    ];
}

/**
 * Ensure footer tables exist
 * @param mysqli $conn Database connection
 */
function ensureFooterTableExists($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        // Create footer_data table
        $footerDataTable = "CREATE TABLE IF NOT EXISTS footer_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100),
            phone VARCHAR(50),
            email VARCHAR(255),
            fax VARCHAR(50),
            copyright_text TEXT,
            link_column_widths TEXT,
            social_column_widths TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Add column width columns if they don't exist
        $checkLinkWidths = $conn->query("SHOW COLUMNS FROM footer_data LIKE 'link_column_widths'");
        if ($checkLinkWidths && $checkLinkWidths->num_rows == 0) {
            $conn->query("ALTER TABLE footer_data ADD COLUMN link_column_widths TEXT AFTER copyright_text");
        }
        $checkSocialWidths = $conn->query("SHOW COLUMNS FROM footer_data LIKE 'social_column_widths'");
        if ($checkSocialWidths && $checkSocialWidths->num_rows == 0) {
            $conn->query("ALTER TABLE footer_data ADD COLUMN social_column_widths TEXT AFTER link_column_widths");
        }
        
        // Create footer_links table
        $footerLinksTable = "CREATE TABLE IF NOT EXISTS footer_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            icon_name VARCHAR(100),
            display_type ENUM('icon', 'icon_text', 'text') DEFAULT 'text',
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order (display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Create footer_social table
        $footerSocialTable = "CREATE TABLE IF NOT EXISTS footer_social (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(100) NOT NULL,
            url VARCHAR(500) NOT NULL,
            icon_name VARCHAR(100),
            display_type ENUM('icon', 'icon_text', 'text') DEFAULT 'icon_text',
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order (display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Migrate existing tables to add new columns if they exist
        migrateFooterTables($conn);
        
        $result1 = $conn->query($footerDataTable);
        $result2 = $conn->query($footerLinksTable);
        $result3 = $conn->query($footerSocialTable);
        
        if ($result1 === TRUE && $result2 === TRUE && $result3 === TRUE) {
            // Migrate existing data from JSON fields if they exist
            migrateFooterData($conn);
            // Migrate tables to add icon and display_type columns
            migrateFooterTables($conn);
            return true;
        } else {
            error_log("Error creating footer tables: " . $conn->error);
            return false;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Error ensuring footer tables exist: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate footer tables to add icon_name and display_type columns
 * @param mysqli $conn Database connection
 */
function migrateFooterTables($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        // Check and add icon_name column to footer_links
        $checkLinksIcon = $conn->query("SHOW COLUMNS FROM footer_links LIKE 'icon_name'");
        if ($checkLinksIcon && $checkLinksIcon->num_rows == 0) {
            $conn->query("ALTER TABLE footer_links ADD COLUMN icon_name VARCHAR(100) AFTER url");
        }
        
        // Check and add display_type column to footer_links
        $checkLinksDisplay = $conn->query("SHOW COLUMNS FROM footer_links LIKE 'display_type'");
        if ($checkLinksDisplay && $checkLinksDisplay->num_rows == 0) {
            $conn->query("ALTER TABLE footer_links ADD COLUMN display_type ENUM('icon', 'icon_text', 'text') DEFAULT 'text' AFTER icon_name");
        }
        
        // Check and add icon_name column to footer_social
        $checkSocialIcon = $conn->query("SHOW COLUMNS FROM footer_social LIKE 'icon_name'");
        if ($checkSocialIcon && $checkSocialIcon->num_rows == 0) {
            $conn->query("ALTER TABLE footer_social ADD COLUMN icon_name VARCHAR(100) AFTER url");
        }
        
        // Check and add display_type column to footer_social
        $checkSocialDisplay = $conn->query("SHOW COLUMNS FROM footer_social LIKE 'display_type'");
        if ($checkSocialDisplay && $checkSocialDisplay->num_rows == 0) {
            $conn->query("ALTER TABLE footer_social ADD COLUMN display_type ENUM('icon', 'icon_text', 'text') DEFAULT 'icon_text' AFTER icon_name");
        }
        
        // Check and add is_visible column to footer_links
        $checkLinksVisible = $conn->query("SHOW COLUMNS FROM footer_links LIKE 'is_visible'");
        if ($checkLinksVisible && $checkLinksVisible->num_rows == 0) {
            $conn->query("ALTER TABLE footer_links ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER display_order");
        }
        
        // Check and add is_visible column to footer_social
        $checkSocialVisible = $conn->query("SHOW COLUMNS FROM footer_social LIKE 'is_visible'");
        if ($checkSocialVisible && $checkSocialVisible->num_rows == 0) {
            $conn->query("ALTER TABLE footer_social ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER display_order");
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Footer tables migration note: " . $e->getMessage());
        return true; // Don't fail if migration errors
    }
}

/**
 * Migrate footer data from JSON fields to separate tables
 * @param mysqli $conn Database connection
 */
function migrateFooterData($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        // Check if footer_data has links/social_media columns (old structure)
        $columns = $conn->query("SHOW COLUMNS FROM footer_data LIKE 'links'");
        if ($columns && $columns->num_rows > 0) {
            // Old structure exists, migrate data
            $stmt = $conn->prepare("SELECT id, links, social_media FROM footer_data WHERE links IS NOT NULL OR social_media IS NOT NULL");
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Migrate links
                if (!empty($row['links'])) {
                    $links = json_decode($row['links'], true);
                    if (is_array($links)) {
                        foreach ($links as $index => $link) {
                            if (!empty($link['label']) && !empty($link['url'])) {
                                $linkStmt = $conn->prepare("INSERT INTO footer_links (label, url, display_order) VALUES (?, ?, ?)");
                                $order = $index;
                                $linkStmt->bind_param("ssi", $link['label'], $link['url'], $order);
                                $linkStmt->execute();
                                $linkStmt->close();
                            }
                        }
                    }
                }
                
                // Migrate social media
                if (!empty($row['social_media'])) {
                    $social = json_decode($row['social_media'], true);
                    if (is_array($social)) {
                        foreach ($social as $index => $item) {
                            if (!empty($item['platform']) && !empty($item['url'])) {
                                $socialStmt = $conn->prepare("INSERT INTO footer_social (platform, url, display_order) VALUES (?, ?, ?)");
                                $order = $index;
                                $socialStmt->bind_param("ssi", $item['platform'], $item['url'], $order);
                                $socialStmt->execute();
                                $socialStmt->close();
                            }
                        }
                    }
                }
            }
            $stmt->close();
            
            // Remove old columns after migration
            $conn->query("ALTER TABLE footer_data DROP COLUMN IF EXISTS links");
            $conn->query("ALTER TABLE footer_data DROP COLUMN IF EXISTS social_media");
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        // Migration is optional, don't fail if it errors
        error_log("Footer data migration note: " . $e->getMessage());
        return true;
    }
}

/**
 * Save footer data
 * @param array $data Footer data
 * @return bool Success
 */
function saveFooterData($data) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Ensure tables exist
    ensureFooterTableExists($conn);
    
    try {
        // Check if record exists
        $checkStmt = $conn->prepare("SELECT id FROM footer_data ORDER BY id DESC LIMIT 1");
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existing = $result->fetch_assoc();
        $checkStmt->close();
        
        // Extract all values to variables (bind_param requires variables, not expressions)
        $companyName = $data['company_name'] ?? '';
        $address = $data['address'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $postalCode = $data['postal_code'] ?? '';
        $country = $data['country'] ?? '';
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';
        $fax = $data['fax'] ?? '';
        $copyrightText = $data['copyright_text'] ?? '';
        $linkColumnWidths = isset($data['link_column_widths']) && is_array($data['link_column_widths']) ? json_encode($data['link_column_widths']) : json_encode(['20%', '40.5%', '12.5%', '12%', '5%', '8%']);
        $socialColumnWidths = isset($data['social_column_widths']) && is_array($data['social_column_widths']) ? json_encode($data['social_column_widths']) : json_encode(['20%', '40.5%', '12.5%', '12%', '5%', '8%']);
        
        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE footer_data SET 
                company_name = ?, address = ?, city = ?, state = ?, postal_code = ?, country = ?,
                phone = ?, email = ?, fax = ?, copyright_text = ?, link_column_widths = ?, social_column_widths = ?
                WHERE id = ?");
            if (!$stmt) {
                return false;
            }
            
            $existingId = $existing['id'];
            
            $stmt->bind_param("ssssssssssssi",
                $companyName,
                $address,
                $city,
                $state,
                $postalCode,
                $country,
                $phone,
                $email,
                $fax,
                $copyrightText,
                $linkColumnWidths,
                $socialColumnWidths,
                $existingId
            );
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO footer_data 
                (company_name, address, city, state, postal_code, country, phone, email, fax, copyright_text, link_column_widths, social_column_widths)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("ssssssssssss",
                $companyName,
                $address,
                $city,
                $state,
                $postalCode,
                $country,
                $phone,
                $email,
                $fax,
                $copyrightText,
                $linkColumnWidths,
                $socialColumnWidths
            );
        }
        
        $success = $stmt->execute();
        $stmt->close();
        
        if (!$success) {
            return false;
        }
        
        // Save links
        $links = $data['links'] ?? [];
        // Delete all existing links
        $deleteLinksStmt = $conn->prepare("DELETE FROM footer_links");
        $deleteLinksStmt->execute();
        $deleteLinksStmt->close();
        
        // Insert new links
        if (!empty($links)) {
            $linkStmt = $conn->prepare("INSERT INTO footer_links (label, url, icon_name, display_type, display_order, is_visible) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($links as $index => $link) {
                if (!empty($link['label']) && !empty($link['url'])) {
                    $order = $index;
                    $linkLabel = $link['label'];
                    $linkUrl = $link['url'];
                    $linkIconName = $link['icon_name'] ?? '';
                    $linkDisplayType = $link['display_type'] ?? 'text';
                    $linkIsVisible = isset($link['is_visible']) ? (int)$link['is_visible'] : 1;
                    $linkStmt->bind_param("ssssii", $linkLabel, $linkUrl, $linkIconName, $linkDisplayType, $order, $linkIsVisible);
                    $linkStmt->execute();
                }
            }
            $linkStmt->close();
        }
        
        // Save social media
        $socialMedia = $data['social_media'] ?? [];
        // Delete all existing social media
        $deleteSocialStmt = $conn->prepare("DELETE FROM footer_social");
        $deleteSocialStmt->execute();
        $deleteSocialStmt->close();
        
        // Insert new social media
        if (!empty($socialMedia)) {
            $socialStmt = $conn->prepare("INSERT INTO footer_social (platform, url, icon_name, display_type, display_order, is_visible) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($socialMedia as $index => $social) {
                if (!empty($social['platform']) && !empty($social['url'])) {
                    $order = $index;
                    $socialPlatform = $social['platform'];
                    $socialUrl = $social['url'];
                    $socialIconName = $social['icon_name'] ?? '';
                    $socialDisplayType = $social['display_type'] ?? 'icon_text';
                    $socialIsVisible = isset($social['is_visible']) ? (int)$social['is_visible'] : 1;
                    $socialStmt->bind_param("ssssii", $socialPlatform, $socialUrl, $socialIconName, $socialDisplayType, $order, $socialIsVisible);
                    $socialStmt->execute();
                }
            }
            $socialStmt->close();
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error saving footer data: " . $e->getMessage());
        return false;
    }
}

/**
 * Create settings_parameters table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createSettingsParametersTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS settings_parameters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section VARCHAR(100) NOT NULL,
            parameter_name VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            value TEXT NOT NULL,
            min_range DECIMAL(10,2) NULL,
            max_range DECIMAL(10,2) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_section (section),
            INDEX idx_parameter_name (parameter_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating settings_parameters table: " . $conn->error);
            return false;
        }
        
        // Try to add FULLTEXT index if supported (MyISAM) or use regular indexes
        // For InnoDB, we'll use regular indexes for search
        $fulltextCheck = $conn->query("SHOW INDEX FROM settings_parameters WHERE Key_name = 'idx_search'");
        if ($fulltextCheck && $fulltextCheck->num_rows == 0) {
            // Try FULLTEXT, but don't fail if not supported
            @$conn->query("ALTER TABLE settings_parameters ADD FULLTEXT idx_search (section, parameter_name, description, value)");
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating settings_parameters table: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a parameter value by section and name
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if parameter doesn't exist
 * @return mixed Parameter value or default
 */
function getParameter($section, $name, $default = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $stmt = $conn->prepare("SELECT value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("ss", $section, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['value'] : $default;
    } catch (mysqli_sql_exception $e) {
        return $default;
    }
}

/**
 * Get all parameters grouped by section
 * @param string|null $section Optional section filter
 * @return array Parameters grouped by section
 */
function getAllParametersBySection($section = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    // Ensure configs table exists
    createSettingsParametersConfigsTable($conn);
    
    try {
        if ($section) {
            $stmt = $conn->prepare("SELECT 
                sp.id, 
                sp.section, 
                sp.parameter_name, 
                sp.description, 
                sp.value, 
                sp.min_range, 
                sp.max_range,
                spc.input_type,
                spc.options_json,
                spc.placeholder,
                spc.help_text,
                spc.validation_rules
            FROM settings_parameters sp
            LEFT JOIN settings_parameters_configs spc ON sp.id = spc.parameter_id
            WHERE sp.section = ? 
            ORDER BY sp.parameter_name ASC");
            $stmt->bind_param("s", $section);
        } else {
            $stmt = $conn->prepare("SELECT 
                sp.id, 
                sp.section, 
                sp.parameter_name, 
                sp.description, 
                sp.value, 
                sp.min_range, 
                sp.max_range,
                spc.input_type,
                spc.options_json,
                spc.placeholder,
                spc.help_text,
                spc.validation_rules
            FROM settings_parameters sp
            LEFT JOIN settings_parameters_configs spc ON sp.id = spc.parameter_id
            ORDER BY sp.section ASC, sp.parameter_name ASC");
        }
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $parameters = [];
        
        while ($row = $result->fetch_assoc()) {
            $sectionName = $row['section'];
            if (!isset($parameters[$sectionName])) {
                $parameters[$sectionName] = [];
            }
            
            // Parse options_json if present
            if ($row['options_json']) {
                $row['options'] = json_decode($row['options_json'], true);
            } else {
                $row['options'] = null;
            }
            
            // Parse validation_rules if present
            if ($row['validation_rules']) {
                $row['validation'] = json_decode($row['validation_rules'], true);
            } else {
                $row['validation'] = null;
            }
            
            $parameters[$sectionName][] = $row;
        }
        
        $stmt->close();
        
        return $parameters;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting parameters by section: " . $e->getMessage());
        return [];
    }
}

/**
 * Search parameters across all fields
 * @param string $query Search query
 * @param string|null $section Optional section filter
 * @return array Parameters grouped by section
 */
function searchParameters($query, $section = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $searchTerm = "%" . $conn->real_escape_string($query) . "%";
        
        if ($section) {
            $stmt = $conn->prepare("SELECT id, section, parameter_name, description, value, min_range, max_range FROM settings_parameters WHERE section = ? AND (section LIKE ? OR parameter_name LIKE ? OR description LIKE ? OR value LIKE ?) ORDER BY section ASC, parameter_name ASC");
            $stmt->bind_param("sssss", $section, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        } else {
            $stmt = $conn->prepare("SELECT id, section, parameter_name, description, value, min_range, max_range FROM settings_parameters WHERE section LIKE ? OR parameter_name LIKE ? OR description LIKE ? OR value LIKE ? ORDER BY section ASC, parameter_name ASC");
            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $parameters = [];
        
        while ($row = $result->fetch_assoc()) {
            $sectionName = $row['section'];
            if (!isset($parameters[$sectionName])) {
                $parameters[$sectionName] = [];
            }
            $parameters[$sectionName][] = $row;
        }
        
        $stmt->close();
        
        return $parameters;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/**
 * Update a parameter value
 * @param int $id Parameter ID
 * @param string $value New value
 * @return bool Success
 */
function updateParameter($id, $value) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Normalize HEX color values to uppercase
    $normalizedValue = $value;
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value))) {
        $normalizedValue = strtoupper(trim($value));
    }
    
    try {
        $stmt = $conn->prepare("UPDATE settings_parameters SET value = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("si", $normalizedValue, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error updating parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all unique sections
 * @return array Array of section names
 */
function getAllSections() {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT DISTINCT section FROM settings_parameters ORDER BY section ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $sections = [];
        
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row['section'];
        }
        
        $stmt->close();
        
        return $sections;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/**
 * Generate CSS variables from database parameters
 * @return string CSS :root block with all variables
 */
function generateCSSVariables() {
    $conn = getDBConnection();
    if ($conn === null) {
        return '';
    }
    
    try {
        $stmt = $conn->prepare("SELECT parameter_name, value FROM settings_parameters WHERE parameter_name LIKE '--%' ORDER BY parameter_name ASC");
        if (!$stmt) {
            return '';
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $css = ":root {\n";
        
        while ($row = $result->fetch_assoc()) {
            $name = $row['parameter_name'];
            $value = $row['value'];
            $css .= "  " . $name . ": " . $value . ";\n";
        }
        
        $css .= "}\n";
        $stmt->close();
        
        return $css;
    } catch (mysqli_sql_exception $e) {
        return '';
    }
}

/**
 * Insert or update a parameter
 * @param string $section Section name
 * @param string $parameterName Parameter name
 * @param string $value Parameter value
 * @param string|null $description Parameter description
 * @param float|null $minRange Minimum range
 * @param float|null $maxRange Maximum range
 * @return bool Success
 */
function upsertParameter($section, $parameterName, $value, $description = null, $minRange = null, $maxRange = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Normalize HEX color values to uppercase (standard: all HEX codes must be uppercase)
    $normalizedValue = $value;
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value))) {
        $normalizedValue = strtoupper(trim($value));
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO settings_parameters (section, parameter_name, description, value, min_range, max_range) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = ?, description = ?, min_range = ?, max_range = ?, updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssdddsdd", $section, $parameterName, $description, $normalizedValue, $minRange, $maxRange, $normalizedValue, $description, $minRange, $maxRange);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error upserting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get system timezone from parameters
 * @return string Timezone identifier (e.g., 'Australia/Brisbane')
 */
function getSystemTimezone() {
    return getParameter('System', '--system-timezone', 'Australia/Brisbane');
}

/**
 * Set PHP default timezone from system parameter
 * @return bool Success
 */
function setSystemTimezone() {
    $timezone = getSystemTimezone();
    if ($timezone) {
        try {
            date_default_timezone_set($timezone);
            return true;
        } catch (Exception $e) {
            error_log("Invalid timezone: " . $timezone . " - " . $e->getMessage());
            // Fallback to default
            date_default_timezone_set('Australia/Brisbane');
            return false;
        }
    }
    // Fallback to default
    date_default_timezone_set('Australia/Brisbane');
    return false;
}

/**
 * Get system date format from parameters
 * @return string Date format (e.g., 'Y-m-d')
 */
function getSystemDateFormat() {
    return getParameter('System', '--system-date-format', 'Y-m-d');
}

/**
 * Get system time format from parameters
 * @return string Time format (e.g., 'H:i:s')
 */
function getSystemTimeFormat() {
    return getParameter('System', '--system-time-format', 'H:i:s');
}

/**
 * Get system datetime format from parameters
 * @return string DateTime format (e.g., 'Y-m-d H:i:s')
 */
function getSystemDateTimeFormat() {
    return getParameter('System', '--system-datetime-format', 'Y-m-d H:i:s');
}

/**
 * Format a timestamp using system date format
 * @param int|string $timestamp Unix timestamp or date string
 * @return string Formatted date
 */
function formatSystemDate($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    } elseif (is_string($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    $format = getSystemDateFormat();
    return date($format, $timestamp);
}

/**
 * Format a timestamp using system time format
 * @param int|string $timestamp Unix timestamp or date string
 * @return string Formatted time
 */
function formatSystemTime($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    } elseif (is_string($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    $format = getSystemTimeFormat();
    return date($format, $timestamp);
}

/**
 * Format a timestamp using system datetime format
 * @param int|string $timestamp Unix timestamp or date string
 * @return string Formatted datetime
 */
function formatSystemDateTime($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    } elseif (is_string($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    $format = getSystemDateTimeFormat();
    return date($format, $timestamp);
}

/**
 * Create settings_parameters_configs table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createSettingsParametersConfigsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS settings_parameters_configs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parameter_id INT NOT NULL,
            input_type VARCHAR(50) NOT NULL,
            options_json TEXT NULL,
            placeholder VARCHAR(255) NULL,
            help_text TEXT NULL,
            validation_rules JSON NULL,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parameter_id) REFERENCES settings_parameters(id) ON DELETE CASCADE,
            UNIQUE KEY unique_parameter_config (parameter_id),
            INDEX idx_input_type (input_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating settings_parameters_configs table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating settings_parameters_configs table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create timezones table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createTimezonesTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS timezones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timezone_identifier VARCHAR(100) UNIQUE NOT NULL,
            city_name VARCHAR(100) NOT NULL,
            country VARCHAR(100) NOT NULL,
            utc_offset DECIMAL(4,1) NOT NULL,
            display_label VARCHAR(255) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_order (display_order),
            INDEX idx_identifier (timezone_identifier),
            INDEX idx_country (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating timezones table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating timezones table: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all timezones from database
 * @param bool $activeOnly Only return active timezones
 * @return array Array of timezone records
 */
function getAllTimezones($activeOnly = true) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    createTimezonesTable($conn);
    
    try {
        if ($activeOnly) {
            $stmt = $conn->prepare("SELECT * FROM timezones WHERE is_active = 1 ORDER BY display_order ASC, country ASC, city_name ASC");
        } else {
            $stmt = $conn->prepare("SELECT * FROM timezones ORDER BY display_order ASC, country ASC, city_name ASC");
        }
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $timezones = [];
        
        while ($row = $result->fetch_assoc()) {
            $timezones[] = $row;
        }
        
        $stmt->close();
        return $timezones;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting timezones: " . $e->getMessage());
        return [];
    }
}

/**
 * Get timezone by ID
 * @param int $id Timezone ID
 * @return array|null Timezone record or null
 */
function getTimezoneById($id) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM timezones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timezone = $result->fetch_assoc();
        $stmt->close();
        
        return $timezone ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting timezone by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get timezone by PHP timezone identifier
 * @param string $identifier PHP timezone identifier (e.g., 'Australia/Brisbane')
 * @return array|null Timezone record or null
 */
function getTimezoneByIdentifier($identifier) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM timezones WHERE timezone_identifier = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $timezone = $result->fetch_assoc();
        $stmt->close();
        
        return $timezone ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting timezone by identifier: " . $e->getMessage());
        return null;
    }
}

/**
 * Format timezone label for display
 * @param array $timezone Timezone record
 * @return string Formatted label
 */
function formatTimezoneLabel($timezone) {
    if (is_array($timezone) && isset($timezone['display_label'])) {
        return $timezone['display_label'];
    }
    
    // Fallback formatting
    $city = $timezone['city_name'] ?? '';
    $country = $timezone['country'] ?? '';
    $offset = $timezone['utc_offset'] ?? 0;
    $offsetStr = $offset >= 0 ? '+' . $offset : (string)$offset;
    
    return $city . ', ' . $country . ' (UTC' . $offsetStr . ')';
}

/**
 * Refresh timezone parameter options from timezones table
 * Updates the --system-timezone parameter's dropdown options
 * @return bool Success
 */
function refreshTimezoneParameterOptions() {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createTimezonesTable($conn);
    
    // Get timezone parameter ID
    $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = 'System' AND parameter_name = '--system-timezone'");
    $paramStmt->execute();
    $paramResult = $paramStmt->get_result();
    $param = $paramResult->fetch_assoc();
    $paramStmt->close();
    
    if (!$param) {
        return false;
    }
    
    $paramId = $param['id'];
    
    // Get all active timezones from table
    $tzStmt = $conn->prepare("SELECT timezone_identifier, display_label FROM timezones WHERE is_active = 1 ORDER BY display_order ASC, country ASC, city_name ASC");
    $tzStmt->execute();
    $tzResult = $tzStmt->get_result();
    
    $timezoneOptions = [];
    while ($tz = $tzResult->fetch_assoc()) {
        $timezoneOptions[] = [
            'value' => $tz['timezone_identifier'],
            'label' => $tz['display_label']
        ];
    }
    $tzStmt->close();
    
    if (empty($timezoneOptions)) {
        return false;
    }
    
    // Update parameter config with new options
    $optionsJson = json_encode($timezoneOptions);
    $updateStmt = $conn->prepare("UPDATE settings_parameters_configs SET options_json = ? WHERE parameter_id = ?");
    $updateStmt->bind_param("si", $optionsJson, $paramId);
    $success = $updateStmt->execute();
    $updateStmt->close();
    
    return $success;
}

/**
 * Auto-detect and migrate existing parameters to new input types
 * @param mysqli $conn Database connection
 * @return array Migration results with statistics
 */
function migrateParameterInputConfigs($conn) {
    if ($conn === null) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Ensure table exists
    createSettingsParametersConfigsTable($conn);
    
    // Get all parameters
    $stmt = $conn->prepare("SELECT id, section, parameter_name, description, value, min_range, max_range FROM settings_parameters");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Failed to query parameters'];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $parameters = [];
    while ($row = $result->fetch_assoc()) {
        $parameters[] = $row;
    }
    $stmt->close();
    
    $migrated = 0;
    $skipped = 0;
    $errors = [];
    $migrationDetails = [];
    
    foreach ($parameters as $param) {
        // Check if config already exists
        $checkStmt = $conn->prepare("SELECT id FROM settings_parameters_configs WHERE parameter_id = ?");
        $checkStmt->bind_param("i", $param['id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            $skipped++;
            continue;
        }
        $checkStmt->close();
        
        $inputType = 'text';
        $optionsJson = null;
        $detectionMethod = 'default';
        
        $value = trim($param['value']);
        $paramNameLower = strtolower($param['parameter_name']);
        
        // Detect colors
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value) || preg_match('/^rgba?\(/', $value)) {
            $inputType = 'color';
            $detectionMethod = 'color_pattern';
        }
        // Detect yes/no
        elseif (in_array(strtolower($value), ['yes', 'no'])) {
            $inputType = 'dropdown';
            $optionsJson = json_encode(['yes', 'no']);
            $detectionMethod = 'yes_no_values';
        }
        // Detect display/hide
        elseif (in_array(strtoupper($value), ['DISPLAY', 'HIDE'])) {
            $inputType = 'dropdown';
            $optionsJson = json_encode(['DISPLAY', 'HIDE']);
            $detectionMethod = 'display_hide_values';
        }
        // Detect numeric with ranges
        elseif (is_numeric($value) && ($param['min_range'] !== null || $param['max_range'] !== null)) {
            $inputType = 'number';
            $options = [];
            if ($param['min_range'] !== null) {
                $options['min'] = floatval($param['min_range']);
            }
            if ($param['max_range'] !== null) {
                $options['max'] = floatval($param['max_range']);
            }
            $options['step'] = 0.01;
            $optionsJson = json_encode($options);
            $detectionMethod = 'numeric_with_range';
        }
        // Detect fonts (special case - keep current custom font selector)
        elseif (strpos($paramNameLower, 'font') !== false && 
                (strpos($paramNameLower, 'primary') !== false || 
                 strpos($paramNameLower, 'secondary') !== false ||
                 strpos($paramNameLower, 'family') !== false)) {
            // Keep as text for now - font selector is handled specially in UI
            $inputType = 'text';
            $detectionMethod = 'font_parameter';
        }
        
        // Insert config
        $insertStmt = $conn->prepare("INSERT INTO settings_parameters_configs (parameter_id, input_type, options_json) VALUES (?, ?, ?)");
        if (!$insertStmt) {
            $errors[] = "Failed to prepare insert for parameter {$param['id']}: " . $conn->error;
            continue;
        }
        
        $insertStmt->bind_param("iss", $param['id'], $inputType, $optionsJson);
        if ($insertStmt->execute()) {
            $migrated++;
            $migrationDetails[] = [
                'parameter_id' => $param['id'],
                'parameter_name' => $param['parameter_name'],
                'input_type' => $inputType,
                'method' => $detectionMethod
            ];
        } else {
            $errors[] = "Failed to migrate parameter {$param['id']} ({$param['parameter_name']}): " . $insertStmt->error;
        }
        $insertStmt->close();
    }
    
    return [
        'success' => true,
        'migrated' => $migrated,
        'skipped' => $skipped,
        'errors' => $errors,
        'details' => $migrationDetails
    ];
}

/**
 * Get input config for a parameter
 * @param int $parameterId Parameter ID
 * @return array|null Input config or null if not found
 */
function getParameterInputConfig($parameterId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, parameter_id, input_type, options_json, placeholder, help_text, validation_rules, display_order FROM settings_parameters_configs WHERE parameter_id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $parameterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row && $row['options_json']) {
            $row['options'] = json_decode($row['options_json'], true);
        } else {
            $row['options'] = null;
        }
        
        if ($row && $row['validation_rules']) {
            $row['validation'] = json_decode($row['validation_rules'], true);
        } else {
            $row['validation'] = null;
        }
        
        return $row;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting parameter input config: " . $e->getMessage());
        return null;
    }
}

/**
 * Upsert parameter input config
 * @param int $parameterId Parameter ID
 * @param string $inputType Input type (text, number, color, dropdown, multiselect, checkbox, textarea)
 * @param string|null $optionsJson JSON string of options
 * @param string|null $placeholder Placeholder text
 * @param string|null $helpText Help text
 * @param string|null $validationRulesJson JSON string of validation rules
 * @return bool Success
 */
function upsertParameterInputConfig($parameterId, $inputType, $optionsJson = null, $placeholder = null, $helpText = null, $validationRulesJson = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Ensure table exists
    createSettingsParametersConfigsTable($conn);
    
    try {
        $stmt = $conn->prepare("INSERT INTO settings_parameters_configs (parameter_id, input_type, options_json, placeholder, help_text, validation_rules) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE input_type = ?, options_json = ?, placeholder = ?, help_text = ?, validation_rules = ?, updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("isssssissss", $parameterId, $inputType, $optionsJson, $placeholder, $helpText, $validationRulesJson, $inputType, $optionsJson, $placeholder, $helpText, $validationRulesJson);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error upserting parameter input config: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete parameter input config
 * @param int $parameterId Parameter ID
 * @return bool Success
 */
function deleteParameterInputConfig($parameterId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM settings_parameters_configs WHERE parameter_id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $parameterId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error deleting parameter input config: " . $e->getMessage());
        return false;
    }
}

// Auto-initialize database on first load (optional - can be called manually)
// initializeDatabase();

// ============================================================================
// SCHEDULED HEADERS SYSTEM - Database Tables and Functions
// ============================================================================

/**
 * Create scheduled_headers table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeadersTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_headers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            is_default TINYINT(1) DEFAULT 0,
            priority INT DEFAULT 0,
            display_location ENUM('admin', 'frontend', 'both') DEFAULT 'both',
            background_color VARCHAR(7),
            background_image VARCHAR(255),
            background_position VARCHAR(50) DEFAULT 'center',
            background_size VARCHAR(50) DEFAULT 'cover',
            background_repeat VARCHAR(20) DEFAULT 'no-repeat',
            header_height VARCHAR(20),
            transition_type ENUM('fade', 'slide', 'instant') DEFAULT 'fade',
            transition_duration INT DEFAULT 300,
            timezone VARCHAR(50) DEFAULT 'UTC',
            is_recurring TINYINT(1) DEFAULT 0,
            recurrence_type ENUM('yearly', 'monthly', 'weekly', 'daily') NULL,
            recurrence_day INT NULL,
            recurrence_month INT NULL,
            start_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_date DATE NULL,
            end_time TIME NULL,
            is_active TINYINT(1) DEFAULT 1,
            test_mode_enabled TINYINT(1) DEFAULT 0,
            logo_path VARCHAR(255),
            logo_position VARCHAR(50),
            search_bar_visible TINYINT(1) DEFAULT 1,
            search_bar_style TEXT,
            menu_items_visible TINYINT(1) DEFAULT 1,
            menu_items_style TEXT,
            user_info_visible TINYINT(1) DEFAULT 1,
            user_info_style TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_display_location (display_location),
            INDEX idx_priority (priority),
            INDEX idx_dates (start_date, end_date),
            INDEX idx_active (is_active),
            INDEX idx_default (is_default),
            INDEX idx_test_mode (test_mode_enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_headers table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_headers table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_images table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderImagesTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            header_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            image_path_webp VARCHAR(255),
            original_width INT,
            original_height INT,
            optimized_width INT,
            optimized_height INT,
            position ENUM('left', 'center', 'right', 'background', 'overlay') DEFAULT 'center',
            alignment VARCHAR(50),
            width VARCHAR(20),
            height VARCHAR(20),
            opacity DECIMAL(3,2) DEFAULT 1.00,
            z_index INT DEFAULT 0,
            display_order INT DEFAULT 0,
            mobile_visible TINYINT(1) DEFAULT 1,
            mobile_width VARCHAR(20),
            mobile_height VARCHAR(20),
            is_ai_generated TINYINT(1) DEFAULT 0,
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE CASCADE,
            INDEX idx_header_id (header_id),
            INDEX idx_position (position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_images table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_images table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_text_overlays table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderTextOverlaysTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_text_overlays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            header_id INT NOT NULL,
            content TEXT NOT NULL,
            position ENUM('left', 'center', 'right', 'top', 'bottom') DEFAULT 'center',
            alignment VARCHAR(50),
            font_size VARCHAR(20),
            font_color VARCHAR(7),
            font_family VARCHAR(100),
            font_weight VARCHAR(20),
            background_color VARCHAR(7),
            padding VARCHAR(20),
            border_radius VARCHAR(20),
            z_index INT DEFAULT 0,
            display_order INT DEFAULT 0,
            mobile_visible TINYINT(1) DEFAULT 1,
            mobile_font_size VARCHAR(20),
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE CASCADE,
            INDEX idx_header_id (header_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_text_overlays table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_text_overlays table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_ctas table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderCTAsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_ctas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            header_id INT NOT NULL,
            text VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            button_style TEXT,
            position ENUM('left', 'center', 'right', 'top', 'bottom') DEFAULT 'center',
            alignment VARCHAR(50),
            font_size VARCHAR(20),
            font_color VARCHAR(7),
            background_color VARCHAR(7),
            padding VARCHAR(20),
            border_radius VARCHAR(20),
            z_index INT DEFAULT 0,
            display_order INT DEFAULT 0,
            open_in_new_tab TINYINT(1) DEFAULT 0,
            tracking_enabled TINYINT(1) DEFAULT 1,
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE CASCADE,
            INDEX idx_header_id (header_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_ctas table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_ctas table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_analytics table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderAnalyticsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            header_id INT NOT NULL,
            cta_id INT NULL,
            event_type ENUM('view', 'click', 'conversion') DEFAULT 'view',
            display_location ENUM('admin', 'frontend') NOT NULL,
            user_ip VARCHAR(45),
            user_agent TEXT,
            referrer VARCHAR(500),
            session_id VARCHAR(255),
            conversion_value DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE CASCADE,
            FOREIGN KEY (cta_id) REFERENCES scheduled_header_ctas(id) ON DELETE SET NULL,
            INDEX idx_header_id (header_id),
            INDEX idx_cta_id (cta_id),
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at),
            INDEX idx_display_location (display_location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_analytics table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_analytics table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_cache table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderCacheTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            display_location ENUM('admin', 'frontend') NOT NULL,
            header_id INT NULL,
            cache_key VARCHAR(255) NOT NULL,
            cached_data TEXT,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cache_key (cache_key),
            INDEX idx_display_location (display_location),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_cache table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_cache table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_versions table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderVersionsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_versions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            header_id INT NOT NULL,
            version_number INT NOT NULL,
            header_data TEXT NOT NULL,
            images_data TEXT,
            text_overlays_data TEXT,
            ctas_data TEXT,
            changed_by INT NULL,
            change_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE CASCADE,
            INDEX idx_header_id (header_id),
            INDEX idx_version_number (version_number),
            UNIQUE KEY unique_header_version (header_id, version_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_versions table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_versions table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_templates table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderTemplatesTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100),
            description TEXT,
            thumbnail_path VARCHAR(255),
            header_data TEXT NOT NULL,
            images_data TEXT,
            text_overlays_data TEXT,
            ctas_data TEXT,
            is_system_template TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            usage_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_templates table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_templates table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create scheduled_header_ai_generations table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createScheduledHeaderAIGenerationsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS scheduled_header_ai_generations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            header_id INT NULL,
            prompt TEXT NOT NULL,
            prompt_subject VARCHAR(255),
            prompt_style VARCHAR(100),
            prompt_colors VARCHAR(255),
            prompt_mood VARCHAR(100),
            prompt_additional TEXT,
            ai_service VARCHAR(50) DEFAULT 'dalle3',
            generation_cost DECIMAL(10,4),
            variations_generated INT DEFAULT 1,
            selected_variation INT,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (header_id) REFERENCES scheduled_headers(id) ON DELETE SET NULL,
            INDEX idx_header_id (header_id),
            INDEX idx_generated_at (generated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating scheduled_header_ai_generations table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scheduled_header_ai_generations table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create ai_image_generation_settings table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createAIImageGenerationSettingsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableSQL = "CREATE TABLE IF NOT EXISTS ai_image_generation_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating ai_image_generation_settings table: " . $conn->error);
            return false;
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating ai_image_generation_settings table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create ai_generation_usage table
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function createAIGenerationUsageTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        // Ensure parent table exists first
        createScheduledHeaderAIGenerationsTable($conn);
        
        $tableSQL = "CREATE TABLE IF NOT EXISTS ai_generation_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            generation_id INT NULL,
            cost DECIMAL(10,4) NOT NULL,
            prompt_length INT,
            variations_count INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_cost (cost),
            INDEX idx_generation_id (generation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($tableSQL) !== TRUE) {
            error_log("Error creating ai_generation_usage table: " . $conn->error);
            return false;
        }
        
        // Add foreign key constraint separately if it doesn't exist
        $fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'ai_generation_usage' 
            AND CONSTRAINT_NAME = 'fk_ai_generation_usage_generation_id'");
        
        if (!$fkCheck || $fkCheck->num_rows == 0) {
            $fkSQL = "ALTER TABLE ai_generation_usage 
                ADD CONSTRAINT fk_ai_generation_usage_generation_id 
                FOREIGN KEY (generation_id) REFERENCES scheduled_header_ai_generations(id) ON DELETE SET NULL";
            $conn->query($fkSQL);
        }
        if ($fkCheck) $fkCheck->close();
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating ai_generation_usage table: " . $e->getMessage());
        // Try creating without foreign key if it fails
        try {
            $tableSQL = "CREATE TABLE IF NOT EXISTS ai_generation_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                generation_id INT NULL,
                cost DECIMAL(10,4) NOT NULL,
                prompt_length INT,
                variations_count INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                INDEX idx_cost (cost),
                INDEX idx_generation_id (generation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($tableSQL) === TRUE) {
                return true;
            }
        } catch (Exception $e2) {
            error_log("Error creating ai_generation_usage table (fallback): " . $e2->getMessage());
        }
        return false;
    }
}

/**
 * Initialize all scheduled headers tables
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function initializeScheduledHeadersTables($conn) {
    if ($conn === null) {
        return false;
    }
    
    $success = true;
    
    // Create tables in order (parent tables first)
    $tables = [
        'createScheduledHeadersTable',
        'createScheduledHeaderImagesTable',
        'createScheduledHeaderTextOverlaysTable',
        'createScheduledHeaderCTAsTable',
        'createScheduledHeaderAnalyticsTable',
        'createScheduledHeaderCacheTable',
        'createScheduledHeaderVersionsTable',
        'createScheduledHeaderTemplatesTable',
        'createScheduledHeaderAIGenerationsTable',
        'createAIImageGenerationSettingsTable',
        'createAIGenerationUsageTable'
    ];
    
    foreach ($tables as $tableFunc) {
        if (!$tableFunc($conn)) {
            $success = false;
        }
    }
    
    return $success;
}

// ============================================================================
// SCHEDULED HEADERS SYSTEM - Additional CRUD Functions
// ============================================================================

/**
 * Save scheduled header with related data
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    // Ensure tables exist
    createScheduledHeadersTable($conn);
    createScheduledHeaderCacheTable($conn);
    
    if ($currentDateTime === null) {
        $currentDateTime = new DateTime();
    }
    
    // Check cache first if enabled
    if ($useCache) {
        $cached = getCachedHeader($displayLocation);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    try {
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');
        $currentDay = (int)$currentDateTime->format('j');
        $currentMonth = (int)$currentDateTime->format('n');
        $dayOfWeek = (int)$currentDateTime->format('w'); // 0 = Sunday, 6 = Saturday
        
        // Build query for active headers - simplified approach
        $query = "SELECT * FROM scheduled_headers 
                  WHERE is_active = 1 
                  AND (display_location = ? OR display_location = 'both')
                  ORDER BY priority DESC, start_date DESC, start_time DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return getDefaultHeader($displayLocation);
        }
        
        $stmt->bind_param("s", $displayLocation);
        $stmt->execute();
        $result = $stmt->get_result();
        $headers = [];
        while ($row = $result->fetch_assoc()) {
            if (isHeaderActive($row, $currentDateTime)) {
                $headers[] = $row;
            }
        }
        $stmt->close();
        
        if (!empty($headers)) {
            $header = $headers[0]; // Highest priority
            // Cache the result
            if ($useCache) {
                setCachedHeader($displayLocation, $header);
            }
            return $header;
        }
        
        // If no active header, return default
        return getDefaultHeader($displayLocation);
        
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting active header: " . $e->getMessage());
        return getDefaultHeader($displayLocation);
    }
}

/**
 * Get default header for a display location
 * @param string $displayLocation 'admin', 'frontend', or 'both'
 * @return array|null Header data or null
 */
function getDefaultHeader($displayLocation) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_headers 
                                WHERE is_default = 1 
                                AND (display_location = ? OR display_location = 'both')
                                AND is_active = 1
                                ORDER BY priority DESC
                                LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $displayLocation);
        $stmt->execute();
        $result = $stmt->get_result();
        $header = $result->fetch_assoc();
        $stmt->close();
        
        return $header;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting default header: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all scheduled headers
 * @param string|null $displayLocation Optional filter by location
 * @return array Headers array
 */
function getAllScheduledHeaders($displayLocation = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    createScheduledHeadersTable($conn);
    
    try {
        if ($displayLocation) {
            $stmt = $conn->prepare("SELECT * FROM scheduled_headers 
                                    WHERE display_location = ? OR display_location = 'both'
                                    ORDER BY priority DESC, created_at DESC");
            $stmt->bind_param("s", $displayLocation);
        } else {
            $stmt = $conn->prepare("SELECT * FROM scheduled_headers 
                                    ORDER BY priority DESC, created_at DESC");
        }
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $headers = [];
        while ($row = $result->fetch_assoc()) {
            $headers[] = $row;
        }
        $stmt->close();
        
        return $headers;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting all scheduled headers: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if header is active for given date/time
 * @param array $header Header data
 * @param DateTime $currentDateTime Current date/time
 * @param bool $testMode Whether test mode is enabled
 * @return bool
 */
function isHeaderActive($header, $currentDateTime, $testMode = false) {
    if ($testMode && !empty($header['test_mode_enabled'])) {
        return true;
    }
    
    if (empty($header['is_active'])) {
        return false;
    }
    
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('H:i:s');
    
    // Handle timezone conversion if needed
    if (!empty($header['timezone']) && $header['timezone'] !== 'UTC') {
        try {
            $tz = new DateTimeZone($header['timezone']);
            $currentDateTime->setTimezone($tz);
            $currentDate = $currentDateTime->format('Y-m-d');
            $currentTime = $currentDateTime->format('H:i:s');
        } catch (Exception $e) {
            // Invalid timezone, use server time
        }
    }
    
    if (!empty($header['is_recurring'])) {
        return checkRecurringSchedule($header, $currentDateTime);
    } else {
        // One-time schedule
        $startDate = $header['start_date'];
        $startTime = $header['start_time'] ?? '00:00:00';
        $endDate = $header['end_date'] ?? null;
        $endTime = $header['end_time'] ?? '23:59:59';
        
        // Check if current date/time is within range
        $startDateTime = $startDate . ' ' . $startTime;
        $endDateTime = ($endDate ?: $startDate) . ' ' . $endTime;
        $currentDateTimeStr = $currentDate . ' ' . $currentTime;
        
        return ($currentDateTimeStr >= $startDateTime && $currentDateTimeStr <= $endDateTime);
    }
}

/**
 * Check if recurring header matches current date
 * @param array $header Header data
 * @param DateTime $currentDate Current date
 * @return bool
 */
function checkRecurringSchedule($header, $currentDate) {
    if (empty($header['is_recurring']) || empty($header['recurrence_type'])) {
        return false;
    }
    
    $day = (int)$currentDate->format('j');
    $month = (int)$currentDate->format('n');
    $dayOfWeek = (int)$currentDate->format('w'); // 0 = Sunday
    
    switch ($header['recurrence_type']) {
        case 'yearly':
            return ($header['recurrence_month'] == $month && $header['recurrence_day'] == $day);
        case 'monthly':
            return ($header['recurrence_day'] == $day);
        case 'weekly':
            return ($header['recurrence_day'] == $dayOfWeek);
        case 'daily':
            return true;
        default:
            return false;
    }
}

/**
 * Set cached header
 * @param string $displayLocation
 * @param array $header Header data
 * @param int $ttl Time to live in seconds (default 600 = 10 minutes)
 * @return bool
 */
function setCachedHeader($displayLocation, $header, $ttl = 600) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderCacheTable($conn);
    
    try {
        $cacheKey = 'header_' . $displayLocation . '_' . date('Y-m-d_H');
        $cachedData = json_encode($header);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        $headerId = $header['id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO scheduled_header_cache 
                                (display_location, header_id, cache_key, cached_data, expires_at) 
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE 
                                cached_data = ?, expires_at = ?, header_id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sisssssi", $displayLocation, $headerId, $cacheKey, $cachedData, $expiresAt, 
                         $cachedData, $expiresAt, $headerId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error setting cached header: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear header cache
 * @param string|null $displayLocation Optional location to clear, null for all
 * @return bool
 */
/**
 * Clear header cache entries
 * Note: This function performs bulk deletion by display_location or all entries.
 * This is intentional for cache clearing operations. If deleting specific cache entries
 * in the future, use the cache entry ID instead.
 * @param string|null $displayLocation Optional display location to clear cache for
 * @return bool Success
 */
function clearHeaderCache($displayLocation = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderCacheTable($conn);
    
    try {
        // Bulk deletion by display_location is acceptable for cache clearing
        // If deleting specific entries, use ID instead
        if ($displayLocation) {
            $stmt = $conn->prepare("DELETE FROM scheduled_header_cache WHERE display_location = ?");
            $stmt->bind_param("s", $displayLocation);
        } else {
            // Delete all cache entries
            $stmt = $conn->prepare("DELETE FROM scheduled_header_cache");
        }
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error clearing header cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Get active header for a display location
 * @param string $displayLocation 'admin', 'frontend', or 'both'
 * @param DateTime|null $currentDateTime Current date/time (defaults to now)
 * @param bool $useCache Whether to use cache
 * @return array|null Header data or null
 */
function getActiveHeader($displayLocation, $currentDateTime = null, $useCache = true) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    // Ensure tables exist
    createScheduledHeadersTable($conn);
    createScheduledHeaderCacheTable($conn);
    
    if ($currentDateTime === null) {
        $currentDateTime = new DateTime();
    }
    
    // Check cache first if enabled
    if ($useCache) {
        $cached = getCachedHeader($displayLocation);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    try {
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');
        $currentDay = (int)$currentDateTime->format('j');
        $currentMonth = (int)$currentDateTime->format('n');
        $dayOfWeek = (int)$currentDateTime->format('w'); // 0 = Sunday, 6 = Saturday
        
        // Build query for active headers - simplified approach
        $query = "SELECT * FROM scheduled_headers 
                  WHERE is_active = 1 
                  AND (display_location = ? OR display_location = 'both')
                  ORDER BY priority DESC, start_date DESC, start_time DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return getDefaultHeader($displayLocation);
        }
        
        $stmt->bind_param("s", $displayLocation);
        $stmt->execute();
        $result = $stmt->get_result();
        $headers = [];
        while ($row = $result->fetch_assoc()) {
            if (isHeaderActive($row, $currentDateTime)) {
                $headers[] = $row;
            }
        }
        $stmt->close();
        
        if (!empty($headers)) {
            $header = $headers[0]; // Highest priority
            // Cache the result
            if ($useCache) {
                setCachedHeader($displayLocation, $header);
            }
            return $header;
        }
        
        // If no active header, return default
        return getDefaultHeader($displayLocation);
        
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting active header: " . $e->getMessage());
        return getDefaultHeader($displayLocation);
    }
}


/**
 * Get cached header
 * @param string $displayLocation
 * @return array|null
 */
function getCachedHeader($displayLocation) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    createScheduledHeaderCacheTable($conn);
    
    try {
        $cacheKey = 'header_' . $displayLocation . '_' . date('Y-m-d_H');
        $stmt = $conn->prepare("SELECT cached_data, expires_at FROM scheduled_header_cache 
                                WHERE cache_key = ? AND expires_at > NOW()");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $cacheKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return json_decode($row['cached_data'], true);
        }
        
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting cached header: " . $e->getMessage());
        return null;
    }
}

/**
 * Save scheduled header with related data
 * @param array $headerData Header main data
 * @param array $images Images array
 * @param array $textOverlays Text overlays array
 * @param array $ctas CTAs array
 * @param bool $createVersion Whether to create version
 * @return int|false Header ID or false on failure
 */
function saveScheduledHeader($headerData, $images = [], $textOverlays = [], $ctas = [], $createVersion = true) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Ensure tables exist
    createScheduledHeadersTable($conn);
    createScheduledHeaderImagesTable($conn);
    createScheduledHeaderTextOverlaysTable($conn);
    createScheduledHeaderCTAsTable($conn);
    createScheduledHeaderVersionsTable($conn);
    
    try {
        $conn->begin_transaction();
        
        $headerId = $headerData['id'] ?? null;
        $isUpdate = !empty($headerId);
        
        // If updating, create version before changes
        if ($isUpdate && $createVersion) {
            $existingHeader = getScheduledHeaderById($headerId);
            if ($existingHeader) {
                createHeaderVersion($headerId, $existingHeader, 
                    $existingHeader['images'] ?? [], 
                    $existingHeader['text_overlays'] ?? [], 
                    $existingHeader['ctas'] ?? []);
            }
        }
        
        // Prepare header data
        $name = $headerData['name'] ?? '';
        $description = $headerData['description'] ?? null;
        $isDefault = isset($headerData['is_default']) ? (int)$headerData['is_default'] : 0;
        $priority = isset($headerData['priority']) ? (int)$headerData['priority'] : 0;
        $displayLocation = $headerData['display_location'] ?? 'both';
        $backgroundColor = $headerData['background_color'] ?? null;
        $backgroundImage = $headerData['background_image'] ?? null;
        $backgroundPosition = $headerData['background_position'] ?? 'center';
        $backgroundSize = $headerData['background_size'] ?? 'cover';
        $backgroundRepeat = $headerData['background_repeat'] ?? 'no-repeat';
        $headerHeight = $headerData['header_height'] ?? null;
        $transitionType = $headerData['transition_type'] ?? 'fade';
        $transitionDuration = isset($headerData['transition_duration']) ? (int)$headerData['transition_duration'] : 300;
        $timezone = $headerData['timezone'] ?? 'UTC';
        $isRecurring = isset($headerData['is_recurring']) ? (int)$headerData['is_recurring'] : 0;
        $recurrenceType = $headerData['recurrence_type'] ?? null;
        $recurrenceDay = isset($headerData['recurrence_day']) ? (int)$headerData['recurrence_day'] : null;
        $recurrenceMonth = isset($headerData['recurrence_month']) ? (int)$headerData['recurrence_month'] : null;
        $startDate = $headerData['start_date'] ?? date('Y-m-d');
        $startTime = $headerData['start_time'] ?? '00:00:00';
        $endDate = $headerData['end_date'] ?? null;
        $endTime = $headerData['end_time'] ?? null;
        $isActive = isset($headerData['is_active']) ? (int)$headerData['is_active'] : 1;
        $testModeEnabled = isset($headerData['test_mode_enabled']) ? (int)$headerData['test_mode_enabled'] : 0;
        $logoPath = $headerData['logo_path'] ?? null;
        $logoPosition = $headerData['logo_position'] ?? null;
        $searchBarVisible = isset($headerData['search_bar_visible']) ? (int)$headerData['search_bar_visible'] : 1;
        $searchBarStyle = $headerData['search_bar_style'] ?? null;
        $menuItemsVisible = isset($headerData['menu_items_visible']) ? (int)$headerData['menu_items_visible'] : 1;
        $menuItemsStyle = $headerData['menu_items_style'] ?? null;
        $userInfoVisible = isset($headerData['user_info_visible']) ? (int)$headerData['user_info_visible'] : 1;
        $userInfoStyle = $headerData['user_info_style'] ?? null;
        
        if ($isUpdate) {
            // Update existing header
            $stmt = $conn->prepare("UPDATE scheduled_headers SET 
                name = ?, description = ?, is_default = ?, priority = ?, display_location = ?,
                background_color = ?, background_image = ?, background_position = ?, background_size = ?, background_repeat = ?,
                header_height = ?, transition_type = ?, transition_duration = ?, timezone = ?,
                is_recurring = ?, recurrence_type = ?, recurrence_day = ?, recurrence_month = ?,
                start_date = ?, start_time = ?, end_date = ?, end_time = ?,
                is_active = ?, test_mode_enabled = ?,
                logo_path = ?, logo_position = ?,
                search_bar_visible = ?, search_bar_style = ?,
                menu_items_visible = ?, menu_items_style = ?,
                user_info_visible = ?, user_info_style = ?
                WHERE id = ?");
            
            $stmt->bind_param("ssiisssssssisississiisssssssssssi",
                $name, $description, $isDefault, $priority, $displayLocation,
                $backgroundColor, $backgroundImage, $backgroundPosition, $backgroundSize, $backgroundRepeat,
                $headerHeight, $transitionType, $transitionDuration, $timezone,
                $isRecurring, $recurrenceType, $recurrenceDay, $recurrenceMonth,
                $startDate, $startTime, $endDate, $endTime,
                $isActive, $testModeEnabled,
                $logoPath, $logoPosition,
                $searchBarVisible, $searchBarStyle,
                $menuItemsVisible, $menuItemsStyle,
                $userInfoVisible, $userInfoStyle,
                $headerId
            );
        } else {
            // Insert new header
            $stmt = $conn->prepare("INSERT INTO scheduled_headers 
                (name, description, is_default, priority, display_location,
                background_color, background_image, background_position, background_size, background_repeat,
                header_height, transition_type, transition_duration, timezone,
                is_recurring, recurrence_type, recurrence_day, recurrence_month,
                start_date, start_time, end_date, end_time,
                is_active, test_mode_enabled,
                logo_path, logo_position,
                search_bar_visible, search_bar_style,
                menu_items_visible, menu_items_style,
                user_info_visible, user_info_style)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssiisssssssisississiisssssssssss",
                $name, $description, $isDefault, $priority, $displayLocation,
                $backgroundColor, $backgroundImage, $backgroundPosition, $backgroundSize, $backgroundRepeat,
                $headerHeight, $transitionType, $transitionDuration, $timezone,
                $isRecurring, $recurrenceType, $recurrenceDay, $recurrenceMonth,
                $startDate, $startTime, $endDate, $endTime,
                $isActive, $testModeEnabled,
                $logoPath, $logoPosition,
                $searchBarVisible, $searchBarStyle,
                $menuItemsVisible, $menuItemsStyle,
                $userInfoVisible, $userInfoStyle
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to save header: " . $stmt->error);
        }
        
        if (!$isUpdate) {
            $headerId = $conn->insert_id;
        }
        $stmt->close();
        
        // If this is set as default, unset other defaults for same location
        if ($isDefault) {
            $unsetStmt = $conn->prepare("UPDATE scheduled_headers SET is_default = 0 
                                        WHERE id != ? AND is_default = 1 
                                        AND (display_location = ? OR display_location = 'both')");
            $unsetStmt->bind_param("is", $headerId, $displayLocation);
            $unsetStmt->execute();
            $unsetStmt->close();
        }
        
        // Delete existing related data using foreign key (acceptable for cascading deletes)
        // Note: If updating individual items, use their own ID instead
        $deleteImagesStmt = $conn->prepare("DELETE FROM scheduled_header_images WHERE header_id = ?");
        $deleteImagesStmt->bind_param("i", $headerId);
        $deleteImagesStmt->execute();
        $deleteImagesStmt->close();
        
        $deleteOverlaysStmt = $conn->prepare("DELETE FROM scheduled_header_text_overlays WHERE header_id = ?");
        $deleteOverlaysStmt->bind_param("i", $headerId);
        $deleteOverlaysStmt->execute();
        $deleteOverlaysStmt->close();
        
        $deleteCTAsStmt = $conn->prepare("DELETE FROM scheduled_header_ctas WHERE header_id = ?");
        $deleteCTAsStmt->bind_param("i", $headerId);
        $deleteCTAsStmt->execute();
        $deleteCTAsStmt->close();
        
        // Insert images
        foreach ($images as $image) {
            $imgStmt = $conn->prepare("INSERT INTO scheduled_header_images 
                (header_id, image_path, image_path_webp, original_width, original_height, optimized_width, optimized_height,
                position, alignment, width, height, opacity, z_index, display_order, mobile_visible, mobile_width, mobile_height, is_ai_generated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $imgStmt->bind_param("issiiiiissdiiissi",
                $headerId,
                $image['image_path'] ?? '',
                $image['image_path_webp'] ?? null,
                $image['original_width'] ?? null,
                $image['original_height'] ?? null,
                $image['optimized_width'] ?? null,
                $image['optimized_height'] ?? null,
                $image['position'] ?? 'center',
                $image['alignment'] ?? null,
                $image['width'] ?? null,
                $image['height'] ?? null,
                $image['opacity'] ?? 1.0,
                $image['z_index'] ?? 0,
                $image['display_order'] ?? 0,
                $image['mobile_visible'] ?? 1,
                $image['mobile_width'] ?? null,
                $image['mobile_height'] ?? null,
                $image['is_ai_generated'] ?? 0
            );
            $imgStmt->execute();
            $imgStmt->close();
        }
        
        // Insert text overlays
        foreach ($textOverlays as $overlay) {
            $overlayStmt = $conn->prepare("INSERT INTO scheduled_header_text_overlays 
                (header_id, content, position, alignment, font_size, font_color, font_family, font_weight,
                background_color, padding, border_radius, z_index, display_order, mobile_visible, mobile_font_size)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $overlayStmt->bind_param("issssssssssiiis",
                $headerId,
                $overlay['content'] ?? '',
                $overlay['position'] ?? 'center',
                $overlay['alignment'] ?? null,
                $overlay['font_size'] ?? null,
                $overlay['font_color'] ?? null,
                $overlay['font_family'] ?? null,
                $overlay['font_weight'] ?? null,
                $overlay['background_color'] ?? null,
                $overlay['padding'] ?? null,
                $overlay['border_radius'] ?? null,
                $overlay['z_index'] ?? 0,
                $overlay['display_order'] ?? 0,
                $overlay['mobile_visible'] ?? 1,
                $overlay['mobile_font_size'] ?? null
            );
            $overlayStmt->execute();
            $overlayStmt->close();
        }
        
        // Insert CTAs
        foreach ($ctas as $cta) {
            $ctaStmt = $conn->prepare("INSERT INTO scheduled_header_ctas 
                (header_id, text, url, button_style, position, alignment, font_size, font_color, background_color,
                padding, border_radius, z_index, display_order, open_in_new_tab, tracking_enabled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ctaStmt->bind_param("issssssssssiiii",
                $headerId,
                $cta['text'] ?? '',
                $cta['url'] ?? '',
                $cta['button_style'] ?? null,
                $cta['position'] ?? 'center',
                $cta['alignment'] ?? null,
                $cta['font_size'] ?? null,
                $cta['font_color'] ?? null,
                $cta['background_color'] ?? null,
                $cta['padding'] ?? null,
                $cta['border_radius'] ?? null,
                $cta['z_index'] ?? 0,
                $cta['display_order'] ?? 0,
                $cta['open_in_new_tab'] ?? 0,
                $cta['tracking_enabled'] ?? 1
            );
            $ctaStmt->execute();
            $ctaStmt->close();
        }
        
        // Clear cache
        clearHeaderCache();
        
        $conn->commit();
        return $headerId;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving scheduled header: " . $e->getMessage());
        return false;
    }
}

/**
 * Get scheduled header by ID
 * @param int $headerId
 * @return array|null
 */
function getScheduledHeaderById($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_headers WHERE id = ?");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $header = $result->fetch_assoc();
        $stmt->close();
        
        if ($header) {
            // Get related data
            $header['images'] = getHeaderImages($headerId);
            $header['text_overlays'] = getHeaderTextOverlays($headerId);
            $header['ctas'] = getHeaderCTAs($headerId);
        }
        
        return $header;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting scheduled header: " . $e->getMessage());
        return null;
    }
}

/**
 * Get header images
 * @param int $headerId
 * @return array
 */
function getHeaderImages($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_images WHERE header_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
        return $images;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header images: " . $e->getMessage());
        return [];
    }
}

/**
 * Get header text overlays
 * @param int $headerId
 * @return array
 */
function getHeaderTextOverlays($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_text_overlays WHERE header_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $overlays = [];
        while ($row = $result->fetch_assoc()) {
            $overlays[] = $row;
        }
        $stmt->close();
        return $overlays;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header text overlays: " . $e->getMessage());
        return [];
    }
}

/**
 * Get header CTAs
 * @param int $headerId
 * @return array
 */
function getHeaderCTAs($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_ctas WHERE header_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ctas = [];
        while ($row = $result->fetch_assoc()) {
            $ctas[] = $row;
        }
        $stmt->close();
        return $ctas;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header CTAs: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete scheduled header
 * @param int $id
 * @return bool
 */
function deleteScheduledHeader($id) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $conn->begin_transaction();
        
        // Delete header (cascade will delete related data)
        $stmt = $conn->prepare("DELETE FROM scheduled_headers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        // Clear cache
        clearHeaderCache();
        
        $conn->commit();
        return $success;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Error deleting scheduled header: " . $e->getMessage());
        return false;
    }
}

/**
 * Create header version
 * @param int $headerId
 * @param array $headerData
 * @param array $images
 * @param array $textOverlays
 * @param array $ctas
 * @return bool
 */
function createHeaderVersion($headerId, $headerData, $images, $textOverlays, $ctas) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderVersionsTable($conn);
    
    try {
        // Get next version number
        $versionStmt = $conn->prepare("SELECT MAX(version_number) as max_version FROM scheduled_header_versions WHERE header_id = ?");
        $versionStmt->bind_param("i", $headerId);
        $versionStmt->execute();
        $versionResult = $versionStmt->get_result();
        $versionRow = $versionResult->fetch_assoc();
        $nextVersion = ($versionRow['max_version'] ?? 0) + 1;
        $versionStmt->close();
        
        $stmt = $conn->prepare("INSERT INTO scheduled_header_versions 
            (header_id, version_number, header_data, images_data, text_overlays_data, ctas_data, change_description)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $headerDataJson = json_encode($headerData);
        $imagesJson = json_encode($images);
        $textOverlaysJson = json_encode($textOverlays);
        $ctasJson = json_encode($ctas);
        $changeDescription = $headerData['change_description'] ?? null;
        
        $stmt->bind_param("iisssss", $headerId, $nextVersion, $headerDataJson, $imagesJson, $textOverlaysJson, $ctasJson, $changeDescription);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating header version: " . $e->getMessage());
        return false;
    }
}

/**
 * Get header versions
 * @param int $headerId
 * @return array
 */
function getHeaderVersions($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_versions WHERE header_id = ? ORDER BY version_number DESC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $versions = [];
        while ($row = $result->fetch_assoc()) {
            $versions[] = $row;
        }
        $stmt->close();
        return $versions;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header versions: " . $e->getMessage());
        return [];
    }
}

/**
 * Rollback to version
 * @param int $headerId
 * @param int $versionNumber
 * @return bool
 */
function rollbackToVersion($headerId, $versionNumber) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_versions WHERE header_id = ? AND version_number = ?");
        $stmt->bind_param("ii", $headerId, $versionNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $version = $result->fetch_assoc();
        $stmt->close();
        
        if (!$version) {
            return false;
        }
        
        $headerData = json_decode($version['header_data'], true);
        $images = json_decode($version['images_data'], true) ?: [];
        $textOverlays = json_decode($version['text_overlays_data'], true) ?: [];
        $ctas = json_decode($version['ctas_data'], true) ?: [];
        
        $headerData['id'] = $headerId;
        $headerData['change_description'] = "Rolled back to version $versionNumber";
        
        return saveScheduledHeader($headerData, $images, $textOverlays, $ctas, false);
    } catch (mysqli_sql_exception $e) {
        error_log("Error rolling back to version: " . $e->getMessage());
        return false;
    }
}

/**
 * Track header event (view, click, conversion)
 * @param int $headerId
 * @param string $eventType 'view', 'click', 'conversion'
 * @param string $displayLocation 'admin' or 'frontend'
 * @param int|null $ctaId CTA ID if click/conversion
 * @param float|null $conversionValue Conversion value if conversion
 * @return bool
 */
function trackHeaderEvent($headerId, $eventType, $displayLocation, $ctaId = null, $conversionValue = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderAnalyticsTable($conn);
    
    try {
        $userIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $sessionId = session_id() ?: null;
        
        $stmt = $conn->prepare("INSERT INTO scheduled_header_analytics 
            (header_id, cta_id, event_type, display_location, user_ip, user_agent, referrer, session_id, conversion_value)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssssd", $headerId, $ctaId, $eventType, $displayLocation, $userIp, $userAgent, $referrer, $sessionId, $conversionValue);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error tracking header event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get header analytics
 * @param int $headerId
 * @param string|null $startDate
 * @param string|null $endDate
 * @return array
 */
function getHeaderAnalytics($headerId, $startDate = null, $endDate = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "SELECT event_type, display_location, COUNT(*) as count, 
                 SUM(CASE WHEN conversion_value IS NOT NULL THEN conversion_value ELSE 0 END) as total_value
                 FROM scheduled_header_analytics 
                 WHERE header_id = ?";
        $params = [$headerId];
        $types = "i";
        
        if ($startDate) {
            $query .= " AND created_at >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        
        if ($endDate) {
            $query .= " AND created_at <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        
        $query .= " GROUP BY event_type, display_location";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics = [];
        while ($row = $result->fetch_assoc()) {
            $analytics[] = $row;
        }
        $stmt->close();
        
        return $analytics;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header analytics: " . $e->getMessage());
        return [];
    }
}

/**
 * Migrate setup_scripts table
 * Creates table for tracking setup script execution and lifecycle
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateSetupScriptsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS setup_scripts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            script_name VARCHAR(255) UNIQUE NOT NULL,
            script_path VARCHAR(500) NOT NULL,
            script_type ENUM('setup', 'migration', 'cleanup', 'data_import', 'parameter') DEFAULT 'setup',
            status ENUM('pending', 'completed', 'archived', 'deleted') DEFAULT 'pending',
            one_time_only TINYINT(1) DEFAULT 0,
            can_rerun TINYINT(1) DEFAULT 1,
            executed_at DATETIME,
            completed_at DATETIME,
            archived_at DATETIME,
            deleted_at DATETIME,
            execution_count INT DEFAULT 0,
            last_execution DATETIME,
            execution_time_ms INT,
            steps TEXT,
            results TEXT,
            retention_days INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_archived_at (archived_at),
            INDEX idx_script_type (script_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating setup_scripts table: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate scripts_settings table
 * Creates table for storing script settings (retention, behavior, etc.)
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateScriptsSettingsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS scripts_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            description TEXT,
            category VARCHAR(50) DEFAULT 'other',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scripts_settings table: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate scripts_templates table
 * Creates table for storing script templates
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateScriptsTemplatesTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS scripts_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(100) NOT NULL,
            template_type ENUM('default', 'setup', 'migration', 'cleanup', 'data_import', 'parameter') NOT NULL,
            template_data TEXT NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_default_per_type (template_type, is_default),
            INDEX idx_template_type (template_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scripts_templates table: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate scripts_archive table
 * Creates table for storing additional archive metadata
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateScriptsArchiveTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        // First ensure setup_scripts table exists
        migrateSetupScriptsTable($conn);
        
        $sql = "CREATE TABLE IF NOT EXISTS scripts_archive (
            id INT AUTO_INCREMENT PRIMARY KEY,
            script_id INT NOT NULL,
            archive_reason VARCHAR(255),
            archive_notes TEXT,
            file_size_bytes BIGINT,
            file_hash VARCHAR(64),
            archived_by VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_script_id (script_id),
            FOREIGN KEY (script_id) REFERENCES setup_scripts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating scripts_archive table: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// SCRIPT MANAGEMENT - Helper Functions
// ============================================================================

/**
 * Register a setup script in the database
 * @param string $scriptPath Full path to the script file
 * @param string $scriptType Type of script: 'setup', 'migration', 'cleanup', 'data_import', 'parameter'
 * @param bool $oneTimeOnly Whether script can only run once
 * @param bool $canRerun Whether script can be rerun after completion
 * @param int|null $retentionDays Custom retention days (NULL = use default)
 * @return array|false Script data if successful, false on failure
 */
function registerSetupScript($scriptPath, $scriptType = 'setup', $oneTimeOnly = false, $canRerun = true, $retentionDays = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateSetupScriptsTable($conn);
    
    try {
        $scriptName = basename($scriptPath);
        $now = date('Y-m-d H:i:s');
        
        // Check if script already exists
        $checkStmt = $conn->prepare("SELECT * FROM setup_scripts WHERE script_name = ?");
        $checkStmt->bind_param("s", $scriptName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existing = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($existing) {
            // Update execution info
            $updateStmt = $conn->prepare("UPDATE setup_scripts SET execution_count = execution_count + 1, last_execution = ? WHERE id = ?");
            $updateStmt->bind_param("si", $now, $existing['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Return updated record
            $getStmt = $conn->prepare("SELECT * FROM setup_scripts WHERE id = ?");
            $getStmt->bind_param("i", $existing['id']);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $script = $result->fetch_assoc();
            $getStmt->close();
            
            return $script;
        } else {
            // Insert new script
            $insertStmt = $conn->prepare("INSERT INTO setup_scripts (script_name, script_path, script_type, one_time_only, can_rerun, executed_at, last_execution, execution_count, retention_days) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $insertStmt->bind_param("sssiiisi", $scriptName, $scriptPath, $scriptType, $oneTimeOnly, $canRerun, $now, $now, $retentionDays);
            $insertStmt->execute();
            $scriptId = $conn->insert_id;
            $insertStmt->close();
            
            // Return new record
            $getStmt = $conn->prepare("SELECT * FROM setup_scripts WHERE id = ?");
            $getStmt->bind_param("i", $scriptId);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $script = $result->fetch_assoc();
            $getStmt->close();
            
            return $script;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Error registering setup script: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark a script as completed and move to archive
 * @param string $scriptPath Full path to the script file
 * @param array $steps Array of execution steps
 * @param array $results Execution results
 * @return bool Success
 */
function markScriptCompleted($scriptPath, $steps = [], $results = []) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $scriptName = basename($scriptPath);
        $now = date('Y-m-d H:i:s');
        $stepsJson = json_encode($steps);
        $resultsJson = json_encode($results);
        
        // Get script record
        $getStmt = $conn->prepare("SELECT * FROM setup_scripts WHERE script_name = ?");
        $getStmt->bind_param("s", $scriptName);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $script = $result->fetch_assoc();
        $getStmt->close();
        
        if (!$script) {
            return false;
        }
        
        // Update script status
        $updateStmt = $conn->prepare("UPDATE setup_scripts SET status = 'completed', completed_at = ?, steps = ?, results = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $now, $stepsJson, $resultsJson, $script['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Move file to archive directory
        $archiveDir = __DIR__ . '/../admin/setup/archive/';
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
        
        $archivePath = $archiveDir . $scriptName;
        if (file_exists($scriptPath) && is_file($scriptPath)) {
            if (copy($scriptPath, $archivePath)) {
                // Update archived_at timestamp
                $archiveStmt = $conn->prepare("UPDATE setup_scripts SET status = 'archived', archived_at = ? WHERE id = ?");
                $archiveStmt->bind_param("si", $now, $script['id']);
                $archiveStmt->execute();
                $archiveStmt->close();
                
                // Create archive metadata
                $fileSize = filesize($archivePath);
                $fileHash = hash_file('sha256', $archivePath);
                $archiveReason = 'Script execution completed successfully';
                
                saveArchiveMetadata($script['id'], [
                    'archive_reason' => $archiveReason,
                    'file_size_bytes' => $fileSize,
                    'file_hash' => $fileHash,
                    'archived_by' => 'system'
                ]);
            }
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error marking script as completed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get script status
 * @param string $scriptPath Full path to the script file
 * @return array|false Script data or false if not found
 */
function getSetupScriptStatus($scriptPath) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $scriptName = basename($scriptPath);
        $stmt = $conn->prepare("SELECT * FROM setup_scripts WHERE script_name = ?");
        $stmt->bind_param("s", $scriptName);
        $stmt->execute();
        $result = $stmt->get_result();
        $script = $result->fetch_assoc();
        $stmt->close();
        
        return $script ?: false;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting script status: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a script can be rerun
 * @param string $scriptPath Full path to the script file
 * @return bool True if script can be rerun
 */
function canRerunScript($scriptPath) {
    $script = getSetupScriptStatus($scriptPath);
    if (!$script) {
        return true; // New script can be run
    }
    
    // Check one_time_only flag
    if ($script['one_time_only'] && $script['status'] === 'completed') {
        return false;
    }
    
    // Check can_rerun flag
    if (!$script['can_rerun'] && $script['status'] === 'completed') {
        return false;
    }
    
    return true;
}

/**
 * Get script settings by category
 * @param string|null $category Category filter (null = all)
 * @return array Array of settings
 */
function getScriptSettings($category = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    migrateScriptsSettingsTable($conn);
    
    try {
        if ($category) {
            $stmt = $conn->prepare("SELECT * FROM scripts_settings WHERE category = ? ORDER BY setting_key");
            $stmt->bind_param("s", $category);
        } else {
            $stmt = $conn->prepare("SELECT * FROM scripts_settings ORDER BY category, setting_key");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            // Convert value based on type
            if ($row['setting_type'] === 'json') {
                $row['value'] = json_decode($row['setting_value'], true);
            } elseif ($row['setting_type'] === 'integer') {
                $row['value'] = (int)$row['setting_value'];
            } elseif ($row['setting_type'] === 'boolean') {
                $row['value'] = (bool)$row['setting_value'];
            } else {
                $row['value'] = $row['setting_value'];
            }
            $settings[] = $row;
        }
        $stmt->close();
        
        return $settings;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting script settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Save a script setting
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $type Setting type: 'string', 'integer', 'boolean', 'json'
 * @param string $category Category
 * @param string $description Description
 * @return bool Success
 */
function saveScriptSetting($key, $value, $type = 'string', $category = 'other', $description = '') {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateScriptsSettingsTable($conn);
    
    try {
        // Convert value to string for storage
        if ($type === 'json') {
            $valueStr = json_encode($value);
        } else {
            $valueStr = (string)$value;
        }
        
        $stmt = $conn->prepare("INSERT INTO scripts_settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?, category = ?, description = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("sssssssss", $key, $valueStr, $type, $category, $description, $valueStr, $type, $category, $description);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error saving script setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Get script template for a script type
 * @param string $scriptType Script type: 'default', 'setup', 'migration', 'cleanup', 'data_import', 'parameter'
 * @return array|false Template data or false if not found
 */
function getScriptTemplate($scriptType = 'default') {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateScriptsTemplatesTable($conn);
    
    try {
        // First try to get default template for this type
        $stmt = $conn->prepare("SELECT * FROM scripts_templates WHERE template_type = ? AND is_default = 1 LIMIT 1");
        $stmt->bind_param("s", $scriptType);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template) {
            $template['template_data'] = json_decode($template['template_data'], true);
            return $template;
        }
        
        // If no default for this type, try 'default' type
        if ($scriptType !== 'default') {
            return getScriptTemplate('default');
        }
        
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting script template: " . $e->getMessage());
        return false;
    }
}

/**
 * Save a script template
 * @param string $templateName Template name
 * @param string $templateType Template type
 * @param array $templateData Template data (will be JSON encoded)
 * @param bool $isDefault Whether this is the default template for this type
 * @param string $description Description
 * @return bool Success
 */
function saveScriptTemplates($templateName, $templateType, $templateData, $isDefault = false, $description = '') {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateScriptsTemplatesTable($conn);
    
    try {
        $templateDataJson = json_encode($templateData);
        
        // If setting as default, unset other defaults for this type
        if ($isDefault) {
            $unsetStmt = $conn->prepare("UPDATE scripts_templates SET is_default = 0 WHERE template_type = ?");
            $unsetStmt->bind_param("s", $templateType);
            $unsetStmt->execute();
            $unsetStmt->close();
        }
        
        $stmt = $conn->prepare("INSERT INTO scripts_templates (template_name, template_type, template_data, is_default, description) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE template_data = ?, is_default = ?, description = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("sssississ", $templateName, $templateType, $templateDataJson, $isDefault, $description, $templateDataJson, $isDefault, $description);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error saving script template: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all templates, optionally filtered by type
 * @param string|null $templateType Template type filter (null = all)
 * @return array Array of templates
 */
function getScriptTemplates($templateType = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    migrateScriptsTemplatesTable($conn);
    
    try {
        if ($templateType) {
            $stmt = $conn->prepare("SELECT * FROM scripts_templates WHERE template_type = ? ORDER BY is_default DESC, template_name");
            $stmt->bind_param("s", $templateType);
        } else {
            $stmt = $conn->prepare("SELECT * FROM scripts_templates ORDER BY template_type, is_default DESC, template_name");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $row['template_data'] = json_decode($row['template_data'], true);
            $templates[] = $row;
        }
        $stmt->close();
        
        return $templates;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting script templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Set a template as default for its type
 * @param int $templateId Template ID
 * @return bool Success
 */
function setDefaultTemplates($templateId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Get template to find its type
        $getStmt = $conn->prepare("SELECT template_type FROM scripts_templates WHERE id = ?");
        $getStmt->bind_param("i", $templateId);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $template = $result->fetch_assoc();
        $getStmt->close();
        
        if (!$template) {
            return false;
        }
        
        // Unset other defaults for this type
        $unsetStmt = $conn->prepare("UPDATE scripts_templates SET is_default = 0 WHERE template_type = ?");
        $unsetStmt->bind_param("s", $template['template_type']);
        $unsetStmt->execute();
        $unsetStmt->close();
        
        // Set this template as default
        $setStmt = $conn->prepare("UPDATE scripts_templates SET is_default = 1 WHERE id = ?");
        $setStmt->bind_param("i", $templateId);
        $success = $setStmt->execute();
        $setStmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error setting default template: " . $e->getMessage());
        return false;
    }
}

/**
 * Get archive metadata for a script
 * @param int $scriptId Script ID
 * @return array|false Archive metadata or false if not found
 */
function getArchiveMetadata($scriptId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateScriptsArchiveTable($conn);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scripts_archive WHERE script_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $scriptId);
        $stmt->execute();
        $result = $stmt->get_result();
        $metadata = $result->fetch_assoc();
        $stmt->close();
        
        return $metadata ?: false;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting archive metadata: " . $e->getMessage());
        return false;
    }
}

/**
 * Save archive metadata
 * @param int $scriptId Script ID
 * @param array $metadata Metadata array
 * @return bool Success
 */
function saveArchiveMetadata($scriptId, $metadata) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateScriptsArchiveTable($conn);
    
    try {
        $archiveReason = $metadata['archive_reason'] ?? null;
        $archiveNotes = $metadata['archive_notes'] ?? null;
        $fileSizeBytes = $metadata['file_size_bytes'] ?? null;
        $fileHash = $metadata['file_hash'] ?? null;
        $archivedBy = $metadata['archived_by'] ?? 'system';
        
        $stmt = $conn->prepare("INSERT INTO scripts_archive (script_id, archive_reason, archive_notes, file_size_bytes, file_hash, archived_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $scriptId, $archiveReason, $archiveNotes, $fileSizeBytes, $fileHash, $archivedBy);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error saving archive metadata: " . $e->getMessage());
        return false;
    }
}

/**
 * Get archived scripts with filters
 * @param array $filters Filter array (status, script_type, days_old, etc.)
 * @return array Array of archived scripts
 */
function getArchivedScripts($filters = []) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $where = ["status = 'archived'"];
        $params = [];
        $types = '';
        
        if (isset($filters['script_type'])) {
            $where[] = "script_type = ?";
            $params[] = $filters['script_type'];
            $types .= 's';
        }
        
        if (isset($filters['days_old'])) {
            $where[] = "archived_at <= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $filters['days_old'];
            $types .= 'i';
        }
        
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM setup_scripts WHERE {$whereClause} ORDER BY archived_at DESC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $scripts = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['steps']) {
                $row['steps'] = json_decode($row['steps'], true);
            }
            if ($row['results']) {
                $row['results'] = json_decode($row['results'], true);
            }
            $scripts[] = $row;
        }
        $stmt->close();
        
        return $scripts;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting archived scripts: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete scripts archived more than X days ago
 * @param int|null $daysOld Days old threshold (null = use retention settings)
 * @return array Array with 'deleted_count' and 'deleted_scripts'
 */
function deleteOldArchivedScripts($daysOld = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['deleted_count' => 0, 'deleted_scripts' => []];
    }
    
    try {
        // If daysOld not specified, get from settings
        if ($daysOld === null) {
            $globalRetention = getParameter('Setup', '--setup-script-retention-days-global', 30);
            $daysOld = (int)$globalRetention;
        }
        
        $filters = ['days_old' => $daysOld];
        $scripts = getArchivedScripts($filters);
        
        $deletedCount = 0;
        $deletedScripts = [];
        $archiveDir = __DIR__ . '/../admin/setup/archive/';
        
        foreach ($scripts as $script) {
            // Delete file from archive
            $archivePath = $archiveDir . $script['script_name'];
            if (file_exists($archivePath)) {
                unlink($archivePath);
            }
            
            // Update status to deleted
            $now = date('Y-m-d H:i:s');
            $updateStmt = $conn->prepare("UPDATE setup_scripts SET status = 'deleted', deleted_at = ? WHERE id = ?");
            $updateStmt->bind_param("si", $now, $script['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $deletedCount++;
            $deletedScripts[] = $script['script_name'];
        }
        
        return [
            'deleted_count' => $deletedCount,
            'deleted_scripts' => $deletedScripts
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Error deleting old archived scripts: " . $e->getMessage());
        return ['deleted_count' => 0, 'deleted_scripts' => []];
    }
}

// ============================================================================
// FILE PROTECTION SYSTEM - Table Migrations
// ============================================================================

/**
 * Migrate protected_files table
 * Creates table for storing protected file information
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateProtectedFilesTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS protected_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(500) UNIQUE NOT NULL,
            protection_level ENUM('hard_block', 'backup_required', 'backup_optional') NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_file_path (file_path),
            INDEX idx_protection_level (protection_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        
        // Seed initial BACKUP REQUIRED files (core critical files)
        $defaultFiles = [
            ['file_path' => 'config/database.php', 'protection_level' => 'backup_required', 'description' => 'Database configuration and connection - critical system file'],
            ['file_path' => 'admin/includes/layout.php', 'protection_level' => 'backup_required', 'description' => 'Layout wrapper system - critical for all admin pages'],
            ['file_path' => 'admin/includes/auth.php', 'protection_level' => 'backup_required', 'description' => 'Authentication system - critical for security'],
            ['file_path' => 'admin/includes/sidebar.php', 'protection_level' => 'backup_required', 'description' => 'Sidebar menu system - critical for navigation']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO protected_files (file_path, protection_level, description) VALUES (?, ?, ?)");
        foreach ($defaultFiles as $file) {
            $stmt->bind_param("sss", $file['file_path'], $file['protection_level'], $file['description']);
            $stmt->execute();
        }
        $stmt->close();
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating protected_files table: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate file_backups table
 * Creates table for storing file backups before modification
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateFileBackupsTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS file_backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(500) NOT NULL,
            backup_content TEXT NOT NULL,
            backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            modified_by VARCHAR(255),
            reason VARCHAR(255),
            restored TINYINT(1) DEFAULT 0,
            INDEX idx_file_path (file_path),
            INDEX idx_backup_timestamp (backup_timestamp),
            INDEX idx_restored (restored)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating file_backups table: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate customers table
 * Ensures customers table exists with all required columns
 * @param mysqli $conn Database connection
 * @return bool Success
 */
function migrateCustomersTable($conn) {
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(50),
            company VARCHAR(255),
            address_line1 VARCHAR(255),
            address_line2 VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100) DEFAULT 'Australia',
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8),
            status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_city (city),
            INDEX idx_postal_code (postal_code),
            INDEX idx_location (latitude, longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating customers table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new customer
 * @param array $data Customer data
 * @return int|false Customer ID on success, false on failure
 */
function createCustomer($data) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    migrateCustomersTable($conn);
    
    try {
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, company, address_line1, address_line2, city, state, postal_code, country, latitude, longitude, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $company = $data['company'] ?? null;
        $addressLine1 = $data['address_line1'] ?? null;
        $addressLine2 = $data['address_line2'] ?? null;
        $city = $data['city'] ?? null;
        $state = $data['state'] ?? null;
        $postalCode = $data['postal_code'] ?? null;
        $country = $data['country'] ?? 'Australia';
        $latitude = !empty($data['latitude']) ? $data['latitude'] : null;
        $longitude = !empty($data['longitude']) ? $data['longitude'] : null;
        $status = $data['status'] ?? 'active';
        $notes = $data['notes'] ?? null;
        
        $stmt->bind_param("ssssssssssddss", $name, $email, $phone, $company, $addressLine1, $addressLine2, $city, $state, $postalCode, $country, $latitude, $longitude, $status, $notes);
        
        if ($stmt->execute()) {
            $customerId = $conn->insert_id;
            $stmt->close();
            return $customerId;
        } else {
            $stmt->close();
            return false;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating customer: " . $e->getMessage());
        return false;
    }
}

/**
 * Get customer by ID
 * @param int $id Customer ID
 * @return array|null Customer data or null if not found
 */
function getCustomer($id) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        return $customer;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting customer: " . $e->getMessage());
        return null;
    }
}

/**
 * Update customer
 * @param int $id Customer ID
 * @param array $data Customer data
 * @return bool Success
 */
function updateCustomer($id, $data) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, company = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, latitude = ?, longitude = ?, status = ?, notes = ? WHERE id = ?");
        
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $company = $data['company'] ?? null;
        $addressLine1 = $data['address_line1'] ?? null;
        $addressLine2 = $data['address_line2'] ?? null;
        $city = $data['city'] ?? null;
        $state = $data['state'] ?? null;
        $postalCode = $data['postal_code'] ?? null;
        $country = $data['country'] ?? 'Australia';
        $latitude = !empty($data['latitude']) ? $data['latitude'] : null;
        $longitude = !empty($data['longitude']) ? $data['longitude'] : null;
        $status = $data['status'] ?? 'active';
        $notes = $data['notes'] ?? null;
        
        $stmt->bind_param("ssssssssssddssi", $name, $email, $phone, $company, $addressLine1, $addressLine2, $city, $state, $postalCode, $country, $latitude, $longitude, $status, $notes, $id);
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error updating customer: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete customer
 * @param int $id Customer ID
 * @return bool Success
 */
function deleteCustomer($id) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error deleting customer: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all customers
 * @param array $filters Optional filters (status, city, search)
 * @return array Array of customers
 */
function getAllCustomers($filters = []) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['city'])) {
            $where[] = "city = ?";
            $params[] = $filters['city'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = "(name LIKE ? OR email LIKE ? OR company LIKE ? OR address_line1 LIKE ? OR city LIKE ? OR state LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= str_repeat('s', 6);
        }
        
        $sql = "SELECT * FROM customers";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        $stmt->close();
        return $customers;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting customers: " . $e->getMessage());
        return [];
    }
}

/**
 * Search customers
 * @param string $query Search query
 * @return array Array of matching customers
 */
function searchCustomers($query) {
    return getAllCustomers(['search' => $query]);
}

/**
 * Geocode address and update customer coordinates
 * @param int $customerId Customer ID
 * @param string $address Full address string
 * @return bool Success
 */
function geocodeAddress($customerId, $address) {
    $apiKey = getGoogleMapsApiKey();
    if (empty($apiKey) || empty($address)) {
        return false;
    }
    
    // Use Google Geocoding API
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . urlencode($apiKey);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($data && $data['status'] === 'OK' && !empty($data['results'][0]['geometry']['location'])) {
        $location = $data['results'][0]['geometry']['location'];
        $latitude = $location['lat'];
        $longitude = $location['lng'];
        
        $conn = getDBConnection();
        if ($conn === null) {
            return false;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE customers SET latitude = ?, longitude = ? WHERE id = ?");
            $stmt->bind_param("ddi", $latitude, $longitude, $customerId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (mysqli_sql_exception $e) {
            error_log("Error updating customer coordinates: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

/**
 * Get Google Maps API key from settings
 * @return string|null API key or null if not set
 */
function getGoogleMapsApiKey() {
    return getSetting('google_maps_api_key', null);
}

