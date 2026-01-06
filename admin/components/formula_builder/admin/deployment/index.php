<?php
/**
 * Formula Builder Component - Deployment Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/deployment.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$deployments = [];

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if ($formula) {
        $deployments = formula_builder_get_deployments($formulaId);
    }
}

if (!$formula) {
    header('Location: ../formulas/index.php');
    exit;
}

// Handle create deployment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_deployment'])) {
    $environment = $_POST['environment'] ?? 'staging';
    $rolloutPercentage = (int)($_POST['rollout_percentage'] ?? 100);
    $deployedBy = $_SESSION['user_id'] ?? 1;
    
    $result = formula_builder_create_deployment($formulaId, $environment, $deployedBy, $rolloutPercentage);
    if ($result['success']) {
        // Auto-deploy
        formula_builder_deploy_to_environment($result['deployment_id']);
        header('Location: index.php?formula_id=' . $formulaId . '&deployed=1');
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Failed to create deployment';
    }
}

// Handle rollback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rollback_deployment'])) {
    $deploymentId = (int)$_POST['deployment_id'];
    $result = formula_builder_rollback_deployment($deploymentId);
    if ($result['success']) {
        header('Location: index.php?formula_id=' . $formulaId . '&rolled_back=1');
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Failed to rollback';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Deployments - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .status-deployed { color: #28a745; }
        .status-pending { color: #ffc107; }
        .status-failed { color: #dc3545; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>
    <h1>Deployments: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="../formulas/edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
    
    <div style="margin-top: 30px;">
        <h2>Create Deployment</h2>
        <form method="POST">
            <div class="form-group">
                <label for="environment">Environment *</label>
                <select id="environment" name="environment" required>
                    <option value="development">Development</option>
                    <option value="staging" selected>Staging</option>
                    <option value="production">Production</option>
                </select>
            </div>
            <div class="form-group">
                <label for="rollout_percentage">Rollout Percentage</label>
                <input type="number" id="rollout_percentage" name="rollout_percentage" value="100" min="1" max="100">
                <small>Percentage of traffic to deploy to (for canary deployments)</small>
            </div>
            <button type="submit" name="create_deployment" class="btn">Create & Deploy</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Deployment History</h2>
        <table>
            <thead>
                <tr>
                    <th>Environment</th>
                    <th>Status</th>
                    <th>Rollout</th>
                    <th>Deployed By</th>
                    <th>Deployed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deployments)): ?>
                    <tr>
                        <td colspan="6">No deployments found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deployments as $deployment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($deployment['environment']); ?></td>
                            <td class="status-<?php echo $deployment['deployment_status']; ?>">
                                <?php echo htmlspecialchars($deployment['deployment_status']); ?>
                            </td>
                            <td><?php echo $deployment['rollout_percentage']; ?>%</td>
                            <td>User <?php echo $deployment['deployed_by']; ?></td>
                            <td><?php echo $deployment['deployed_at'] ? date('Y-m-d H:i:s', strtotime($deployment['deployed_at'])) : 'Not deployed'; ?></td>
                            <td>
                                <?php if ($deployment['deployment_status'] === 'deployed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="deployment_id" value="<?php echo $deployment['id']; ?>">
                                        <button type="submit" name="rollback_deployment" class="btn btn-danger" onclick="return confirm('Rollback this deployment?');">Rollback</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

