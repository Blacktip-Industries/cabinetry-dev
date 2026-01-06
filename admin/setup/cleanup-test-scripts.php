<?php
/**
 * Cleanup Test Scripts
 * Identifies and allows deletion of test scripts and temporary utility scripts
 * 
 * Usage: Navigate to /admin/setup/cleanup-test-scripts.php in your browser
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require authentication
requireAuth();

$setupDir = __DIR__;
$filesToDelete = [];
$errors = [];
$deletedFiles = [];

// Define patterns for files that can be safely deleted
$deletablePatterns = [
    'test-',           // Files starting with "test-"
    'Test-',           // Files starting with "Test-"
    'TEST-',           // Files starting with "TEST-"
    '-test',           // Files ending with "-test"
    'list-',           // List/utility scripts (temporary)
    'List-',           // List/utility scripts (temporary)
];

// Files to always keep (important scripts)
$keepFiles = [
    'SETUP_SCRIPT_TEMPLATE.php',
    'SETUP_SCRIPT_STANDARDS.md',
    'DEVELOPMENT_STANDARDS.md',
    'cleanup-test-scripts.php', // Don't delete this script itself
    'icons.php',
    'menus.php',
    'page_columns.php',
    'protected_files.php',
    'test_protected_file_backups.php', // Keep the new backup test script
];

// Handle deletion
if (isset($_POST['delete_files']) && is_array($_POST['delete_files'])) {
    foreach ($_POST['delete_files'] as $filename) {
        $filepath = $setupDir . '/' . basename($filename);
        
        // Security check: ensure file is in setup directory
        if (strpos(realpath($filepath), realpath($setupDir)) !== 0) {
            $errors[] = "Security check failed for: " . htmlspecialchars($filename);
            continue;
        }
        
        // Don't allow deletion of keep files
        if (in_array(basename($filename), $keepFiles)) {
            $errors[] = "Cannot delete protected file: " . htmlspecialchars($filename);
            continue;
        }
        
        if (file_exists($filepath) && is_file($filepath)) {
            if (unlink($filepath)) {
                $deletedFiles[] = $filename;
            } else {
                $errors[] = "Failed to delete: " . htmlspecialchars($filename);
            }
        } else {
            $errors[] = "File not found: " . htmlspecialchars($filename);
        }
    }
    
    // Reload page after deletion to show updated list
    if (!empty($deletedFiles)) {
        header('Location: ?deleted=' . count($deletedFiles));
        exit;
    }
}

// Scan directory for deletable files
$allFiles = scandir($setupDir);
foreach ($allFiles as $file) {
    if ($file === '.' || $file === '..' || is_dir($setupDir . '/' . $file)) {
        continue;
    }
    
    // Skip keep files
    if (in_array($file, $keepFiles)) {
        continue;
    }
    
    // Check if file matches deletable patterns
    $isDeletable = false;
    foreach ($deletablePatterns as $pattern) {
        if (stripos($file, $pattern) !== false) {
            $isDeletable = true;
            break;
        }
    }
    
    if ($isDeletable) {
        $filepath = $setupDir . '/' . $file;
        $filesToDelete[] = [
            'name' => $file,
            'size' => filesize($filepath),
            'modified' => filemtime($filepath),
            'path' => $filepath
        ];
    }
}

// Sort by modification date (oldest first)
usort($filesToDelete, function($a, $b) {
    return $a['modified'] - $b['modified'];
});

$deletedCount = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Test Scripts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #ffeaa7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .checkbox-cell {
            text-align: center;
            width: 50px;
        }
        .file-size {
            color: #6c757d;
            font-size: 0.9em;
        }
        .file-date {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cleanup Test Scripts</h1>
        
        <?php if ($deletedCount > 0): ?>
            <div class="success">
                <strong>✅ Success!</strong><br><br>
                <?php echo $deletedCount; ?> file(s) deleted successfully.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>❌ Errors occurred:</strong><br><br>
                <?php foreach ($errors as $error): ?>
                    • <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($filesToDelete)): ?>
            <div class="success">
                <strong>✅ No test scripts found!</strong><br><br>
                All files in the setup directory are either important scripts or don't match the deletable patterns.
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Found <?php echo count($filesToDelete); ?> potentially deletable file(s):</strong><br><br>
                These files match patterns for test scripts or temporary utility scripts. Review the list below and select which ones to delete.
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected files? This action cannot be undone.');">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" id="select-all" onchange="document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = this.checked);">
                            </th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Last Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filesToDelete as $file): ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="delete_files[]" value="<?php echo htmlspecialchars($file['name']); ?>" class="file-checkbox">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($file['name']); ?></strong>
                                </td>
                                <td class="file-size">
                                    <?php echo number_format($file['size'] / 1024, 2); ?> KB
                                </td>
                                <td class="file-date">
                                    <?php echo date('Y-m-d H:i:s', $file['modified']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">🗑️ Delete Selected Files</button>
                    <a href="?" class="btn btn-secondary" style="margin-left: 10px;">🔄 Refresh List</a>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="info">
            <strong>What this script does:</strong><br><br>
            This script identifies files in the setup directory that match patterns for test scripts or temporary utility scripts:
            <ul>
                <li>Files starting with "test-", "Test-", or "TEST-"</li>
                <li>Files ending with "-test"</li>
                <li>Files starting with "list-" or "List-" (temporary utility scripts)</li>
            </ul>
            <br>
            <strong>Protected files:</strong> The following files are always protected from deletion:
            <ul>
                <li>SETUP_SCRIPT_TEMPLATE.php</li>
                <li>SETUP_SCRIPT_STANDARDS.md</li>
                <li>DEVELOPMENT_STANDARDS.md</li>
                <li>icons.php, menus.php, page_columns.php</li>
                <li>This cleanup script itself</li>
            </ul>
            <br>
            <strong>Note:</strong> Always review the files before deleting. This script only identifies potential test/temporary files - use your judgment to determine if they should be deleted.
        </div>
        
        <p>
            <a href="../settings/parameters.php">View Parameters Page</a> | 
            <a href="../setup/">Back to Setup</a>
        </p>
    </div>
</body>
</html>

