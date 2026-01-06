<?php
/**
 * Layout Component - Accessibility Management
 * WCAG compliance checking and validation
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/accessibility.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Accessibility Management', true, 'layout_accessibility');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Accessibility Management</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$selectedTemplateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
$checkResult = null;
$recommendations = [];

if ($selectedTemplateId > 0) {
    $checkResult = layout_accessibility_check_template($selectedTemplateId);
    $recommendations = layout_accessibility_get_recommendations($selectedTemplateId);
}

$templates = layout_element_template_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Accessibility Management</h1>
    </div>

    <!-- Template Selection -->
    <div class="section">
        <h2>Select Template</h2>
        <form method="get" class="form-inline">
            <select name="template_id" class="form-control" onchange="this.form.submit()">
                <option value="0">-- Select template --</option>
                <?php foreach ($templates as $template): ?>
                <option value="<?php echo $template['id']; ?>" <?php echo $selectedTemplateId === $template['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($template['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($checkResult): ?>
    <!-- Compliance Results -->
    <div class="section">
        <h2>WCAG Compliance Check</h2>
        <div class="compliance-score">
            <div class="score-value" style="font-size: 3em; color: <?php 
                echo $checkResult['score'] >= 95 ? '#28a745' : ($checkResult['score'] >= 85 ? '#ffc107' : '#dc3545'); 
            ?>;">
                <?php echo $checkResult['score']; ?>%
            </div>
            <div class="score-level">
                Level: <strong><?php echo $checkResult['level']; ?></strong>
            </div>
        </div>
        
        <?php if (!empty($checkResult['issues'])): ?>
        <div class="alert alert-error">
            <h3>Issues (<?php echo count($checkResult['issues']); ?>)</h3>
            <ul>
                <?php foreach ($checkResult['issues'] as $issue): ?>
                <li><?php echo htmlspecialchars($issue['message']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($checkResult['warnings'])): ?>
        <div class="alert alert-warning">
            <h3>Warnings (<?php echo count($checkResult['warnings']); ?>)</h3>
            <ul>
                <?php foreach ($checkResult['warnings'] as $warning): ?>
                <li><?php echo htmlspecialchars($warning['message']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recommendations -->
    <?php if (!empty($recommendations)): ?>
    <div class="section">
        <h2>Recommendations</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Issue</th>
                        <th>Fix Suggestion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recommendations as $rec): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?php echo $rec['priority'] === 'high' ? 'error' : 'warning'; ?>">
                                <?php echo htmlspecialchars($rec['priority']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($rec['message']); ?></td>
                        <td><code><?php echo htmlspecialchars($rec['fix']); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.compliance-score {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 1rem 0;
}
</style>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

