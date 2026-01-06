-- Layout Component Database Schema
-- Version: 3.0.0
-- Advanced Flexible Layout System with Design System & Template Management

-- Config table for storing installation metadata
CREATE TABLE IF NOT EXISTS layout_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table for component-specific settings
CREATE TABLE IF NOT EXISTS layout_parameters (
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

-- Layout definitions - Stores layout templates
CREATE TABLE IF NOT EXISTS layout_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    layout_data JSON NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_preset TINYINT(1) DEFAULT 0,
    version VARCHAR(50) DEFAULT '1.0.0',
    tags JSON NULL,
    category VARCHAR(100) NULL,
    notes TEXT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    parent_layout_id INT NULL,
    published_at TIMESTAMP NULL,
    scheduled_publish_at TIMESTAMP NULL,
    scheduled_unpublish_at TIMESTAMP NULL,
    requires_approval TINYINT(1) DEFAULT 0,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    last_edited_by INT NULL,
    last_edited_at TIMESTAMP NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_parent_layout_id (parent_layout_id),
    INDEX idx_created_by (created_by),
    INDEX idx_is_default (is_default),
    INDEX idx_is_preset (is_preset),
    FOREIGN KEY (parent_layout_id) REFERENCES layout_definitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout assignments - Maps layouts to pages
CREATE TABLE IF NOT EXISTS layout_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_name VARCHAR(255) NOT NULL,
    layout_id INT NOT NULL,
    custom_overrides JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_page_layout (page_name, layout_id),
    INDEX idx_page_name (page_name),
    INDEX idx_layout_id (layout_id),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout versions - Version history for layouts (rollback support)
CREATE TABLE IF NOT EXISTS layout_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    version VARCHAR(50) NOT NULL,
    layout_data JSON NOT NULL,
    change_description TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_version (version),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout resize states - User-specific resize states (optional)
CREATE TABLE IF NOT EXISTS layout_resize_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    layout_id INT NOT NULL,
    resize_data JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_layout (user_id, layout_id),
    INDEX idx_user_id (user_id),
    INDEX idx_layout_id (layout_id),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout cache - Performance caching for generated CSS/HTML
CREATE TABLE IF NOT EXISTS layout_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    cache_key VARCHAR(255) NOT NULL,
    cache_data TEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_cache_key (cache_key),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout component dependencies - Tracks which components layouts depend on
CREATE TABLE IF NOT EXISTS layout_component_dependencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    is_required TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_component_name (component_name),
    UNIQUE KEY unique_layout_component (layout_id, component_name),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout analytics - Detailed analytics for layouts
CREATE TABLE IF NOT EXISTS layout_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    user_id INT NULL,
    page_name VARCHAR(255) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_page_name (page_name),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout performance metrics - Performance tracking for layouts
CREATE TABLE IF NOT EXISTS layout_performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    metric_type VARCHAR(50) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    page_name VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_metric_type (metric_type),
    INDEX idx_page_name (page_name),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout backups - Automatic backups before major changes
CREATE TABLE IF NOT EXISTS layout_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    backup_data JSON NOT NULL,
    backup_reason VARCHAR(255) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout webhooks - Webhook configurations for layout events
CREATE TABLE IF NOT EXISTS layout_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500) NOT NULL,
    event_types JSON NOT NULL,
    secret_key VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    retry_count INT DEFAULT 0,
    last_triggered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_webhook_name (webhook_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout performance budgets - Performance budgets for layouts
CREATE TABLE IF NOT EXISTS layout_performance_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    budget_type VARCHAR(50) NOT NULL,
    budget_value DECIMAL(10,4) NOT NULL,
    alert_threshold DECIMAL(10,4) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_budget_type (budget_type),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout A/B tests - A/B testing configurations
CREATE TABLE IF NOT EXISTS layout_ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(255) NOT NULL,
    layout_a_id INT NOT NULL,
    layout_b_id INT NOT NULL,
    traffic_split JSON NOT NULL,
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    status ENUM('draft', 'running', 'paused', 'completed') DEFAULT 'draft',
    metrics JSON NULL,
    winner_layout_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_layout_a_id (layout_a_id),
    INDEX idx_layout_b_id (layout_b_id),
    INDEX idx_winner_layout_id (winner_layout_id),
    FOREIGN KEY (layout_a_id) REFERENCES layout_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_b_id) REFERENCES layout_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_layout_id) REFERENCES layout_definitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout A/B test results - A/B test results tracking
CREATE TABLE IF NOT EXISTS layout_ab_test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    layout_id INT NOT NULL,
    visitor_id VARCHAR(255) NULL,
    session_id VARCHAR(255) NULL,
    conversion_event VARCHAR(100) NULL,
    conversion_value DECIMAL(10,2) NULL,
    page_load_time DECIMAL(10,4) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_id (test_id),
    INDEX idx_layout_id (layout_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (test_id) REFERENCES layout_ab_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout roles - Role definitions for permissions
CREATE TABLE IF NOT EXISTS layout_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    permissions JSON NOT NULL,
    is_system_role TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_is_system_role (is_system_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout user roles - User role assignments
CREATE TABLE IF NOT EXISTS layout_user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id),
    UNIQUE KEY unique_user_role (user_id, role_id),
    FOREIGN KEY (role_id) REFERENCES layout_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout user permissions - Direct user permissions
CREATE TABLE IF NOT EXISTS layout_user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    is_granted TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_permission (permission),
    UNIQUE KEY unique_user_permission (user_id, permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout role permissions - Role permissions
CREATE TABLE IF NOT EXISTS layout_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    is_granted TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_id (role_id),
    INDEX idx_permission (permission),
    UNIQUE KEY unique_role_permission (role_id, permission),
    FOREIGN KEY (role_id) REFERENCES layout_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout translations - Translation strings
CREATE TABLE IF NOT EXISTS layout_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL,
    translation_key VARCHAR(255) NOT NULL,
    translation_value TEXT NOT NULL,
    context VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_language_code (language_code),
    INDEX idx_translation_key (translation_key),
    UNIQUE KEY unique_language_key (language_code, translation_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout validation rules - Custom validation rules
CREATE TABLE IF NOT EXISTS layout_validation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type VARCHAR(50) NOT NULL,
    rule_config JSON NOT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout marketplace layouts - Marketplace layout metadata
CREATE TABLE IF NOT EXISTS layout_marketplace_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marketplace_id VARCHAR(255) UNIQUE NOT NULL,
    layout_id INT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    tags JSON NULL,
    author_name VARCHAR(255) NULL,
    author_email VARCHAR(255) NULL,
    screenshots JSON NULL,
    component_requirements JSON NULL,
    version_compatibility VARCHAR(50) NULL,
    rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    download_count INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_marketplace_id (marketplace_id),
    INDEX idx_layout_id (layout_id),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout marketplace reviews - Marketplace layout reviews
CREATE TABLE IF NOT EXISTS layout_marketplace_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marketplace_layout_id INT NOT NULL,
    user_id INT NULL,
    user_name VARCHAR(255) NULL,
    rating INT NOT NULL,
    review_text TEXT NULL,
    is_verified_purchase TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_marketplace_layout_id (marketplace_layout_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (marketplace_layout_id) REFERENCES layout_marketplace_layouts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout schedules - Scheduled publish/unpublish
CREATE TABLE IF NOT EXISTS layout_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    schedule_type ENUM('publish', 'unpublish') NOT NULL,
    scheduled_at DATETIME NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_schedule_type (schedule_type),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_is_completed (is_completed),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout section templates - Reusable section templates
CREATE TABLE IF NOT EXISTS layout_section_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    section_data JSON NOT NULL,
    category VARCHAR(100) NULL,
    tags JSON NULL,
    usage_count INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_usage_count (usage_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout variables - Layout variables/placeholders
CREATE TABLE IF NOT EXISTS layout_variables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variable_name VARCHAR(255) UNIQUE NOT NULL,
    variable_value TEXT NULL,
    variable_type VARCHAR(50) DEFAULT 'string',
    description TEXT NULL,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_variable_name (variable_name),
    INDEX idx_variable_type (variable_type),
    INDEX idx_is_system (is_system)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout approvals - Approval workflow tracking
CREATE TABLE IF NOT EXISTS layout_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_reviewer_id (reviewer_id),
    INDEX idx_status (status),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Layout error log - Error logging
CREATE TABLE IF NOT EXISTS layout_error_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_id INT NULL,
    error_type VARCHAR(50) NOT NULL,
    error_message TEXT NOT NULL,
    error_data JSON NULL,
    page_name VARCHAR(255) NULL,
    user_id INT NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layout_id (layout_id),
    INDEX idx_error_type (error_type),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (layout_id) REFERENCES layout_definitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DESIGN SYSTEM & TEMPLATE MANAGEMENT TABLES
-- ============================================

-- Element templates - Individual UI element templates
CREATE TABLE IF NOT EXISTS layout_element_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    element_type ENUM('button', 'card', 'input', 'label', 'badge', 'date_picker', 'color_picker', 'select', 'checkbox', 'radio', 'table', 'table_tabs', 'pagination', 'breadcrumbs', 'tabs', 'alert', 'toast', 'modal', 'tooltip', 'progress', 'grid', 'container', 'section', 'sidebar', 'header', 'footer') NOT NULL,
    category VARCHAR(100) NULL,
    html TEXT NOT NULL,
    css TEXT NULL,
    js TEXT NULL,
    custom_code JSON NULL,
    animations JSON NULL,
    properties JSON NULL,
    variants JSON NULL,
    tags JSON NULL,
    accessibility_data JSON NULL,
    validation_status ENUM('pending', 'passed', 'failed', 'warning') DEFAULT 'pending',
    performance_score INT NULL,
    usage_count INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_element_type (element_type),
    INDEX idx_category (category),
    INDEX idx_validation_status (validation_status),
    INDEX idx_is_published (is_published),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Design systems - Design system definitions with hierarchical structure
CREATE TABLE IF NOT EXISTS layout_design_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    parent_design_system_id INT NULL,
    theme_data JSON NULL,
    performance_settings JSON NULL,
    accessibility_settings JSON NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 0,
    version VARCHAR(50) DEFAULT '1.0.0',
    tags JSON NULL,
    category VARCHAR(100) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_parent_design_system_id (parent_design_system_id),
    INDEX idx_is_default (is_default),
    INDEX idx_is_published (is_published),
    INDEX idx_category (category),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (parent_design_system_id) REFERENCES layout_design_systems(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Design system elements - Links design systems to element templates
CREATE TABLE IF NOT EXISTS layout_design_system_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    design_system_id INT NOT NULL,
    element_template_id INT NOT NULL,
    is_override TINYINT(1) DEFAULT 0,
    override_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_design_system_id (design_system_id),
    INDEX idx_element_template_id (element_template_id),
    INDEX idx_is_override (is_override),
    UNIQUE KEY unique_design_system_element (design_system_id, element_template_id),
    FOREIGN KEY (design_system_id) REFERENCES layout_design_systems(id) ON DELETE CASCADE,
    FOREIGN KEY (element_template_id) REFERENCES layout_element_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Element template versions - Full version history for templates
CREATE TABLE IF NOT EXISTS layout_element_template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    element_template_id INT NOT NULL,
    version VARCHAR(50) NOT NULL,
    template_data JSON NOT NULL,
    change_description TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_element_template_id (element_template_id),
    INDEX idx_version (version),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (element_template_id) REFERENCES layout_element_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template exports - Export metadata and files
CREATE TABLE IF NOT EXISTS layout_template_exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    export_name VARCHAR(255) NOT NULL,
    export_type ENUM('template', 'design_system', 'collection') NOT NULL,
    export_data JSON NOT NULL,
    dependencies JSON NULL,
    preview_images JSON NULL,
    metadata JSON NULL,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_export_name (export_name),
    INDEX idx_export_type (export_type),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Component templates - Cross-component template associations
CREATE TABLE IF NOT EXISTS layout_component_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    element_template_id INT NULL,
    design_system_id INT NULL,
    template_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_element_template_id (element_template_id),
    INDEX idx_design_system_id (design_system_id),
    FOREIGN KEY (element_template_id) REFERENCES layout_element_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (design_system_id) REFERENCES layout_design_systems(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI processing queue - AI image processing queue
CREATE TABLE IF NOT EXISTS layout_ai_processing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(500) NOT NULL,
    image_type VARCHAR(50) NULL,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    ai_service VARCHAR(50) NULL,
    processing_result JSON NULL,
    error_message TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_processing_status (processing_status),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collaboration sessions - Real-time collaboration sessions
CREATE TABLE IF NOT EXISTS layout_collaboration_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('element_template', 'design_system') NOT NULL,
    resource_id INT NOT NULL,
    user_id INT NOT NULL,
    session_data JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_activity_at (last_activity_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collaboration comments - Comments and discussions
CREATE TABLE IF NOT EXISTS layout_collaboration_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('element_template', 'design_system') NOT NULL,
    resource_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    comment_text TEXT NOT NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_comment_id (parent_comment_id),
    INDEX idx_is_resolved (is_resolved),
    FOREIGN KEY (parent_comment_id) REFERENCES layout_collaboration_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval workflows - Approval workflow tracking
CREATE TABLE IF NOT EXISTS layout_approval_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('element_template', 'design_system') NOT NULL,
    resource_id INT NOT NULL,
    workflow_name VARCHAR(255) NOT NULL,
    workflow_steps JSON NOT NULL,
    current_step INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions - Advanced permission system
CREATE TABLE IF NOT EXISTS layout_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('element_template', 'design_system', 'collection') NOT NULL,
    resource_id INT NULL,
    user_id INT NULL,
    role_id INT NULL,
    permission VARCHAR(100) NOT NULL,
    is_granted TINYINT(1) DEFAULT 1,
    ip_restrictions JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission (permission),
    INDEX idx_is_granted (is_granted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs - Audit trail
CREATE TABLE IF NOT EXISTS layout_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50) NULL,
    resource_id INT NULL,
    action_details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics events - Analytics tracking
CREATE TABLE IF NOT EXISTS layout_analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50) NULL,
    resource_id INT NULL,
    user_id INT NULL,
    session_id VARCHAR(255) NULL,
    event_data JSON NULL,
    performance_metrics JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test results - Test results storage
CREATE TABLE IF NOT EXISTS layout_test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_type ENUM('visual_regression', 'cross_browser', 'accessibility', 'performance', 'security') NOT NULL,
    resource_type VARCHAR(50) NULL,
    resource_id INT NULL,
    test_data JSON NOT NULL,
    test_status ENUM('passed', 'failed', 'warning') NOT NULL,
    test_report JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_type (test_type),
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_test_status (test_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collections - Template collections/folders
CREATE TABLE IF NOT EXISTS layout_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    parent_collection_id INT NULL,
    collection_type ENUM('folder', 'smart_collection') DEFAULT 'folder',
    filter_rules JSON NULL,
    is_favorite TINYINT(1) DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_parent_collection_id (parent_collection_id),
    INDEX idx_collection_type (collection_type),
    INDEX idx_is_favorite (is_favorite),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (parent_collection_id) REFERENCES layout_collections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection items - Items in collections
CREATE TABLE IF NOT EXISTS layout_collection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    item_type ENUM('element_template', 'design_system') NOT NULL,
    item_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_collection_id (collection_id),
    INDEX idx_item_type (item_type),
    INDEX idx_item_id (item_id),
    UNIQUE KEY unique_collection_item (collection_id, item_type, item_id),
    FOREIGN KEY (collection_id) REFERENCES layout_collections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starter kits - Starter kit definitions
CREATE TABLE IF NOT EXISTS layout_starter_kits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    kit_type VARCHAR(100) NULL,
    industry VARCHAR(100) NULL,
    kit_data JSON NOT NULL,
    preview_image VARCHAR(500) NULL,
    is_featured TINYINT(1) DEFAULT 0,
    usage_count INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_kit_type (kit_type),
    INDEX idx_industry (industry),
    INDEX idx_is_featured (is_featured),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulk operations - Bulk operation tracking
CREATE TABLE IF NOT EXISTS layout_bulk_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_type VARCHAR(100) NOT NULL,
    operation_data JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    progress INT DEFAULT 0,
    total_items INT DEFAULT 0,
    processed_items INT DEFAULT 0,
    error_log JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_operation_type (operation_type),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search index - Search index for AI-powered search
CREATE TABLE IF NOT EXISTS layout_search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(50) NOT NULL,
    resource_id INT NOT NULL,
    search_vector TEXT NULL,
    fulltext_content TEXT NULL,
    metadata JSON NULL,
    last_indexed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_last_indexed_at (last_indexed_at),
    FULLTEXT idx_fulltext (fulltext_content),
    UNIQUE KEY unique_resource (resource_type, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

