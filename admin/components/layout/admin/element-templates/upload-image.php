<?php
/**
 * Layout Component - Upload Image for AI Processing
 * Upload image to generate template from
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/ai_processor.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Upload Image for AI Processing', true, 'layout_element_templates');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Upload Image</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/ai-images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Invalid file type. Please upload JPG, PNG, or WebP images.';
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            // Add to AI processing queue
            $result = layout_ai_process_image($uploadPath, $fileType);
            
            if ($result['success']) {
                $success = 'Image uploaded and queued for AI processing. Processing ID: ' . $result['queue_id'];
                header('Location: edit.php?id=' . $result['template_id']);
                exit;
            } else {
                $error = 'Failed to queue image for processing: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Failed to upload image.';
        }
    } else {
        $error = 'Upload error: ' . $_FILES['image']['error'];
    }
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Upload Image for AI Processing</h1>
        <div class="layout__actions">
            <a href="index.php" class="btn btn-secondary">Back to Templates</a>
            <a href="../ai-processor/cursor-integration.php" class="btn btn-secondary">Use Cursor Integration</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="upload-form-container">
        <div class="upload-info">
            <h2>How it works</h2>
            <ol>
                <li>Upload an image of a UI element (button, card, form, etc.)</li>
                <li>AI will analyze the image and extract design patterns</li>
                <li>A template will be generated with HTML, CSS, and properties</li>
                <li>You can refine the template in the visual editor</li>
            </ol>
            <p><strong>Supported formats:</strong> JPG, PNG, WebP</p>
            <p><strong>Recommended:</strong> Clear, high-contrast images work best</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label>Select Image *</label>
                <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" required>
                <small>Upload an image of the UI element you want to create a template for</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Upload and Process</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.upload-form-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--layout-spacing-xl);
}

.upload-info {
    background: var(--layout-color-surface-secondary);
    padding: var(--layout-spacing-lg);
    border-radius: var(--layout-border-radius-md);
    border: 1px solid var(--layout-color-border);
}

.upload-info h2 {
    margin-top: 0;
}

.upload-info ol {
    padding-left: 20px;
}

.upload-info li {
    margin-bottom: var(--layout-spacing-sm);
}

.upload-form {
    background: var(--layout-color-surface);
    padding: var(--layout-spacing-xl);
    border-radius: var(--layout-border-radius-md);
    border: 1px solid var(--layout-color-border);
}

.upload-form input[type="file"] {
    padding: var(--layout-spacing-sm);
    border: 2px dashed var(--layout-color-border);
    border-radius: var(--layout-border-radius-sm);
    width: 100%;
    cursor: pointer;
}

@media (max-width: 768px) {
    .upload-form-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

