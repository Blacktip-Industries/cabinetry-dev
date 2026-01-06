<?php
/**
 * Formula Builder Component - View Version
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/versions.php';

$versionId = (int)($_GET['id'] ?? 0);
$formulaId = (int)($_GET['formula_id'] ?? 0);
$version = null;
$formula = null;

if ($versionId) {
    $version = formula_builder_get_version($versionId);
    if (!$version) {
        header('Location: versions.php?formula_id=' . $formulaId . '&error=notfound');
        exit;
    }
    
    if ($formulaId) {
        $formula = formula_builder_get_formula_by_id($formulaId);
    }
} else {
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Version <?php echo $version['version_number']; ?> - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .header { margin-bottom: 20px; }
        .version-info { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .code-block { background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 20px 0; overflow-x: auto; }
        .code-block pre { margin: 0; font-family: monospace; white-space: pre-wrap; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        .tag { display: inline-block; background: #007bff; color: white; padding: 3px 10px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Version <?php echo $version['version_number']; ?></h1>
        <div>
            <?php if ($formula): ?>
                <a href="version_compare.php?formula_id=<?php echo $formulaId; ?>&version1=<?php echo $formula['version']; ?>&version2=<?php echo $version['version_number']; ?>" class="btn">Compare with Current</a>
                <?php if ($version['version_number'] != $formula['version']): ?>
                    <a href="version_rollback.php?formula_id=<?php echo $formulaId; ?>&version_id=<?php echo $versionId; ?>" class="btn btn-success">Rollback to This Version</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="versions.php?formula_id=<?php echo $version['formula_id']; ?>" class="btn btn-secondary">Back to History</a>
        </div>
    </div>
    
    <div class="version-info">
        <p><strong>Version Number:</strong> <?php echo $version['version_number']; ?></p>
        <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($version['created_at'])); ?></p>
        <p><strong>Created By:</strong> <?php echo $version['created_by'] ? 'User ' . $version['created_by'] : 'System'; ?></p>
        <?php if ($version['is_tagged'] && $version['tag_name']): ?>
            <p><strong>Tag:</strong> <span class="tag"><?php echo htmlspecialchars($version['tag_name']); ?></span></p>
        <?php endif; ?>
        <?php if ($version['changelog']): ?>
            <p><strong>Changelog:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($version['changelog'])); ?></p>
        <?php endif; ?>
    </div>
    
    <div>
        <h2>Formula Code</h2>
        <div class="code-block">
            <pre><?php echo htmlspecialchars($version['formula_code']); ?></pre>
        </div>
    </div>
    
    <?php if ($formula && count(formula_builder_get_versions($formulaId)) > 1): ?>
        <div style="margin-top: 30px;">
            <h2>Compare With</h2>
            <form method="GET" action="version_compare.php">
                <input type="hidden" name="formula_id" value="<?php echo $formulaId; ?>">
                <input type="hidden" name="version1" value="<?php echo $version['version_number']; ?>">
                <label>Compare with version: 
                    <select name="version2">
                        <?php 
                        $allVersions = formula_builder_get_versions($formulaId);
                        foreach ($allVersions as $v): 
                            if ($v['id'] != $versionId):
                        ?>
                            <option value="<?php echo $v['version_number']; ?>">v<?php echo $v['version_number']; ?></option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </label>
                <button type="submit" class="btn">Compare</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>

