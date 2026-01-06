-- Access Component Database Schema
-- All tables prefixed with access_ for isolation
-- Version: 1.0.0
-- Standalone component - no dependencies on existing tables

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS access_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS access_parameters (
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
CREATE TABLE IF NOT EXISTS access_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES access_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account types table (configurable account types)
CREATE TABLE IF NOT EXISTS access_account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    requires_approval TINYINT(1) DEFAULT 0,
    auto_approve TINYINT(1) DEFAULT 0,
    special_requirements JSON NULL,
    registration_workflow JSON NULL,
    custom_validation_hook VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    icon VARCHAR(100) NULL,
    color VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account type fields table (custom fields per account type)
CREATE TABLE IF NOT EXISTS access_account_type_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type_id INT NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    field_type VARCHAR(50) NOT NULL,
    is_required TINYINT(1) DEFAULT 0,
    validation_rules JSON NULL,
    options_json TEXT NULL,
    default_value TEXT NULL,
    placeholder VARCHAR(255) NULL,
    help_text TEXT NULL,
    conditional_logic JSON NULL,
    display_order INT DEFAULT 0,
    section VARCHAR(255) NULL,
    field_group VARCHAR(255) NULL,
    css_class VARCHAR(255) NULL,
    wrapper_class VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES access_account_types(id) ON DELETE CASCADE,
    INDEX idx_account_type_id (account_type_id),
    INDEX idx_field_name (field_name),
    INDEX idx_display_order (display_order),
    INDEX idx_section (section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accounts table (account records)
CREATE TABLE IF NOT EXISTS access_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type_id INT NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_code VARCHAR(100) UNIQUE NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    status ENUM('pending', 'active', 'suspended', 'archived', 'expired') DEFAULT 'pending',
    approved_at DATETIME NULL,
    approved_by INT NULL,
    expiry_date DATE NULL,
    metadata JSON NULL,
    custom_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES access_account_types(id),
    INDEX idx_account_type_id (account_type_id),
    INDEX idx_account_code (account_code),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account data table (custom field data for accounts - EAV pattern)
CREATE TABLE IF NOT EXISTS access_account_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_field_name (field_name),
    UNIQUE KEY unique_account_field (account_id, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table (user records)
CREATE TABLE IF NOT EXISTS access_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    avatar_url VARCHAR(500) NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    language VARCHAR(10) DEFAULT 'en',
    status ENUM('active', 'inactive', 'suspended', 'pending_verification') DEFAULT 'pending_verification',
    email_verified TINYINT(1) DEFAULT 0,
    email_verification_token VARCHAR(100) NULL,
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires DATETIME NULL,
    two_factor_enabled TINYINT(1) DEFAULT 0,
    two_factor_secret VARCHAR(255) NULL,
    backup_codes JSON NULL,
    last_login DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    metadata JSON NULL,
    preferences JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_email_verification_token (email_verification_token),
    INDEX idx_password_reset_token (password_reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User accounts table (many-to-many: users can belong to multiple accounts)
CREATE TABLE IF NOT EXISTS access_user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    role_id INT NULL,
    is_primary_account TINYINT(1) DEFAULT 0,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_account (user_id, account_id),
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table (role definitions)
CREATE TABLE IF NOT EXISTS access_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    is_system_role TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table (permission definitions)
CREATE TABLE IF NOT EXISTS access_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(255) UNIQUE NOT NULL,
    permission_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    is_system_permission TINYINT(1) DEFAULT 0,
    parent_permission_id INT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_permission_id) REFERENCES access_permissions(id) ON DELETE SET NULL,
    INDEX idx_permission_key (permission_key),
    INDEX idx_category (category),
    INDEX idx_parent_permission_id (parent_permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role permissions table (role-permission mapping)
CREATE TABLE IF NOT EXISTS access_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES access_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES access_permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User permissions table (custom user permissions - overrides role permissions)
CREATE TABLE IF NOT EXISTS access_user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id INT NULL,
    permission_id INT NOT NULL,
    granted TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES access_permissions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_permission_id (permission_id),
    UNIQUE KEY unique_user_account_permission (user_id, account_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrations table (registration requests for approval workflow)
CREATE TABLE IF NOT EXISTS access_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    submitted_data JSON NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES access_account_types(id),
    INDEX idx_account_type_id (account_type_id),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (session tracking)
CREATE TABLE IF NOT EXISTS access_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    login_type ENUM('frontend', 'backend') DEFAULT 'frontend',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login history table (login audit trail)
CREATE TABLE IF NOT EXISTS access_login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    account_id INT NULL,
    login_type ENUM('frontend', 'backend') DEFAULT 'frontend',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(255) NULL,
    location_data JSON NULL,
    device_info JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflows table (custom workflow definitions)
CREATE TABLE IF NOT EXISTS access_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    workflow_type VARCHAR(100) NOT NULL,
    trigger_events JSON NULL,
    steps JSON NOT NULL,
    conditions JSON NULL,
    actions JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workflow_type (workflow_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Field types table (custom field type definitions)
CREATE TABLE IF NOT EXISTS access_field_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_type_key VARCHAR(100) UNIQUE NOT NULL,
    field_type_name VARCHAR(255) NOT NULL,
    handler_class VARCHAR(255) NULL,
    validation_class VARCHAR(255) NULL,
    render_function VARCHAR(255) NULL,
    is_system TINYINT(1) DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_field_type_key (field_type_key),
    INDEX idx_is_system (is_system)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hooks table (hook/event system for extensibility)
CREATE TABLE IF NOT EXISTS access_hooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hook_name VARCHAR(255) NOT NULL,
    hook_type ENUM('action', 'filter') NOT NULL,
    callback_function VARCHAR(255) NOT NULL,
    priority INT DEFAULT 10,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hook_name (hook_name),
    INDEX idx_hook_type (hook_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email templates table (customizable email templates)
CREATE TABLE IF NOT EXISTS access_email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(255) UNIQUE NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NULL,
    variables JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_key (template_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log table (comprehensive audit logging)
CREATE TABLE IF NOT EXISTS access_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    performed_by INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at),
    INDEX idx_performed_by (performed_by),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table (general messaging system)
CREATE TABLE IF NOT EXISTS access_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NULL,
    to_user_id INT NULL,
    account_id INT NULL,
    message_type ENUM('order', 'quote', 'general', 'direct', 'notification', 'amendment') DEFAULT 'general',
    subject VARCHAR(500),
    message TEXT NOT NULL,
    related_entity_type VARCHAR(100) NULL,
    related_entity_id INT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    is_archived TINYINT(1) DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES access_users(id) ON DELETE SET NULL,
    FOREIGN KEY (to_user_id) REFERENCES access_users(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE CASCADE,
    INDEX idx_to_user (to_user_id),
    INDEX idx_from_user (from_user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_message_type (message_type),
    INDEX idx_related_entity (related_entity_type, related_entity_id),
    INDEX idx_is_read (is_read),
    INDEX idx_is_archived (is_archived),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message attachments table (file attachments for messages)
CREATE TABLE IF NOT EXISTS access_message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    uploaded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES access_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES access_users(id) ON DELETE SET NULL,
    INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat sessions table (chat session management)
CREATE TABLE IF NOT EXISTS access_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    admin_user_id INT NULL,
    status ENUM('waiting', 'active', 'closed', 'transferred') DEFAULT 'waiting',
    subject VARCHAR(500) NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    last_message_at DATETIME NOT NULL,
    is_forwarded_to_customer TINYINT(1) DEFAULT 0,
    forwarded_at DATETIME NULL,
    forwarded_by INT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_user_id) REFERENCES access_users(id) ON DELETE SET NULL,
    FOREIGN KEY (forwarded_by) REFERENCES access_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_status (status),
    INDEX idx_last_message_at (last_message_at),
    INDEX idx_is_forwarded (is_forwarded_to_customer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages table (individual chat messages)
CREATE TABLE IF NOT EXISTS access_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_session_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    sender_type ENUM('user', 'admin', 'system', 'ai') DEFAULT 'user',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    is_edited TINYINT(1) DEFAULT 0,
    edited_at DATETIME NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_session_id) REFERENCES access_chat_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    INDEX idx_chat_session_id (chat_session_id),
    INDEX idx_sender_user_id (sender_user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read),
    INDEX idx_sender_type (sender_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat attachments table (file attachments for chat)
CREATE TABLE IF NOT EXISTS access_chat_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_message_id) REFERENCES access_chat_messages(id) ON DELETE CASCADE,
    INDEX idx_chat_message_id (chat_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin availability table (admin online status)
CREATE TABLE IF NOT EXISTS access_admin_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    is_available TINYINT(1) DEFAULT 0,
    status_message VARCHAR(255) NULL,
    last_active_at DATETIME NOT NULL,
    current_chat_count INT DEFAULT 0,
    max_concurrent_chats INT DEFAULT 5,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    INDEX idx_is_available (is_available),
    INDEX idx_last_active_at (last_active_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table (system notifications)
CREATE TABLE IF NOT EXISTS access_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    account_id INT NULL,
    notification_type ENUM('order_update', 'quote_update', 'message', 'chat', 'system') DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500) NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES access_users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES access_accounts(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_is_read (is_read),
    INDEX idx_notification_type (notification_type),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

