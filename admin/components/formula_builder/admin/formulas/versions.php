<?php
/**
 * Formula Builder Component - Version History
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/versions.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$versions = [];
$taggedOnly = isset($_GET['tagged']) && $_GET['tagged'] == '1';

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: index.php?error=notfound');
        exit;
    }
    
    $filters = [];
    if ($taggedOnly) {
        $filters['tagged_only'] = true;
    }
    
    $versions = formula_builder_get_versions($formulaId, $filters);
} else {
    header('Location: index.php');
    exit;
}

// Handle tag/untag actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tag_version'])) {
        $versionId = (int)$_POST['version_id'];
        $tagName = trim($_POST['tag_name'] ?? '');
        if (!empty($tagName)) {
            formula_builder_tag_version($versionId, $tagName);
            header('Location: versions.php?formula_id=' . $formulaId);
            exit;
        }
    } elseif (isset($_POST['untag_version'])) {
        $versionId = (int)$_POST['version_id'];
        formula_builder_untag_version($versionId);
        header('Location: versions.php?formula_id=' . $formulaId);
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Version History - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .header { margin-bottom: 20px; }
        .filters { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .tag { display: inline-block; background: #007bff; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 2px; border: none; cursor: pointer; font-size: 12px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
        .changelog { color: #666; font-size: 12px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; }
        .current-version { background: #d4edda; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Version History: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
        <div>
            <a href="edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
            <a href="versions.php?formula_id=<?php echo $formulaId; ?>&tagged=<?php echo $taggedOnly ? '0' : '1'; ?>" class="btn">
                <?php echo $taggedOnly ? 'Show All' : 'Show Tagged Only'; ?>
            </a>
        </div>
    </div>
    
    <div class="filters">
        <p><strong>Current Version:</strong> <?php echo $formula['version']; ?></p>
        <p><strong>Total Versions:</strong> <?php echo count($versions); ?></p>
    </div>
    
    <?php if (empty($versions)): ?>
        <p>No versions found. Versions will be created automatically when you save formula changes.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Date</th>
                    <th>Author</th>
                    <th>Changelog</th>
                    <th>Tag</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $version): ?>
                    <tr class="<?php echo $version['version_number'] == $formula['version'] ? 'current-version' : ''; ?>">
                        <td>
                            <strong>v<?php echo $version['version_number']; ?></strong>
                            <?php if ($version['version_number'] == $formula['version']): ?>
                                <span style="color: green;">(Current)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($version['created_at'])); ?></td>
                        <td><?php echo $version['created_by'] ? 'User ' . $version['created_by'] : 'System'; ?></td>
                        <td class="changelog" title="<?php echo htmlspecialchars($version['changelog'] ?? ''); ?>">
                            <?php echo htmlspecialchars(substr($version['changelog'] ?? 'No changelog', 0, 50)); ?>
                            <?php echo strlen($version['changelog'] ?? '') > 50 ? '...' : ''; ?>
                        </td>
                        <td>
                            <?php if ($version['is_tagged'] && $version['tag_name']): ?>
                                <span class="tag"><?php echo htmlspecialchars($version['tag_name']); ?></span>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="version_id" value="<?php echo $version['id']; ?>">
                                    <input type="text" name="tag_name" placeholder="Tag name" style="width: 100px; padding: 2px;" required>
                                    <button type="submit" name="tag_version" class="btn" style="padding: 2px 5px;">Tag</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="version_view.php?id=<?php echo $version['id']; ?>&formula_id=<?php echo $formulaId; ?>" class="btn">View</a>
                            <a href="version_compare.php?formula_id=<?php echo $formulaId; ?>&version1=<?php echo $formula['version']; ?>&version2=<?php echo $version['version_number']; ?>" class="btn">Compare</a>
                            <?php if ($version['version_number'] != $formula['version']): ?>
                                <a href="version_rollback.php?formula_id=<?php echo $formulaId; ?>&version_id=<?php echo $version['id']; ?>" class="btn btn-success">Rollback</a>
                            <?php endif; ?>
                            <?php if ($version['is_tagged']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="version_id" value="<?php echo $version['id']; ?>">
                                    <button type="submit" name="untag_version" class="btn btn-danger" style="padding: 2px 5px;" onclick="return confirm('Remove tag?');">Untag</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>

