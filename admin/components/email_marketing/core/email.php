<?php
/**
 * Email Marketing Component - Email Sending Functions
 * Handles email sending via SMTP or service providers
 */

require_once __DIR__ . '/database.php';

/**
 * Send email using configured method (SMTP or service provider)
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body (optional)
 * @param string $fromEmail From email (optional, uses config if not provided)
 * @param string $fromName From name (optional, uses config if not provided)
 * @return bool Success
 */
function email_marketing_send_email($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null) {
    $method = email_marketing_get_parameter('Email', 'email_method', 'smtp');
    
    if ($method === 'service') {
        return email_marketing_send_via_service($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName);
    } else {
        return email_marketing_send_via_smtp($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName);
    }
}

/**
 * Send email via SMTP
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body
 * @param string $fromEmail From email
 * @param string $fromName From name
 * @return bool Success
 */
function email_marketing_send_via_smtp($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null) {
    // Get SMTP settings
    $smtpHost = email_marketing_get_parameter('Email', 'smtp_host', '');
    $smtpPort = email_marketing_get_parameter('Email', 'smtp_port', '587');
    $smtpEncryption = email_marketing_get_parameter('Email', 'smtp_encryption', 'tls');
    $smtpUsername = email_marketing_get_parameter('Email', 'smtp_username', '');
    $smtpPassword = email_marketing_get_parameter('Email', 'smtp_password', '');
    
    $fromEmail = $fromEmail ?? email_marketing_get_parameter('Email', 'from_email', 'noreply@example.com');
    $fromName = $fromName ?? email_marketing_get_parameter('Email', 'from_name', 'Email Marketing');
    
    // Use PHP mail() if SMTP not configured
    if (empty($smtpHost)) {
        return email_marketing_send_via_php_mail($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName);
    }
    
    // Try to use PHPMailer if available, otherwise fall back to PHP mail()
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return email_marketing_send_via_phpmailer($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName, $smtpHost, $smtpPort, $smtpEncryption, $smtpUsername, $smtpPassword);
    }
    
    // Fallback to PHP mail()
    return email_marketing_send_via_php_mail($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName);
}

/**
 * Send email via service provider (SendGrid, Mailgun, AWS SES)
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body
 * @param string $fromEmail From email
 * @param string $fromName From name
 * @return bool Success
 */
function email_marketing_send_via_service($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null) {
    $provider = email_marketing_get_parameter('Email', 'service_provider', 'sendgrid');
    $apiKey = email_marketing_get_parameter('Email', 'service_api_key', '');
    
    $fromEmail = $fromEmail ?? email_marketing_get_parameter('Email', 'from_email', 'noreply@example.com');
    $fromName = $fromName ?? email_marketing_get_parameter('Email', 'from_name', 'Email Marketing');
    
    if (empty($apiKey)) {
        error_log("Email Marketing: Service provider API key not configured");
        return false;
    }
    
    switch ($provider) {
        case 'sendgrid':
            return email_marketing_send_via_sendgrid($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName, $apiKey);
        case 'mailgun':
            return email_marketing_send_via_mailgun($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName, $apiKey);
        case 'ses':
            return email_marketing_send_via_ses($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName, $apiKey);
        default:
            error_log("Email Marketing: Unknown service provider: " . $provider);
            return false;
    }
}

/**
 * Send email via PHP mail() function
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body
 * @param string $fromEmail From email
 * @param string $fromName From name
 * @return bool Success
 */
function email_marketing_send_via_php_mail($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null) {
    $fromEmail = $fromEmail ?? email_marketing_get_parameter('Email', 'from_email', 'noreply@example.com');
    $fromName = $fromName ?? email_marketing_get_parameter('Email', 'from_name', 'Email Marketing');
    
    $headers = [
        "From: {$fromName} <{$fromEmail}>",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8"
    ];
    
    return mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
}

/**
 * Send email via SendGrid API
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body
 * @param string $fromEmail From email
 * @param string $fromName From name
 * @param string $apiKey SendGrid API key
 * @return bool Success
 */
function email_marketing_send_via_sendgrid($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null, $apiKey = null) {
    $url = 'https://api.sendgrid.com/v3/mail/send';
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]]
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName
        ],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/html',
                'value' => $bodyHtml
            ]
        ]
    ];
    
    if ($bodyText) {
        $data['content'][] = [
            'type' => 'text/plain',
            'value' => $bodyText
        ];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Send email via Mailgun API
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body
 * @param string $fromEmail From email
 * @param string $fromName From name
 * @param string $apiKey Mailgun API key
 * @return bool Success
 */
function email_marketing_send_via_mailgun($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null, $apiKey = null) {
    $domain = email_marketing_get_parameter('Email', 'mailgun_domain', '');
    if (empty($domain)) {
        error_log("Email Marketing: Mailgun domain not configured");
        return false;
    }
    
    $url = "https://api.mailgun.net/v3/{$domain}/messages";
    
    $data = [
        'from' => "{$fromName} <{$fromEmail}>",
        'to' => $to,
        'subject' => $subject,
        'html' => $bodyHtml
    ];
    
    if ($bodyText) {
        $data['text'] = $bodyText;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, "api:{$apiKey}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Send email via AWS SES API
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $bodyHtml HTML body
 * @param string $bodyText Plain text body
 * @param string $fromEmail From email
 * @param string $fromName From name
 * @param string $apiKey AWS access key
 * @return bool Success
 */
function email_marketing_send_via_ses($to, $subject, $bodyHtml, $bodyText = null, $fromEmail = null, $fromName = null, $apiKey = null) {
    // AWS SES requires AWS SDK or custom implementation
    // For now, return false and log that SES requires AWS SDK
    error_log("Email Marketing: AWS SES requires AWS SDK - not implemented yet");
    return false;
}

/**
 * Replace template variables in content
 * @param string $content Template content
 * @param array $variables Variables array
 * @return string Content with variables replaced
 */
function email_marketing_replace_template_variables($content, $variables) {
    foreach ($variables as $key => $value) {
        $content = str_replace('{{' . $key . '}}', $value, $content);
        $content = str_replace('{' . $key . '}', $value, $content);
    }
    return $content;
}

/**
 * Send email using template
 * @param int $templateId Template ID
 * @param string $toEmail Recipient email
 * @param array $variables Template variables
 * @return bool Success
 */
function email_marketing_send_template_email($templateId, $toEmail, $variables = []) {
    $template = email_marketing_get_template($templateId);
    if (!$template) {
        error_log("Email Marketing: Template not found: {$templateId}");
        return false;
    }
    
    $subject = email_marketing_replace_template_variables($template['subject'], $variables);
    $bodyHtml = email_marketing_replace_template_variables($template['body_html'], $variables);
    $bodyText = !empty($template['body_text']) ? email_marketing_replace_template_variables($template['body_text'], $variables) : null;
    
    return email_marketing_send_email($toEmail, $subject, $bodyHtml, $bodyText);
}

