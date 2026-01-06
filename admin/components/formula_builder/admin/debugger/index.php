<?php
/**
 * Formula Builder Component - Debugger
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/debugger.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$sessions = [];

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: ../formulas/index.php?error=notfound');
        exit;
    }
    
    // Get debug sessions for this formula
    $conn = formula_builder_get_db_connection();
    if ($conn) {
        $tableName = formula_builder_get_table_name('debug_sessions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        $stmt->close();
    }
} else {
    header('Location: ../formulas/index.php');
    exit;
}

// Handle create session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    $inputDataJson = $_POST['input_data'] ?? '{}';
    $inputData = json_decode($inputDataJson, true) ?: [];
    
    $result = formula_builder_create_debug_session($formulaId, $inputData);
    if ($result['success']) {
        header('Location: session.php?session_id=' . $result['session_id']);
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debugger - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        textarea { width: 100%; padding: 8px; box-sizing: border-box; min-height: 150px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Debugger: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="../formulas/edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
    
    <div style="margin-top: 20px;">
        <h2>Create Debug Session</h2>
        <form method="POST">
            <div class="form-group">
                <label for="input_data">Input Data (JSON)</label>
                <textarea id="input_data" name="input_data" required><?php echo htmlspecialchars($_POST['input_data'] ?? '{}'); ?></textarea>
            </div>
            <button type="submit" name="create_session" class="btn">Create Session</button>
        </form>
    </div>
    
    <?php if (!empty($sessions)): ?>
        <div style="margin-top: 30px;">
            <h2>Recent Debug Sessions</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Created</th>
                        <th>Last Accessed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td><?php echo $session['id']; ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($session['created_at'])); ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($session['last_accessed'])); ?></td>
                            <td>
                                <a href="session.php?session_id=<?php echo $session['id']; ?>" class="btn">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>

