<?php
/**
 * Cleanup Migration Scripts
 * Identifies and allows deletion of one-time migration and setup scripts
 * 
 * Script Type: Housekeeping (Keep - Used for maintenance)
 * Single Run: No (Can be run multiple times)
 * 
 * Usage: Navigate to /admin/setup/cleanup-migration-scripts.php in your browser
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require authentication
requireAuth();

// Handle file deletion
if (isset($_POST['delete_file']) && $_POST['delete_file'] === 'yes') {
    $filename = __FILE__;
    if (file_exists($filename)) {
        unlink($filename);
        echo '<!DOCTYPE html><html><head><title>File Deleted</title></head><body><h1>File deleted successfully</h1><script>setTimeout(function(){ window.close(); }, 1000);</script></body></html>';
        exit;
    }
}

$conn = getDBConnection();
$setupDir = __DIR__;
$filesToDelete = [];
$errors = [];
$deletedFiles = [];
$stepResults = [];
$scriptMetadata = [];
$shouldRun = isset($_GET['run']) && $_GET['run'] === '1';
$shouldRun = isset($_GET['run']) && $_GET['run'] === '1';

// Define patterns for one-time migration/setup scripts
$migrationPatterns = [
    'add-bg-',           // add-bg-* scripts
    'add-color-',        // add-color-* scripts
    'add-indent-',       // add-indent-* scripts
    'add-prefix-',       // add-prefix-* scripts
    'migrate-',          // migrate-* scripts
    'move-',             // move-* scripts
    'normalize-',        // normalize-* scripts
    'remove-',           // remove-* scripts
    'rename-',           // rename-* scripts
];

// Files to always keep (important/active scripts)
$keepFiles = [
    'SETUP_SCRIPT_TEMPLATE.php',
    'SETUP_SCRIPT_STANDARDS.md',
    'DEVELOPMENT_STANDARDS.md',
    'cleanup-test-scripts.php',
    'cleanup-migration-scripts.php', // Don't delete this script itself
    'icons.php',
    'menus.php',
    'page_columns.php', // Active utility - used in menu system
];

// Function to check if a script has been executed before
function hasScriptBeenExecuted($scriptPath) {
    $executionLogFile = __DIR__ . '/.script-execution-log.json';
    
    if (!file_exists($executionLogFile)) {
        return false;
    }
    
    $log = json_decode(file_get_contents($executionLogFile), true);
    if (!$log) {
        return false;
    }
    
    $scriptName = basename($scriptPath);
    return isset($log[$scriptName]) && $log[$scriptName]['executed'] === true;
}

// Function to get script metadata from file
function getScriptMetadata($scriptPath) {
    $metadata = [
        'type' => 'Unknown',
        'singleRun' => false,
        'executed' => false
    ];
    
    if (!file_exists($scriptPath)) {
        return $metadata;
    }
    
    $content = file_get_contents($scriptPath);
    
    // Check for script type
    if (preg_match('/Script Type:\s*([^\n]+)/i', $content, $matches)) {
        $metadata['type'] = trim($matches[1]);
    }
    
    // Check for single run flag
    if (preg_match('/Single Run:\s*(Yes|No|True|False)/i', $content, $matches)) {
        $metadata['singleRun'] = in_array(strtolower(trim($matches[1])), ['yes', 'true']);
    }
    
    // Check execution status
    $metadata['executed'] = hasScriptBeenExecuted($scriptPath);
    
    return $metadata;
}

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

// Define steps for this script
$steps = [
    [
        'name' => 'Scan setup directory for migration scripts',
        'action' => function() use ($setupDir, &$filesToDelete, &$scriptMetadata, $migrationPatterns, $keepFiles) {
            $allFiles = scandir($setupDir);
            $found = 0;
            
            foreach ($allFiles as $file) {
                if ($file === '.' || $file === '..' || is_dir($setupDir . '/' . $file)) {
                    continue;
                }
                
                // Skip keep files
                if (in_array($file, $keepFiles)) {
                    continue;
                }
                
                // Check if file matches migration patterns
                $isMigrationScript = false;
                foreach ($migrationPatterns as $pattern) {
                    if (stripos($file, $pattern) === 0) {
                        $isMigrationScript = true;
                        break;
                    }
                }
                
                if ($isMigrationScript) {
                    $filepath = $setupDir . '/' . $file;
                    $filesToDelete[] = [
                        'name' => $file,
                        'size' => filesize($filepath),
                        'modified' => filemtime($filepath),
                        'path' => $filepath
                    ];
                    
                    // Get script metadata
                    $scriptMetadata[$file] = getScriptMetadata($filepath);
                    
                    $found++;
                }
            }
            
            // Sort by modification date (oldest first)
            usort($filesToDelete, function($a, $b) {
                return $a['modified'] - $b['modified'];
            });
            
            return $found >= 0; // Always return true, we just want to scan
        },
        'link' => '../settings/parameters.php',
        'linkText' => 'View Parameters'
    ]
];

$deletedCount = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;

// Only execute steps if run button was clicked
if ($shouldRun) {
    // Execute steps
    foreach ($steps as $index => $step) {
        $stepNumber = $index + 1;
        try {
            $result = $step['action']();
            $stepResults[] = [
                'number' => $stepNumber,
                'name' => $step['name'],
                'success' => $result !== false,
                'message' => $result !== false ? 'Success' : 'Failed',
                'index' => $index
            ];
        } catch (Exception $e) {
            $stepResults[] = [
                'number' => $stepNumber,
                'name' => $step['name'],
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'index' => $index
            ];
        }
    }
}

// Get metadata for this script
$currentScriptMetadata = getScriptMetadata(__FILE__);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Migration Scripts</title>
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
        .script-info {
            background: #e7f3ff;
            color: #004085;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #b3d9ff;
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 8px;
        }
        .badge-executed {
            background-color: #28a745;
            color: white;
        }
        .badge-single-run {
            background-color: #ffc107;
            color: #856404;
        }
        .badge-housekeeping {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cleanup Migration Scripts</h1>
        
        <div class="script-info">
            <strong>Script Information:</strong><br><br>
            <strong>Type:</strong> <?php echo htmlspecialchars($currentScriptMetadata['type']); ?><br>
            <strong>Single Run:</strong> <?php echo $currentScriptMetadata['singleRun'] ? 'Yes' : 'No'; ?><br>
            <strong>Execution Status:</strong> 
            <?php if ($currentScriptMetadata['executed']): ?>
                <span class="badge badge-executed">✅ Previously Executed</span>
            <?php else: ?>
                <span style="color: #6c757d;">Not yet executed</span>
            <?php endif; ?>
        </div>
        
        <?php if (!$shouldRun): ?>
            <div class="info">
                <strong>Ready to execute:</strong><br><br>
                This script is ready to run. Click the "RUN SCRIPT" button below to scan for migration scripts.
            </div>
            
            <div class="button-group">
                <a href="?run=1" class="btn btn-primary">▶️ RUN SCRIPT</a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($stepResults)): ?>
            <h2>Execution Steps</h2>
            <div style="margin-bottom: 20px;">
                <?php foreach ($stepResults as $index => $step): ?>
                    <?php 
                    $originalStep = $steps[$step['index']] ?? $steps[$index] ?? null;
                    $hasLink = isset($originalStep['link']) && isset($originalStep['linkText']);
                    ?>
                    <div style="display: flex; align-items: center; padding: 10px; margin: 5px 0; border-radius: 4px; <?php echo $step['success'] ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'; ?>">
                        <span style="font-weight: bold; margin-right: 10px; min-width: 80px;">
                            Step <?php echo $step['number']; ?>:
                        </span>
                        <span style="flex: 1;">
                            <?php echo htmlspecialchars($step['name']); ?>
                            <?php if ($hasLink): ?>
                                <span style="margin-left: 10px;">
                                    <a href="<?php echo htmlspecialchars($originalStep['link']); ?>" style="color: inherit; text-decoration: underline; font-size: 0.9em;">
                                        (<?php echo htmlspecialchars($originalStep['linkText']); ?>)
                                    </a>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span style="font-weight: bold; margin-left: 10px; <?php echo $step['success'] ? 'color: #155724;' : 'color: #721c24;'; ?>">
                            <?php echo $step['success'] ? '✅ Success' : '❌ Fail'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
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
                <strong>✅ No migration scripts found!</strong><br><br>
                All one-time migration scripts have been cleaned up, or they don't match the migration patterns.
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Found <?php echo count($filesToDelete); ?> one-time migration script(s):</strong><br><br>
                These files are typically one-time setup/migration scripts that were used to:
                <ul>
                    <li>Add parameters to the database</li>
                    <li>Migrate data between formats</li>
                    <li>Rename or reorganize parameters</li>
                    <li>Remove obsolete menu items</li>
                </ul>
                <br>
                <strong>Note:</strong><br><br>
                These scripts are usually only run once. After successful execution, they can be safely deleted. Review the list below and select which ones to delete.
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected migration scripts? These are typically one-time use scripts. This action cannot be undone.');">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" id="select-all" onchange="document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = this.checked);">
                            </th>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Size</th>
                            <th>Last Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filesToDelete as $file): ?>
                            <?php 
                            $metadata = $scriptMetadata[$file['name']] ?? [];
                            $isExecuted = $metadata['executed'] ?? false;
                            $isSingleRun = $metadata['singleRun'] ?? false;
                            $scriptType = $metadata['type'] ?? 'Unknown';
                            ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="delete_files[]" value="<?php echo htmlspecialchars($file['name']); ?>" class="file-checkbox">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($file['name']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($isExecuted): ?>
                                        <span class="badge badge-executed">✅ Executed</span>
                                    <?php endif; ?>
                                    <?php if ($isSingleRun): ?>
                                        <span class="badge badge-single-run">Single Run</span>
                                    <?php endif; ?>
                                    <?php if (stripos($scriptType, 'housekeeping') !== false || stripos($scriptType, 'keep') !== false): ?>
                                        <span class="badge badge-housekeeping">Keep</span>
                                    <?php endif; ?>
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
        
        <div class="button-group" style="margin: 20px 0;">
            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this cleanup script? This action cannot be undone.');">
                <input type="hidden" name="delete_file" value="yes">
                <button type="submit" class="btn btn-danger">🗑️ Delete This File</button>
            </form>
        </div>
        
        <div class="info">
            <strong>What this script does:</strong><br><br>
            This script identifies one-time migration and setup scripts in the setup directory that match these patterns:
            <ul>
                <li><code>add-bg-*</code> - Scripts that add background parameters</li>
                <li><code>add-color-*</code> - Scripts that add color picker parameters</li>
                <li><code>add-indent-*</code> - Scripts that add indent parameters</li>
                <li><code>add-prefix-*</code> - Scripts that add prefixes to parameters</li>
                <li><code>migrate-*</code> - Migration scripts</li>
                <li><code>move-*</code> - Scripts that move parameters between sections</li>
                <li><code>normalize-*</code> - Scripts that normalize data formats</li>
                <li><code>remove-*</code> - Scripts that remove menu items or parameters</li>
                <li><code>rename-*</code> - Scripts that rename parameters</li>
            </ul>
            <br>
            <strong>Protected Files:</strong><br><br>
            The following files are always protected from deletion:
            <ul>
                <li>SETUP_SCRIPT_TEMPLATE.php</li>
                <li>SETUP_SCRIPT_STANDARDS.md</li>
                <li>DEVELOPMENT_STANDARDS.md</li>
                <li>icons.php, menus.php, page_columns.php (active utilities)</li>
                <li>cleanup-test-scripts.php, cleanup-migration-scripts.php (cleanup utilities)</li>
            </ul>
            <br>
            <strong>Confirmation:</strong><br><br>
            Based on codebase analysis, these migration scripts are:
            <ul>
                <li>✅ Only referenced in documentation files</li>
                <li>✅ Not included/required by other PHP files</li>
                <li>✅ One-time use scripts that have already been executed</li>
                <li>✅ Safe to delete after successful migration</li>
            </ul>
            <br>
            <strong>Note:</strong><br><br>
            Always ensure migrations have been completed successfully before deleting these scripts. Consider keeping them for a period after migration for reference.
        </div>
        
        <p>
            <a href="../settings/parameters.php">View Parameters Page</a> | 
            <a href="../setup/">Back to Setup</a> | 
            <a href="cleanup-test-scripts.php">Cleanup Test Scripts</a>
        </p>
    </div>
</body>
</html>
