<?php
/**
 * Formula Builder Component - Admin Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

// Check if installed
if (!formula_builder_is_installed()) {
    header('Location: ../install.php');
    exit;
}

$conn = formula_builder_get_db_connection();

// Get statistics
$totalFormulas = 0;
$activeFormulas = 0;
$totalExecutions = 0;

if ($conn) {
    try {
        $tableName = formula_builder_get_table_name('product_formulas');
        $result = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM {$tableName}");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalFormulas = $row['total'] ?? 0;
            $activeFormulas = $row['active'] ?? 0;
        }
        
        $logTable = formula_builder_get_table_name('execution_log');
        $result = $conn->query("SELECT COUNT(*) as total FROM {$logTable}");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalExecutions = $row['total'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting statistics: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Formula Builder - Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .card { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        .card h3 { margin-top: 0; }
        .card .number { font-size: 2em; font-weight: bold; color: #007bff; }
        .actions { margin-top: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Formula Builder Dashboard</h1>
    
    <div class="dashboard">
        <div class="card">
            <h3>Total Formulas</h3>
            <div class="number"><?php echo $totalFormulas; ?></div>
        </div>
        <div class="card">
            <h3>Active Formulas</h3>
            <div class="number"><?php echo $activeFormulas; ?></div>
        </div>
        <div class="card">
            <h3>Total Executions</h3>
            <div class="number"><?php echo $totalExecutions; ?></div>
        </div>
    </div>
    
    <div class="actions">
        <a href="formulas/index.php" class="btn">Manage Formulas</a>
        <a href="formulas/create.php" class="btn">Create Formula</a>
        <a href="library/index.php" class="btn">Template Library</a>
        <a href="library/marketplace.php" class="btn">Marketplace</a>
        <a href="api/index.php" class="btn">API Keys</a>
        <a href="webhooks/index.php" class="btn">Webhooks</a>
        <a href="notifications/index.php" class="btn">Notifications</a>
        <a href="monitoring/index.php" class="btn">Monitoring & Alerts</a>
        <a href="i18n/index.php" class="btn">Internationalization</a>
        <a href="../install.php" class="btn">Settings</a>
    </div>
</body>
</html>

