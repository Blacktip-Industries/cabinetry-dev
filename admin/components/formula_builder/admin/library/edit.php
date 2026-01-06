<?php
/**
 * Formula Builder Component - Edit Template
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/library.php';

$templateId = (int)($_GET['id'] ?? 0);
$template = null;
$errors = [];
$success = false;

if ($templateId) {
    $template = formula_builder_get_template($templateId);
    if (!$template) {
        header('Location: index.php?error=notfound');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateData = [
        'id' => $templateId,
        'formula_name' => trim($_POST['formula_name'] ?? ''),
        'formula_code' => $_POST['formula_code'] ?? '',
        'category' => trim($_POST['category'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'tags' => trim($_POST['tags'] ?? ''),
        'is_public' => isset($_POST['is_public']) ? 1 : 0
    ];
    
    // Validate
    if (empty($templateData['formula_name'])) {
        $errors[] = 'Template name is required';
    }
    if (empty($templateData['formula_code'])) {
        $errors[] = 'Formula code is required';
    }
    
    if (empty($errors)) {
        $result = formula_builder_update_template($templateId, $templateData);
        if ($result['success']) {
            $success = true;
            header('Location: view.php?id=' . $templateId . '&updated=1');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Error updating template';
        }
    }
}

// Get categories
$categories = formula_builder_get_categories();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Template - Formula Library</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        textarea { min-height: 300px; font-family: monospace; }
        .error { color: red; margin-top: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <h1>Edit Template: <?php echo htmlspecialchars($template['formula_name']); ?></h1>
    <a href="view.php?id=<?php echo $templateId; ?>" class="btn btn-secondary">Back to Template</a>
    
    <?php if (!empty($errors)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <strong>Errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="margin-top: 20px;">
        <div class="form-group">
            <label for="formula_name">Template Name *</label>
            <input type="text" id="formula_name" name="formula_name" value="<?php echo htmlspecialchars($_POST['formula_name'] ?? $template['formula_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" list="categories" value="<?php echo htmlspecialchars($_POST['category'] ?? $template['category']); ?>">
            <datalist id="categories">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? $template['description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? $template['tags']); ?>">
        </div>
        
        <div class="form-group">
            <label for="formula_code">Formula Code *</label>
            <textarea id="formula_code" name="formula_code" required><?php echo htmlspecialchars($_POST['formula_code'] ?? $template['formula_code']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_public" value="1" <?php echo (($_POST['is_public'] ?? $template['is_public']) ? 'checked' : ''); ?>>
                Make this template public (visible to all users)
            </label>
        </div>
        
        <button type="submit" class="btn">Update Template</button>
        <a href="view.php?id=<?php echo $templateId; ?>" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>

