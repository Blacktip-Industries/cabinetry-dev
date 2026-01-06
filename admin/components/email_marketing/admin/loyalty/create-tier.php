<?php
/**
 * Email Marketing Component - Create Loyalty Tier
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

// Get account types if access component is available
$accountTypes = [];
if (function_exists('access_list_account_types')) {
    $accountTypes = access_list_account_types();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tierData = [
        'tier_name' => $_POST['tier_name'] ?? '',
        'tier_order' => (int)($_POST['tier_order'] ?? 0),
        'minimum_spend_amount' => $_POST['minimum_spend_amount'] ?? 0,
        'maximum_spend_amount' => !empty($_POST['maximum_spend_amount']) ? $_POST['maximum_spend_amount'] : null,
        'icon_name' => $_POST['icon_name'] ?? null,
        'icon_svg_path' => $_POST['icon_svg_path'] ?? null,
        'color_hex' => $_POST['color_hex'] ?? null,
        'badge_text' => $_POST['badge_text'] ?? null,
        'badge_style' => $_POST['badge_style'] ?? 'badge',
        'description' => $_POST['description'] ?? null,
        'benefits_json' => !empty($_POST['benefits']) ? explode("\n", $_POST['benefits']) : [],
        'applicable_account_type_ids' => !empty($_POST['account_type_ids']) ? $_POST['account_type_ids'] : [],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    $tierId = email_marketing_save_loyalty_tier($tierData);
    if ($tierId) {
        header('Location: tiers.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Loyalty Tier</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Create Loyalty Tier</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Tier Name (e.g., Bronze, Silver, Gold, Titanium):</label><br>
                <input type="text" name="tier_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Tier Order (lower = higher tier):</label><br>
                <input type="number" name="tier_order" value="0" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Minimum Spend Amount:</label><br>
                <input type="number" name="minimum_spend_amount" step="0.01" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Maximum Spend Amount (leave empty for unlimited):</label><br>
                <input type="number" name="maximum_spend_amount" step="0.01" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Color (Hex):</label><br>
                <input type="color" name="color_hex" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Badge Style:</label><br>
                <select name="badge_style" style="width: 100%; padding: 8px;">
                    <option value="ribbon">Ribbon</option>
                    <option value="badge">Badge</option>
                    <option value="label">Label</option>
                    <option value="icon_only">Icon Only</option>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Benefits (one per line):</label><br>
                <textarea name="benefits" rows="5" style="width: 100%; padding: 8px;"></textarea>
            </div>
            
            <button type="submit" class="email-marketing-button">Create Tier</button>
        </form>
    </div>
</body>
</html>

