<?php
/**
 * Database Initialization Script
 * Run this script to create the necessary database tables and initialize default settings
 * 
 * Script Type: Utility (Keep - Used for database initialization)
 * Single Run: No (Safe to rerun - idempotent)
 * 
 * Usage: Navigate to /admin/init-db.php in your browser
 * Or run: php admin/init-db.php from command line
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Require authentication
requireAuth();

$conn = getDBConnection();
$success = [];
$errors = [];
$warnings = [];
$info = [];
$showAdminForm = false;
$adminCreated = false;

// Check if admin user needs to be created
$needsAdminUser = false;
if ($conn !== null) {
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();
    $needsAdminUser = ($row['count'] == 0);
}

// Handle admin user form submission
if ($needsAdminUser && isset($_POST['create_admin']) && $_POST['create_admin'] === '1') {
    $email = trim($_POST['admin_email'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $name = trim($_POST['admin_name'] ?? 'Administrator');
    
    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (empty($name)) {
        $name = 'Administrator';
    }
    
    // Create admin user if validation passed
    if (empty($errors) && $conn !== null) {
        $passwordHash = hashPassword($password);
        $insertStmt = $conn->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $email, $passwordHash, $name);
        
        if ($insertStmt->execute()) {
            $success[] = "Admin user created successfully";
            $info[] = "Email: " . htmlspecialchars($email);
            $adminCreated = true;
            $needsAdminUser = false; // User now exists
        } else {
            $errors[] = "Error creating admin user: " . $insertStmt->error;
        }
        $insertStmt->close();
    } else {
        $showAdminForm = true; // Show form again if validation failed
    }
} elseif ($needsAdminUser) {
    $showAdminForm = true; // Show form if no users exist
}

// Initialize database
$result = initializeDatabase();

if ($result) {
    $success[] = "Database tables created successfully";
    $info[] = "Users table: OK";
    $info[] = "Admin menus table: OK";
    $info[] = "Settings table: OK";
    $info[] = "Page columns table: OK";
    $info[] = "Setup icons table: OK";
    $info[] = "Footer data table: OK";
    
    // Run migration to add new columns and update existing data
    if ($conn !== null && function_exists('migrateSettingsTable')) {
        migrateSettingsTable($conn);
        $info[] = "Settings table migration: OK";
    }
    
    // Ensure footer_data table exists (in case it wasn't created)
    if ($conn !== null && function_exists('ensureFooterTableExists')) {
        if (ensureFooterTableExists($conn)) {
            $info[] = "Footer data table verified: OK";
        } else {
            $warnings[] = "Footer data table creation had issues";
        }
    }
} else {
    $errors[] = "Error creating database tables. Please check your database configuration.";
    $errors[] = "Make sure the database 'cabinetry_dev' exists.";
    }
    
    // Menu auto-creation removed - menu items must be manually created via the Menus page
    
    // Initialize menu width settings if they don't exist
    $checkMenuAdminWidth = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'menu_admin_width'";
    $result = $conn->query($checkMenuAdminWidth);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertMenuAdminWidth = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('menu_admin_width', '280', 'Menu - Admin', 'Admin Menu Width (px)', 'Set the width of the admin sidebar menu in pixels. Current width: 280px')";
        $conn->query($insertMenuAdminWidth);
        $info[] = "Initialized menu_admin_width setting";
    }
    
    $checkMenuFrontendWidth = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'menu_frontend_width'";
    $result = $conn->query($checkMenuFrontendWidth);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertMenuFrontendWidth = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('menu_frontend_width', '280', 'Menu - Frontend', 'Frontend Menu Width (px)', 'Set the width of the frontend sidebar menu in pixels. Current width: 280px')";
        $conn->query($insertMenuFrontendWidth);
        $info[] = "Initialized menu_frontend_width setting";
    }
    
    // Initialize header height setting if it doesn't exist
    $checkHeaderHeight = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'header_height'";
    $result = $conn->query($checkHeaderHeight);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertHeaderHeight = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('header_height', '100', 'Layout', 'Header Height (px)', 'Set the height of the admin header in pixels. Current height: 100px')";
        $conn->query($insertHeaderHeight);
        $info[] = "Initialized header_height setting";
    }
    
    // Initialize footer height setting if it doesn't exist
    $checkFooterHeight = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'footer_height'";
    $result = $conn->query($checkFooterHeight);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertFooterHeight = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('footer_height', '60', 'Layout', 'Footer Height (px)', 'Set the height of the admin footer in pixels. Current height: 60px')";
        $conn->query($insertFooterHeight);
        $info[] = "Initialized footer_height setting";
    }
    
    // Initialize table border settings if they don't exist
    $tableBorderSettings = [
        ['key' => 'test_table_show_border', 'value' => 'yes', 'label' => 'Show Table Border', 'description' => 'TESTING - Enable or disable table borders for troubleshooting layout and alignment issues'],
        ['key' => 'test_table_border_thickness', 'value' => '1', 'label' => 'Table Border Thickness (px)', 'description' => 'TESTING - Set the thickness of table borders in pixels (for testing layout)'],
        ['key' => 'test_table_border_color', 'value' => '#000000', 'label' => 'Table Border Color', 'description' => 'TESTING - Set the color of table borders (hex color code) (for testing layout)'],
        ['key' => 'test_table_cellpadding', 'value' => '8', 'label' => 'Cellpadding (px)', 'description' => 'TESTING - Set the padding inside table cells in pixels (for testing layout)']
    ];
    
    foreach ($tableBorderSettings as $setting) {
        $checkSetting = "SELECT COUNT(*) as count FROM settings WHERE setting_key = '{$setting['key']}'";
        $result = $conn->query($checkSetting);
        $row = $result->fetch_assoc();
        
        if ($row && $row['count'] == 0) {
            $insertSetting = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
                ('{$setting['key']}', '{$setting['value']}', 'Layout Table Test', '{$setting['label']}', '{$setting['description']}')";
            $conn->query($insertSetting);
            $info[] = "Initialized {$setting['key']} setting";
        }
    }
    
    // Remove deprecated color_picker_size setting (replaced by --color-picker-size parameter)
    // Use ID for deletion to ensure correct row is accessed
    $colorPickerSizeId = getSettingIdByKey('color_picker_size');
    if ($colorPickerSizeId) {
        $deleteStmt = $conn->prepare("DELETE FROM settings WHERE id = ?");
        $deleteStmt->bind_param("i", $colorPickerSizeId);
        if ($deleteStmt->execute()) {
            if ($deleteStmt->affected_rows > 0) {
                $success[] = "Removed deprecated color_picker_size setting from database";
            }
        } else {
            $errors[] = "Error attempting to remove color_picker_size setting: " . $deleteStmt->error;
        }
        $deleteStmt->close();
    } else {
        $info[] = "color_picker_size setting not found (already removed or never existed)";
    }
    
    // Initialize menu active background color setting if it doesn't exist
    $checkMenuActiveBgColor = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'menu_active_bg_color'";
    $result = $conn->query($checkMenuActiveBgColor);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertMenuActiveBgColor = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('menu_active_bg_color', 'rgba(255, 255, 255, 0.15)', 'Menu', 'Menu Active Background Color', 'Set the background color for active menu items. Use rgba format (e.g., rgba(255, 255, 255, 0.15)) or hex format (e.g., #ffffff)')";
        $conn->query($insertMenuActiveBgColor);
        $info[] = "Initialized menu_active_bg_color setting";
    }
    
    // Initialize menu active text color setting if it doesn't exist
    $checkMenuActiveTextColor = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'menu_active_text_color'";
    $result = $conn->query($checkMenuActiveTextColor);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertMenuActiveTextColor = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('menu_active_text_color', '#ffffff', 'Menu', 'Menu Active Text Color', 'Set the text color for active menu items. Use hex format (e.g., #ffffff)')";
        $conn->query($insertMenuActiveTextColor);
        $info[] = "Initialized menu_active_text_color setting";
    }
    
    // Initialize section heading background color setting if it doesn't exist
    $checkSectionHeadingBgColor = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'section_heading_bg_color'";
    $result = $conn->query($checkSectionHeadingBgColor);
    $row = $result->fetch_assoc();
    
    if ($row && $row['count'] == 0) {
        $insertSectionHeadingBgColor = "INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES 
            ('section_heading_bg_color', '#f5f5f5', 'Layout', 'Section Heading Background Color', 'Set the background color for section headings (e.g., Contact Information, Footer Links). Use hex format (e.g., #f5f5f5)')";
        $conn->query($insertSectionHeadingBgColor);
        $info[] = "Initialized section_heading_bg_color setting";
    }
    
    // Sync setting sections as sub-menus under Settings
    if (function_exists('syncSettingSectionMenus')) {
        syncSettingSectionMenus();
        $info[] = "Settings section menus synced";
    }
}

if (empty($errors)) {
    $success[] = "Database initialization complete";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Initialization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #bee5eb;
        }
        .script-info {
            background: #e7f3ff;
            color: #004085;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #b3d9ff;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .admin-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border: 2px solid #007bff;
        }
        .admin-form h2 {
            margin-top: 0;
            color: #333;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Initialization</h1>
        
        <div class="script-info">
            <strong>Script Information:</strong><br><br>
            <strong>Type:</strong> Utility (Keep - Used for database initialization)<br>
            <strong>Single Run:</strong> No (Safe to rerun - idempotent)<br>
            <strong>Status:</strong> This script is idempotent and safe to run multiple times. It will only create missing tables and settings, and will not duplicate existing data.
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="success">
                <strong>✅ Success:</strong><br><br>
                <?php foreach ($success as $msg): ?>
                    • <?php echo htmlspecialchars($msg); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>❌ Errors:</strong><br><br>
                <?php foreach ($errors as $error): ?>
                    • <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($warnings)): ?>
            <div class="warning">
                <strong>⚠️ Warnings:</strong><br><br>
                <?php foreach ($warnings as $warning): ?>
                    • <?php echo htmlspecialchars($warning); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($info)): ?>
            <div class="info">
                <strong>ℹ️ Information:</strong><br><br>
                <?php foreach ($info as $inf): ?>
                    • <?php echo htmlspecialchars($inf); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($showAdminForm): ?>
            <div class="admin-form">
                <h2>Create Admin User</h2>
                <p>No admin users found. Please create the first admin user to continue.</p>
                <form method="POST" action="">
                    <input type="hidden" name="create_admin" value="1">
                    
                    <div class="form-group">
                        <label for="admin_name">Name:</label>
                        <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($_POST['admin_name'] ?? 'Administrator'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Email:</label>
                        <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Password:</label>
                        <input type="password" id="admin_password" name="admin_password" required minlength="6">
                        <small style="color: #666; display: block; margin-top: 5px;">Password must be at least 6 characters long</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Admin User</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>What this script does:</strong><br><br>
            This script initializes the database and sets up default configurations. It performs the following operations:
            <ul>
                <li><strong>Creates database tables</strong> - Users, admin menus, settings, page columns, setup icons, and footer data tables</li>
                <li><strong>Runs migrations</strong> - Updates settings table structure if needed</li>
                <li><strong>Creates default admin user</strong> - Only if no users exist. You will be prompted to enter email and password securely</li>
                <li><strong>Creates default menu items</strong> - Only if menus table is empty</li>
                <li><strong>Initializes default settings</strong> - Menu widths, header/footer heights, table border settings, color settings, etc. (only if they don't exist)</li>
                <li><strong>Removes deprecated settings</strong> - Cleans up old unused settings like color_picker_size</li>
                <li><strong>Syncs setting section menus</strong> - Updates admin menu structure based on settings sections</li>
            </ul>
            <strong>Note:</strong> This script is idempotent, meaning it's safe to run multiple times. It will only create missing items and will not duplicate existing data or break existing configurations.
        </div>
        
        <p>
            <a href="settings/parameters.php">View Parameters Page</a> | 
            <a href="setup/">Back to Setup</a>
        </p>
    </div>
</body>
</html>
