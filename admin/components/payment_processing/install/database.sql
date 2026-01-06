-- Payment Processing Component Database Schema
-- All tables prefixed with payment_processing_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS payment_processing_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS payment_processing_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(100) NOT NULL,
    parameter_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    value TEXT NOT NULL,
    min_range DECIMAL(10,2) NULL,
    max_range DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_parameter_name (parameter_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters configs table (stores UI configuration for parameters)
CREATE TABLE IF NOT EXISTS payment_processing_parameters_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parameter_id INT NOT NULL,
    input_type VARCHAR(50) NOT NULL,
    options_json TEXT NULL,
    placeholder VARCHAR(255) NULL,
    help_text TEXT NULL,
    validation_rules JSON NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parameter_id) REFERENCES payment_processing_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- GATEWAY TABLES
-- ============================================

-- Gateways table (stores gateway configurations)
CREATE TABLE IF NOT EXISTS payment_processing_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_key VARCHAR(100) UNIQUE NOT NULL,
    gateway_name VARCHAR(255) NOT NULL,
    gateway_type VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    is_test_mode TINYINT(1) DEFAULT 1,
    config_json TEXT NULL,
    supported_currencies JSON NULL,
    supported_payment_methods JSON NULL,
    webhook_url VARCHAR(500) NULL,
    webhook_secret VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gateway_key (gateway_key),
    INDEX idx_is_active (is_active),
    INDEX idx_gateway_type (gateway_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TRANSACTION TABLES
-- ============================================

-- Transactions table (main transaction records)
CREATE TABLE IF NOT EXISTS payment_processing_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    gateway_id INT NOT NULL,
    account_id INT NULL,
    order_id INT NULL,
    transaction_type ENUM('payment', 'refund', 'subscription', 'partial_payment') DEFAULT 'payment',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled', 'expired') DEFAULT 'pending',
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50) NOT NULL,
    gateway_transaction_id VARCHAR(255) NULL,
    gateway_response TEXT NULL,
    customer_email VARCHAR(255) NULL,
    customer_name VARCHAR(255) NULL,
    billing_address JSON NULL,
    shipping_address JSON NULL,
    metadata JSON NULL,
    fraud_score DECIMAL(5,2) NULL,
    fraud_status ENUM('clean', 'review', 'blocked') NULL,
    processed_at DATETIME NULL,
    completed_at DATETIME NULL,
    failed_at DATETIME NULL,
    failure_reason TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gateway_id) REFERENCES payment_processing_gateways(id) ON DELETE RESTRICT,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_gateway_id (gateway_id),
    INDEX idx_account_id (account_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_customer_email (customer_email),
    INDEX idx_gateway_transaction_id (gateway_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction items table (line items for transactions)
CREATE TABLE IF NOT EXISTS payment_processing_transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_description TEXT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES payment_processing_transactions(id) ON DELETE CASCADE,
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REFUND TABLES
-- ============================================

-- Refunds table (refund records)
CREATE TABLE IF NOT EXISTS payment_processing_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_id VARCHAR(100) UNIQUE NOT NULL,
    transaction_id INT NOT NULL,
    gateway_id INT NOT NULL,
    refund_type ENUM('full', 'partial') DEFAULT 'full',
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    reason TEXT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    gateway_refund_id VARCHAR(255) NULL,
    gateway_response TEXT NULL,
    processed_at DATETIME NULL,
    completed_at DATETIME NULL,
    failed_at DATETIME NULL,
    failure_reason TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES payment_processing_transactions(id) ON DELETE RESTRICT,
    FOREIGN KEY (gateway_id) REFERENCES payment_processing_gateways(id) ON DELETE RESTRICT,
    INDEX idx_refund_id (refund_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_gateway_id (gateway_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUBSCRIPTION TABLES
-- ============================================

-- Subscriptions table (subscription records)
CREATE TABLE IF NOT EXISTS payment_processing_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id VARCHAR(100) UNIQUE NOT NULL,
    gateway_id INT NOT NULL,
    account_id INT NULL,
    gateway_subscription_id VARCHAR(255) NULL,
    plan_name VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    billing_cycle ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    billing_interval INT DEFAULT 1,
    status ENUM('active', 'cancelled', 'expired', 'suspended', 'pending') DEFAULT 'pending',
    trial_period_days INT DEFAULT 0,
    trial_start_date DATETIME NULL,
    trial_end_date DATETIME NULL,
    current_period_start DATETIME NULL,
    current_period_end DATETIME NULL,
    next_billing_date DATETIME NULL,
    cancelled_at DATETIME NULL,
    cancellation_reason TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gateway_id) REFERENCES payment_processing_gateways(id) ON DELETE RESTRICT,
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_gateway_id (gateway_id),
    INDEX idx_account_id (account_id),
    INDEX idx_status (status),
    INDEX idx_next_billing_date (next_billing_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscription payments table (subscription payment history)
CREATE TABLE IF NOT EXISTS payment_processing_subscription_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    transaction_id INT NULL,
    payment_number INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    billing_period_start DATETIME NULL,
    billing_period_end DATETIME NULL,
    paid_at DATETIME NULL,
    failed_at DATETIME NULL,
    failure_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES payment_processing_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES payment_processing_transactions(id) ON DELETE SET NULL,
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status),
    INDEX idx_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- WEBHOOK TABLES
-- ============================================

-- Webhooks table (webhook event logs)
CREATE TABLE IF NOT EXISTS payment_processing_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_id VARCHAR(255) NULL,
    payload TEXT NOT NULL,
    signature VARCHAR(500) NULL,
    status ENUM('pending', 'processing', 'processed', 'failed', 'ignored') DEFAULT 'pending',
    processing_time_ms INT NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gateway_id) REFERENCES payment_processing_gateways(id) ON DELETE CASCADE,
    INDEX idx_gateway_id (gateway_id),
    INDEX idx_event_type (event_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT & SECURITY TABLES
-- ============================================

-- Audit log table (complete audit trail)
CREATE TABLE IF NOT EXISTS payment_processing_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    details JSON NULL,
    changes_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_type (action_type),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fraud rules table (fraud detection rules)
CREATE TABLE IF NOT EXISTS payment_processing_fraud_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type VARCHAR(100) NOT NULL,
    rule_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    action ENUM('allow', 'review', 'block') DEFAULT 'review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority),
    INDEX idx_rule_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fraud events table (fraud detection events)
CREATE TABLE IF NOT EXISTS payment_processing_fraud_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NULL,
    rule_id INT NULL,
    event_type VARCHAR(100) NOT NULL,
    risk_score DECIMAL(5,2) NOT NULL,
    details JSON NULL,
    action_taken VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES payment_processing_transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (rule_id) REFERENCES payment_processing_fraud_rules(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_rule_id (rule_id),
    INDEX idx_event_type (event_type),
    INDEX idx_risk_score (risk_score),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encrypted data table (encrypted sensitive data)
CREATE TABLE IF NOT EXISTS payment_processing_encrypted_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    data_key VARCHAR(255) NOT NULL,
    encrypted_value TEXT NOT NULL,
    encryption_method VARCHAR(50) DEFAULT 'AES-256-GCM',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_data_key (data_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ENHANCED FEATURES TABLES
-- ============================================

-- Payment method rules table (conditional payment method availability)
CREATE TABLE IF NOT EXISTS payment_processing_payment_method_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    conditions JSON NOT NULL,
    allowed_methods JSON NOT NULL,
    blocked_methods JSON NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment plans table (installment/payment plan templates)
CREATE TABLE IF NOT EXISTS payment_processing_payment_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    number_of_installments INT NOT NULL,
    installment_amount DECIMAL(15,2) NOT NULL,
    frequency ENUM('daily', 'weekly', 'biweekly', 'monthly', 'quarterly') DEFAULT 'monthly',
    first_payment_days INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Installments table (individual installment records)
CREATE TABLE IF NOT EXISTS payment_processing_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    payment_plan_id INT NULL,
    installment_number INT NOT NULL,
    total_installments INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    due_date DATE NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'overdue') DEFAULT 'pending',
    transaction_id_for_payment INT NULL,
    reminder_sent_at DATETIME NULL,
    paid_at DATETIME NULL,
    failed_at DATETIME NULL,
    failure_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES payment_processing_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_plan_id) REFERENCES payment_processing_payment_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (transaction_id_for_payment) REFERENCES payment_processing_transactions(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_plan_id (payment_plan_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval workflows table (approval workflow definitions)
CREATE TABLE IF NOT EXISTS payment_processing_approval_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    conditions JSON NOT NULL,
    approval_levels JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approvals table (approval records)
CREATE TABLE IF NOT EXISTS payment_processing_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NULL,
    refund_id INT NULL,
    workflow_id INT NOT NULL,
    approval_level INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approver_id INT NULL,
    approver_name VARCHAR(255) NULL,
    comments TEXT NULL,
    approved_at DATETIME NULL,
    rejected_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES payment_processing_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (refund_id) REFERENCES payment_processing_refunds(id) ON DELETE CASCADE,
    FOREIGN KEY (workflow_id) REFERENCES payment_processing_approval_workflows(id) ON DELETE RESTRICT,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_refund_id (refund_id),
    INDEX idx_workflow_id (workflow_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automation rules table (automation rule definitions)
CREATE TABLE IF NOT EXISTS payment_processing_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    conditions JSON NOT NULL,
    actions JSON NOT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority),
    INDEX idx_trigger_event (trigger_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom statuses table (custom status definitions)
CREATE TABLE IF NOT EXISTS payment_processing_custom_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_key VARCHAR(100) UNIQUE NOT NULL,
    status_name VARCHAR(255) NOT NULL,
    status_category ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'custom') DEFAULT 'custom',
    description TEXT NULL,
    color_hex VARCHAR(7) NULL,
    icon_name VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_status_category (status_category),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status transitions table (status transition rules)
CREATE TABLE IF NOT EXISTS payment_processing_status_transitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_status VARCHAR(100) NOT NULL,
    to_status VARCHAR(100) NOT NULL,
    conditions JSON NULL,
    actions JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_from_status (from_status),
    INDEX idx_to_status (to_status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports table (saved report definitions)
CREATE TABLE IF NOT EXISTS payment_processing_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    filters JSON NOT NULL,
    grouping JSON NULL,
    columns JSON NOT NULL,
    created_by INT NULL,
    is_public TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_created_by (created_by),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bank reconciliation table (bank statement imports)
CREATE TABLE IF NOT EXISTS payment_processing_bank_reconciliation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    statement_date DATE NOT NULL,
    bank_name VARCHAR(255) NULL,
    account_number VARCHAR(100) NULL,
    opening_balance DECIMAL(15,2) NOT NULL,
    closing_balance DECIMAL(15,2) NOT NULL,
    statement_data JSON NOT NULL,
    matched_transactions JSON NULL,
    discrepancies JSON NULL,
    reconciled_by INT NULL,
    reconciled_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_statement_date (statement_date),
    INDEX idx_reconciled_by (reconciled_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Outbound webhooks table (outbound webhook configurations)
CREATE TABLE IF NOT EXISTS payment_processing_outbound_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500) NOT NULL,
    event_types JSON NOT NULL,
    secret_key VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    retry_attempts INT DEFAULT 3,
    timeout_seconds INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Outbound webhook logs table (outbound webhook delivery logs)
CREATE TABLE IF NOT EXISTS payment_processing_outbound_webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    payload TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'retrying') DEFAULT 'pending',
    http_code INT NULL,
    response TEXT NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES payment_processing_outbound_webhooks(id) ON DELETE CASCADE,
    INDEX idx_webhook_id (webhook_id),
    INDEX idx_status (status),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification templates table (email/SMS templates)
CREATE TABLE IF NOT EXISTS payment_processing_notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type ENUM('email', 'sms', 'push') DEFAULT 'email',
    notification_event VARCHAR(100) NOT NULL,
    subject VARCHAR(500) NULL,
    body_text TEXT NOT NULL,
    body_html TEXT NULL,
    variables JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_type (template_type),
    INDEX idx_notification_event (notification_event),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin alerts table (admin alert configurations)
CREATE TABLE IF NOT EXISTS payment_processing_admin_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_name VARCHAR(255) NOT NULL,
    alert_type VARCHAR(100) NOT NULL,
    conditions JSON NOT NULL,
    notification_channels JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_alert_type (alert_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin alert logs table (admin alert trigger logs)
CREATE TABLE IF NOT EXISTS payment_processing_admin_alert_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    alert_data JSON NOT NULL,
    notification_sent TINYINT(1) DEFAULT 0,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES payment_processing_admin_alerts(id) ON DELETE CASCADE,
    INDEX idx_alert_id (alert_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant accounts table (multi-merchant support - foundation)
CREATE TABLE IF NOT EXISTS payment_processing_merchant_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_name VARCHAR(255) NOT NULL,
    merchant_code VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_merchant_code (merchant_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

