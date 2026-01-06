<?php
/**
 * Email Marketing Component - Data Mining
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$sources = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM email_marketing_lead_sources ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $sources[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Mining</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Data Mining</h1>
        <p><a href="configure.php" class="email-marketing-button">Configure New Source</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Source Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Last Run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sources as $source): ?>
                <tr>
                    <td><?php echo htmlspecialchars($source['source_name']); ?></td>
                    <td><?php echo htmlspecialchars($source['source_type']); ?></td>
                    <td><?php echo $source['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><?php echo $source['last_run_at'] ?? 'Never'; ?></td>
                    <td>
                        <a href="run.php?id=<?php echo $source['id']; ?>">Run</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

