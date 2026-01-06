<?php
/**
 * Header Import Handler
 * Imports header configuration from JSON or ZIP
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/header_functions.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Import Header', true, 'setup_header_import');

$conn = getDBConnection();
$error = '';
$success = '';
$importData = null;

// Get indent parameters for labels and helper text
if ($conn) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

// Normalize indent values (add 'px' if numeric and no unit)
if (!empty($indentLabel)) {
    $indentLabel = trim($indentLabel);
    if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
        $indentLabel = $indentLabel . 'px';
    }
} else {
    $indentLabel = '0px';
}

if (!empty($indentHelperText)) {
    $indentHelperText = trim($indentHelperText);
    if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
        $indentHelperText = $indentHelperText . 'px';
    }
} else {
    $indentHelperText = '0px';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'import') {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'No file uploaded or upload error';
        } else {
            $file = $_FILES['import_file'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($fileExt === 'zip') {
                // Handle ZIP import
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === TRUE) {
                    // Extract JSON config
                    $jsonContent = $zip->getFromName('header_config.json');
                    if ($jsonContent) {
                        $importData = json_decode($jsonContent, true);
                        
                        // Extract images to temp directory
                        $tempDir = sys_get_temp_dir() . '/header_import_' . uniqid();
                        mkdir($tempDir, 0755, true);
                        
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            if (strpos($filename, 'images/') === 0) {
                                $zip->extractTo($tempDir, $filename);
                            }
                        }
                        
                        $zip->close();
                        
                        // Process import
                        $result = processImport($importData, $tempDir);
                        if ($result['success']) {
                            $success = 'Header imported successfully! Header ID: ' . $result['header_id'];
                            $importData = null; // Clear after successful import
                        } else {
                            $error = $result['error'];
                        }
                        
                        // Cleanup temp directory
                        array_map('unlink', glob("$tempDir/images/*"));
                        rmdir("$tempDir/images");
                        rmdir($tempDir);
                    } else {
                        $error = 'Invalid ZIP file: header_config.json not found';
                    }
                } else {
                    $error = 'Failed to open ZIP file';
                }
            } elseif ($fileExt === 'json') {
                // Handle JSON import
                $jsonContent = file_get_contents($file['tmp_name']);
                $importData = json_decode($jsonContent, true);
                
                if ($importData) {
                    $result = processImport($importData);
                    if ($result['success']) {
                        $success = 'Header imported successfully! Header ID: ' . $result['header_id'];
                        $importData = null;
                    } else {
                        $error = $result['error'];
                    }
                } else {
                    $error = 'Invalid JSON file';
                }
            } else {
                $error = 'Unsupported file type. Please upload a JSON or ZIP file.';
            }
        }
    } elseif ($action === 'preview_import') {
        // Preview import data
        $jsonContent = $_POST['json_content'] ?? '';
        $importData = json_decode($jsonContent, true);
        if (!$importData) {
            $error = 'Invalid JSON content';
        }
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Import Header</h2>
        <p class="text-muted">Import a header configuration from a JSON or ZIP file</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo htmlspecialchars($success); ?>
    <br><br>
    <a href="header.php?action=edit&id=<?php echo isset($result['header_id']) ? $result['header_id'] : ''; ?>" class="btn btn-primary btn-small">Edit Imported Header</a>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Import Header Configuration</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            
            <div class="form-group">
                <label for="import_file" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Import File *</label>
                <input type="file" id="import_file" name="import_file" class="input" accept=".json,.zip" required>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Upload a JSON file (config only) or ZIP file (config + images)</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="overwrite_existing" value="1">
                    Overwrite existing header if name matches
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-medium">Import Header</button>
                <a href="header.php" class="btn btn-secondary btn-medium">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if ($importData && $action === 'preview_import'): ?>
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Import Preview</h3>
    </div>
    <div class="card-body">
        <p><strong>Header Name:</strong> <?php echo htmlspecialchars($importData['header']['name'] ?? 'N/A'); ?></p>
        <p><strong>Display Location:</strong> <?php echo htmlspecialchars($importData['header']['display_location'] ?? 'N/A'); ?></p>
        <p><strong>Images:</strong> <?php echo count($importData['images'] ?? []); ?></p>
        <p><strong>Text Overlays:</strong> <?php echo count($importData['text_overlays'] ?? []); ?></p>
        <p><strong>CTAs:</strong> <?php echo count($importData['ctas'] ?? []); ?></p>
    </div>
</div>
<?php endif; ?>

<?php
function processImport($importData, $imageDir = null) {
    if (empty($importData['header'])) {
        return ['success' => false, 'error' => 'Invalid import data: header section missing'];
    }
    
    $headerData = $importData['header'];
    
    // Check if header with same name exists
    $existingHeaders = getAllScheduledHeaders();
    $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';
    
    $existingId = null;
    foreach ($existingHeaders as $h) {
        if ($h['name'] === $headerData['name']) {
            $existingId = $h['id'];
            break;
        }
    }
    
    if ($existingId && !$overwrite) {
        return ['success' => false, 'error' => 'Header with name "' . $headerData['name'] . '" already exists. Enable "Overwrite existing" to replace it.'];
    }
    
    if ($existingId && $overwrite) {
        $headerData['id'] = $existingId;
    } else {
        $headerData['id'] = null;
        // Modify name if importing as new
        $headerData['name'] = $headerData['name'] . ' (Imported)';
    }
    
    // Process images if image directory provided
    $images = $importData['images'] ?? [];
    if ($imageDir && is_dir($imageDir . '/images')) {
        foreach ($images as &$image) {
            if (!empty($image['image_path'])) {
                $oldPath = basename($image['image_path']);
                $newPath = 'uploads/headers/originals/' . uniqid('imported_') . '_' . $oldPath;
                $sourceFile = $imageDir . '/images/' . $oldPath;
                
                if (file_exists($sourceFile)) {
                    $destDir = dirname(__DIR__ . '/../../' . $newPath);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($sourceFile, __DIR__ . '/../../' . $newPath);
                    $image['image_path'] = $newPath;
                }
            }
        }
    }
    
    // Save header
    $result = saveScheduledHeader(
        $headerData,
        $images,
        $importData['text_overlays'] ?? [],
        $importData['ctas'] ?? [],
        false // Don't create version on import
    );
    
    if ($result) {
        return ['success' => true, 'header_id' => $result];
    } else {
        return ['success' => false, 'error' => 'Failed to save imported header'];
    }
}

endLayout();
?>

