<?php
/**
 * Email Marketing Component - Create Milestone
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = email_marketing_get_db_connection();
    if ($conn) {
        $milestoneData = [
            'milestone_name' => $_POST['milestone_name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'target_spend_amount' => $_POST['target_spend_amount'] ?? 0,
            'bonus_points_amount' => (int)($_POST['bonus_points_amount'] ?? 0),
            'points_expiry_days' => !empty($_POST['points_expiry_days']) ? (int)$_POST['points_expiry_days'] : null,
            'can_repeat' => isset($_POST['can_repeat']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $sql = "INSERT INTO email_marketing_loyalty_milestones (milestone_name, description, target_spend_amount, bonus_points_amount, points_expiry_days, can_repeat, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiiii",
            $milestoneData['milestone_name'],
            $milestoneData['description'],
            $milestoneData['target_spend_amount'],
            $milestoneData['bonus_points_amount'],
            $milestoneData['points_expiry_days'],
            $milestoneData['can_repeat'],
            $milestoneData['is_active']
        );
        
        if ($stmt->execute()) {
            header('Location: milestones.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Milestone</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Create Milestone</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Milestone Name:</label><br>
                <input type="text" name="milestone_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Target Spend Amount:</label><br>
                <input type="number" name="target_spend_amount" step="0.01" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Bonus Points Amount:</label><br>
                <input type="number" name="bonus_points_amount" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Points Expiry Days (leave empty for never expires, 0 for default):</label><br>
                <input type="number" name="points_expiry_days" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>
                    <input type="checkbox" name="can_repeat" value="1"> Can Repeat (customer can hit this milestone multiple times)
                </label>
            </div>
            
            <button type="submit" class="email-marketing-button">Create Milestone</button>
        </form>
    </div>
</body>
</html>

