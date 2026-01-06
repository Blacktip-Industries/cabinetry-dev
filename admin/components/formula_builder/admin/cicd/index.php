<?php
/**
 * Formula Builder Component - CI/CD Pipelines
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/cicd.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$pipelines = [];

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if ($formula) {
        // Get pipelines for formula
        $conn = formula_builder_get_db_connection();
        if ($conn) {
            $tableName = formula_builder_get_table_name('cicd_pipelines');
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $formulaId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['stages'] = json_decode($row['stages'], true) ?: [];
                $pipelines[] = $row;
            }
            $stmt->close();
        }
    }
}

if (!$formula) {
    header('Location: ../formulas/index.php');
    exit;
}

// Handle create pipeline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_pipeline'])) {
    $pipelineName = trim($_POST['pipeline_name'] ?? '');
    $triggerType = $_POST['trigger_type'] ?? 'manual';
    $stages = [];
    
    if (isset($_POST['stage_test'])) $stages[] = ['name' => 'test', 'enabled' => true];
    if (isset($_POST['stage_quality'])) $stages[] = ['name' => 'quality', 'enabled' => true];
    if (isset($_POST['stage_security'])) $stages[] = ['name' => 'security', 'enabled' => true];
    if (isset($_POST['stage_deploy'])) $stages[] = ['name' => 'deploy', 'enabled' => true];
    
    if (empty($pipelineName)) {
        $errors[] = 'Pipeline name is required';
    } else {
        $result = formula_builder_create_pipeline($formulaId, $pipelineName, $triggerType, $stages);
        if ($result['success']) {
            header('Location: index.php?formula_id=' . $formulaId . '&created=1');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create pipeline';
        }
    }
}

// Handle run pipeline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_pipeline'])) {
    $pipelineId = (int)$_POST['pipeline_id'];
    $result = formula_builder_run_pipeline($pipelineId);
    if ($result['success']) {
        header('Location: run.php?pipeline_id=' . $pipelineId);
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Failed to run pipeline';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>CI/CD Pipelines - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .checkbox-group { display: flex; gap: 15px; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; }
        .checkbox-group input[type="checkbox"] { width: auto; margin-right: 5px; }
    </style>
</head>
<body>
    <h1>CI/CD Pipelines: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="../formulas/edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
    
    <div style="margin-top: 30px;">
        <h2>Create Pipeline</h2>
        <form method="POST">
            <div class="form-group">
                <label for="pipeline_name">Pipeline Name *</label>
                <input type="text" id="pipeline_name" name="pipeline_name" required>
            </div>
            <div class="form-group">
                <label for="trigger_type">Trigger Type *</label>
                <select id="trigger_type" name="trigger_type" required>
                    <option value="manual">Manual</option>
                    <option value="on_save">On Save</option>
                    <option value="on_commit">On Commit</option>
                    <option value="scheduled">Scheduled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Pipeline Stages</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="stage_test" checked> Test</label>
                    <label><input type="checkbox" name="stage_quality" checked> Quality</label>
                    <label><input type="checkbox" name="stage_security" checked> Security</label>
                    <label><input type="checkbox" name="stage_deploy"> Deploy</label>
                </div>
            </div>
            <button type="submit" name="create_pipeline" class="btn">Create Pipeline</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Pipelines (<?php echo count($pipelines); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Trigger</th>
                    <th>Stages</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pipelines)): ?>
                    <tr>
                        <td colspan="5">No pipelines found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pipelines as $pipeline): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pipeline['pipeline_name']); ?></td>
                            <td><?php echo htmlspecialchars($pipeline['trigger_type']); ?></td>
                            <td>
                                <?php 
                                $stageNames = array_map(function($s) { return $s['name']; }, $pipeline['stages']);
                                echo implode(', ', $stageNames);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($pipeline['status']); ?></td>
                            <td>
                                <a href="run.php?pipeline_id=<?php echo $pipeline['id']; ?>" class="btn btn-success">Run</a>
                                <a href="status.php?pipeline_id=<?php echo $pipeline['id']; ?>" class="btn">Status</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

