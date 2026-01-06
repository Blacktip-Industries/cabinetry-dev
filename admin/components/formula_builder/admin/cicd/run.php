<?php
/**
 * Formula Builder Component - CI/CD Pipeline Run
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/cicd.php';

$pipelineId = (int)($_GET['pipeline_id'] ?? 0);
$runResult = null;

if ($pipelineId) {
    $runResult = formula_builder_run_pipeline($pipelineId);
} else {
    header('Location: index.php');
    exit;
}

$status = formula_builder_get_pipeline_status($pipelineId);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Pipeline Run - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn-secondary { background: #6c757d; }
        .stage-result { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .stage-result.success { background: #d4edda; border-left: 4px solid #28a745; }
        .stage-result.failed { background: #f8d7da; border-left: 4px solid #dc3545; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Pipeline Run Results</h1>
    <a href="index.php?formula_id=<?php echo $status['pipeline']['formula_id']; ?>" class="btn btn-secondary">Back to Pipelines</a>
    
    <?php if ($runResult && $runResult['success']): ?>
        <div style="margin-top: 20px;">
            <h2>Run #<?php echo $runResult['run_number']; ?> - <?php echo strtoupper($runResult['status']); ?></h2>
            
            <?php foreach ($runResult['results'] as $stageName => $stageResult): ?>
                <div class="stage-result <?php echo $stageResult['success'] ? 'success' : 'failed'; ?>">
                    <h3><?php echo ucfirst($stageName); ?> Stage</h3>
                    <p><strong>Status:</strong> <?php echo $stageResult['success'] ? 'Passed ✓' : 'Failed ✗'; ?></p>
                    <pre><?php echo htmlspecialchars(json_encode($stageResult, JSON_PRETTY_PRINT)); ?></pre>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <p>Pipeline run failed: <?php echo $runResult['error'] ?? 'Unknown error'; ?></p>
        </div>
    <?php endif; ?>
</body>
</html>

