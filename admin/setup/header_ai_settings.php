<?php
/**
 * AI Image Generation Settings
 * Configure API keys and settings for AI image generation
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('AI Image Generation Settings', true, 'setup_header_ai_settings');

$conn = getDBConnection();
$error = '';
$success = '';

// Ensure table exists
if ($conn) {
    createAIImageGenerationSettingsTable($conn);
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}

// Get indent parameters for labels and helper text
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

// Normalize indent values (add 'px' if numeric and no unit)
if (!empty($indentLabel)) {
    $indentLabel = trim($indentLabel);
    if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
        $indentLabel = $indentLabel . 'px';
    }
} else {
    $indentLabel = '0px';
}

if (!empty($indentHelperText)) {
    $indentHelperText = trim($indentHelperText);
    if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
        $indentHelperText = $indentHelperText . 'px';
    }
} else {
    $indentHelperText = '0px';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        $openaiKey = trim($_POST['openai_api_key'] ?? '');
        $stableDiffusionKey = trim($_POST['stable_diffusion_api_key'] ?? '');
        $defaultService = $_POST['default_service'] ?? 'dalle3';
        $maxVariations = (int)($_POST['max_variations'] ?? 1);
        $costLimit = isset($_POST['cost_limit']) ? (float)$_POST['cost_limit'] : null;
        
        if ($conn) {
            // Save OpenAI key
            $stmt = $conn->prepare("INSERT INTO ai_image_generation_settings (setting_key, setting_value) 
                VALUES ('openai_api_key', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $openaiKey, $openaiKey);
            $stmt->execute();
            $stmt->close();
            
            // Save Stable Diffusion key
            $stmt = $conn->prepare("INSERT INTO ai_image_generation_settings (setting_key, setting_value) 
                VALUES ('stable_diffusion_api_key', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $stableDiffusionKey, $stableDiffusionKey);
            $stmt->execute();
            $stmt->close();
            
            // Save default service
            $stmt = $conn->prepare("INSERT INTO ai_image_generation_settings (setting_key, setting_value) 
                VALUES ('default_service', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $defaultService, $defaultService);
            $stmt->execute();
            $stmt->close();
            
            // Save max variations
            $stmt = $conn->prepare("INSERT INTO ai_image_generation_settings (setting_key, setting_value) 
                VALUES ('max_variations', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?");
            $maxVarStr = (string)$maxVariations;
            $stmt->bind_param("ss", $maxVarStr, $maxVarStr);
            $stmt->execute();
            $stmt->close();
            
            // Save cost limit
            if ($costLimit !== null) {
                $stmt = $conn->prepare("INSERT INTO ai_image_generation_settings (setting_key, setting_value) 
                    VALUES ('cost_limit', ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?");
                $costLimitStr = (string)$costLimit;
                $stmt->bind_param("ss", $costLimitStr, $costLimitStr);
                $stmt->execute();
                $stmt->close();
            }
            
            $success = 'Settings saved successfully!';
        }
    }
}

// Get current settings
$settings = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM ai_image_generation_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
}

// Get usage statistics
$usageStats = [
    'total_generations' => 0,
    'total_cost' => 0,
    'this_month' => 0,
    'this_month_cost' => 0
];

if ($conn) {
    // Ensure table exists before querying
    createAIGenerationUsageTable($conn);
    
    // Check if table exists and has data
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_generation_usage'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Total generations
        $stmt = $conn->query("SELECT COUNT(*) as count, SUM(cost) as total FROM ai_generation_usage");
        if ($stmt && $row = $stmt->fetch_assoc()) {
            $usageStats['total_generations'] = $row['count'] ?? 0;
            $usageStats['total_cost'] = number_format($row['total'] ?? 0, 2);
        }
        if ($stmt) $stmt->close();
        
        // This month
        $stmt = $conn->query("SELECT COUNT(*) as count, SUM(cost) as total FROM ai_generation_usage 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        if ($stmt && $row = $stmt->fetch_assoc()) {
            $usageStats['this_month'] = $row['count'] ?? 0;
            $usageStats['this_month_cost'] = number_format($row['total'] ?? 0, 2);
        }
        if ($stmt) $stmt->close();
    }
    if ($tableCheck) $tableCheck->close();
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>AI Image Generation Settings</h2>
        <p class="text-muted">Configure API keys and settings for AI-powered header image generation</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3>Usage Statistics</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <strong>Total Generations</strong>
                <p style="font-size: 1.5rem; margin: 0.5rem 0;"><?php echo $usageStats['total_generations']; ?></p>
            </div>
            <div>
                <strong>Total Cost</strong>
                <p style="font-size: 1.5rem; margin: 0.5rem 0;">$<?php echo $usageStats['total_cost']; ?></p>
            </div>
            <div>
                <strong>This Month</strong>
                <p style="font-size: 1.5rem; margin: 0.5rem 0;"><?php echo $usageStats['this_month']; ?></p>
            </div>
            <div>
                <strong>This Month Cost</strong>
                <p style="font-size: 1.5rem; margin: 0.5rem 0;">$<?php echo $usageStats['this_month_cost']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>API Configuration</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            
            <div class="form-group">
                <label for="openai_api_key" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">OpenAI API Key (for DALL-E 3)</label>
                <input type="password" id="openai_api_key" name="openai_api_key" class="input" 
                    value="<?php echo htmlspecialchars($settings['openai_api_key'] ?? ''); ?>" 
                    placeholder="sk-...">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></small>
            </div>
            
            <div class="form-group">
                <label for="stable_diffusion_api_key" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Stable Diffusion API Key</label>
                <input type="password" id="stable_diffusion_api_key" name="stable_diffusion_api_key" class="input" 
                    value="<?php echo htmlspecialchars($settings['stable_diffusion_api_key'] ?? ''); ?>" 
                    placeholder="API key">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">API key for Stable Diffusion service (if using)</small>
            </div>
            
            <div class="form-group">
                <label for="default_service" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Default AI Service</label>
                <select id="default_service" name="default_service" class="input">
                    <option value="dalle3" <?php echo ($settings['default_service'] ?? 'dalle3') === 'dalle3' ? 'selected' : ''; ?>>DALL-E 3 (OpenAI)</option>
                    <option value="stable_diffusion" <?php echo ($settings['default_service'] ?? '') === 'stable_diffusion' ? 'selected' : ''; ?>>Stable Diffusion</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="max_variations" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Max Variations per Generation</label>
                <input type="number" id="max_variations" name="max_variations" class="input" 
                    min="1" max="4" value="<?php echo $settings['max_variations'] ?? 1; ?>">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Maximum number of image variations to generate per request</small>
            </div>
            
            <div class="form-group">
                <label for="cost_limit" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Monthly Cost Limit ($)</label>
                <input type="number" id="cost_limit" name="cost_limit" class="input" 
                    min="0" step="0.01" value="<?php echo $settings['cost_limit'] ?? ''; ?>" placeholder="0.00">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Set a monthly spending limit (0 = no limit)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-medium">Save Settings</button>
                <a href="header.php" class="btn btn-secondary btn-medium">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Pricing Information</h3>
    </div>
    <div class="card-body">
        <h4>DALL-E 3</h4>
        <ul>
            <li><strong>Standard Quality (1024x1024):</strong> $0.04 per image</li>
            <li><strong>HD Quality (1024x1024):</strong> $0.08 per image</li>
        </ul>
        <p style="margin-top: var(--spacing-lg);"><small>Pricing may vary. Check <a href="https://openai.com/pricing" target="_blank">OpenAI Pricing</a> for current rates.</small></p>
    </div>
</div>

<?php endLayout(); ?>

