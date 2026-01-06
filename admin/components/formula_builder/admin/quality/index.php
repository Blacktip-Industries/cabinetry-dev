<?php
/**
 * Formula Builder Component - Quality Checks
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/quality.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$report = null;
$runCheck = isset($_GET['run_check']);

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        header('Location: ../formulas/index.php?error=notfound');
        exit;
    }
    
    if ($runCheck) {
        $result = formula_builder_run_quality_check($formulaId);
        if ($result['success']) {
            $report = $result['report'];
        }
    } else {
        // Get latest report
        $conn = formula_builder_get_db_connection();
        if ($conn) {
            $tableName = formula_builder_get_table_name('quality_reports');
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY generated_at DESC LIMIT 1");
            $stmt->bind_param("i", $formulaId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                $report = [
                    'quality_score' => $row['quality_score'],
                    'complexity_score' => $row['complexity_score'],
                    'security_score' => $row['security_score'],
                    'performance_score' => $row['performance_score'],
                    'issues' => json_decode($row['issues'], true) ?: [],
                    'suggestions' => json_decode($row['suggestions'], true) ?: []
                ];
            }
        }
    }
} else {
    header('Location: ../formulas/index.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quality Check - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
        .score-card { display: inline-block; padding: 20px; margin: 10px; background: #f5f5f5; border-radius: 4px; text-align: center; min-width: 150px; }
        .score-value { font-size: 48px; font-weight: bold; }
        .score-label { font-size: 14px; color: #666; margin-top: 5px; }
        .score-excellent { color: #28a745; }
        .score-good { color: #17a2b8; }
        .score-fair { color: #ffc107; }
        .score-poor { color: #dc3545; }
        .issue { padding: 10px; margin: 10px 0; border-left: 4px solid #dc3545; background: #f8d7da; }
        .issue.high { border-left-color: #dc3545; }
        .issue.medium { border-left-color: #ffc107; }
        .issue.low { border-left-color: #17a2b8; }
        .suggestion { padding: 10px; margin: 10px 0; border-left: 4px solid #17a2b8; background: #d1ecf1; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <h1>Quality Check: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="../formulas/edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
    <a href="index.php?formula_id=<?php echo $formulaId; ?>&run_check=1" class="btn btn-success">Run Quality Check</a>
    
    <?php if ($report): ?>
        <div style="margin-top: 30px;">
            <h2>Quality Scores</h2>
            <div>
                <?php
                $qualityScore = (float)$report['quality_score'];
                $qualityClass = $qualityScore >= 80 ? 'score-excellent' : ($qualityScore >= 60 ? 'score-good' : ($qualityScore >= 40 ? 'score-fair' : 'score-poor'));
                ?>
                <div class="score-card">
                    <div class="score-value <?php echo $qualityClass; ?>"><?php echo round($qualityScore, 1); ?></div>
                    <div class="score-label">Overall Quality</div>
                </div>
                <div class="score-card">
                    <div class="score-value score-poor"><?php echo round($report['complexity_score'], 1); ?></div>
                    <div class="score-label">Complexity</div>
                </div>
                <div class="score-card">
                    <div class="score-value <?php echo (float)$report['security_score'] >= 80 ? 'score-excellent' : ((float)$report['security_score'] >= 60 ? 'score-good' : 'score-poor'); ?>">
                        <?php echo round($report['security_score'], 1); ?>
                    </div>
                    <div class="score-label">Security</div>
                </div>
                <div class="score-card">
                    <div class="score-value <?php echo (float)$report['performance_score'] >= 80 ? 'score-excellent' : ((float)$report['performance_score'] >= 60 ? 'score-good' : 'score-poor'); ?>">
                        <?php echo round($report['performance_score'], 1); ?>
                    </div>
                    <div class="score-label">Performance</div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($report['issues'])): ?>
            <div style="margin-top: 30px;">
                <h2>Issues</h2>
                <?php foreach ($report['issues'] as $issue): ?>
                    <div class="issue <?php echo $issue['severity']; ?>">
                        <strong><?php echo strtoupper($issue['severity']); ?> - <?php echo htmlspecialchars($issue['type']); ?>:</strong>
                        <p><?php echo htmlspecialchars($issue['message']); ?></p>
                        <?php if (isset($issue['recommendation'])): ?>
                            <p><em>Recommendation: <?php echo htmlspecialchars($issue['recommendation']); ?></em></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($report['suggestions'])): ?>
            <div style="margin-top: 30px;">
                <h2>Suggestions</h2>
                <?php foreach ($report['suggestions'] as $suggestion): ?>
                    <div class="suggestion">
                        <strong><?php echo htmlspecialchars($suggestion['type']); ?>:</strong>
                        <p><?php echo htmlspecialchars($suggestion['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 4px;">
            <p>No quality report available. Click "Run Quality Check" to generate one.</p>
        </div>
    <?php endif; ?>
</body>
</html>

