<?php
/**
 * Email Marketing Component - Default Parameters
 * Inserts default parameters during installation
 */

/**
 * Insert default parameters
 * @param mysqli $conn Database connection
 * @return array Result
 */
function email_marketing_insert_default_parameters($conn) {
    $defaultParams = [
        // Email Configuration
        ['section' => 'Email', 'parameter_name' => 'email_method', 'value' => 'smtp', 'description' => 'Email sending method (smtp or service)'],
        ['section' => 'Email', 'parameter_name' => 'smtp_host', 'value' => '', 'description' => 'SMTP host'],
        ['section' => 'Email', 'parameter_name' => 'smtp_port', 'value' => '587', 'description' => 'SMTP port'],
        ['section' => 'Email', 'parameter_name' => 'smtp_encryption', 'value' => 'tls', 'description' => 'SMTP encryption (tls or ssl)'],
        ['section' => 'Email', 'parameter_name' => 'smtp_username', 'value' => '', 'description' => 'SMTP username'],
        ['section' => 'Email', 'parameter_name' => 'smtp_password', 'value' => '', 'description' => 'SMTP password'],
        ['section' => 'Email', 'parameter_name' => 'from_email', 'value' => '', 'description' => 'Default from email address'],
        ['section' => 'Email', 'parameter_name' => 'from_name', 'value' => 'Email Marketing', 'description' => 'Default from name'],
        ['section' => 'Email', 'parameter_name' => 'service_provider', 'value' => 'sendgrid', 'description' => 'Email service provider (sendgrid, mailgun, ses)'],
        ['section' => 'Email', 'parameter_name' => 'service_api_key', 'value' => '', 'description' => 'Email service API key'],
        
        // Queue Configuration
        ['section' => 'Queue', 'parameter_name' => 'queue_enabled', 'value' => 'yes', 'description' => 'Enable email queue system'],
        ['section' => 'Queue', 'parameter_name' => 'queue_batch_size', 'value' => '50', 'description' => 'Email queue batch size'],
        ['section' => 'Queue', 'parameter_name' => 'rate_limit_per_hour', 'value' => '1000', 'description' => 'Rate limit per hour'],
        
        // Loyalty Points
        ['section' => 'Loyalty', 'parameter_name' => 'default_points_expiry_days', 'value' => '365', 'description' => 'Default points expiry in days'],
        ['section' => 'Loyalty', 'parameter_name' => 'points_per_dollar', 'value' => '1', 'description' => 'Default points per dollar'],
        
        // Data Mining
        ['section' => 'Data Mining', 'parameter_name' => 'auto_run_enabled', 'value' => 'no', 'description' => 'Enable automatic data mining runs'],
        ['section' => 'Data Mining', 'parameter_name' => 'scraping_rate_limit', 'value' => '10', 'description' => 'Scraping rate limit (requests per minute)']
    ];
    
    $inserted = 0;
    $errors = [];
    
    foreach ($defaultParams as $param) {
        try {
            $stmt = $conn->prepare("INSERT INTO email_marketing_parameters (section, parameter_name, value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description)");
            $stmt->bind_param("ssss", $param['section'], $param['parameter_name'], $param['value'], $param['description']);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

