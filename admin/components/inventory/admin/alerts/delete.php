<?php
/**
 * Inventory Component - Delete Alert Rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/alerts.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$alertId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($alertId > 0 && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $result = inventory_delete_alert($alertId);
    if ($result['success']) {
        header('Location: index.php?deleted=1');
        exit;
    }
}

header('Location: index.php');
exit;

