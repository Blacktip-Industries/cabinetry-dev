<?php
/**
 * Order Management Component - Printing Functions
 * PDF and document generation
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Generate PDF document
 * @param string $templateType Template type (invoice, packing_slip, label, etc.)
 * @param int $orderId Order ID
 * @param array $options Generation options
 * @return array Result with file path
 */
function order_management_generate_pdf($templateType, $orderId, $options = []) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get order data
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    // Get template
    $template = order_management_get_print_template($templateType);
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    // Generate HTML content
    $html = order_management_render_print_template($template, $order, $options);
    
    // Generate PDF (would use library like TCPDF or DomPDF)
    $outputDir = order_management_get_parameter('print_output_directory', __DIR__ . '/../../uploads/prints/');
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $fileName = $templateType . '_' . $orderId . '_' . time() . '.pdf';
    $filePath = $outputDir . $fileName;
    
    // Placeholder for PDF generation
    // In production, would use: $pdf->Output($filePath, 'F');
    file_put_contents($filePath, $html); // Temporary: save HTML instead
    
    // Log generation
    $tableName = order_management_get_table_name('print_logs');
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, template_type, file_path, generated_by, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("issi", $orderId, $templateType, $filePath, $userId);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true, 'file_path' => $filePath, 'file_name' => $fileName];
}

/**
 * Get print template
 * @param string $templateType Template type
 * @return array|null Template data
 */
function order_management_get_print_template($templateType) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('print_templates');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE template_type = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $templateType);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        return $template;
    }
    
    return null;
}

/**
 * Render print template
 * @param array $template Template data
 * @param array $order Order data
 * @param array $options Options
 * @return string HTML content
 */
function order_management_render_print_template($template, $order, $options = []) {
    $html = $template['template_content'] ?? '';
    
    // Replace placeholders
    $replacements = [
        '{order_number}' => $order['order_number'] ?? $order['id'],
        '{order_date}' => date('Y-m-d', strtotime($order['created_at'])),
        '{total_amount}' => number_format($order['total_amount'] ?? 0, 2),
        '{customer_name}' => $order['customer_name'] ?? 'N/A',
        '{shipping_address}' => $order['shipping_address'] ?? 'N/A'
    ];
    
    foreach ($replacements as $placeholder => $value) {
        $html = str_replace($placeholder, $value, $html);
    }
    
    return $html;
}

