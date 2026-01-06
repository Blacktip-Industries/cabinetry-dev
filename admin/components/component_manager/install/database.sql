-- Component Manager Database Schema
-- Version: 1.0.0
-- Comprehensive Component Lifecycle Management System

-- Config table for storing installation metadata
CREATE TABLE IF NOT EXISTS component_manager_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table for component-specific settings
CREATE TABLE IF NOT EXISTS component_manager_parameters (
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

-- Registry table - Tracks all installed components
CREATE TABLE IF NOT EXISTS component_manager_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) UNIQUE NOT NULL,
    current_version VARCHAR(50) NOT NULL,
    installed_version VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    status_category ENUM('basic', 'detailed', 'custom') DEFAULT 'basic',
    component_path VARCHAR(500) NOT NULL,
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_checked_at TIMESTAMP NULL,
    health_status ENUM('healthy', 'warning', 'error') DEFAULT 'healthy',
    health_message TEXT NULL,
    health_last_checked_at TIMESTAMP NULL,
    dependencies JSON NULL,
    dependencies_status ENUM('met', 'unmet', 'unknown') DEFAULT 'unknown',
    dependencies_warnings JSON NULL,
    requirements JSON NULL,
    performance_metrics JSON NULL,
    performance_tracking_enabled TINYINT(1) DEFAULT 0,
    performance_tracking_level ENUM('no', 'basic', 'comprehensive') DEFAULT 'no',
    security_scan_enabled TINYINT(1) DEFAULT 0,
    security_scan_level ENUM('no', 'basic', 'comprehensive') DEFAULT 'no',
    security_scan_results JSON NULL,
    security_scan_last_at TIMESTAMP NULL,
    custom_status_data JSON NULL,
    author VARCHAR(255) NULL,
    description TEXT NULL,
    INDEX idx_component_name (component_name),
    INDEX idx_status (status),
    INDEX idx_status_category (status_category),
    INDEX idx_health_status (health_status),
    INDEX idx_dependencies_status (dependencies_status),
    INDEX idx_performance_tracking_enabled (performance_tracking_enabled),
    INDEX idx_security_scan_enabled (security_scan_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Changelog table - Centralized changelog for all components
CREATE TABLE IF NOT EXISTS component_manager_changelog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    change_type ENUM('bug_fix', 'feature', 'update', 'security', 'breaking', 'deprecation') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    files_changed JSON NULL,
    database_changes JSON NULL,
    savepoint_id INT NULL,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_version (version),
    INDEX idx_change_type (change_type),
    INDEX idx_created_at (created_at),
    INDEX idx_savepoint_id (savepoint_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backups table - Links component backups to savepoints
CREATE TABLE IF NOT EXISTS component_manager_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    savepoint_id INT NOT NULL,
    backup_type ENUM('pre_update', 'manual', 'auto', 'pre_uninstall') DEFAULT 'manual',
    reason TEXT,
    retention_policy ENUM('manual_cleanup', 'auto_cleanup', 'smart_retention', 'unlimited') DEFAULT 'manual_cleanup',
    retention_period_days INT NULL,
    is_important TINYINT(1) DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_version (version),
    INDEX idx_savepoint_id (savepoint_id),
    INDEX idx_backup_type (backup_type),
    INDEX idx_retention_policy (retention_policy),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_important (is_important)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update history table - Tracks all update operations
CREATE TABLE IF NOT EXISTS component_manager_update_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    from_version VARCHAR(50) NOT NULL,
    to_version VARCHAR(50) NOT NULL,
    status ENUM('pending', 'in_progress', 'success', 'failed', 'rolled_back') DEFAULT 'pending',
    backup_savepoint_id INT NULL,
    rollback_savepoint_id INT NULL,
    error_message TEXT NULL,
    migration_log JSON NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    performed_by VARCHAR(100) DEFAULT 'system',
    INDEX idx_component_name (component_name),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Installation history table - Tracks component installations with detailed step-by-step reporting
CREATE TABLE IF NOT EXISTS component_manager_installation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    installation_mode ENUM('track', 'orchestrate', 'both') DEFAULT 'both',
    status ENUM('pending', 'in_progress', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    performed_by VARCHAR(100) DEFAULT 'system',
    installation_preview JSON NULL,
    total_steps INT DEFAULT 0,
    completed_steps INT DEFAULT 0,
    failed_steps INT DEFAULT 0,
    error_summary TEXT NULL,
    troubleshooting_guidance JSON NULL,
    INDEX idx_component_name (component_name),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Installation steps table - Detailed step-by-step tracking
CREATE TABLE IF NOT EXISTS component_manager_installation_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    installation_id INT NOT NULL,
    step_number INT NOT NULL,
    step_name VARCHAR(255) NOT NULL,
    step_description TEXT NULL,
    step_type ENUM('validation', 'dependency_check', 'backup', 'database', 'file', 'config', 'migration', 'verification', 'cleanup') NOT NULL,
    status ENUM('pending', 'running', 'success', 'failed', 'skipped') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration_ms INT NULL,
    error_message TEXT NULL,
    error_code VARCHAR(50) NULL,
    troubleshooting_steps JSON NULL,
    step_data JSON NULL,
    INDEX idx_installation_id (installation_id),
    INDEX idx_step_number (step_number),
    INDEX idx_status (status),
    FOREIGN KEY (installation_id) REFERENCES component_manager_installation_history(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conflicts table - Tracks component conflicts
CREATE TABLE IF NOT EXISTS component_manager_conflicts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    conflict_type ENUM('function', 'table', 'css_variable', 'css_class', 'other') NOT NULL,
    conflict_name VARCHAR(255) NOT NULL,
    conflicting_component VARCHAR(100) NOT NULL,
    severity ENUM('warning', 'error', 'critical') DEFAULT 'warning',
    resolution_strategy ENUM('manual') DEFAULT 'manual',
    resolution_suggestions JSON NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT NULL,
    INDEX idx_component_name (component_name),
    INDEX idx_conflict_type (conflict_type),
    INDEX idx_severity (severity),
    INDEX idx_resolved_at (resolved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage table - Tracks component usage
CREATE TABLE IF NOT EXISTS component_manager_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    access_type ENUM('page_load', 'api_call', 'function_call', 'admin_access') NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    page_url VARCHAR(500) NULL,
    INDEX idx_component_name (component_name),
    INDEX idx_accessed_at (accessed_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table - Component categories and tags
CREATE TABLE IF NOT EXISTS component_manager_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    parent_category_id INT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent_category_id (parent_category_id),
    FOREIGN KEY (parent_category_id) REFERENCES component_manager_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS component_manager_component_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_component_category (component_name, category_id),
    INDEX idx_component_name (component_name),
    INDEX idx_category_id (category_id),
    FOREIGN KEY (category_id) REFERENCES component_manager_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS component_manager_component_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    tag_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_tag_name (tag_name),
    UNIQUE KEY unique_component_tag (component_name, tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table - Notification system
CREATE TABLE IF NOT EXISTS component_manager_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    notification_type ENUM('update_available', 'health_check', 'backup_created', 'update_completed', 'error') NOT NULL,
    component_name VARCHAR(100) NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS component_manager_notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email_enabled TINYINT(1) DEFAULT 0,
    dashboard_enabled TINYINT(1) DEFAULT 1,
    webhook_url VARCHAR(500) NULL,
    sms_enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_notification (user_id, notification_type),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exports table - Component export tracking
CREATE TABLE IF NOT EXISTS component_manager_exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    export_file_path VARCHAR(500) NOT NULL,
    export_type ENUM('full', 'files_only', 'database_only') DEFAULT 'full',
    version VARCHAR(50) NOT NULL,
    file_size BIGINT NULL,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_component_name (component_name),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance metrics table - Component performance tracking
CREATE TABLE IF NOT EXISTS component_manager_performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    metric_type VARCHAR(50) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    metric_unit VARCHAR(20) NULL,
    context JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_metric_type (metric_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security scans table - Component security scan results
CREATE TABLE IF NOT EXISTS component_manager_security_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    scan_type ENUM('basic', 'comprehensive') DEFAULT 'basic',
    scan_results JSON NOT NULL,
    vulnerabilities_found INT DEFAULT 0,
    severity_summary JSON NULL,
    scan_status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    scanned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_scan_type (scan_type),
    INDEX idx_scan_status (scan_status),
    INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled operations table - Scheduled/automated operations
CREATE TABLE IF NOT EXISTS component_manager_scheduled_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_type ENUM('health_check', 'update_check', 'backup', 'security_scan', 'performance_tracking') NOT NULL,
    component_name VARCHAR(100) NULL,
    schedule_type ENUM('once', 'daily', 'weekly', 'monthly', 'custom') NOT NULL,
    schedule_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NOT NULL,
    run_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_operation_type (operation_type),
    INDEX idx_component_name (component_name),
    INDEX idx_is_active (is_active),
    INDEX idx_next_run_at (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports table - Component reports and analytics
CREATE TABLE IF NOT EXISTS component_manager_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_config JSON NOT NULL,
    report_data JSON NULL,
    report_format ENUM('basic', 'detailed') DEFAULT 'basic',
    generated_at TIMESTAMP NULL,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_report_format (report_format),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documentation table - Component documentation
CREATE TABLE IF NOT EXISTS component_manager_documentation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    doc_type ENUM('readme', 'api', 'changelog', 'example', 'guide') NOT NULL,
    doc_content TEXT NOT NULL,
    doc_path VARCHAR(500) NULL,
    last_updated TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_doc_type (doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics table - Component analytics
CREATE TABLE IF NOT EXISTS component_manager_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    metric_type ENUM('usage', 'performance', 'error', 'user') NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    metric_data JSON NULL,
    user_id INT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_metric_type (metric_type),
    INDEX idx_recorded_at (recorded_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trends table - Trend analysis
CREATE TABLE IF NOT EXISTS component_manager_trends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    trend_type VARCHAR(50) NOT NULL,
    trend_data JSON NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_trend_type (trend_type),
    INDEX idx_period_start (period_start),
    INDEX idx_period_end (period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibility table - Compatibility checking
CREATE TABLE IF NOT EXISTS component_manager_compatibility (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    check_type ENUM('version', 'extension', 'component', 'conflict') NOT NULL,
    check_name VARCHAR(100) NOT NULL,
    required_value VARCHAR(255) NOT NULL,
    actual_value VARCHAR(255) NULL,
    is_compatible TINYINT(1) DEFAULT 1,
    check_message TEXT NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_check_type (check_type),
    INDEX idx_is_compatible (is_compatible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resources table - Resource management
CREATE TABLE IF NOT EXISTS component_manager_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    resource_type ENUM('file', 'table', 'asset', 'config', 'other') NOT NULL,
    resource_path VARCHAR(500) NOT NULL,
    resource_data JSON NULL,
    is_orphaned TINYINT(1) DEFAULT 0,
    tracked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_verified_at TIMESTAMP NULL,
    INDEX idx_component_name (component_name),
    INDEX idx_resource_type (resource_type),
    INDEX idx_is_orphaned (is_orphaned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optimizations table - Resource optimization suggestions
CREATE TABLE IF NOT EXISTS component_manager_optimizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    optimization_type VARCHAR(50) NOT NULL,
    optimization_description TEXT NOT NULL,
    optimization_suggestion TEXT NOT NULL,
    estimated_benefit VARCHAR(255) NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_applied TINYINT(1) DEFAULT 0,
    applied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_optimization_type (optimization_type),
    INDEX idx_is_applied (is_applied)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys table - API key management
CREATE TABLE IF NOT EXISTS component_manager_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    permissions JSON NULL,
    rate_limit_per_minute INT DEFAULT 60,
    rate_limit_per_hour INT DEFAULT 1000,
    is_active TINYINT(1) DEFAULT 1,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API usage tracking table
CREATE TABLE IF NOT EXISTS component_manager_api_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    response_code INT NOT NULL,
    response_time_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (api_key_id) REFERENCES component_manager_api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks table - Webhook management
CREATE TABLE IF NOT EXISTS component_manager_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500) NOT NULL,
    event_types JSON NOT NULL,
    secret_key VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    retry_count INT DEFAULT 3,
    timeout_seconds INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook trigger history table
CREATE TABLE IF NOT EXISTS component_manager_webhook_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    component_name VARCHAR(100) NULL,
    payload JSON NOT NULL,
    response_code INT NULL,
    response_body TEXT NULL,
    error_message TEXT NULL,
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_id (webhook_id),
    INDEX idx_event_type (event_type),
    INDEX idx_triggered_at (triggered_at),
    FOREIGN KEY (webhook_id) REFERENCES component_manager_webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Health alerts table
CREATE TABLE IF NOT EXISTS component_manager_health_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    alert_type ENUM('error', 'warning', 'performance', 'dependency', 'update') NOT NULL,
    alert_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    alert_data JSON NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_at TIMESTAMP NULL,
    resolved_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_alert_type (alert_type),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

