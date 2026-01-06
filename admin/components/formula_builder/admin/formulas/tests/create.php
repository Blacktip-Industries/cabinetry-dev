<?php
/**
 * Formula Builder Component - Create Test Case
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tests.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$errors = [];
$success = false;

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: ../index.php?error=notfound');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testName = trim($_POST['test_name'] ?? '');
    $inputDataJson = $_POST['input_data'] ?? '{}';
    $expectedResultJson = $_POST['expected_result'] ?? '';
    $runAfterSave = isset($_POST['run_after_save']);
    
    // Validate
    if (empty($testName)) {
        $errors[] = 'Test name is required';
    }
    
    // Parse input data
    $inputData = json_decode($inputDataJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'Invalid JSON in input data: ' . json_last_error_msg();
    }
    
    // Parse expected result (optional)
    $expectedResult = null;
    if (!empty($expectedResultJson)) {
        $expectedResult = json_decode($expectedResultJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid JSON in expected result: ' . json_last_error_msg();
        }
    }
    
    if (empty($errors)) {
        $result = formula_builder_create_test($formulaId, $testName, $inputData, $expectedResult);
        
        if ($result['success']) {
            if ($runAfterSave) {
                header('Location: run.php?test_id=' . $result['test_id'] . '&formula_id=' . $formulaId);
                exit;
            } else {
                header('Location: index.php?formula_id=' . $formulaId);
                exit;
            }
        } else {
            $errors[] = $result['error'] ?? 'Failed to create test';
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Test Case - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        textarea { font-family: monospace; min-height: 150px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .formula-preview { background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 20px 0; }
        .formula-preview pre { margin: 0; font-family: monospace; }
        .checkbox-group { margin: 10px 0; }
        .checkbox-group input[type="checkbox"] { margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Create Test Case: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="index.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Test Suite</a>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="formula-preview">
        <h3>Formula Code</h3>
        <pre><?php echo htmlspecialchars($formula['formula_code']); ?></pre>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label for="test_name">Test Name *</label>
            <input type="text" id="test_name" name="test_name" value="<?php echo htmlspecialchars($_POST['test_name'] ?? ''); ?>" required>
            <small>Enter a descriptive name for this test case</small>
        </div>
        
        <div class="form-group">
            <label for="input_data">Input Data (JSON) *</label>
            <textarea id="input_data" name="input_data" required><?php echo htmlspecialchars($_POST['input_data'] ?? '{\n    "width": 600,\n    "height": 800,\n    "base_price": 100\n}'); ?></textarea>
            <small>Enter the input data as JSON. Example: {"width": 600, "height": 800, "base_price": 100}</small>
        </div>
        
        <div class="form-group">
            <label for="expected_result">Expected Result (JSON, optional)</label>
            <textarea id="expected_result" name="expected_result"><?php echo htmlspecialchars($_POST['expected_result'] ?? ''); ?></textarea>
            <small>Enter the expected result. If provided, the test will compare actual vs expected. Leave empty to just record the result.</small>
        </div>
        
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="run_after_save" value="1" <?php echo isset($_POST['run_after_save']) ? 'checked' : ''; ?>>
                Run test after saving
            </label>
        </div>
        
        <button type="submit" class="btn btn-success">Create Test</button>
        <a href="index.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Cancel</a>
    </form>
    
    <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
        <h3>Example Test Cases</h3>
        <p><strong>Basic calculation:</strong></p>
        <pre>Input: {"width": 600, "height": 800, "base_price": 100}
Expected: 100</pre>
        
        <p><strong>With material:</strong></p>
        <pre>Input: {"width": 600, "height": 800, "depth": 400, "base_price": 100, "material": "White Gloss"}
Expected: 150</pre>
        
        <p><strong>Edge case - zero values:</strong></p>
        <pre>Input: {"width": 0, "height": 0, "base_price": 0}
Expected: 0</pre>
    </div>
</body>
</html>

