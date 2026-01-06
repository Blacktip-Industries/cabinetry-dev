<?php
/**
 * Layout Component - Component Integration Dashboard
 * Overview of component integration status
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/component_integration.php';
require_once __DIR__ . '/../../core/layout_database.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Component Integration Dashboard', true, 'layout_component_integration');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Component Integration Dashboard</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

// Get integration statistics
$installedComponents = layout_component_get_installed();
$allErrors = layout_get_integration_errors();
$allWarnings = layout_get_integration_warnings();
$layouts = layout_get_definitions([], 100, 0);

// Calculate statistics
$totalLayouts = count($layouts);
$layoutsWithIssues = 0;
$totalDependencies = 0;
$missingRequired = 0;
$missingOptional = 0;

foreach ($layouts as $layout) {
    $checkResult = layout_component_dependency_check_all($layout['id']);
    $totalDependencies += $checkResult['total_required'] + $checkResult['total_optional'];
    if (!empty($checkResult['missing_required']) || !empty($checkResult['missing_optional'])) {
        $layoutsWithIssues++;
    }
    $missingRequired += count($checkResult['missing_required']);
    $missingOptional += count($checkResult['missing_optional']);
}

$healthScore = $totalDependencies > 0 
    ? round((($totalDependencies - $missingRequired) / $totalDependencies) * 100) 
    : 100;

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Component Integration Dashboard</h1>
        <div class="layout__actions">
            <a href="index.php" class="btn btn-secondary">Manage Dependencies</a>
            <a href="templates.php" class="btn btn-secondary">Component Templates</a>
        </div>
    </div>

    <!-- Health Overview -->
    <div class="dashboard__section">
        <h2>Integration Health</h2>
        <div class="dashboard__stats">
            <div class="stat-card">
                <div class="stat-card__value" style="font-size: 2.5em; color: <?php echo $healthScore >= 90 ? '#28a745' : ($healthScore >= 70 ? '#ffc107' : '#dc3545'); ?>">
                    <?php echo $healthScore; ?>%
                </div>
                <div class="stat-card__label">Health Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value"><?php echo count($installedComponents); ?></div>
                <div class="stat-card__label">Installed Components</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value"><?php echo $totalLayouts; ?></div>
                <div class="stat-card__label">Total Layouts</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value"><?php echo $layoutsWithIssues; ?></div>
                <div class="stat-card__label">Layouts with Issues</div>
            </div>
        </div>
    </div>

    <!-- Issues Summary -->
    <?php if (!empty($allErrors) || !empty($allWarnings)): ?>
    <div class="dashboard__section">
        <h2>Issues Summary</h2>
        
        <?php if (!empty($allErrors)): ?>
        <div class="alert alert-error">
            <h3><?php echo count($allErrors); ?> Critical Issues</h3>
            <ul>
                <?php foreach (array_slice($allErrors, 0, 10) as $error): ?>
                <li>
                    <strong><?php echo htmlspecialchars($error['component']); ?></strong> - 
                    <?php echo htmlspecialchars($error['message']); ?>
                    <?php if (isset($error['layout_name'])): ?>
                        (Layout: <?php echo htmlspecialchars($error['layout_name']); ?>)
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($allErrors) > 10): ?>
                <p><em>... and <?php echo count($allErrors) - 10; ?> more</em></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($allWarnings)): ?>
        <div class="alert alert-warning">
            <h3><?php echo count($allWarnings); ?> Warnings</h3>
            <ul>
                <?php foreach (array_slice($allWarnings, 0, 10) as $warning): ?>
                <li>
                    <strong><?php echo htmlspecialchars($warning['component']); ?></strong> - 
                    <?php echo htmlspecialchars($warning['message']); ?>
                    <?php if (isset($warning['layout_name'])): ?>
                        (Layout: <?php echo htmlspecialchars($warning['layout_name']); ?>)
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($allWarnings) > 10): ?>
                <p><em>... and <?php echo count($allWarnings) - 10; ?> more</em></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Installed Components -->
    <div class="dashboard__section">
        <h2>Installed Components</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Version</th>
                        <th>Capabilities</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($installedComponents)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No components installed</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($installedComponents as $component): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($component['name']); ?></strong>
                            <?php if ($component['description']): ?>
                                <br><small><?php echo htmlspecialchars($component['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($component['version'] ?? 'Unknown'); ?></td>
                        <td>
                            <?php if (!empty($component['capabilities'])): ?>
                                <?php echo implode(', ', array_map('htmlspecialchars', $component['capabilities'])); ?>
                            <?php else: ?>
                                <em>None detected</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-success">Installed</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dependency Statistics -->
    <div class="dashboard__section">
        <h2>Dependency Statistics</h2>
        <div class="dashboard__stats">
            <div class="stat-card">
                <div class="stat-card__value"><?php echo $totalDependencies; ?></div>
                <div class="stat-card__label">Total Dependencies</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value" style="color: #28a745;"><?php echo $totalDependencies - $missingRequired - $missingOptional; ?></div>
                <div class="stat-card__label">Installed</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value" style="color: #dc3545;"><?php echo $missingRequired; ?></div>
                <div class="stat-card__label">Missing Required</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value" style="color: #ffc107;"><?php echo $missingOptional; ?></div>
                <div class="stat-card__label">Missing Optional</div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard__section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-card {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.stat-card__value {
    font-size: 2em;
    font-weight: bold;
    color: #333;
    margin-bottom: 0.5rem;
}

.stat-card__label {
    color: #666;
    font-size: 0.9em;
}

.alert ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.alert li {
    margin: 0.25rem 0;
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

