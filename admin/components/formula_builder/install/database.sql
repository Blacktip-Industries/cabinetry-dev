-- Formula Builder Component Database Schema
-- All tables prefixed with formula_builder_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS formula_builder_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS formula_builder_parameters (
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

-- Product formulas table (stores formulas per product)
CREATE TABLE IF NOT EXISTS formula_builder_product_formulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    formula_name VARCHAR(255) NOT NULL,
    formula_code TEXT NOT NULL,
    formula_type ENUM('expression', 'script', 'visual') DEFAULT 'script',
    version INT DEFAULT 1,
    is_active BOOLEAN DEFAULT 1,
    cache_enabled BOOLEAN DEFAULT 1,
    cache_duration INT DEFAULT 3600,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_is_active (is_active),
    INDEX idx_product_active (product_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formula cache table (caches formula results)
CREATE TABLE IF NOT EXISTS formula_builder_formula_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    cache_key VARCHAR(255) NOT NULL,
    result TEXT NOT NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_formula_id (formula_id),
    INDEX idx_cache_key (cache_key),
    INDEX idx_expires_at (expires_at),
    UNIQUE KEY unique_formula_cache (formula_id, cache_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Functions table (custom function registry)
CREATE TABLE IF NOT EXISTS formula_builder_functions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    function_name VARCHAR(100) UNIQUE NOT NULL,
    function_type ENUM('builtin', 'custom') NOT NULL,
    function_code TEXT,
    description TEXT,
    parameters TEXT,
    return_type VARCHAR(50),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_function_name (function_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Execution log table (logs formula executions)
CREATE TABLE IF NOT EXISTS formula_builder_execution_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    execution_time_ms INT,
    input_data TEXT,
    output_data TEXT,
    error_message TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VERSION CONTROL
-- ============================================

-- Formula versions table (version history)
CREATE TABLE IF NOT EXISTS formula_builder_formula_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    version_number INT NOT NULL,
    formula_code TEXT NOT NULL,
    changelog TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_tagged BOOLEAN DEFAULT 0,
    tag_name VARCHAR(100) NULL,
    INDEX idx_formula_id (formula_id),
    INDEX idx_version_number (version_number),
    UNIQUE KEY unique_formula_version (formula_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TESTING
-- ============================================

-- Formula tests table (test cases)
CREATE TABLE IF NOT EXISTS formula_builder_formula_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    test_name VARCHAR(255) NOT NULL,
    input_data TEXT,
    expected_result TEXT,
    actual_result TEXT,
    status ENUM('pending', 'passed', 'failed', 'error') DEFAULT 'pending',
    execution_time_ms INT NULL,
    last_run_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LIBRARY & TEMPLATES
-- ============================================

-- Formula library table (reusable templates)
CREATE TABLE IF NOT EXISTS formula_builder_formula_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_name VARCHAR(255) NOT NULL,
    formula_code TEXT NOT NULL,
    category VARCHAR(100),
    description TEXT,
    parameters TEXT,
    tags VARCHAR(500),
    usage_count INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_public BOOLEAN DEFAULT 1,
    INDEX idx_category (category),
    INDEX idx_tags (tags),
    INDEX idx_usage_count (usage_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template ratings table
CREATE TABLE IF NOT EXISTS formula_builder_template_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template_id (template_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CACHING
-- ============================================

-- Query cache table (database query result cache)
CREATE TABLE IF NOT EXISTS formula_builder_query_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    query_hash VARCHAR(64) NOT NULL,
    result TEXT NOT NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_table_name (table_name),
    INDEX idx_query_hash (query_hash),
    INDEX idx_expires_at (expires_at),
    UNIQUE KEY unique_query_cache (table_name, query_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PERMISSIONS
-- ============================================

-- Permissions table (role-based permissions)
CREATE TABLE IF NOT EXISTS formula_builder_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NULL,
    user_id INT NULL,
    role_name VARCHAR(100) NULL,
    permission_type ENUM('view', 'edit', 'delete', 'execute', 'share', 'manage_library', 'view_analytics') NOT NULL,
    granted BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_name (role_name),
    INDEX idx_permission_type (permission_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ANALYTICS
-- ============================================

-- Analytics table (metrics and analytics data)
CREATE TABLE IF NOT EXISTS formula_builder_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4),
    metric_data TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_metric_type (metric_type),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEBUGGING
-- ============================================

-- Debug sessions table
CREATE TABLE IF NOT EXISTS formula_builder_debug_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    session_data TEXT,
    breakpoints TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EVENTS & WEBHOOKS
-- ============================================

-- Events table (event logging)
CREATE TABLE IF NOT EXISTS formula_builder_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    formula_id INT NULL,
    user_id INT NULL,
    event_data TEXT,
    webhook_sent BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_formula_id (formula_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks table
CREATE TABLE IF NOT EXISTS formula_builder_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    event_types TEXT,
    secret VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS
-- ============================================

-- Notifications table
CREATE TABLE IF NOT EXISTS formula_builder_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    channel ENUM('email', 'in_app', 'sms', 'push') NOT NULL,
    message TEXT,
    read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_read (read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification preferences table
CREATE TABLE IF NOT EXISTS formula_builder_notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    channel ENUM('email', 'in_app', 'sms', 'push') NOT NULL,
    enabled BOOLEAN DEFAULT 1,
    frequency ENUM('immediate', 'digest_daily', 'digest_weekly') DEFAULT 'immediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_notification (user_id, notification_type, channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INTEGRATIONS
-- ============================================

-- Component integrations table
CREATE TABLE IF NOT EXISTS formula_builder_component_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL UNIQUE,
    integration_status ENUM('detected', 'integrated', 'error') DEFAULT 'detected',
    available_functions TEXT,
    last_checked TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MIGRATIONS
-- ============================================

-- Migrations table
CREATE TABLE IF NOT EXISTS formula_builder_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_version VARCHAR(50) NOT NULL,
    migration_name VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rollback_data TEXT,
    status ENUM('pending', 'applied', 'rolled_back', 'failed') DEFAULT 'pending',
    INDEX idx_migration_version (migration_version),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- API
-- ============================================

-- API keys table
CREATE TABLE IF NOT EXISTS formula_builder_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    user_id INT NULL,
    permissions TEXT,
    rate_limit INT DEFAULT 1000,
    is_active BOOLEAN DEFAULT 1,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALERTS & MONITORING
-- ============================================

-- Alert rules table
CREATE TABLE IF NOT EXISTS formula_builder_alert_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    metric_type VARCHAR(100) NOT NULL,
    threshold_value DECIMAL(10,4) NOT NULL,
    comparison_operator ENUM('>', '<', '>=', '<=', '==', '!=') NOT NULL,
    alert_channels TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_metric_type (metric_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alerts table
CREATE TABLE IF NOT EXISTS formula_builder_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_rule_id INT NULL,
    formula_id INT NULL,
    alert_level ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    resolved BOOLEAN DEFAULT 0,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_rule_id (alert_rule_id),
    INDEX idx_formula_id (formula_id),
    INDEX idx_alert_level (alert_level),
    INDEX idx_resolved (resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COLLABORATION
-- ============================================

-- Collaborations table
CREATE TABLE IF NOT EXISTS formula_builder_collaborations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    changes TEXT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table
CREATE TABLE IF NOT EXISTS formula_builder_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    user_id INT NOT NULL,
    line_number INT NULL,
    comment_text TEXT NOT NULL,
    resolved BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_resolved (resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workspaces table
CREATE TABLE IF NOT EXISTS formula_builder_workspaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workspace members table
CREATE TABLE IF NOT EXISTS formula_builder_workspace_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    permissions TEXT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_workspace_member (workspace_id, user_id),
    INDEX idx_workspace_id (workspace_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BACKUP & RECOVERY
-- ============================================

-- Backups table
CREATE TABLE IF NOT EXISTS formula_builder_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('full', 'incremental') NOT NULL,
    backup_file VARCHAR(500) NOT NULL,
    backup_size BIGINT,
    encrypted BOOLEAN DEFAULT 0,
    verification_hash VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    INDEX idx_backup_type (backup_type),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT & COMPLIANCE
-- ============================================

-- Audit log table
CREATE TABLE IF NOT EXISTS formula_builder_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(100) NOT NULL,
    formula_id INT NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    action_data TEXT,
    before_state TEXT,
    after_state TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_type (action_type),
    INDEX idx_formula_id (formula_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consents table (GDPR)
CREATE TABLE IF NOT EXISTS formula_builder_consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    consent_type VARCHAR(100) NOT NULL,
    consent_given BOOLEAN DEFAULT 0,
    consent_date TIMESTAMP NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_consent (user_id, consent_type),
    INDEX idx_user_id (user_id),
    INDEX idx_consent_type (consent_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data retention table
CREATE TABLE IF NOT EXISTS formula_builder_data_retention (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_type VARCHAR(100) NOT NULL UNIQUE,
    retention_days INT NOT NULL,
    auto_delete BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_type (data_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEPLOYMENT
-- ============================================

-- Deployments table
CREATE TABLE IF NOT EXISTS formula_builder_deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    environment ENUM('development', 'staging', 'production') NOT NULL,
    deployment_status ENUM('pending', 'deploying', 'deployed', 'rolled_back', 'failed') NOT NULL,
    rollout_percentage INT DEFAULT 100,
    deployed_by INT NOT NULL,
    deployed_at TIMESTAMP NULL,
    rolled_back_at TIMESTAMP NULL,
    INDEX idx_formula_id (formula_id),
    INDEX idx_environment (environment),
    INDEX idx_deployment_status (deployment_status),
    INDEX idx_deployed_at (deployed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature flags table
CREATE TABLE IF NOT EXISTS formula_builder_feature_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_name VARCHAR(255) NOT NULL,
    environment ENUM('development', 'staging', 'production') NOT NULL,
    is_enabled BOOLEAN DEFAULT 0,
    enabled_for_users TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_feature_environment (feature_name, environment),
    INDEX idx_environment (environment),
    INDEX idx_is_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- QUALITY & PERFORMANCE
-- ============================================

-- Quality reports table
CREATE TABLE IF NOT EXISTS formula_builder_quality_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    quality_score DECIMAL(5,2),
    complexity_score DECIMAL(5,2),
    security_score DECIMAL(5,2),
    performance_score DECIMAL(5,2),
    issues TEXT,
    suggestions TEXT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_quality_score (quality_score),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI/CD pipelines table
CREATE TABLE IF NOT EXISTS formula_builder_cicd_pipelines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    pipeline_name VARCHAR(255) NOT NULL,
    trigger_type ENUM('manual', 'on_save', 'on_commit', 'scheduled') NOT NULL,
    status ENUM('active', 'paused', 'archived') DEFAULT 'active',
    stages TEXT,
    artifacts TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI/CD runs table
CREATE TABLE IF NOT EXISTS formula_builder_cicd_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pipeline_id INT NOT NULL,
    run_number INT NOT NULL,
    status ENUM('running', 'passed', 'failed', 'cancelled') NOT NULL,
    test_results TEXT,
    quality_results TEXT,
    security_results TEXT,
    deployment_status VARCHAR(100) NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_pipeline_id (pipeline_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at),
    UNIQUE KEY unique_pipeline_run (pipeline_id, run_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance benchmarks table
CREATE TABLE IF NOT EXISTS formula_builder_performance_benchmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    benchmark_name VARCHAR(255) NOT NULL,
    execution_time_ms DECIMAL(10,2),
    memory_usage_mb DECIMAL(10,2),
    query_count INT,
    cache_hits INT,
    benchmarked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_benchmark_name (benchmark_name),
    INDEX idx_benchmarked_at (benchmarked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance profiles table
CREATE TABLE IF NOT EXISTS formula_builder_performance_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    profile_data TEXT,
    bottlenecks TEXT,
    optimization_suggestions TEXT,
    profiled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_profiled_at (profiled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SCALABILITY
-- ============================================

-- Servers table
CREATE TABLE IF NOT EXISTS formula_builder_servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_name VARCHAR(255) NOT NULL UNIQUE,
    server_url VARCHAR(500) NOT NULL,
    server_status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    load_average DECIMAL(5,2),
    capacity INT,
    last_heartbeat TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_server_status (server_status),
    INDEX idx_load_average (load_average)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue jobs table
CREATE TABLE IF NOT EXISTS formula_builder_queue_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    job_type VARCHAR(100) NOT NULL,
    job_data TEXT,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    priority INT DEFAULT 0,
    assigned_server INT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_assigned_server (assigned_server, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AI/ML
-- ============================================

-- AI suggestions table
CREATE TABLE IF NOT EXISTS formula_builder_ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    suggestion_type VARCHAR(100) NOT NULL,
    suggestion_text TEXT NOT NULL,
    confidence_score DECIMAL(5,2),
    accepted BOOLEAN NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_formula_id (formula_id),
    INDEX idx_suggestion_type (suggestion_type),
    INDEX idx_accepted (accepted),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI models table
CREATE TABLE IF NOT EXISTS formula_builder_ai_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(255) NOT NULL UNIQUE,
    model_type VARCHAR(100) NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT 0,
    accuracy_score DECIMAL(5,2),
    last_trained TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_model_type (model_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

