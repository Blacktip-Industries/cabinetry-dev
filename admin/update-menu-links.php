<?php
/**
 * Update Menu Links Script
 * Updates all menu links that point to old settings pages to the new paths
 * 
 * Usage: Navigate to /admin/update-menu-links.php in your browser
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!checkAuth()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$updated = 0;
$errors = [];

if ($conn === null) {
    die('Database connection failed');
}

// Map of old URLs to new URLs
$urlMappings = [
    '/admin/settings.php?section=Header' => '/admin/settings/parameters.php?section=Header',
    '/admin/settings.php?section=Layout' => '/admin/settings/parameters.php?section=Layout',
    '/admin/settings.php?section=Menu' => '/admin/settings/parameters.php?section=Menu',
    '/admin/settings.php?section=Menu%20-%20Admin' => '/admin/settings/parameters.php?section=Menu - Admin',
    '/admin/settings.php?section=Menu%20-%20Frontend' => '/admin/settings/parameters.php?section=Menu - Frontend',
    '/admin/settings_header.php' => '/admin/settings/parameters.php?section=Header',
    '/admin/settings_layout.php' => '/admin/settings/parameters.php?section=Layout',
    '/admin/settings_menu.php' => '/admin/settings/parameters.php?section=Menu',
];

// Also update by title for settings sub-menus
// Note: Header, Layout, and Menu are now managed through Parameters page, so they won't appear as menu items
$titleUrlMap = [
    'Header' => '/admin/settings/parameters.php?section=Header',
    'Layout' => '/admin/settings/parameters.php?section=Layout',
    'Menu' => '/admin/settings/parameters.php?section=Menu',
    'Menu - Admin' => '/admin/settings/parameters.php?section=Menu - Admin',
    'Menu - Frontend' => '/admin/settings/parameters.php?section=Menu - Frontend',
];

try {
    // First, update by URL pattern
    foreach ($urlMappings as $oldUrl => $newUrl) {
        $stmt = $conn->prepare("UPDATE admin_menus SET url = ? WHERE url = ? AND menu_type = 'admin'");
        $stmt->bind_param("ss", $newUrl, $oldUrl);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            if ($affected > 0) {
                $updated += $affected;
            }
        } else {
            $errors[] = "Error updating URL from '$oldUrl': " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Also update by title (for settings sub-menus under Settings parent)
    $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = 'Settings' AND menu_type = 'admin' AND parent_id IS NULL LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $settingsParent = $result->fetch_assoc();
    $stmt->close();
    
    if ($settingsParent) {
        $settingsParentId = $settingsParent['id'];
        
        foreach ($titleUrlMap as $title => $newUrl) {
            $stmt = $conn->prepare("UPDATE admin_menus SET url = ? WHERE title = ? AND parent_id = ? AND menu_type = 'admin'");
            $stmt->bind_param("ssi", $newUrl, $title, $settingsParentId);
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                if ($affected > 0) {
                    $updated += $affected;
                }
            } else {
                $errors[] = "Error updating menu with title '$title': " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Also update any URLs that contain the old patterns
    // Note: Header, Layout, and Menu sections now use parameters.php?section=...
    $patterns = [
        ['pattern' => '/admin/settings.php?section=Header', 'new' => '/admin/settings/parameters.php?section=Header'],
        ['pattern' => '/admin/settings.php?section=Layout', 'new' => '/admin/settings/parameters.php?section=Layout'],
        ['pattern' => '/admin/settings.php?section=Menu', 'new' => '/admin/settings/parameters.php?section=Menu'],
    ];
    
    // Update URLs that start with /admin/settings.php?section=
    $stmt = $conn->prepare("SELECT id, url, title FROM admin_menus WHERE url LIKE '/admin/settings.php?section=%' AND menu_type = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $oldUrl = $row['url'];
        $title = $row['title'];
        $id = $row['id'];
        
        // Determine new URL based on title or URL parameter
        $newUrl = null;
        if (isset($titleUrlMap[$title])) {
            $newUrl = $titleUrlMap[$title];
        } else {
            // Parse section from URL
            parse_str(parse_url($oldUrl, PHP_URL_QUERY), $params);
            if (isset($params['section'])) {
                $section = $params['section'];
                if (isset($titleUrlMap[$section])) {
                    $newUrl = $titleUrlMap[$section];
                }
            }
        }
        
        if ($newUrl) {
            $updateStmt = $conn->prepare("UPDATE admin_menus SET url = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newUrl, $id);
            if ($updateStmt->execute()) {
                $updated++;
            } else {
                $errors[] = "Error updating menu ID $id: " . $updateStmt->error;
            }
            $updateStmt->close();
        }
    }
    $stmt->close();
    
    // Now run the sync function to ensure everything is correct
    if (function_exists('syncSettingSectionMenus')) {
        syncSettingSectionMenus();
    }
    
} catch (mysqli_sql_exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Menu Links</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .success {
            color: green;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            color: red;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Update Menu Links</h1>
    
    <?php if ($updated > 0): ?>
    <div class="success">
        <strong>Success!</strong> Updated <?php echo $updated; ?> menu link(s).
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="error">
        <strong>Errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($updated === 0 && empty($errors)): ?>
    <div class="info">
        No menu links needed updating. All links are already pointing to the correct paths.
    </div>
    <?php endif; ?>
    
    <p><a href="dashboard.php">Return to Dashboard</a></p>
</body>
</html>

