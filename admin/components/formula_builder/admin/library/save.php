<?php
/**
 * Formula Builder Component - Save Template
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/library.php';

$errors = [];
$success = false;
$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;

// Get formula if formula_id provided
if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateData = [
        'formula_name' => trim($_POST['formula_name'] ?? ''),
        'formula_code' => $_POST['formula_code'] ?? '',
        'category' => trim($_POST['category'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'tags' => trim($_POST['tags'] ?? ''),
        'is_public' => isset($_POST['is_public']) ? 1 : 0,
        'created_by' => $_SESSION['user_id'] ?? 0
    ];
    
    // Validate
    if (empty($templateData['formula_name'])) {
        $errors[] = 'Template name is required';
    }
    if (empty($templateData['formula_code'])) {
        $errors[] = 'Formula code is required';
    }
    
    if (empty($errors)) {
        $result = formula_builder_save_template($templateData);
        if ($result['success']) {
            $success = true;
            header('Location: view.php?id=' . $result['template_id']);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Error saving template';
        }
    }
}

// Get categories for dropdown
$categories = formula_builder_get_categories();

// Get suggested tags if formula code provided
$suggestedTags = [];
if (!empty($_POST['formula_code']) || ($formula && !empty($formula['formula_code']))) {
    $code = $_POST['formula_code'] ?? $formula['formula_code'];
    $suggestedTags = formula_builder_suggest_tags($code);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Save Template - Formula Library</title>
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
        .suggested-tags { margin-top: 5px; font-size: 12px; color: #666; }
        .suggested-tags span { display: inline-block; background: #e9ecef; padding: 2px 8px; border-radius: 3px; margin-right: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Save Formula as Template</h1>
    <a href="index.php" class="btn btn-secondary">Back to Library</a>
    
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
            <input type="text" id="formula_name" name="formula_name" value="<?php echo htmlspecialchars($_POST['formula_name'] ?? ($formula ? $formula['formula_name'] : '')); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" list="categories" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
            <datalist id="categories">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ($formula ? $formula['description'] : '')); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="e.g., pricing, materials, calculation">
            <?php if (!empty($suggestedTags)): ?>
                <div class="suggested-tags">
                    <strong>Suggested:</strong>
                    <?php foreach ($suggestedTags as $tag): ?>
                        <span onclick="addTag('<?php echo htmlspecialchars($tag); ?>')"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="formula_code">Formula Code *</label>
            <textarea id="formula_code" name="formula_code" required><?php echo htmlspecialchars($_POST['formula_code'] ?? ($formula ? $formula['formula_code'] : '')); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_public" value="1" <?php echo (!isset($_POST['is_public']) || $_POST['is_public']) ? 'checked' : ''; ?>>
                Make this template public (visible to all users)
            </label>
        </div>
        
        <button type="submit" class="btn">Save Template</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
    
    <script>
        function addTag(tag) {
            var tagsInput = document.getElementById('tags');
            var currentTags = tagsInput.value.split(',').map(t => t.trim()).filter(t => t);
            if (!currentTags.includes(tag)) {
                currentTags.push(tag);
                tagsInput.value = currentTags.join(', ');
            }
        }
    </script>
</body>
</html>

