-- Email Marketing Component Database Schema
-- All tables prefixed with email_marketing_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS email_marketing_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS email_marketing_parameters (
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
CREATE TABLE IF NOT EXISTS email_marketing_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES email_marketing_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EMAIL CAMPAIGN TABLES
-- ============================================

-- Campaigns table
CREATE TABLE IF NOT EXISTS email_marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    campaign_type ENUM('welcome', 'promotional', 'trade_followup', 'abandoned_cart', 'order_confirmation', 're_engagement', 'custom') DEFAULT 'promotional',
    status ENUM('draft', 'scheduled', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    template_id INT NULL,
    subject VARCHAR(500) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    target_criteria JSON NULL,
    account_type_ids JSON NULL,
    schedule_type ENUM('one_time', 'recurring', 'immediate') DEFAULT 'one_time',
    schedule_settings JSON NULL,
    scheduled_send_at DATETIME NULL,
    sent_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    bounced_count INT DEFAULT 0,
    unsubscribed_count INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campaign_type (campaign_type),
    INDEX idx_status (status),
    INDEX idx_template_id (template_id),
    INDEX idx_scheduled_send_at (scheduled_send_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email templates table
CREATE TABLE IF NOT EXISTS email_marketing_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type ENUM('welcome', 'promotional', 'coupon', 'trade_followup', 'abandoned_cart', 'order_confirmation', 're_engagement', 'loyalty_notification', 'custom') DEFAULT 'custom',
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NULL,
    template_variables JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email queue table
CREATE TABLE IF NOT EXISTS email_marketing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    account_id INT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    status ENUM('pending', 'sending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    scheduled_send_at DATETIME NOT NULL,
    actual_send_at DATETIME NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_marketing_campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_account_id (account_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_send_at (scheduled_send_at),
    INDEX idx_recipient_email (recipient_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email tracking table
CREATE TABLE IF NOT EXISTS email_marketing_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    campaign_id INT NOT NULL,
    opened TINYINT(1) DEFAULT 0,
    opened_at DATETIME NULL,
    clicked TINYINT(1) DEFAULT 0,
    clicked_at DATETIME NULL,
    clicked_url VARCHAR(1000) NULL,
    bounced TINYINT(1) DEFAULT 0,
    bounce_type VARCHAR(50) NULL,
    bounce_message TEXT NULL,
    bounced_at DATETIME NULL,
    unsubscribed TINYINT(1) DEFAULT 0,
    unsubscribed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES email_marketing_queue(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES email_marketing_campaigns(id) ON DELETE CASCADE,
    INDEX idx_queue_id (queue_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_opened (opened),
    INDEX idx_clicked (clicked),
    INDEX idx_bounced (bounced)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LEAD GENERATION TABLES
-- ============================================

-- Lead sources table
CREATE TABLE IF NOT EXISTS email_marketing_lead_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(255) NOT NULL,
    source_type ENUM('api', 'scraping', 'manual') NOT NULL,
    search_criteria JSON NOT NULL,
    api_config JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    schedule_settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_source_type (source_type),
    INDEX idx_is_active (is_active),
    INDEX idx_next_run_at (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leads table
CREATE TABLE IF NOT EXISTS email_marketing_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_id INT NULL,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) DEFAULT 'Australia',
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    industry VARCHAR(255) NULL,
    sector VARCHAR(255) NULL,
    description TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'converted', 'archived') DEFAULT 'pending',
    quality_score INT DEFAULT 0,
    notes TEXT NULL,
    assigned_to INT NULL,
    converted_to_account_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES email_marketing_lead_sources(id) ON DELETE SET NULL,
    INDEX idx_source_id (source_id),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_converted_to_account_id (converted_to_account_id),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lead activities table
CREATE TABLE IF NOT EXISTS email_marketing_lead_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    activity_type ENUM('email_sent', 'email_opened', 'email_clicked', 'note_added', 'status_changed', 'assigned', 'converted') NOT NULL,
    activity_data JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES email_marketing_leads(id) ON DELETE CASCADE,
    INDEX idx_lead_id (lead_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COUPON/DISCOUNT TABLES
-- ============================================

-- Coupons table
CREATE TABLE IF NOT EXISTS email_marketing_coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_order_value DECIMAL(10,2) DEFAULT 0,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NULL,
    usage_limit_per_customer INT NULL,
    usage_limit_total INT NULL,
    usage_count INT DEFAULT 0,
    campaign_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_marketing_campaigns(id) ON DELETE SET NULL,
    INDEX idx_coupon_code (coupon_code),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_is_active (is_active),
    INDEX idx_valid_from (valid_from),
    INDEX idx_valid_to (valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupon usage table
CREATE TABLE IF NOT EXISTS email_marketing_coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    account_id INT NULL,
    email VARCHAR(255) NULL,
    order_id INT NULL,
    discount_amount_applied DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES email_marketing_coupons(id) ON DELETE CASCADE,
    INDEX idx_coupon_id (coupon_id),
    INDEX idx_account_id (account_id),
    INDEX idx_email (email),
    INDEX idx_order_id (order_id),
    INDEX idx_used_at (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOYALTY POINTS TABLES (ADVANCED SYSTEM)
-- ============================================

-- Loyalty rules table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    rule_type ENUM('standard', 'tiered', 'milestone', 'event') DEFAULT 'standard',
    points_per_dollar DECIMAL(10,4) NULL,
    points_percentage DECIMAL(5,2) NULL,
    minimum_order_value DECIMAL(10,2) DEFAULT 0,
    applicable_account_type_ids JSON NULL,
    calculation_basis ENUM('order_total', 'order_after_points_discount') DEFAULT 'order_after_points_discount',
    is_active TINYINT(1) DEFAULT 1,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active),
    INDEX idx_valid_from (valid_from),
    INDEX idx_valid_to (valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tiered rules table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_tiered_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    tier_name VARCHAR(255) NOT NULL,
    tier_order INT NOT NULL,
    spend_period_type ENUM('total_lifetime', 'rolling_days', 'calendar_period') NOT NULL,
    spend_period_value INT NULL,
    minimum_spend_amount DECIMAL(10,2) NOT NULL,
    maximum_spend_amount DECIMAL(10,2) NULL,
    points_percentage DECIMAL(5,2) NULL,
    points_per_dollar DECIMAL(10,4) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES email_marketing_loyalty_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_tier_order (tier_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Milestones table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    milestone_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    target_spend_amount DECIMAL(10,2) NOT NULL,
    bonus_points_amount INT NOT NULL,
    points_expiry_days INT NULL,
    applicable_account_type_ids JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    can_repeat TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_target_spend_amount (target_spend_amount),
    INDEX idx_is_active (is_active),
    INDEX idx_valid_from (valid_from),
    INDEX idx_valid_to (valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_type ENUM('birthday', 'anniversary', 'promotional', 'custom') NOT NULL,
    points_amount INT NOT NULL,
    points_expiry_days INT NULL,
    applicable_account_type_ids JSON NULL,
    event_date_field VARCHAR(100) NULL,
    days_before_event INT DEFAULT 0,
    days_after_event INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_is_active (is_active),
    INDEX idx_valid_from (valid_from),
    INDEX idx_valid_to (valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loyalty points balance table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    points_balance INT DEFAULT 0,
    points_earned_total INT DEFAULT 0,
    points_redeemed_total INT DEFAULT 0,
    points_expired_total INT DEFAULT 0,
    current_tier_id INT NULL,
    last_earned_at DATETIME NULL,
    last_redeemed_at DATETIME NULL,
    last_notification_sent_at DATETIME NULL,
    last_tier_check_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_account (account_id),
    INDEX idx_account_id (account_id),
    INDEX idx_current_tier_id (current_tier_id),
    INDEX idx_points_balance (points_balance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Point allocations table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_point_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    allocation_type ENUM('standard_order', 'tiered_order', 'milestone', 'event', 'manual_adjustment') NOT NULL,
    rule_id INT NULL,
    points_amount INT NOT NULL,
    expiry_date DATE NULL,
    order_id INT NULL,
    transaction_id INT NULL,
    is_expired TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES email_marketing_loyalty_points(account_id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_allocation_type (allocation_type),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_is_expired (is_expired),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loyalty transactions table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    transaction_type ENUM('earned', 'redeemed', 'expired', 'adjusted', 'milestone_bonus', 'event_reward') NOT NULL,
    points_amount INT NOT NULL,
    order_id INT NULL,
    allocation_id INT NULL,
    description TEXT NULL,
    expiry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES email_marketing_loyalty_points(account_id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loyalty notifications table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('balance_reminder', 'expiry_warning', 'milestone_achieved', 'event_reward', 'tier_upgrade') NOT NULL,
    trigger_condition JSON NOT NULL,
    email_template_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_template_id) REFERENCES email_marketing_templates(id) ON DELETE SET NULL,
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification log table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    notification_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    points_balance_at_send INT DEFAULT 0,
    expiring_points_amount INT NULL,
    expiry_date DATE NULL,
    email_sent TINYINT(1) DEFAULT 0,
    email_sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES email_marketing_loyalty_points(account_id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES email_marketing_loyalty_notifications(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_notification_id (notification_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_email_sent_at (email_sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOYALTY TIER/LABEL TABLES
-- ============================================

-- Loyalty tiers table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_name VARCHAR(255) NOT NULL,
    tier_order INT NOT NULL,
    minimum_spend_amount DECIMAL(10,2) NOT NULL,
    maximum_spend_amount DECIMAL(10,2) NULL,
    icon_name VARCHAR(100) NULL,
    icon_svg_path TEXT NULL,
    color_hex VARCHAR(7) NULL,
    badge_text VARCHAR(255) NULL,
    badge_style ENUM('ribbon', 'badge', 'label', 'icon_only') DEFAULT 'badge',
    description TEXT NULL,
    benefits_json JSON NULL,
    applicable_account_type_ids JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tier_order (tier_order),
    INDEX idx_minimum_spend_amount (minimum_spend_amount),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tier history table
CREATE TABLE IF NOT EXISTS email_marketing_loyalty_tier_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    tier_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_reason ENUM('automatic_spend', 'manual', 'promotion') DEFAULT 'automatic_spend',
    assigned_by INT NULL,
    spend_amount_at_assignment DECIMAL(10,2) NOT NULL,
    is_current TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES email_marketing_loyalty_points(account_id) ON DELETE CASCADE,
    FOREIGN KEY (tier_id) REFERENCES email_marketing_loyalty_tiers(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_tier_id (tier_id),
    INDEX idx_is_current (is_current),
    INDEX idx_assigned_at (assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for current_tier_id in loyalty_points table
ALTER TABLE email_marketing_loyalty_points 
ADD CONSTRAINT fk_current_tier FOREIGN KEY (current_tier_id) REFERENCES email_marketing_loyalty_tiers(id) ON DELETE SET NULL;

-- ============================================
-- AUTOMATION TABLES
-- ============================================

-- Automation rules table
CREATE TABLE IF NOT EXISTS email_marketing_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    trigger_type ENUM('account_created', 'order_placed', 'days_since_last_order', 'points_earned', 'tier_upgraded', 'custom') NOT NULL,
    trigger_conditions JSON NOT NULL,
    campaign_id INT NOT NULL,
    delay_hours INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_marketing_campaigns(id) ON DELETE CASCADE,
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trade schedules table
CREATE TABLE IF NOT EXISTS email_marketing_trade_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type_id INT NULL,
    campaign_id INT NOT NULL,
    frequency ENUM('weekly', 'biweekly', 'monthly', 'custom_days') NOT NULL,
    frequency_value INT NULL,
    time_of_day TIME NULL,
    day_of_week INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_marketing_campaigns(id) ON DELETE CASCADE,
    INDEX idx_account_type_id (account_type_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

