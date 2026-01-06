<?php
/**
 * Payment Processing Component - Default Parameters
 * Inserts default parameters during installation
 */

/**
 * Insert default parameters
 * @param mysqli $conn Database connection
 * @return array Result
 */
function payment_processing_insert_default_parameters($conn) {
    $defaultParams = [
        // General Settings
        ['section' => 'General', 'parameter_name' => 'default_currency', 'value' => 'USD', 'description' => 'Default currency code'],
        ['section' => 'General', 'parameter_name' => 'transaction_timeout_seconds', 'value' => '300', 'description' => 'Transaction timeout in seconds'],
        ['section' => 'General', 'parameter_name' => 'auto_capture_enabled', 'value' => 'yes', 'description' => 'Automatically capture payments after authorization'],
        
        // Security Settings
        ['section' => 'Security', 'parameter_name' => 'encryption_method', 'value' => 'AES-256-GCM', 'description' => 'Encryption method for sensitive data'],
        ['section' => 'Security', 'parameter_name' => 'audit_log_retention_days', 'value' => '365', 'description' => 'Number of days to retain audit logs'],
        ['section' => 'Security', 'parameter_name' => 'fraud_detection_enabled', 'value' => 'yes', 'description' => 'Enable fraud detection'],
        ['section' => 'Security', 'parameter_name' => 'fraud_detection_sensitivity', 'value' => 'medium', 'description' => 'Fraud detection sensitivity (low, medium, high)'],
        ['section' => 'Security', 'parameter_name' => 'require_3d_secure', 'value' => 'no', 'description' => 'Require 3D Secure for all transactions'],
        ['section' => 'Security', 'parameter_name' => 'rate_limit_per_minute', 'value' => '60', 'description' => 'Rate limit for payment requests per minute'],
        
        // Webhook Settings
        ['section' => 'Webhooks', 'parameter_name' => 'webhook_retry_attempts', 'value' => '3', 'description' => 'Number of webhook retry attempts'],
        ['section' => 'Webhooks', 'parameter_name' => 'webhook_retry_delay_seconds', 'value' => '60', 'description' => 'Delay between webhook retry attempts in seconds'],
        ['section' => 'Webhooks', 'parameter_name' => 'webhook_timeout_seconds', 'value' => '30', 'description' => 'Webhook request timeout in seconds'],
        
        // Email Notifications
        ['section' => 'Notifications', 'parameter_name' => 'send_payment_receipt', 'value' => 'yes', 'description' => 'Send payment receipt emails'],
        ['section' => 'Notifications', 'parameter_name' => 'send_failure_notification', 'value' => 'yes', 'description' => 'Send payment failure notifications'],
        ['section' => 'Notifications', 'parameter_name' => 'send_refund_notification', 'value' => 'yes', 'description' => 'Send refund confirmation emails'],
        
        // Refund Settings
        ['section' => 'Refunds', 'parameter_name' => 'refund_approval_required', 'value' => 'no', 'description' => 'Require approval for refunds'],
        ['section' => 'Refunds', 'parameter_name' => 'partial_refund_enabled', 'value' => 'yes', 'description' => 'Allow partial refunds'],
        ['section' => 'Refunds', 'parameter_name' => 'refund_reason_required', 'value' => 'yes', 'description' => 'Require reason for refunds'],
        
        // Subscription Settings
        ['section' => 'Subscriptions', 'parameter_name' => 'default_billing_cycle', 'value' => 'monthly', 'description' => 'Default subscription billing cycle'],
        ['section' => 'Subscriptions', 'parameter_name' => 'subscription_retry_attempts', 'value' => '3', 'description' => 'Number of retry attempts for failed subscription payments'],
        ['section' => 'Subscriptions', 'parameter_name' => 'subscription_retry_delay_days', 'value' => '3', 'description' => 'Days between subscription payment retry attempts'],
        
        // Data Retention
        ['section' => 'Data Retention', 'parameter_name' => 'transaction_retention_days', 'value' => '2555', 'description' => 'Number of days to retain transaction data (7 years)'],
        ['section' => 'Data Retention', 'parameter_name' => 'archived_data_retention_days', 'value' => '3650', 'description' => 'Number of days to retain archived data (10 years)'],
        ['section' => 'Data Retention', 'parameter_name' => 'auto_archive_enabled', 'value' => 'yes', 'description' => 'Automatically archive old transactions'],
        
        // Payment Method Rules
        ['section' => 'Payment Methods', 'parameter_name' => 'payment_method_rules_enabled', 'value' => 'yes', 'description' => 'Enable payment method rules'],
        
        // Payment Plans
        ['section' => 'Payment Plans', 'parameter_name' => 'payment_plans_enabled', 'value' => 'yes', 'description' => 'Enable payment plans/installments'],
        ['section' => 'Payment Plans', 'parameter_name' => 'auto_process_installments', 'value' => 'yes', 'description' => 'Automatically process due installments'],
        ['section' => 'Payment Plans', 'parameter_name' => 'installment_reminder_days', 'value' => '3', 'description' => 'Days before due date to send reminder'],
        
        // Approval Workflows
        ['section' => 'Approvals', 'parameter_name' => 'approval_workflows_enabled', 'value' => 'yes', 'description' => 'Enable approval workflows'],
        ['section' => 'Approvals', 'parameter_name' => 'default_approval_threshold', 'value' => '1000', 'description' => 'Default amount threshold requiring approval'],
        
        // Automation
        ['section' => 'Automation', 'parameter_name' => 'automation_rules_enabled', 'value' => 'yes', 'description' => 'Enable automation rules'],
        
        // Tax Settings
        ['section' => 'Tax', 'parameter_name' => 'default_gst_rate', 'value' => '10', 'description' => 'Default GST rate (%)'],
        ['section' => 'Tax', 'parameter_name' => 'default_vat_rate', 'value' => '20', 'description' => 'Default VAT rate (%)'],
        ['section' => 'Tax', 'parameter_name' => 'tax_calculation_enabled', 'value' => 'no', 'description' => 'Enable automatic tax calculation'],
        
        // Notifications
        ['section' => 'Notifications', 'parameter_name' => 'admin_email', 'value' => '', 'description' => 'Admin email for alerts'],
        ['section' => 'Notifications', 'parameter_name' => 'admin_phone', 'value' => '', 'description' => 'Admin phone for SMS alerts'],
        ['section' => 'Notifications', 'parameter_name' => 'sms_provider', 'value' => '', 'description' => 'SMS provider (twilio, aws_sns, etc.)'],
        ['section' => 'Notifications', 'parameter_name' => 'sms_api_key', 'value' => '', 'description' => 'SMS API key (encrypted)'],
        ['section' => 'Notifications', 'parameter_name' => 'from_email', 'value' => '', 'description' => 'From email address for notifications'],
        
        // Outbound Webhooks
        ['section' => 'Outbound Webhooks', 'parameter_name' => 'outbound_webhooks_enabled', 'value' => 'yes', 'description' => 'Enable outbound webhooks'],
        
        // CSS Variables (for theming)
        ['section' => 'CSS', 'parameter_name' => '--payment-processing-button-primary-bg', 'value' => 'var(--color-primary, #007bff)', 'description' => 'Primary button background color'],
        ['section' => 'CSS', 'parameter_name' => '--payment-processing-button-primary-text', 'value' => 'var(--color-white, #ffffff)', 'description' => 'Primary button text color'],
        ['section' => 'CSS', 'parameter_name' => '--payment-processing-card-bg', 'value' => 'var(--bg-card, #ffffff)', 'description' => 'Card background color'],
        ['section' => 'CSS', 'parameter_name' => '--payment-processing-border-radius', 'value' => 'var(--border-radius-md, 8px)', 'description' => 'Border radius'],
    ];
    
    $inserted = 0;
    $errors = [];
    
    foreach ($defaultParams as $param) {
        try {
            $tableName = 'payment_processing_parameters';
            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description)");
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

