<?php
/**
 * Formula Builder Component - Debug Session
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/debugger.php';

$sessionId = (int)($_GET['session_id'] ?? 0);
$session = null;
$formula = null;

if ($sessionId) {
    $session = formula_builder_get_debug_session($sessionId);
    if ($session) {
        $formula = formula_builder_get_formula_by_id($session['formula_id']);
    }
}

if (!$session || !$formula) {
    header('Location: index.php?error=notfound');
    exit;
}

// Handle breakpoint actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_breakpoint'])) {
        $lineNumber = (int)$_POST['line_number'];
        formula_builder_set_breakpoint($sessionId, $lineNumber);
        header('Location: session.php?session_id=' . $sessionId);
        exit;
    } elseif (isset($_POST['remove_breakpoint'])) {
        $lineNumber = (int)$_POST['line_number'];
        formula_builder_remove_breakpoint($sessionId, $lineNumber);
        header('Location: session.php?session_id=' . $sessionId);
        exit;
    }
}

$variables = formula_builder_inspect_variables($sessionId);
$trace = formula_builder_get_execution_trace($session['formula_id'], $session['session_data']['input_data'] ?? []);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Session - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1600px; margin: 20px auto; padding: 20px; }
        .debug-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .debug-panel { border: 1px solid #ddd; border-radius: 4px; padding: 15px; }
        .code-panel { grid-column: 1 / -1; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 2px; border: none; cursor: pointer; font-size: 12px; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .variable { padding: 5px; margin: 5px 0; background: #f5f5f5; border-radius: 3px; }
        .breakpoint { background: #ffc107; color: #000; padding: 2px 5px; border-radius: 3px; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Debug Session #<?php echo $sessionId; ?></h1>
    <a href="index.php?formula_id=<?php echo $session['formula_id']; ?>" class="btn btn-secondary">Back</a>
    
    <div class="debug-container">
        <div class="code-panel debug-panel">
            <h3>Formula Code</h3>
            <pre><?php echo htmlspecialchars($formula['formula_code']); ?></pre>
            <div style="margin-top: 10px;">
                <form method="POST" style="display: inline;">
                    <input type="number" name="line_number" placeholder="Line number" style="width: 100px; padding: 5px;" required>
                    <button type="submit" name="set_breakpoint" class="btn">Set Breakpoint</button>
                </form>
            </div>
        </div>
        
        <div class="debug-panel">
            <h3>Variables</h3>
            <?php if (!empty($variables['variables'])): ?>
                <?php foreach ($variables['variables'] as $name => $value): ?>
                    <div class="variable">
                        <strong><?php echo htmlspecialchars($name); ?>:</strong>
                        <pre style="margin: 5px 0 0 0; font-size: 11px;"><?php echo htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No variables available</p>
            <?php endif; ?>
        </div>
        
        <div class="debug-panel">
            <h3>Execution Trace</h3>
            <p><strong>Status:</strong> <?php echo $trace['success'] ? 'Success' : 'Failed'; ?></p>
            <p><strong>Execution Time:</strong> <?php echo round($trace['execution_time'], 2); ?> ms</p>
            <?php if (isset($trace['result']['result'])): ?>
                <p><strong>Result:</strong> <?php echo htmlspecialchars(json_encode($trace['result']['result'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

