<?php
/**
 * Formula Builder Component - Rollback Version
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/versions.php';
require_once __DIR__ . '/../core/diff.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$versionId = (int)($_GET['version_id'] ?? 0);
$formula = null;
$version = null;
$errors = [];
$success = false;

if ($formulaId && $versionId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    $version = formula_builder_get_version($versionId);
    
    if (!$formula || !$version) {
        header('Location: index.php?error=notfound');
        exit;
    }
    
    if ($version['formula_id'] != $formulaId) {
        header('Location: index.php?error=invalid');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

// Handle rollback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_rollback'])) {
    $changelog = trim($_POST['changelog'] ?? 'Rolled back to version ' . $version['version_number']);
    
    $result = formula_builder_rollback_to_version($formulaId, $versionId, $changelog);
    
    if ($result['success']) {
        $success = true;
        header('Location: edit.php?id=' . $formulaId . '&rolled_back=1');
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Rollback failed';
    }
}

// Get diff to show what will change
$diff = formula_builder_get_formula_version_diff($formulaId, $versionId);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Rollback to Version <?php echo $version['version_number']; ?> - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .diff-preview { background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 20px 0; max-height: 400px; overflow-y: auto; }
        .diff-preview pre { margin: 0; font-family: monospace; font-size: 12px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        textarea { width: 100%; padding: 8px; box-sizing: border-box; min-height: 100px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
    </style>
</head>
<body>
    <h1>Rollback to Version <?php echo $version['version_number']; ?></h1>
    
    <div class="warning">
        <strong>Warning:</strong> This will rollback the formula to version <?php echo $version['version_number']; ?>. 
        The current version will be saved as a new version before the rollback, so you can restore it later if needed.
    </div>
    
    <div class="info">
        <p><strong>Formula:</strong> <?php echo htmlspecialchars($formula['formula_name']); ?></p>
        <p><strong>Current Version:</strong> <?php echo $formula['version']; ?></p>
        <p><strong>Target Version:</strong> <?php echo $version['version_number']; ?> (created <?php echo date('Y-m-d H:i:s', strtotime($version['created_at'])); ?>)</p>
        <?php if ($version['changelog']): ?>
            <p><strong>Version Changelog:</strong> <?php echo htmlspecialchars($version['changelog']); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if ($diff && $diff['success']): ?>
        <div>
            <h2>Preview of Changes</h2>
            <div class="diff-preview">
                <pre><?php 
                foreach ($diff['diff'] as $line) {
                    $prefix = '';
                    switch ($line['type']) {
                        case 'added':
                            $prefix = '+ ';
                            break;
                        case 'deleted':
                            $prefix = '- ';
                            break;
                        case 'modified':
                            $prefix = '~ ';
                            break;
                        default:
                            $prefix = '  ';
                    }
                    echo htmlspecialchars($prefix . ($line['new_content'] ?? $line['old_content'] ?? '') . "\n");
                }
                ?></pre>
            </div>
            <p><strong>Summary:</strong> 
                <?php echo $diff['stats']['added']; ?> added, 
                <?php echo $diff['stats']['deleted']; ?> deleted, 
                <?php echo $diff['stats']['modified']; ?> modified
            </p>
        </div>
    <?php endif; ?>
    
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
            <label for="changelog">Rollback Changelog (optional)</label>
            <textarea id="changelog" name="changelog" placeholder="Describe why you're rolling back...">Rolled back to version <?php echo $version['version_number']; ?></textarea>
        </div>
        
        <button type="submit" name="confirm_rollback" class="btn btn-danger" onclick="return confirm('Are you sure you want to rollback to version <?php echo $version['version_number']; ?>? The current version will be saved first.');">
            Confirm Rollback
        </button>
        <a href="versions.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>

