<?php
/**
 * Cleanup All Old Files
 * Identifies and allows deletion of old/unused files in the setup folder
 * 
 * Script Type: Housekeeping (Keep - Used for maintenance)
 * Single Run: No (Can be run multiple times)
 * 
 * Usage: Navigate to /admin/setup/cleanup-all-old-files.php in your browser
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
$fileReferences = [];
$shouldRun = isset($_GET['run']) && $_GET['run'] === '1';

// Files to always keep (important/active scripts)
$keepFiles = [
    'SETUP_SCRIPT_TEMPLATE.php',
    'SETUP_SCRIPT_STANDARDS.md',
    'DEVELOPMENT_STANDARDS.md',
    'cleanup-test-scripts.php',
    'cleanup-migration-scripts.php',
    'cleanup-all-old-files.php', // Don't delete this script itself
    'icons.php',
    'menus.php',
    'page_columns.php', // Active utility - used in menu system
];

// Patterns for old/migration scripts that can potentially be deleted
$oldFilePatterns = [
    'add-',           // add-* scripts
    'migrate-',       // migrate-* scripts
    'move-',          // move-* scripts
    'normalize-',     // normalize-* scripts
    'remove-',        // remove-* scripts
    'rename-',        // rename-* scripts
    'test-',          // test-* scripts (temporary test files)
    'list-',          // list-* scripts (temporary listing scripts)
];

// Function to check if a file is referenced in other PHP files
function isFileReferenced($filename, $setupDir, $projectRoot) {
    $references = [];
    $searchPatterns = [
        basename($filename),
        str_replace($setupDir . '/', '', $filename),
        'setup/' . basename($filename),
    ];
    
    // Search in PHP files
    $phpFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($phpFiles as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getRealPath();
            
            // Skip the file itself
            if ($filePath === $filename) {
                continue;
            }
            
            // Skip vendor/node_modules directories
            if (strpos($filePath, 'vendor') !== false || strpos($filePath, 'node_modules') !== false) {
                continue;
            }
            
            $content = @file_get_contents($filePath);
            if ($content === false) {
                continue;
            }
            
            foreach ($searchPatterns as $pattern) {
                // Check for require/include statements
                if (preg_match('/(?:require|include)(?:_once)?\s*[\'"]' . preg_quote($pattern, '/') . '/i', $content)) {
                    $references[] = [
                        'file' => str_replace($projectRoot . '/', '', $filePath),
                        'type' => 'require/include',
                        'pattern' => $pattern
                    ];
                }
                
                // Check for direct file references in strings
                if (preg_match('/[\'"]' . preg_quote($pattern, '/') . '/i', $content)) {
                    $references[] = [
                        'file' => str_replace($projectRoot . '/', '', $filePath),
                        'type' => 'string reference',
                        'pattern' => $pattern
                    ];
                }
            }
        }
    }
    
    return $references;
}

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
        header('Location: ?deleted=' . count($deletedFiles) . '&run=1');
        exit;
    }
}

// Define steps for this script
$steps = [
    [
        'name' => 'Scan setup directory for old files',
        'action' => function() use ($setupDir, &$filesToDelete, &$scriptMetadata, &$fileReferences, $oldFilePatterns, $keepFiles) {
            $allFiles = scandir($setupDir);
            $found = 0;
            $projectRoot = dirname(dirname($setupDir));
            
            foreach ($allFiles as $file) {
                if ($file === '.' || $file === '..' || is_dir($setupDir . '/' . $file)) {
                    continue;
                }
                
                // Skip keep files
                if (in_array($file, $keepFiles)) {
                    continue;
                }
                
                // Skip non-PHP files (except markdown)
                if (!preg_match('/\.(php|md)$/i', $file)) {
                    continue;
                }
                
                // Check if file matches old file patterns
                $isOldFile = false;
                foreach ($oldFilePatterns as $pattern) {
                    if (stripos($file, $pattern) === 0) {
                        $isOldFile = true;
                        break;
                    }
                }
                
                // Also check for files that look like one-time scripts
                // (files that are likely migrations or setup scripts)
                if (!$isOldFile && preg_match('/^[a-z-]+\.php$/i', $file)) {
                    // Check if it's a PHP file that might be a one-time script
                    $filepath = $setupDir . '/' . $file;
                    $content = @file_get_contents($filepath);
                    if ($content && (
                        stripos($content, 'Script Type:') !== false ||
                        stripos($content, 'Single Run:') !== false ||
                        stripos($content, 'migration') !== false ||
                        stripos($content, 'setup script') !== false
                    )) {
                        $isOldFile = true;
                    }
                }
                
                if ($isOldFile) {
                    $filepath = $setupDir . '/' . $file;
                    $filesToDelete[] = [
                        'name' => $file,
                        'size' => filesize($filepath),
                        'modified' => filemtime($filepath),
                        'path' => $filepath
                    ];
                    
                    // Get script metadata
                    $scriptMetadata[$file] = getScriptMetadata($filepath);
                    
                    // Check if file is referenced elsewhere
                    $references = isFileReferenced($filepath, $setupDir, $projectRoot);
                    $fileReferences[$file] = $references;
                    
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
    <title>Cleanup All Old Files</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
            border: 1px solid #ffeeba;
        }
        .script-info {
            background: #e7f3ff;
            color: #004085;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #b3d9ff;
        }
        .button-group {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
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
        .badge-referenced {
            background-color: #dc3545;
            color: white;
        }
        .badge-safe {
            background-color: #28a745;
            color: white;
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
            background-color: #f5f5f5;
        }
        .file-name {
            font-family: monospace;
            font-weight: 600;
        }
        .file-size {
            color: #6c757d;
            font-size: 0.9em;
        }
        .file-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        .references-list {
            font-size: 0.85em;
            color: #721c24;
            margin-top: 5px;
        }
        .references-list ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cleanup All Old Files</h1>
        
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
                This script will scan the setup folder for old files that may be safe to delete. Click the "RUN SCRIPT" button below to scan for files.
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
                <strong>✅ Successfully deleted <?php echo $deletedCount; ?> file(s)!</strong>
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
        
        <?php if (!empty($filesToDelete)): ?>
            <div class="warning">
                <strong>⚠️ Warning:</strong><br><br>
                The following files were found. Please review each file carefully before deleting. Files marked as "Referenced" are used elsewhere in the website and should NOT be deleted.
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete the selected files? This action cannot be undone.');">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all" onchange="toggleAll(this);"></th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Status</th>
                            <th>Metadata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filesToDelete as $file): ?>
                            <?php 
                            $references = $fileReferences[$file['name']] ?? [];
                            $hasReferences = !empty($references);
                            $metadata = $scriptMetadata[$file['name']] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <?php if (!$hasReferences): ?>
                                        <input type="checkbox" name="delete_files[]" value="<?php echo htmlspecialchars($file['name']); ?>">
                                    <?php else: ?>
                                        <span style="color: #dc3545;">⚠️</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="file-name"><?php echo htmlspecialchars($file['name']); ?></span>
                                    <?php if ($hasReferences): ?>
                                        <div class="references-list">
                                            <strong>Referenced in:</strong>
                                            <ul>
                                                <?php foreach ($references as $ref): ?>
                                                    <li><?php echo htmlspecialchars($ref['file']); ?> (<?php echo htmlspecialchars($ref['type']); ?>)</li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="file-size"><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                                <td class="file-date"><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
                                <td>
                                    <?php if ($hasReferences): ?>
                                        <span class="badge badge-referenced">⚠️ Referenced</span>
                                    <?php else: ?>
                                        <span class="badge badge-safe">✓ Safe to Delete</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($metadata)): ?>
                                        <small>
                                            Type: <?php echo htmlspecialchars($metadata['type'] ?? 'Unknown'); ?><br>
                                            Single Run: <?php echo ($metadata['singleRun'] ?? false) ? 'Yes' : 'No'; ?><br>
                                            Executed: <?php echo ($metadata['executed'] ?? false) ? 'Yes' : 'No'; ?>
                                        </small>
                                    <?php else: ?>
                                        <small style="color: #6c757d;">No metadata</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-danger">🗑️ Delete Selected Files</button>
                    <a href="?" class="btn btn-secondary">🔄 Refresh List</a>
                </div>
            </form>
        <?php elseif ($shouldRun && empty($filesToDelete)): ?>
            <div class="success">
                <strong>✅ No old files found!</strong><br><br>
                The setup folder is clean. No files matching the cleanup patterns were found.
            </div>
        <?php endif; ?>
        
        <div class="button-group">
            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this script file? This action cannot be undone.');">
                <input type="hidden" name="delete_file" value="yes">
                <button type="submit" class="btn btn-danger">🗑️ Delete This File</button>
            </form>
            <?php if ($shouldRun): ?>
                <a href="?" class="btn btn-secondary">🔄 Run Scan Again</a>
            <?php endif; ?>
        </div>
        
        <div class="info">
            <strong>What this script does:</strong><br><br>
            This script scans the setup folder for old files that may be safe to delete:
            <ul>
                <li>Files matching patterns: add-*, migrate-*, move-*, normalize-*, remove-*, rename-*, test-*, list-*</li>
                <li>Files that appear to be one-time setup/migration scripts</li>
                <li>Checks if files are referenced in other PHP files across the website</li>
                <li>Displays file metadata (type, single run status, execution status)</li>
                <li>Allows selective deletion of safe files</li>
            </ul>
            <br>
            <strong>Protected Files:</strong><br><br>
            The following files are always protected and cannot be deleted:
            <ul>
                <li>SETUP_SCRIPT_TEMPLATE.php</li>
                <li>SETUP_SCRIPT_STANDARDS.md</li>
                <li>DEVELOPMENT_STANDARDS.md</li>
                <li>cleanup-test-scripts.php</li>
                <li>cleanup-migration-scripts.php</li>
                <li>cleanup-all-old-files.php (this script)</li>
                <li>icons.php, menus.php, page_columns.php (active utilities)</li>
            </ul>
            <br>
            <strong>Note:</strong><br><br>
            Files marked as "Referenced" are used elsewhere in the website and should NOT be deleted. Only delete files marked as "Safe to Delete" after reviewing their metadata.
        </div>
        
        <p>
            <a href="../settings/parameters.php">View Parameters Page</a> | 
            <a href="../setup/">Back to Setup</a>
        </p>
    </div>
    
    <script>
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="delete_files[]"]');
            checkboxes.forEach(cb => {
                if (!cb.closest('tr').querySelector('.badge-referenced')) {
                    cb.checked = checkbox.checked;
                }
            });
        }
    </script>
</body>
</html>

