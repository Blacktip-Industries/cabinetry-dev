<?php
/**
 * Payment Processing Component - Notification Templates
 * Handles notification template management
 */

require_once __DIR__ . '/database.php';

/**
 * Get notification template
 * @param string $event Notification event
 * @param string $type Template type (email, sms)
 * @return array|null Template data or null
 */
function payment_processing_get_notification_template($event, $type = 'email') {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('notification_templates');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE notification_event = ? AND template_type = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("ss", $event, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template && $template['variables']) {
            $template['variables'] = json_decode($template['variables'], true);
        }
        
        return $template;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting notification template: " . $e->getMessage());
        return null;
    }
}

/**
 * Render notification template with variables
 * @param array $template Template data
 * @param array $variables Variables to substitute
 * @return array Rendered template
 */
function payment_processing_render_notification_template($template, $variables) {
    $subject = $template['subject'] ?? '';
    $bodyText = $template['body_text'] ?? '';
    $bodyHtml = $template['body_html'] ?? '';
    
    // Replace variables
    foreach ($variables as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        $subject = str_replace($placeholder, $value, $subject);
        $bodyText = str_replace($placeholder, $value, $bodyText);
        $bodyHtml = str_replace($placeholder, htmlspecialchars($value), $bodyHtml);
    }
    
    return [
        'subject' => $subject,
        'body_text' => $bodyText,
        'body_html' => $bodyHtml
    ];
}

/**
 * Send notification
 * @param string $event Notification event
 * @param string $recipient Recipient email/phone
 * @param array $variables Template variables
 * @param string $type Notification type
 * @return array Result
 */
function payment_processing_send_notification($event, $recipient, $variables, $type = 'email') {
    $template = payment_processing_get_notification_template($event, $type);
    
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    $rendered = payment_processing_render_notification_template($template, $variables);
    
    switch ($type) {
        case 'email':
            return payment_processing_send_email_notification($recipient, $rendered['subject'], $rendered['body_html'], $rendered['body_text']);
        case 'sms':
            return payment_processing_send_sms_notification($recipient, $rendered['body_text']);
        default:
            return ['success' => false, 'error' => 'Unknown notification type'];
    }
}

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Text body
 * @return array Result
 */
function payment_processing_send_email_notification($to, $subject, $bodyHtml, $bodyText = null) {
    // Check if email_marketing component is available
    if (function_exists('email_marketing_send_email')) {
        return email_marketing_send_email($to, $subject, $bodyHtml, $bodyText);
    }
    
    // Fallback to PHP mail
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . (payment_processing_get_parameter('Notifications', 'from_email', 'noreply@example.com'))
    ];
    
    $success = mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
    
    return [
        'success' => $success,
        'method' => 'php_mail'
    ];
}

/**
 * Send SMS notification
 * @param string $to Recipient phone number
 * @param string $message Message text
 * @return array Result
 */
function payment_processing_send_sms_notification($to, $message) {
    // SMS provider integration would go here
    // For now, return placeholder
    $smsProvider = payment_processing_get_parameter('Notifications', 'sms_provider', '');
    $smsApiKey = payment_processing_get_parameter('Notifications', 'sms_api_key', '');
    
    if (empty($smsProvider) || empty($smsApiKey)) {
        return ['success' => false, 'error' => 'SMS provider not configured'];
    }
    
    // Integration with SMS providers (Twilio, AWS SNS, etc.) would be implemented here
    // This is a placeholder structure
    
    return [
        'success' => false,
        'error' => 'SMS integration not yet implemented',
        'provider' => $smsProvider
    ];
}

/**
 * Create notification template
 * @param array $templateData Template data
 * @return array Result with template ID
 */
function payment_processing_create_notification_template($templateData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('notification_templates');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (template_name, template_type, notification_event, subject, body_text, body_html, variables, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $variablesJson = json_encode($templateData['variables'] ?? []);
        $isActive = $templateData['is_active'] ?? 1;
        
        $stmt->bind_param("sssssssi",
            $templateData['template_name'],
            $templateData['template_type'],
            $templateData['notification_event'],
            $templateData['subject'] ?? null,
            $templateData['body_text'],
            $templateData['body_html'] ?? null,
            $variablesJson,
            $isActive
        );
        $stmt->execute();
        $templateId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'template_id' => $templateId];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

