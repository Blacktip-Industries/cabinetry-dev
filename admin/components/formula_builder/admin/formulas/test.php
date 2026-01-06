<?php
/**
 * Formula Builder Component - Test Formula
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/executor.php';

$formulaId = (int)($_GET['id'] ?? 0);
$formula = null;
$testResult = null;
$testInput = [];

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
    // Parse test input (JSON or form data)
    $testInputJson = $_POST['test_input'] ?? '{}';
    $testInput = json_decode($testInputJson, true);
    if (!is_array($testInput)) {
        $testInput = [];
    }
    
    // Execute formula
    $testResult = formula_builder_execute_formula($formulaId, $testInput);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Formula - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        textarea { width: 100%; padding: 8px; box-sizing: border-box; min-height: 200px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .result-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .result-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .result-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Test Formula: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="index.php" class="btn btn-secondary">Back to List</a>
    <a href="edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Edit Formula</a>
    
    <div style="margin-top: 20px;">
        <h2>Formula Code</h2>
        <pre><?php echo htmlspecialchars($formula['formula_code']); ?></pre>
    </div>
    
    <form method="POST" style="margin-top: 20px;">
        <div class="form-group">
            <label for="test_input">Test Input (JSON format)</label>
            <textarea id="test_input" name="test_input" placeholder='{"width": 600, "height": 800, "base_price": 100}'><?php echo htmlspecialchars(json_encode($testInput, JSON_PRETTY_PRINT)); ?></textarea>
            <small>Enter option values as JSON object. Example: {"width": 600, "height": 800, "base_price": 100}</small>
        </div>
        
        <button type="submit" class="btn">Execute Test</button>
    </form>
    
    <?php if ($testResult !== null): ?>
        <div class="result <?php echo $testResult['success'] ? 'result-success' : 'result-error'; ?>">
            <h3><?php echo $testResult['success'] ? 'Test Successful' : 'Test Failed'; ?></h3>
            
            <?php if ($testResult['success']): ?>
                <p><strong>Result:</strong> <?php echo htmlspecialchars($testResult['result']); ?></p>
                <?php if (isset($testResult['cached'])): ?>
                    <p><em>Result was retrieved from cache</em></p>
                <?php endif; ?>
            <?php else: ?>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($testResult['error'] ?? 'Unknown error'); ?></p>
            <?php endif; ?>
            
            <h4>Full Result:</h4>
            <pre><?php echo htmlspecialchars(json_encode($testResult, JSON_PRETTY_PRINT)); ?></pre>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
        <h3>Example Test Inputs</h3>
        <p><strong>Basic calculation:</strong></p>
        <pre>{"width": 600, "height": 800, "base_price": 100}</pre>
        
        <p><strong>With multiple options:</strong></p>
        <pre>{"width": 600, "height": 800, "depth": 400, "base_price": 100, "material": "White Gloss"}</pre>
    </div>
</body>
</html>

