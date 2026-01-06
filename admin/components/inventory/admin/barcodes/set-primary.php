<?php
/**
 * Inventory Component - Set Primary Barcode
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/barcodes.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$barcodeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($barcodeId > 0) {
    $result = inventory_set_primary_barcode($barcodeId);
    if ($result['success']) {
        header('Location: index.php?primary_set=1');
        exit;
    }
}

header('Location: index.php');
exit;

