<?php
/**
 * Access Component - Email System
 * Handles email template management and sending
 */

require_once __DIR__ . '/database.php';

/**
 * Get email template by key
 * @param string $templateKey Template key
 * @return array|null Template data or null
 */
function access_get_email_template($templateKey) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_email_templates WHERE template_key = ? AND is_active = 1");
        $stmt->bind_param("s", $templateKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        return $template;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting email template: " . $e->getMessage());
        return null;
    }
}

/**
 * Send email using template
 * @param string $templateKey Template key
 * @param string $toEmail Recipient email
 * @param array $variables Template variables
 * @return bool Success
 */
function access_send_email($templateKey, $toEmail, $variables = []) {
    $template = access_get_email_template($templateKey);
    if (!$template) {
        error_log("Access: Email template not found: {$templateKey}");
        return false;
    }
    
    // Replace variables in subject and body
    $subject = access_replace_template_variables($template['subject'], $variables);
    $bodyHtml = access_replace_template_variables($template['body_html'], $variables);
    $bodyText = access_replace_template_variables($template['body_text'] ?? '', $variables);
    
    // Get email settings
    $fromEmail = access_get_parameter('Email', 'from_email', 'noreply@example.com');
    $fromName = access_get_parameter('Email', 'from_name', 'System');
    
    // Send email
    $headers = [
        "From: {$fromName} <{$fromEmail}>",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8"
    ];
    
    return mail($toEmail, $subject, $bodyHtml, implode("\r\n", $headers));
}

/**
 * Replace template variables
 * @param string $content Template content
 * @param array $variables Variables array
 * @return string Content with variables replaced
 */
function access_replace_template_variables($content, $variables) {
    foreach ($variables as $key => $value) {
        $content = str_replace('{{' . $key . '}}', $value, $content);
        $content = str_replace('{' . $key . '}', $value, $content);
    }
    return $content;
}

/**
 * Create or update email template
 * @param array $templateData Template data
 * @return int|false Template ID on success, false on failure
 */
function access_save_email_template($templateData) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $variables = isset($templateData['variables']) ? (is_string($templateData['variables']) ? $templateData['variables'] : json_encode($templateData['variables'])) : null;
        $stmt = $conn->prepare("INSERT INTO access_email_templates (template_key, template_name, subject, body_html, body_text, variables, is_active) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE template_name = VALUES(template_name), subject = VALUES(subject), body_html = VALUES(body_html), body_text = VALUES(body_text), variables = VALUES(variables), is_active = VALUES(is_active)");
        $isActive = $templateData['is_active'] ?? 1;
        $stmt->bind_param("ssssssi",
            $templateData['template_key'],
            $templateData['template_name'],
            $templateData['subject'],
            $templateData['body_html'],
            $templateData['body_text'] ?? null,
            $variables,
            $isActive
        );
        
        if ($stmt->execute()) {
            $templateId = $conn->insert_id;
            $stmt->close();
            return $templateId;
        }
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error saving email template: " . $e->getMessage());
        return false;
    }
}

