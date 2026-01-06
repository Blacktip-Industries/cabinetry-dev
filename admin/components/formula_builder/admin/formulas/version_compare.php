<?php
/**
 * Formula Builder Component - Compare Versions
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/versions.php';
require_once __DIR__ . '/../core/diff.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$version1Num = (int)($_GET['version1'] ?? 0);
$version2Num = (int)($_GET['version2'] ?? 0);
$formula = null;
$version1 = null;
$version2 = null;
$diff = null;
$diffResult = null;

if ($formulaId && $version1Num && $version2Num) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: index.php?error=notfound');
        exit;
    }
    
    // Get versions
    $allVersions = formula_builder_get_versions($formulaId);
    foreach ($allVersions as $v) {
        if ($v['version_number'] == $version1Num) {
            $version1 = $v;
        }
        if ($v['version_number'] == $version2Num) {
            $version2 = $v;
        }
    }
    
    if ($version1 && $version2) {
        // Determine which is older/newer for proper diff display
        if ($version1['version_number'] < $version2['version_number']) {
            $diffResult = formula_builder_compare_versions($version1['formula_code'], $version2['formula_code']);
        } else {
            $diffResult = formula_builder_compare_versions($version2['formula_code'], $version1['formula_code']);
        }
    }
} else {
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Compare Versions - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1600px; margin: 20px auto; padding: 20px; }
        .header { margin-bottom: 20px; }
        .comparison-info { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .diff-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .diff-side { border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
        .diff-header { background: #007bff; color: white; padding: 10px; font-weight: bold; }
        .diff-content { max-height: 600px; overflow-y: auto; }
        .diff-line { padding: 5px 10px; border-bottom: 1px solid #eee; font-family: monospace; font-size: 12px; white-space: pre-wrap; }
        .diff-line.added { background: #d4edda; }
        .diff-line.deleted { background: #f8d7da; }
        .diff-line.modified { background: #fff3cd; }
        .diff-line.unchanged { background: white; }
        .line-number { display: inline-block; width: 40px; color: #666; text-align: right; margin-right: 10px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { padding: 10px; background: #f5f5f5; border-radius: 4px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Compare Versions</h1>
        <div>
            <?php if ($version2 && $version2['version_number'] != $formula['version']): ?>
                <a href="version_rollback.php?formula_id=<?php echo $formulaId; ?>&version_id=<?php echo $version2['id']; ?>" class="btn btn-success">Rollback to v<?php echo $version2['version_number']; ?></a>
            <?php endif; ?>
            <a href="versions.php?formula_id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to History</a>
        </div>
    </div>
    
    <div class="comparison-info">
        <p><strong>Formula:</strong> <?php echo htmlspecialchars($formula['formula_name']); ?></p>
        <p><strong>Comparing:</strong> v<?php echo $version1Num; ?> vs v<?php echo $version2Num; ?></p>
        <?php if ($diffResult): ?>
            <?php
            $added = count(array_filter($diffResult, function($d) { return $d['type'] === 'added'; }));
            $deleted = count(array_filter($diffResult, function($d) { return $d['type'] === 'deleted'; }));
            $modified = count(array_filter($diffResult, function($d) { return $d['type'] === 'modified'; }));
            ?>
            <div class="stats">
                <div class="stat"><strong>Added:</strong> <?php echo $added; ?> lines</div>
                <div class="stat"><strong>Deleted:</strong> <?php echo $deleted; ?> lines</div>
                <div class="stat"><strong>Modified:</strong> <?php echo $modified; ?> lines</div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($diffResult): ?>
        <div class="diff-container">
            <div class="diff-side">
                <div class="diff-header">Version <?php echo $version1Num; ?> (<?php echo date('Y-m-d', strtotime($version1['created_at'])); ?>)</div>
                <div class="diff-content">
                    <?php foreach ($diffResult as $line): ?>
                        <div class="diff-line <?php echo $line['type']; ?>">
                            <span class="line-number"><?php echo $line['old_line'] ?? ''; ?></span>
                            <?php echo htmlspecialchars($line['old_content'] ?? ''); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="diff-side">
                <div class="diff-header">Version <?php echo $version2Num; ?> (<?php echo date('Y-m-d', strtotime($version2['created_at'])); ?>)</div>
                <div class="diff-content">
                    <?php foreach ($diffResult as $line): ?>
                        <div class="diff-line <?php echo $line['type']; ?>">
                            <span class="line-number"><?php echo $line['new_line'] ?? ''; ?></span>
                            <?php echo htmlspecialchars($line['new_content'] ?? ''); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p>Error loading versions for comparison.</p>
    <?php endif; ?>
</body>
</html>

