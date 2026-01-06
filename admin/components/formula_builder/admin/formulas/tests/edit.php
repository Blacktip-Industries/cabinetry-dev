<?php
/**
 * Formula Builder Component - Edit Test Case
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tests.php';

$testId = (int)($_GET['id'] ?? 0);
$formulaId = (int)($_GET['formula_id'] ?? 0);
$test = null;
$formula = null;
$errors = [];
$success = false;

if ($testId) {
    $test = formula_builder_get_test($testId);
    if (!$test) {
        header('Location: index.php?formula_id=' . $formulaId . '&error=notfound');
        exit;
    }
    
    if ($formulaId) {
        $formula = formula_builder_get_formula_by_id($formulaId);
    } else {
        $formula = formula_builder_get_formula_by_id($test['formula_id']);
        $formulaId = $formula['id'];
    }
} else {
    header('Location: index.php');
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
        $updateData = [
            'test_name' => $testName,
            'input_data' => $inputData,
            'expected_result' => $expectedResult
        ];
        
        $result = formula_builder_update_test($testId, $updateData);
        
        if ($result['success']) {
            if ($runAfterSave) {
                header('Location: run.php?test_id=' . $testId . '&formula_id=' . $formulaId);
                exit;
            } else {
                header('Location: index.php?formula_id=' . $formulaId);
                exit;
            }
        } else {
            $errors[] = $result['error'] ?? 'Failed to update test';
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Test Case - Formula Builder</title>
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
        .test-info { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .status-passed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .checkbox-group { margin: 10px 0; }
        .checkbox-group input[type="checkbox"] { margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Edit Test Case: <?php echo htmlspecialchars($test['test_name']); ?></h1>
    <a href="index.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Test Suite</a>
    <a href="view.php?id=<?php echo $testId; ?>&formula_id=<?php echo $formulaId; ?>" class="btn">View Details</a>
    
    <div class="test-info">
        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $test['status']; ?>"><?php echo strtoupper($test['status']); ?></span></p>
        <?php if ($test['last_run_at']): ?>
            <p><strong>Last Run:</strong> <?php echo date('Y-m-d H:i:s', strtotime($test['last_run_at'])); ?></p>
        <?php endif; ?>
        <?php if ($test['execution_time_ms']): ?>
            <p><strong>Execution Time:</strong> <?php echo $test['execution_time_ms']; ?> ms</p>
        <?php endif; ?>
    </div>
    
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
    
    <form method="POST">
        <div class="form-group">
            <label for="test_name">Test Name *</label>
            <input type="text" id="test_name" name="test_name" value="<?php echo htmlspecialchars($_POST['test_name'] ?? $test['test_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="input_data">Input Data (JSON) *</label>
            <textarea id="input_data" name="input_data" required><?php echo htmlspecialchars(json_encode($_POST['input_data'] ?? $test['input_data'], JSON_PRETTY_PRINT)); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="expected_result">Expected Result (JSON, optional)</label>
            <textarea id="expected_result" name="expected_result"><?php echo htmlspecialchars(json_encode($_POST['expected_result'] ?? $test['expected_result'], JSON_PRETTY_PRINT)); ?></textarea>
        </div>
        
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="run_after_save" value="1" <?php echo isset($_POST['run_after_save']) ? 'checked' : ''; ?>>
                Run test after saving
            </label>
        </div>
        
        <button type="submit" class="btn btn-success">Update Test</button>
        <a href="index.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>

