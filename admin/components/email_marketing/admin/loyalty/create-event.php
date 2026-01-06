<?php
/**
 * Email Marketing Component - Create Event
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = email_marketing_get_db_connection();
    if ($conn) {
        $eventData = [
            'event_name' => $_POST['event_name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'event_type' => $_POST['event_type'] ?? 'birthday',
            'points_amount' => (int)($_POST['points_amount'] ?? 0),
            'points_expiry_days' => !empty($_POST['points_expiry_days']) ? (int)$_POST['points_expiry_days'] : null,
            'event_date_field' => $_POST['event_date_field'] ?? 'birthday',
            'days_before_event' => (int)($_POST['days_before_event'] ?? 0),
            'days_after_event' => (int)($_POST['days_after_event'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $sql = "INSERT INTO email_marketing_loyalty_events (event_name, description, event_type, points_amount, points_expiry_days, event_date_field, days_before_event, days_after_event, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiiisiii",
            $eventData['event_name'],
            $eventData['description'],
            $eventData['event_type'],
            $eventData['points_amount'],
            $eventData['points_expiry_days'],
            $eventData['event_date_field'],
            $eventData['days_before_event'],
            $eventData['days_after_event'],
            $eventData['is_active']
        );
        
        if ($stmt->execute()) {
            header('Location: events.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Create Event</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Event Name:</label><br>
                <input type="text" name="event_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Event Type:</label><br>
                <select name="event_type" style="width: 100%; padding: 8px;">
                    <option value="birthday">Birthday</option>
                    <option value="anniversary">Anniversary</option>
                    <option value="promotional">Promotional</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Points Amount:</label><br>
                <input type="number" name="points_amount" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Points Expiry Days (leave empty for never expires, 0 for default):</label><br>
                <input type="number" name="points_expiry_days" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Event Date Field:</label><br>
                <select name="event_date_field" style="width: 100%; padding: 8px;">
                    <option value="birthday">Birthday</option>
                    <option value="account_created_date">Account Created Date</option>
                </select>
            </div>
            
            <button type="submit" class="email-marketing-button">Create Event</button>
        </form>
    </div>
</body>
</html>

