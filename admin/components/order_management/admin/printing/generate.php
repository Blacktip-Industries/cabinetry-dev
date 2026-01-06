<?php
/**
 * Order Management Component - Generate PDF
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/printing.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? 0;
$templateType = $_GET['template_type'] ?? 'invoice';

if ($orderId <= 0) {
    header('Location: ' . order_management_get_component_admin_url() . '/printing/index.php');
    exit;
}

// Generate PDF
$result = order_management_generate_pdf($templateType, $orderId);

if ($result['success'] && file_exists($result['file_path'])) {
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $result['file_name'] . '"');
    readfile($result['file_path']);
    exit;
} else {
    // Error - redirect back
    header('Location: ' . order_management_get_component_admin_url() . '/printing/index.php?error=' . urlencode($result['error'] ?? 'Failed to generate PDF'));
    exit;
}

