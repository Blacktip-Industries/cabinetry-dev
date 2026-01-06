<?php
/**
 * Email Marketing Component - Run Data Mining Source
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/data_mining.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$sourceId = $_GET['id'] ?? 0;
$result = email_marketing_run_data_mining_source($sourceId);

if ($result['success']) {
    echo "Data mining completed. Found " . $result['leads_found'] . " leads.";
} else {
    echo "Error: " . ($result['error'] ?? 'Unknown error');
}

