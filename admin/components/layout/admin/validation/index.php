<?php
/**
 * Layout Component - Validation Management
 * HTML/CSS/JS validation and security scanning
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/validation.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Validation Management', true, 'layout_validation');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Validation Management</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$selectedTemplateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
$validationResult = null;
$securityScan = null;

if ($selectedTemplateId > 0) {
    $validationResult = layout_validation_validate_template($selectedTemplateId);
}

$templates = layout_element_template_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Validation Management</h1>
    </div>

    <!-- Template Selection -->
    <div class="section">
        <h2>Select Template to Validate</h2>
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

    <?php if ($validationResult): ?>
    <!-- Validation Results -->
    <div class="section">
        <h2>Validation Results</h2>
        
        <!-- HTML Validation -->
        <div class="validation-section">
            <h3>HTML Validation</h3>
            <?php if ($validationResult['html']['valid']): ?>
                <div class="alert alert-success">✓ HTML is valid</div>
            <?php else: ?>
                <div class="alert alert-error">
                    <strong>HTML Errors:</strong>
                    <ul>
                        <?php foreach ($validationResult['html']['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($validationResult['html']['warnings'])): ?>
            <div class="alert alert-warning">
                <strong>Warnings:</strong>
                <ul>
                    <?php foreach ($validationResult['html']['warnings'] as $warning): ?>
                    <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- CSS Validation -->
        <div class="validation-section">
            <h3>CSS Validation</h3>
            <?php if ($validationResult['css']['valid']): ?>
                <div class="alert alert-success">✓ CSS is valid</div>
            <?php else: ?>
                <div class="alert alert-error">
                    <strong>CSS Errors:</strong>
                    <ul>
                        <?php foreach ($validationResult['css']['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($validationResult['css']['warnings'])): ?>
            <div class="alert alert-warning">
                <strong>Warnings:</strong>
                <ul>
                    <?php foreach ($validationResult['css']['warnings'] as $warning): ?>
                    <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- JavaScript Validation -->
        <div class="validation-section">
            <h3>JavaScript Validation</h3>
            <?php if ($validationResult['js']['valid']): ?>
                <div class="alert alert-success">✓ JavaScript is valid</div>
            <?php else: ?>
                <div class="alert alert-error">
                    <strong>JavaScript Errors:</strong>
                    <ul>
                        <?php foreach ($validationResult['js']['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($validationResult['js']['warnings'])): ?>
            <div class="alert alert-warning">
                <strong>Warnings:</strong>
                <ul>
                    <?php foreach ($validationResult['js']['warnings'] as $warning): ?>
                    <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Security Scan -->
        <div class="validation-section">
            <h3>Security Scan</h3>
            <?php if ($validationResult['security']['safe']): ?>
                <div class="alert alert-success">✓ No security issues detected</div>
            <?php else: ?>
                <div class="alert alert-<?php echo $validationResult['security']['risk_level'] === 'high' ? 'error' : 'warning'; ?>">
                    <strong>Risk Level: <?php echo strtoupper($validationResult['security']['risk_level']); ?></strong>
                    <ul>
                        <?php foreach ($validationResult['security']['issues'] as $issue): ?>
                        <li><?php echo htmlspecialchars($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Overall Status -->
        <div class="validation-section">
            <h3>Overall Status</h3>
            <?php if ($validationResult['valid']): ?>
                <div class="alert alert-success" style="font-size: 1.2em; padding: 1.5rem;">
                    <strong>✓ Template is valid and safe</strong>
                </div>
            <?php else: ?>
                <div class="alert alert-error" style="font-size: 1.2em; padding: 1.5rem;">
                    <strong>✗ Template has validation errors or security issues</strong>
                    <p>Please fix the issues listed above before using this template.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
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

.validation-section {
    margin: 1.5rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.validation-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
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

