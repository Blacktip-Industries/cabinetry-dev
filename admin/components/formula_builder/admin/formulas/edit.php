<?php
/**
 * Formula Builder Component - Edit Formula
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/parser.php';
require_once __DIR__ . '/../core/library.php';

$formulaId = (int)($_GET['id'] ?? 0);
$formula = null;
$errors = [];
$success = false;

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: index.php?error=notfound');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formulaData = [
        'id' => $formulaId,
        'formula_name' => trim($_POST['formula_name'] ?? ''),
        'formula_code' => $_POST['formula_code'] ?? '',
        'formula_type' => $_POST['formula_type'] ?? 'script',
        'description' => trim($_POST['description'] ?? ''),
        'cache_enabled' => isset($_POST['cache_enabled']) ? 1 : 0,
        'cache_duration' => (int)($_POST['cache_duration'] ?? 3600),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'changelog' => trim($_POST['changelog'] ?? '')
    ];
    
    // Validate
    if (empty($formulaData['formula_name'])) {
        $errors[] = 'Formula name is required';
    }
    if (empty($formulaData['formula_code'])) {
        $errors[] = 'Formula code is required';
    }
    
    if (empty($errors)) {
        // Validate formula syntax
        $syntaxValidation = formula_builder_validate_formula($formulaData['formula_code']);
        if (!$syntaxValidation['success']) {
            $errors = array_merge($errors, $syntaxValidation['errors']);
        }
    }
    
    if (empty($errors)) {
        // Use save_formula function which handles versioning
        $result = formula_builder_save_formula($formulaData);
        
        if ($result['success']) {
            $success = true;
            header('Location: index.php?updated=' . $formulaId);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Error updating formula';
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Formula - Formula Builder</title>
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
    <h1>Edit Formula: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <div style="margin-bottom: 10px;">
        <a href="index.php" class="btn btn-secondary">Back to List</a>
        <a href="versions.php?formula_id=<?php echo $formulaId; ?>" class="btn">Version History (v<?php echo $formula['version']; ?>)</a>
        <a href="tests/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #17a2b8;">Test Suite</a>
        <a href="../debugger/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #6f42c1;">Debugger</a>
        <a href="../analytics/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #fd7e14;">Analytics</a>
        <a href="../quality/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #20c997;">Quality Check</a>
        <a href="../collaboration/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #6610f2;">Collaboration</a>
        <a href="../cicd/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #e83e8c;">CI/CD</a>
        <a href="../ai/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #6f42c1;">AI Features</a>
        <a href="../deployment/index.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #fd7e14;">Deployments</a>
        <a href="../library/save.php?formula_id=<?php echo $formulaId; ?>" class="btn" style="background: #28a745;">Save as Template</a>
        <a href="../library/index.php" class="btn">Load from Template</a>
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
            <label for="formula_name">Formula Name *</label>
            <input type="text" id="formula_name" name="formula_name" value="<?php echo htmlspecialchars($_POST['formula_name'] ?? $formula['formula_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="formula_type">Formula Type</label>
            <select id="formula_type" name="formula_type">
                <option value="script" <?php echo (($_POST['formula_type'] ?? $formula['formula_type']) === 'script') ? 'selected' : ''; ?>>Script</option>
                <option value="expression" <?php echo (($_POST['formula_type'] ?? $formula['formula_type']) === 'expression') ? 'selected' : ''; ?>>Expression</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="formula_code">Formula Code *</label>
            <div id="formula-editor"></div>
            <textarea id="formula_code" name="formula_code" style="display: none;" required><?php echo htmlspecialchars($_POST['formula_code'] ?? $formula['formula_code']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $formula['description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="changelog">Changelog (optional)</label>
            <textarea id="changelog" name="changelog" rows="2" placeholder="Describe what changed in this version..."><?php echo htmlspecialchars($_POST['changelog'] ?? ''); ?></textarea>
            <small>This will be saved with the version history</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="cache_enabled" value="1" <?php echo (($_POST['cache_enabled'] ?? $formula['cache_enabled']) ? 'checked' : ''); ?>>
                Enable Caching
            </label>
        </div>
        
        <div class="form-group">
            <label for="cache_duration">Cache Duration (seconds)</label>
            <input type="number" id="cache_duration" name="cache_duration" value="<?php echo htmlspecialchars($_POST['cache_duration'] ?? $formula['cache_duration']); ?>" min="0">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo (($_POST['is_active'] ?? $formula['is_active']) ? 'checked' : ''); ?>>
                Active
            </label>
        </div>
        
        <button type="submit" class="btn">Update Formula</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
    
    <div id="validation-status" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;"></div>
    
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
            
            var validationTimeout;
            var statusDiv = document.getElementById('validation-status');
            
            // Real-time validation
            editor.onDidChangeModelContent(function() {
                document.getElementById('formula_code').value = editor.getValue();
                
                // Debounce validation
                clearTimeout(validationTimeout);
                validationTimeout = setTimeout(function() {
                    validateFormula(editor.getValue());
                }, 500);
            });
            
            // Initial validation
            validateFormula(editor.getValue());
            
            // Validation function
            function validateFormula(code) {
                fetch('../api/validate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        formula_code: code,
                        formula_id: <?php echo $formulaId; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Update Monaco markers
                    var markers = data.markers || [];
                    var model = editor.getModel();
                    monaco.editor.setModelMarkers(model, 'validation', markers);
                    
                    // Update status display
                    updateValidationStatus(data);
                })
                .catch(error => {
                    console.error('Validation error:', error);
                });
            }
            
            // Update validation status display
            function updateValidationStatus(data) {
                statusDiv.style.display = 'block';
                
                if (data.success && data.errors.length === 0) {
                    var warningCount = (data.warnings || []).length + 
                                     (data.security_warnings || []).length + 
                                     (data.performance_warnings || []).length;
                    
                    if (warningCount === 0) {
                        statusDiv.style.background = '#d4edda';
                        statusDiv.style.color = '#155724';
                        statusDiv.style.border = '1px solid #c3e6cb';
                        statusDiv.innerHTML = '<strong>✓ Valid</strong> - No errors or warnings';
                    } else {
                        statusDiv.style.background = '#fff3cd';
                        statusDiv.style.color = '#856404';
                        statusDiv.style.border = '1px solid #ffeaa7';
                        statusDiv.innerHTML = '<strong>⚠ Valid with warnings</strong> - ' + warningCount + ' warning(s)';
                    }
                } else {
                    statusDiv.style.background = '#f8d7da';
                    statusDiv.style.color = '#721c24';
                    statusDiv.style.border = '1px solid #f5c6cb';
                    statusDiv.innerHTML = '<strong>✗ Errors found</strong> - ' + data.errors.length + ' error(s)';
                }
            }
            
            // Update editor when form is submitted
            document.querySelector('form').addEventListener('submit', function() {
                document.getElementById('formula_code').value = editor.getValue();
            });
        });
    </script>
</body>
</html>

