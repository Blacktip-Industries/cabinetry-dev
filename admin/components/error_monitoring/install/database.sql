-- Error Monitoring Component Database Schema
-- All tables prefixed with error_monitoring_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS error_monitoring_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS error_monitoring_parameters (
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
CREATE TABLE IF NOT EXISTS error_monitoring_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES error_monitoring_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error logs table (main error log entries)
CREATE TABLE IF NOT EXISTS error_monitoring_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_level ENUM('critical', 'high', 'medium', 'low') NOT NULL DEFAULT 'medium',
    error_type ENUM('php_error', 'exception', 'database', 'component') NOT NULL DEFAULT 'php_error',
    error_message TEXT NOT NULL,
    stack_trace TEXT NULL,
    file VARCHAR(500) NULL,
    line INT NULL,
    function VARCHAR(255) NULL,
    component_name VARCHAR(100) NULL,
    error_context JSON NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    group_id INT NULL,
    occurrence_count INT DEFAULT 1,
    memory_usage INT NULL,
    execution_time DECIMAL(10,4) NULL,
    is_archived TINYINT(1) DEFAULT 0,
    environment VARCHAR(50) DEFAULT 'production',
    performance_data JSON NULL,
    suppression_rule_id INT NULL,
    assigned_to INT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('new', 'investigating', 'fixing', 'testing', 'resolved', 'closed') DEFAULT 'new',
    due_date DATETIME NULL,
    correlation_id INT NULL,
    impact_score DECIMAL(10,2) NULL,
    cost_estimate DECIMAL(10,2) NULL,
    users_affected INT NULL,
    sla_id INT NULL,
    team_id INT NULL,
    response_time INT NULL,
    resolution_time INT NULL,
    tags JSON NULL,
    documentation_id INT NULL,
    replay_session_id INT NULL,
    mttr DECIMAL(10,2) NULL,
    mtbf DECIMAL(10,2) NULL,
    parent_error_id INT NULL,
    lifecycle_state VARCHAR(50) NULL,
    is_duplicate TINYINT(1) DEFAULT 0,
    original_error_id INT NULL,
    sampling_rate DECIMAL(5,2) NULL,
    privacy_masked TINYINT(1) DEFAULT 0,
    cluster_id INT NULL,
    rate_limit_applied TINYINT(1) DEFAULT 0,
    retention_policy_id INT NULL,
    performance_correlation_id INT NULL,
    user_experience_impact DECIMAL(10,2) NULL,
    resolution_workflow_id INT NULL,
    incident_id INT NULL,
    cost_tracked DECIMAL(10,2) NULL,
    slo_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_error_level (error_level),
    INDEX idx_error_type (error_type),
    INDEX idx_component_name (component_name),
    INDEX idx_user_id (user_id),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_group_id (group_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_environment (environment),
    INDEX idx_created_at (created_at),
    INDEX idx_correlation_id (correlation_id),
    INDEX idx_sla_id (sla_id),
    INDEX idx_team_id (team_id),
    INDEX idx_cluster_id (cluster_id),
    INDEX idx_incident_id (incident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error groups table (error grouping)
CREATE TABLE IF NOT EXISTS error_monitoring_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_signature VARCHAR(255) NOT NULL,
    fuzzy_signature VARCHAR(255) NULL,
    similarity_threshold DECIMAL(5,2) DEFAULT 80.00,
    first_occurrence TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_occurrence TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_count INT DEFAULT 1,
    is_resolved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_error_signature (error_signature),
    INDEX idx_fuzzy_signature (fuzzy_signature),
    INDEX idx_is_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table (notification settings)
CREATE TABLE IF NOT EXISTS error_monitoring_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('immediate', 'digest', 'threshold') NOT NULL DEFAULT 'immediate',
    error_level ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    settings JSON NULL,
    throttle_config JSON NULL,
    last_sent_at TIMESTAMP NULL,
    throttle_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_notification_type (notification_type),
    INDEX idx_error_level (error_level),
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue table (asynchronous error queue)
CREATE TABLE IF NOT EXISTS error_monitoring_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_data JSON NOT NULL,
    priority INT DEFAULT 0,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Archive table (archived errors for permanent retention)
CREATE TABLE IF NOT EXISTS error_monitoring_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_error_id INT NULL,
    error_data JSON NOT NULL,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT NULL,
    reason TEXT NULL,
    INDEX idx_original_error_id (original_error_id),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hooks table (registered component hooks)
CREATE TABLE IF NOT EXISTS error_monitoring_hooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hook_name VARCHAR(255) NOT NULL,
    callback VARCHAR(255) NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    priority INT DEFAULT 10,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hook_name (hook_name),
    INDEX idx_component_name (component_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classification rules table (AI/ML classification rules and training data)
CREATE TABLE IF NOT EXISTS error_monitoring_classification_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('pattern', 'ml', 'custom') NOT NULL DEFAULT 'pattern',
    rule_config JSON NOT NULL,
    training_data JSON NULL,
    accuracy DECIMAL(5,2) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Filters table (error filtering and suppression rules)
CREATE TABLE IF NOT EXISTS error_monitoring_filters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filter_name VARCHAR(255) NOT NULL,
    filter_type ENUM('regex', 'wildcard', 'rule') NOT NULL,
    filter_pattern TEXT NOT NULL,
    applies_to ENUM('message', 'file', 'component', 'user', 'ip') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_filter_type (filter_type),
    INDEX idx_applies_to (applies_to),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports table (generated error reports)
CREATE TABLE IF NOT EXISTS error_monitoring_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    template_id INT NULL,
    filters JSON NULL,
    format ENUM('html', 'pdf', 'json', 'csv') DEFAULT 'html',
    generated_by INT NULL,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template_id (template_id),
    INDEX idx_generated_by (generated_by),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report templates table (custom report templates)
CREATE TABLE IF NOT EXISTS error_monitoring_report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_config JSON NOT NULL,
    branding_config JSON NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance table (performance metrics and tracking)
CREATE TABLE IF NOT EXISTS error_monitoring_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    metric_data JSON NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE SET NULL,
    INDEX idx_error_id (error_id),
    INDEX idx_metric_type (metric_type),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Environments table (environment configuration and routing)
CREATE TABLE IF NOT EXISTS error_monitoring_environments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    environment_name VARCHAR(50) NOT NULL,
    environment_type ENUM('dev', 'staging', 'production') NOT NULL,
    config_json JSON NOT NULL,
    retention_days INT DEFAULT 30,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_environment_type (environment_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard views table (saved dashboard customizations)
CREATE TABLE IF NOT EXISTS error_monitoring_dashboard_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    view_name VARCHAR(255) NOT NULL,
    user_id INT NULL,
    widget_config JSON NOT NULL,
    layout_config JSON NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_shared TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_default (is_default),
    INDEX idx_is_shared (is_shared)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search index table (full-text search index)
CREATE TABLE IF NOT EXISTS error_monitoring_search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    searchable_text TEXT NOT NULL,
    indexed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    FULLTEXT idx_searchable_text (searchable_text),
    INDEX idx_error_id (error_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Correlations table (error correlation data)
CREATE TABLE IF NOT EXISTS error_monitoring_correlations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    related_error_id INT NOT NULL,
    correlation_type VARCHAR(100) NOT NULL,
    correlation_score DECIMAL(5,2) NULL,
    correlation_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (related_error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_related_error_id (related_error_id),
    INDEX idx_correlation_type (correlation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow table (workflow assignments and status)
CREATE TABLE IF NOT EXISTS error_monitoring_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    assigned_to INT NULL,
    assigned_by INT NULL,
    status ENUM('new', 'investigating', 'fixing', 'testing', 'resolved', 'closed') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    due_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table (error comments and collaboration)
CREATE TABLE IF NOT EXISTS error_monitoring_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- History table (error change history and audit trail)
CREATE TABLE IF NOT EXISTS error_monitoring_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    user_id INT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alerts table (alert and escalation records)
CREATE TABLE IF NOT EXISTS error_monitoring_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    alert_type VARCHAR(100) NOT NULL,
    channel VARCHAR(100) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'acknowledged') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_channel (channel),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alert channels table (alert channel configuration)
CREATE TABLE IF NOT EXISTS error_monitoring_alert_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_name VARCHAR(100) NOT NULL,
    channel_type ENUM('email', 'sms', 'slack', 'webhook', 'pagerduty') NOT NULL,
    config_json JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_channel_type (channel_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys table (API authentication keys)
CREATE TABLE IF NOT EXISTS error_monitoring_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    user_id INT NULL,
    permissions JSON NULL,
    rate_limit INT DEFAULT 1000,
    is_active TINYINT(1) DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API logs table (API access logs)
CREATE TABLE IF NOT EXISTS error_monitoring_api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    request_data JSON NULL,
    response_code INT NULL,
    response_time_ms INT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES error_monitoring_api_keys(id) ON DELETE SET NULL,
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backups table (backup records and restore points)
CREATE TABLE IF NOT EXISTS error_monitoring_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    backup_type ENUM('full', 'incremental') DEFAULT 'full',
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NULL,
    backup_data JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_backup_type (backup_type),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs table (compliance audit logs)
CREATE TABLE IF NOT EXISTS error_monitoring_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test results table (error testing results)
CREATE TABLE IF NOT EXISTS error_monitoring_test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(255) NOT NULL,
    test_type VARCHAR(100) NOT NULL,
    test_data JSON NULL,
    result ENUM('pass', 'fail', 'error') NOT NULL,
    error_message TEXT NULL,
    execution_time_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_type (test_type),
    INDEX idx_result (result),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visualizations table (saved visualizations and charts)
CREATE TABLE IF NOT EXISTS error_monitoring_visualizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visualization_name VARCHAR(255) NOT NULL,
    visualization_type VARCHAR(100) NOT NULL,
    config_json JSON NOT NULL,
    data_json JSON NULL,
    user_id INT NULL,
    is_shared TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_visualization_type (visualization_type),
    INDEX idx_user_id (user_id),
    INDEX idx_is_shared (is_shared)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forecasts table (forecasting data and predictions)
CREATE TABLE IF NOT EXISTS error_monitoring_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forecast_type VARCHAR(100) NOT NULL,
    forecast_data JSON NOT NULL,
    accuracy_score DECIMAL(5,2) NULL,
    predicted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_forecast_type (forecast_type),
    INDEX idx_predicted_at (predicted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Impact table (business impact and cost data)
CREATE TABLE IF NOT EXISTS error_monitoring_impact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    impact_type VARCHAR(100) NOT NULL,
    impact_value DECIMAL(10,2) NOT NULL,
    impact_data JSON NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_impact_type (impact_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SLAs table (SLA definitions and tracking)
CREATE TABLE IF NOT EXISTS error_monitoring_slas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sla_name VARCHAR(255) NOT NULL,
    error_level ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    response_time_minutes INT NOT NULL,
    resolution_time_minutes INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_error_level (error_level),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SLA breaches table (SLA breach records)
CREATE TABLE IF NOT EXISTS error_monitoring_sla_breaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    sla_id INT NOT NULL,
    breach_type ENUM('response', 'resolution') NOT NULL,
    breach_time_minutes INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (sla_id) REFERENCES error_monitoring_slas(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_sla_id (sla_id),
    INDEX idx_breach_type (breach_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teams table (team definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL,
    team_description TEXT NULL,
    permissions JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Team members table (team membership)
CREATE TABLE IF NOT EXISTS error_monitoring_team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(100) DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES error_monitoring_teams(id) ON DELETE CASCADE,
    INDEX idx_team_id (team_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Knowledge base table (knowledge base articles)
CREATE TABLE IF NOT EXISTS error_monitoring_knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_title VARCHAR(255) NOT NULL,
    article_content TEXT NOT NULL,
    tags JSON NULL,
    error_patterns JSON NULL,
    views_count INT DEFAULT 0,
    helpful_count INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_published (is_published),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KB analytics table (KB usage analytics)
CREATE TABLE IF NOT EXISTS error_monitoring_kb_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    error_id INT NULL,
    action VARCHAR(100) NOT NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES error_monitoring_knowledge_base(id) ON DELETE CASCADE,
    INDEX idx_article_id (article_id),
    INDEX idx_error_id (error_id),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automation rules table (automation rules)
CREATE TABLE IF NOT EXISTS error_monitoring_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_conditions JSON NOT NULL,
    rule_actions JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automation logs table (automation execution logs)
CREATE TABLE IF NOT EXISTS error_monitoring_automation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    error_id INT NOT NULL,
    execution_result ENUM('success', 'failed', 'partial') DEFAULT 'success',
    execution_data JSON NULL,
    error_message TEXT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES error_monitoring_automation_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_error_id (error_id),
    INDEX idx_execution_result (execution_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mobile sessions table (mobile app sessions)
CREATE TABLE IF NOT EXISTS error_monitoring_mobile_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_info JSON NULL,
    last_active_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Replay sessions table (error replay session data)
CREATE TABLE IF NOT EXISTS error_monitoring_replay_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    session_data JSON NOT NULL,
    replay_status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_replay_status (replay_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags table (error tags)
CREATE TABLE IF NOT EXISTS error_monitoring_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(255) UNIQUE NOT NULL,
    tag_color VARCHAR(50) NULL,
    parent_tag_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent_tag_id (parent_tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error tags table (error-tag relationships)
CREATE TABLE IF NOT EXISTS error_monitoring_error_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES error_monitoring_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_error_tag (error_id, tag_id),
    INDEX idx_error_id (error_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Metrics table (calculated metrics and KPIs)
CREATE TABLE IF NOT EXISTS error_monitoring_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(255) NOT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    calculation_data JSON NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_type (metric_type),
    INDEX idx_calculated_at (calculated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integrations table (external integration configurations)
CREATE TABLE IF NOT EXISTS error_monitoring_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_name VARCHAR(255) NOT NULL,
    integration_type ENUM('github', 'jira', 'sentry', 'newrelic', 'datadog', 'pagerduty', 'custom') NOT NULL,
    config_json JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_integration_type (integration_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integration logs table (integration execution logs)
CREATE TABLE IF NOT EXISTS error_monitoring_integration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    error_id INT NULL,
    action VARCHAR(100) NOT NULL,
    status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    request_data JSON NULL,
    response_data JSON NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES error_monitoring_integrations(id) ON DELETE CASCADE,
    INDEX idx_integration_id (integration_id),
    INDEX idx_error_id (error_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documentation table (error documentation and annotations)
CREATE TABLE IF NOT EXISTS error_monitoring_documentation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    doc_title VARCHAR(255) NOT NULL,
    doc_content TEXT NOT NULL,
    doc_version INT DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attachments table (documentation attachments)
CREATE TABLE IF NOT EXISTS error_monitoring_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documentation_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (documentation_id) REFERENCES error_monitoring_documentation(id) ON DELETE CASCADE,
    INDEX idx_documentation_id (documentation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schedules table (scheduled tasks and maintenance windows)
CREATE TABLE IF NOT EXISTS error_monitoring_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(255) NOT NULL,
    schedule_type ENUM('maintenance', 'suppression', 'task', 'report') NOT NULL,
    schedule_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    next_run_at DATETIME NULL,
    last_run_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schedule_type (schedule_type),
    INDEX idx_is_active (is_active),
    INDEX idx_next_run_at (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications UI table (in-app notification records)
CREATE TABLE IF NOT EXISTS error_monitoring_notifications_ui (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    notification_data JSON NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deduplication rules table (deduplication rules)
CREATE TABLE IF NOT EXISTS error_monitoring_deduplication_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_config JSON NOT NULL,
    similarity_threshold DECIMAL(5,2) DEFAULT 80.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sampling config table (sampling configuration)
CREATE TABLE IF NOT EXISTS error_monitoring_sampling_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_name VARCHAR(255) NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    sampling_rate DECIMAL(5,2) NOT NULL,
    config_data JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_error_type (error_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy rules table (privacy and data masking rules)
CREATE TABLE IF NOT EXISTS error_monitoring_privacy_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('mask', 'encrypt', 'anonymize', 'delete') NOT NULL,
    rule_pattern TEXT NOT NULL,
    applies_to VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Export jobs table (export job records)
CREATE TABLE IF NOT EXISTS error_monitoring_export_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    filters JSON NULL,
    format ENUM('csv', 'json', 'xml', 'sql') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    file_path VARCHAR(500) NULL,
    file_size BIGINT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import jobs table (import job records)
CREATE TABLE IF NOT EXISTS error_monitoring_import_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    format ENUM('csv', 'json', 'xml', 'sql') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    records_imported INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    error_message TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates table (error templates and presets)
CREATE TABLE IF NOT EXISTS error_monitoring_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type ENUM('error', 'filter', 'dashboard', 'report', 'workflow') NOT NULL,
    template_data JSON NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_type (template_type),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Batch jobs table (batch operation jobs)
CREATE TABLE IF NOT EXISTS error_monitoring_batch_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    job_type VARCHAR(100) NOT NULL,
    job_data JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    progress INT DEFAULT 0,
    total_items INT DEFAULT 0,
    processed_items INT DEFAULT 0,
    error_message TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_job_type (job_type),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dependencies table (error dependency relationships)
CREATE TABLE IF NOT EXISTS error_monitoring_dependencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_error_id INT NOT NULL,
    child_error_id INT NOT NULL,
    dependency_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (child_error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_parent_error_id (parent_error_id),
    INDEX idx_child_error_id (child_error_id),
    INDEX idx_dependency_type (dependency_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lifecycle states table (lifecycle state definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_lifecycle_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_name VARCHAR(50) UNIQUE NOT NULL,
    state_description TEXT NULL,
    state_config JSON NULL,
    is_initial TINYINT(1) DEFAULT 0,
    is_final TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_initial (is_initial),
    INDEX idx_is_final (is_final)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lifecycle transitions table (state transition rules)
CREATE TABLE IF NOT EXISTS error_monitoring_lifecycle_transitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_state_id INT NOT NULL,
    to_state_id INT NOT NULL,
    transition_conditions JSON NULL,
    transition_actions JSON NULL,
    is_automatic TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_state_id) REFERENCES error_monitoring_lifecycle_states(id) ON DELETE CASCADE,
    FOREIGN KEY (to_state_id) REFERENCES error_monitoring_lifecycle_states(id) ON DELETE CASCADE,
    INDEX idx_from_state_id (from_state_id),
    INDEX idx_to_state_id (to_state_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limits table (rate limiting configuration)
CREATE TABLE IF NOT EXISTS error_monitoring_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    limit_name VARCHAR(255) NOT NULL,
    limit_type VARCHAR(100) NOT NULL,
    limit_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_limit_type (limit_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clusters table (error clusters)
CREATE TABLE IF NOT EXISTS error_monitoring_clusters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cluster_name VARCHAR(255) NOT NULL,
    cluster_type VARCHAR(100) NOT NULL,
    cluster_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cluster_type (cluster_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cluster patterns table (cluster patterns)
CREATE TABLE IF NOT EXISTS error_monitoring_cluster_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cluster_id INT NOT NULL,
    pattern_data JSON NOT NULL,
    pattern_score DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cluster_id) REFERENCES error_monitoring_clusters(id) ON DELETE CASCADE,
    INDEX idx_cluster_id (cluster_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alert rules table (alert rule definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_alert_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_conditions JSON NOT NULL,
    rule_actions JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alert conditions table (alert condition logic)
CREATE TABLE IF NOT EXISTS error_monitoring_alert_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    condition_type VARCHAR(100) NOT NULL,
    condition_config JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES error_monitoring_alert_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_condition_type (condition_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Widgets table (custom widget definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_name VARCHAR(255) NOT NULL,
    widget_type VARCHAR(100) NOT NULL,
    widget_config JSON NOT NULL,
    user_id INT NULL,
    is_shared TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_widget_type (widget_type),
    INDEX idx_user_id (user_id),
    INDEX idx_is_shared (is_shared)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Widget marketplace table (widget marketplace entries)
CREATE TABLE IF NOT EXISTS error_monitoring_widget_marketplace (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_id INT NOT NULL,
    marketplace_data JSON NOT NULL,
    download_count INT DEFAULT 0,
    rating DECIMAL(3,2) NULL,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (widget_id) REFERENCES error_monitoring_widgets(id) ON DELETE CASCADE,
    INDEX idx_widget_id (widget_id),
    INDEX idx_is_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API security table (API security configuration)
CREATE TABLE IF NOT EXISTS error_monitoring_api_security (
    id INT AUTO_INCREMENT PRIMARY KEY,
    security_type ENUM('oauth2', 'jwt', 'api_key', 'ip_whitelist') NOT NULL,
    security_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_security_type (security_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API rate limits table (API rate limit rules)
CREATE TABLE IF NOT EXISTS error_monitoring_api_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NULL,
    endpoint VARCHAR(255) NOT NULL,
    rate_limit INT NOT NULL,
    time_window INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES error_monitoring_api_keys(id) ON DELETE CASCADE,
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retention policies table (retention policy definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_retention_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    policy_type VARCHAR(100) NOT NULL,
    policy_config JSON NOT NULL,
    retention_days INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_policy_type (policy_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification templates table (notification templates)
CREATE TABLE IF NOT EXISTS error_monitoring_notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type VARCHAR(100) NOT NULL,
    channel ENUM('email', 'sms', 'slack', 'webhook') NOT NULL,
    template_content TEXT NOT NULL,
    template_variables JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_type (template_type),
    INDEX idx_channel (channel),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template versions table (template versioning)
CREATE TABLE IF NOT EXISTS error_monitoring_template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    version_number INT NOT NULL,
    template_content TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES error_monitoring_notification_templates(id) ON DELETE CASCADE,
    INDEX idx_template_id (template_id),
    INDEX idx_version_number (version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Escalation policies table (escalation policy definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_escalation_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    policy_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Escalation chains table (escalation chain definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_escalation_chains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_id INT NOT NULL,
    chain_level INT NOT NULL,
    chain_config JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (policy_id) REFERENCES error_monitoring_escalation_policies(id) ON DELETE CASCADE,
    INDEX idx_policy_id (policy_id),
    INDEX idx_chain_level (chain_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- On-call rotations table (on-call rotation schedules)
CREATE TABLE IF NOT EXISTS error_monitoring_on_call_rotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rotation_name VARCHAR(255) NOT NULL,
    rotation_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance correlation table (performance correlation data)
CREATE TABLE IF NOT EXISTS error_monitoring_performance_correlation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    performance_metric VARCHAR(100) NOT NULL,
    correlation_score DECIMAL(5,2) NULL,
    correlation_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_performance_metric (performance_metric)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User experience table (user experience impact data)
CREATE TABLE IF NOT EXISTS error_monitoring_user_experience (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    ux_metric VARCHAR(100) NOT NULL,
    ux_value DECIMAL(10,2) NOT NULL,
    ux_data JSON NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_ux_metric (ux_metric)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resolution workflows table (resolution workflow definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_resolution_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    workflow_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow steps table (workflow step definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    step_order INT NOT NULL,
    step_config JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES error_monitoring_resolution_workflows(id) ON DELETE CASCADE,
    INDEX idx_workflow_id (workflow_id),
    INDEX idx_step_order (step_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stakeholders table (stakeholder definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_stakeholders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stakeholder_name VARCHAR(255) NOT NULL,
    stakeholder_email VARCHAR(255) NOT NULL,
    stakeholder_role VARCHAR(100) NULL,
    notification_preferences JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stakeholder reports table (stakeholder report records)
CREATE TABLE IF NOT EXISTS error_monitoring_stakeholder_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stakeholder_id INT NOT NULL,
    report_config JSON NOT NULL,
    report_data JSON NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stakeholder_id) REFERENCES error_monitoring_stakeholders(id) ON DELETE CASCADE,
    INDEX idx_stakeholder_id (stakeholder_id),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics insights table (analytics insights and recommendations)
CREATE TABLE IF NOT EXISTS error_monitoring_analytics_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insight_type VARCHAR(100) NOT NULL,
    insight_data JSON NOT NULL,
    confidence_score DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_insight_type (insight_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prevention rules table (error prevention rules)
CREATE TABLE IF NOT EXISTS error_monitoring_prevention_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type VARCHAR(100) NOT NULL,
    rule_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prevention checks table (prevention check results)
CREATE TABLE IF NOT EXISTS error_monitoring_prevention_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    check_result ENUM('pass', 'fail', 'warning') NOT NULL,
    check_data JSON NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES error_monitoring_prevention_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_check_result (check_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cost tracking table (detailed cost tracking data)
CREATE TABLE IF NOT EXISTS error_monitoring_cost_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_id INT NOT NULL,
    cost_type VARCHAR(100) NOT NULL,
    cost_amount DECIMAL(10,2) NOT NULL,
    cost_data JSON NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    INDEX idx_error_id (error_id),
    INDEX idx_cost_type (cost_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cost budgets table (cost budget definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_cost_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(255) NOT NULL,
    budget_amount DECIMAL(10,2) NOT NULL,
    budget_period ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_budget_period (budget_period),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SLOs table (SLO definitions)
CREATE TABLE IF NOT EXISTS error_monitoring_slos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slo_name VARCHAR(255) NOT NULL,
    slo_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error budgets table (error budget tracking)
CREATE TABLE IF NOT EXISTS error_monitoring_error_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slo_id INT NOT NULL,
    budget_period_start DATETIME NOT NULL,
    budget_period_end DATETIME NOT NULL,
    budget_amount DECIMAL(10,2) NOT NULL,
    consumed_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slo_id) REFERENCES error_monitoring_slos(id) ON DELETE CASCADE,
    INDEX idx_slo_id (slo_id),
    INDEX idx_budget_period_start (budget_period_start),
    INDEX idx_budget_period_end (budget_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incidents table (incident records)
CREATE TABLE IF NOT EXISTS error_monitoring_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_name VARCHAR(255) NOT NULL,
    incident_status ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open',
    incident_severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    incident_data JSON NOT NULL,
    created_by INT NULL,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incident_status (incident_status),
    INDEX idx_incident_severity (incident_severity),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incident errors table (incident-error relationships)
CREATE TABLE IF NOT EXISTS error_monitoring_incident_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    error_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES error_monitoring_incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (error_id) REFERENCES error_monitoring_logs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_incident_error (incident_id, error_id),
    INDEX idx_incident_id (incident_id),
    INDEX idx_error_id (error_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post-mortems table (post-mortem documents)
CREATE TABLE IF NOT EXISTS error_monitoring_post_mortems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    post_mortem_content TEXT NOT NULL,
    post_mortem_data JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES error_monitoring_incidents(id) ON DELETE CASCADE,
    INDEX idx_incident_id (incident_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

