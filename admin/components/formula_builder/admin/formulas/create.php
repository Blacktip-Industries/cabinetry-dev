<?php
/**
 * Formula Builder Component - Create Formula
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/parser.php';
require_once __DIR__ . '/../core/library.php';

$errors = [];
$success = false;
$templateId = (int)($_GET['template_id'] ?? 0);
$template = null;

// Load template if template_id provided
if ($templateId) {
    $template = formula_builder_get_template($templateId);
    if ($template) {
        // Increment usage count
        formula_builder_increment_usage($templateId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formulaData = [
        'product_id' => (int)($_POST['product_id'] ?? 0),
        'formula_name' => trim($_POST['formula_name'] ?? ''),
        'formula_code' => $_POST['formula_code'] ?? '',
        'formula_type' => $_POST['formula_type'] ?? 'script',
        'description' => trim($_POST['description'] ?? ''),
        'cache_enabled' => isset($_POST['cache_enabled']) ? 1 : 0,
        'cache_duration' => (int)($_POST['cache_duration'] ?? 3600)
    ];
    
    // Validate
    $validation = formula_builder_validate_formula_data($formulaData);
    if (!$validation['success']) {
        $errors = $validation['errors'];
    } else {
        // Validate formula syntax
        $syntaxValidation = formula_builder_validate_formula($formulaData['formula_code']);
        if (!$syntaxValidation['success']) {
            $errors = array_merge($errors, $syntaxValidation['errors']);
        }
    }
    
    if (empty($errors)) {
        $conn = formula_builder_get_db_connection();
        if ($conn) {
            try {
                $tableName = formula_builder_get_table_name('product_formulas');
                $stmt = $conn->prepare("INSERT INTO {$tableName} (product_id, formula_name, formula_code, formula_type, description, cache_enabled, cache_duration, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("issssii", 
                    $formulaData['product_id'],
                    $formulaData['formula_name'],
                    $formulaData['formula_code'],
                    $formulaData['formula_type'],
                    $formulaData['description'],
                    $formulaData['cache_enabled'],
                    $formulaData['cache_duration']
                );
                $stmt->execute();
                $formulaId = $conn->insert_id;
                $stmt->close();
                
                $success = true;
                header('Location: index.php?created=' . $formulaId);
                exit;
            } catch (Exception $e) {
                $errors[] = 'Error creating formula: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Database connection failed';
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Formula - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        textarea { min-height: 300px; font-family: monospace; }
        .error { color: red; margin-top: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        #formula-editor { width: 100%; height: 400px; border: 1px solid #ddd; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/vs/editor/editor.main.css">
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/vs/loader.js"></script>
</head>
<body>
    <h1>Create Formula</h1>
    <div style="margin-bottom: 10px;">
        <a href="index.php" class="btn btn-secondary">Back to List</a>
        <a href="../library/index.php" class="btn" style="background: #28a745;">Load from Template</a>
    </div>
    
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
            <label for="product_id">Product ID *</label>
            <input type="number" id="product_id" name="product_id" value="<?php echo htmlspecialchars($_POST['product_id'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="formula_name">Formula Name *</label>
            <input type="text" id="formula_name" name="formula_name" value="<?php echo htmlspecialchars($_POST['formula_name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="formula_type">Formula Type</label>
            <select id="formula_type" name="formula_type">
                <option value="script" <?php echo (($_POST['formula_type'] ?? 'script') === 'script') ? 'selected' : ''; ?>>Script</option>
                <option value="expression" <?php echo (($_POST['formula_type'] ?? '') === 'expression') ? 'selected' : ''; ?>>Expression</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="formula_code">Formula Code *</label>
            <?php if ($template): ?>
                <div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                    <strong>Template loaded:</strong> <?php echo htmlspecialchars($template['formula_name']); ?>
                    <a href="../library/view.php?id=<?php echo $templateId; ?>" target="_blank" style="margin-left: 10px;">View Template</a>
                </div>
            <?php endif; ?>
            <div id="formula-editor"></div>
            <textarea id="formula_code" name="formula_code" style="display: none;" required><?php echo htmlspecialchars($_POST['formula_code'] ?? ($template ? $template['formula_code'] : '// Example formula
var width = get_option(\'width\');
var height = get_option(\'height\');
var base_price = get_option(\'base_price\');
var total = base_price + (width * height * 0.01);
return total;')); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="cache_enabled" value="1" <?php echo isset($_POST['cache_enabled']) ? 'checked' : 'checked'; ?>>
                Enable Caching
            </label>
        </div>
        
        <div class="form-group">
            <label for="cache_duration">Cache Duration (seconds)</label>
            <input type="number" id="cache_duration" name="cache_duration" value="<?php echo htmlspecialchars($_POST['cache_duration'] ?? '3600'); ?>" min="0">
        </div>
        
        <button type="submit" class="btn">Create Formula</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
    
    <script>
        require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/vs' } });
        require(['vs/editor/editor.main'], function() {
            var editor = monaco.editor.create(document.getElementById('formula-editor'), {
                value: document.getElementById('formula_code').value,
                language: 'javascript',
                theme: 'vs',
                automaticLayout: true,
                minimap: { enabled: true },
                wordWrap: 'on',
                lineNumbers: 'on',
                scrollBeyondLastLine: false
            });
            
            // Update hidden textarea when editor content changes
            editor.onDidChangeModelContent(function() {
                document.getElementById('formula_code').value = editor.getValue();
            });
            
            // Update editor when form is submitted
            document.querySelector('form').addEventListener('submit', function() {
                document.getElementById('formula_code').value = editor.getValue();
            });
        });
    </script>
</body>
</html>

