-- Order Management Component Database Schema
-- All tables prefixed with order_management_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS order_management_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS order_management_parameters (
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
CREATE TABLE IF NOT EXISTS order_management_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES order_management_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- WORKFLOW & STATUS MANAGEMENT
-- ============================================

-- Workflows table (custom order status workflows)
CREATE TABLE IF NOT EXISTS order_management_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    trigger_conditions JSON NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workflow_name (workflow_name),
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow steps table (individual steps in workflows)
CREATE TABLE IF NOT EXISTS order_management_workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    step_order INT NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    conditions JSON NULL,
    actions JSON NULL,
    requires_approval TINYINT(1) DEFAULT 0,
    approval_role VARCHAR(100) NULL,
    notifications JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES order_management_workflows(id) ON DELETE CASCADE,
    INDEX idx_workflow_id (workflow_id),
    INDEX idx_step_order (step_order),
    INDEX idx_status_name (status_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status history table (enhanced status history)
CREATE TABLE IF NOT EXISTS order_management_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    workflow_id INT NULL,
    workflow_step_id INT NULL,
    old_status VARCHAR(100) NULL,
    new_status VARCHAR(100) NOT NULL,
    changed_by INT NULL,
    change_type ENUM('manual', 'automated', 'workflow') DEFAULT 'manual',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES order_management_workflows(id) ON DELETE SET NULL,
    FOREIGN KEY (workflow_step_id) REFERENCES order_management_workflow_steps(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_new_status (new_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approvals table (approval chain tracking)
CREATE TABLE IF NOT EXISTS order_management_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    workflow_step_id INT NULL,
    approval_type VARCHAR(100) NOT NULL,
    approver_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_step_id) REFERENCES order_management_workflow_steps(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_approver_id (approver_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FULFILLMENT MANAGEMENT
-- ============================================

-- Fulfillments table (fulfillment records per order)
CREATE TABLE IF NOT EXISTS order_management_fulfillments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    fulfillment_status ENUM('pending', 'picking', 'packing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    warehouse_id INT NULL,
    shipping_method VARCHAR(255) NULL,
    tracking_number VARCHAR(255) NULL,
    picking_date DATETIME NULL,
    packing_date DATETIME NULL,
    shipping_date DATETIME NULL,
    delivered_date DATETIME NULL,
    fulfillment_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_fulfillment_status (fulfillment_status),
    INDEX idx_tracking_number (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fulfillment items table (items per fulfillment)
CREATE TABLE IF NOT EXISTS order_management_fulfillment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fulfillment_id INT NOT NULL,
    order_item_id INT NULL,
    product_id INT NULL,
    variant_id INT NULL,
    quantity_fulfilled INT NOT NULL,
    location_picked_from VARCHAR(255) NULL,
    barcode_data VARCHAR(255) NULL,
    scanned_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fulfillment_id) REFERENCES order_management_fulfillments(id) ON DELETE CASCADE,
    INDEX idx_fulfillment_id (fulfillment_id),
    INDEX idx_order_item_id (order_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Picking lists table (picking list generation)
CREATE TABLE IF NOT EXISTS order_management_picking_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NULL,
    picking_date DATE NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to INT NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_picking_date (picking_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Picking items table (items on picking lists)
CREATE TABLE IF NOT EXISTS order_management_picking_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    picking_list_id INT NOT NULL,
    order_id INT NOT NULL,
    order_item_id INT NULL,
    product_id INT NULL,
    variant_id INT NULL,
    location VARCHAR(255) NULL,
    quantity INT NOT NULL,
    sequence_order INT DEFAULT 0,
    picked_status TINYINT(1) DEFAULT 0,
    picker_id INT NULL,
    picked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (picking_list_id) REFERENCES order_management_picking_lists(id) ON DELETE CASCADE,
    INDEX idx_picking_list_id (picking_list_id),
    INDEX idx_order_id (order_id),
    INDEX idx_sequence_order (sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUTOMATION & RULES
-- ============================================

-- Automation rules table (automation rule definitions)
CREATE TABLE IF NOT EXISTS order_management_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    trigger_conditions JSON NOT NULL,
    actions JSON NOT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_name (rule_name),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automation logs table (automation execution history)
CREATE TABLE IF NOT EXISTS order_management_automation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    order_id INT NOT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    actions_executed JSON NULL,
    execution_result ENUM('success', 'failed', 'partial') DEFAULT 'success',
    error_message TEXT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES order_management_automation_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_order_id (order_id),
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RETURNS & REFUNDS
-- ============================================

-- Returns table (return requests)
CREATE TABLE IF NOT EXISTS order_management_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    return_type ENUM('refund', 'exchange', 'store_credit') DEFAULT 'refund',
    reason TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    requested_by INT NULL,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_return_number (return_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Return items table (items in return)
CREATE TABLE IF NOT EXISTS order_management_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    order_item_id INT NULL,
    product_id INT NULL,
    variant_id INT NULL,
    condition ENUM('new', 'used', 'damaged', 'defective') DEFAULT 'new',
    quantity INT NOT NULL,
    disposition ENUM('restock', 'dispose', 'repair') DEFAULT 'restock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (return_id) REFERENCES order_management_returns(id) ON DELETE CASCADE,
    INDEX idx_return_id (return_id),
    INDEX idx_order_item_id (order_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Refunds table (refund tracking)
CREATE TABLE IF NOT EXISTS order_management_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NULL,
    order_id INT NOT NULL,
    transaction_id INT NULL,
    refund_amount DECIMAL(15,2) NOT NULL,
    refund_method VARCHAR(100) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (return_id) REFERENCES order_management_returns(id) ON DELETE SET NULL,
    INDEX idx_return_id (return_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MULTI-CHANNEL SUPPORT
-- ============================================

-- Channels table (sales channel definitions)
CREATE TABLE IF NOT EXISTS order_management_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_name VARCHAR(255) NOT NULL,
    channel_type ENUM('online', 'phone', 'in_store', 'marketplace', 'other') NOT NULL,
    configuration JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_channel_name (channel_name),
    INDEX idx_channel_type (channel_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order channels table (channel assignment per order)
CREATE TABLE IF NOT EXISTS order_management_order_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    channel_id INT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES order_management_channels(id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_channel_id (channel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CUSTOM FIELDS & METADATA
-- ============================================

-- Custom fields table (custom field definitions)
CREATE TABLE IF NOT EXISTS order_management_custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_name VARCHAR(255) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'select', 'checkbox', 'json') NOT NULL,
    validation_rules JSON NULL,
    display_settings JSON NULL,
    is_required TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_field_name (field_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order metadata table (custom field values per order)
CREATE TABLE IF NOT EXISTS order_management_order_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES order_management_custom_fields(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_field (order_id, field_id),
    INDEX idx_order_id (order_id),
    INDEX idx_field_id (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORTING & ANALYTICS
-- ============================================

-- Report templates table (saved report configurations)
CREATE TABLE IF NOT EXISTS order_management_report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    filters JSON NULL,
    columns JSON NULL,
    grouping JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_name (report_name),
    INDEX idx_report_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report cache table (cached report results)
CREATE TABLE IF NOT EXISTS order_management_report_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NULL,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    generated_data JSON NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES order_management_report_templates(id) ON DELETE SET NULL,
    INDEX idx_template_id (template_id),
    INDEX idx_cache_key (cache_key),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT & HISTORY
-- ============================================

-- Audit log table (complete audit trail)
CREATE TABLE IF NOT EXISTS order_management_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    user_id INT NULL,
    before_values JSON NULL,
    after_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_action_type (action_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS
-- ============================================

-- Notification templates table (notification templates)
CREATE TABLE IF NOT EXISTS order_management_notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type ENUM('email', 'sms', 'push') DEFAULT 'email',
    subject VARCHAR(255) NULL,
    body TEXT NOT NULL,
    triggers JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_name (template_name),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table (notification history)
CREATE TABLE IF NOT EXISTS order_management_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NULL,
    order_id INT NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    sent_status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at DATETIME NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES order_management_notification_templates(id) ON DELETE SET NULL,
    INDEX idx_template_id (template_id),
    INDEX idx_order_id (order_id),
    INDEX idx_sent_status (sent_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDER ORGANIZATION & MANAGEMENT
-- ============================================

-- Tags table (tag definitions)
CREATE TABLE IF NOT EXISTS order_management_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(255) UNIQUE NOT NULL,
    tag_color VARCHAR(7) NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tag_name (tag_name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order tags table (order tag assignments)
CREATE TABLE IF NOT EXISTS order_management_order_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    tag_id INT NOT NULL,
    assigned_by INT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tag_id) REFERENCES order_management_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_tag (order_id, tag_id),
    INDEX idx_order_id (order_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Priorities table (priority level definitions)
CREATE TABLE IF NOT EXISTS order_management_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    priority_name VARCHAR(100) UNIQUE NOT NULL,
    priority_level INT NOT NULL,
    color_code VARCHAR(7) NULL,
    sla_hours INT NULL,
    auto_assignment_rules JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_priority_name (priority_name),
    INDEX idx_priority_level (priority_level),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order priorities table (order priority assignments)
CREATE TABLE IF NOT EXISTS order_management_order_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    priority_id INT NOT NULL,
    assigned_by INT NULL,
    reason TEXT NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (priority_id) REFERENCES order_management_priorities(id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_priority_id (priority_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDER TEMPLATES
-- ============================================

-- Templates table (order template definitions)
CREATE TABLE IF NOT EXISTS order_management_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    default_items JSON NULL,
    customer_rules JSON NULL,
    shipping_defaults JSON NULL,
    payment_defaults JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_name (template_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template items table (items in templates)
CREATE TABLE IF NOT EXISTS order_management_template_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    product_id INT NULL,
    variant_id INT NULL,
    default_quantity INT DEFAULT 1,
    options JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES order_management_templates(id) ON DELETE CASCADE,
    INDEX idx_template_id (template_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDER SPLITTING & MERGING
-- ============================================

-- Order splits table (order split records)
CREATE TABLE IF NOT EXISTS order_management_order_splits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_order_id INT NOT NULL,
    split_reason TEXT NULL,
    split_date DATETIME NOT NULL,
    split_by_user_id INT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_original_order_id (original_order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order merges table (order merge records)
CREATE TABLE IF NOT EXISTS order_management_order_merges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_order_ids JSON NOT NULL,
    target_order_id INT NOT NULL,
    merge_reason TEXT NULL,
    merge_date DATETIME NOT NULL,
    merged_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target_order_id (target_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COST TRACKING
-- ============================================

-- Order costs table (Cost of Goods Sold tracking)
CREATE TABLE IF NOT EXISTS order_management_order_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_item_id INT NULL,
    product_id INT NULL,
    variant_id INT NULL,
    unit_cost DECIMAL(15,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    cost_source ENUM('inventory', 'manual') DEFAULT 'inventory',
    profit_margin DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_order_item_id (order_item_id),
    INDEX idx_cost_source (cost_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ARCHIVING
-- ============================================

-- Archived orders table (archived order data)
CREATE TABLE IF NOT EXISTS order_management_archived_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    archive_date DATETIME NOT NULL,
    archived_by INT NULL,
    archive_reason VARCHAR(255) NULL,
    restore_date DATETIME NULL,
    order_snapshot JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_archive_date (archive_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Archive rules table (auto-archiving rules)
CREATE TABLE IF NOT EXISTS order_management_archive_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    conditions JSON NOT NULL,
    archive_after_days INT NOT NULL,
    status_requirements JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_name (rule_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COMMUNICATION & NOTES
-- ============================================

-- Communications table (order communication history)
CREATE TABLE IF NOT EXISTS order_management_communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    communication_type ENUM('note', 'email', 'call', 'message') NOT NULL,
    direction ENUM('internal', 'customer', 'vendor') DEFAULT 'internal',
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    author_id INT NULL,
    related_email_id INT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_communication_type (communication_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Communication attachments table (file attachments for communications)
CREATE TABLE IF NOT EXISTS order_management_communication_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    communication_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT NULL,
    uploaded_by INT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (communication_id) REFERENCES order_management_communications(id) ON DELETE CASCADE,
    INDEX idx_communication_id (communication_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FILE ATTACHMENTS
-- ============================================

-- Order attachments table (order file attachments)
CREATE TABLE IF NOT EXISTS order_management_order_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type ENUM('invoice', 'packing_slip', 'label', 'custom') DEFAULT 'custom',
    file_size INT NULL,
    description TEXT NULL,
    uploaded_by INT NULL,
    is_public TINYINT(1) DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_file_type (file_type),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEARCH & INDEXING
-- ============================================

-- Search index table (full-text search index)
CREATE TABLE IF NOT EXISTS order_management_search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    searchable_content TEXT NOT NULL,
    indexed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    search_metadata JSON NULL,
    FULLTEXT KEY ft_searchable_content (searchable_content),
    INDEX idx_order_id (order_id),
    INDEX idx_indexed_at (indexed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved searches table (saved search queries)
CREATE TABLE IF NOT EXISTS order_management_saved_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_name VARCHAR(255) NOT NULL,
    user_id INT NULL,
    search_criteria JSON NOT NULL,
    filters JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    INDEX idx_search_name (search_name),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- API & WEBHOOKS
-- ============================================

-- API keys table (API authentication keys)
CREATE TABLE IF NOT EXISTS order_management_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    permissions JSON NULL,
    rate_limit INT DEFAULT 1000,
    created_by INT NULL,
    expires_at DATETIME NULL,
    last_used DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks table (webhook endpoint definitions)
CREATE TABLE IF NOT EXISTS order_management_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    events JSON NOT NULL,
    authentication_method VARCHAR(100) DEFAULT 'api_key',
    auth_credentials TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_triggered DATETIME NULL,
    failure_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_webhook_name (webhook_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook logs table (webhook delivery history)
CREATE TABLE IF NOT EXISTS order_management_webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    order_id INT NULL,
    event_type VARCHAR(100) NOT NULL,
    request_payload JSON NULL,
    response_status INT NULL,
    response_body TEXT NULL,
    status ENUM('success', 'failed', 'retrying') DEFAULT 'success',
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    FOREIGN KEY (webhook_id) REFERENCES order_management_webhooks(id) ON DELETE CASCADE,
    INDEX idx_webhook_id (webhook_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MIGRATION TRACKING
-- ============================================

-- Migration status table (order migration tracking)
CREATE TABLE IF NOT EXISTS order_management_migration_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    migration_status ENUM('pending', 'migrated', 'skipped') DEFAULT 'pending',
    migrated_at DATETIME NULL,
    migrated_by INT NULL,
    migration_notes TEXT NULL,
    errors TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_order_migration (order_id),
    INDEX idx_migration_status (migration_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PERMISSIONS & ROLES
-- ============================================

-- Roles table (custom role definitions)
CREATE TABLE IF NOT EXISTS order_management_roles (
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

-- User roles table (user role assignments)
CREATE TABLE IF NOT EXISTS order_management_user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    FOREIGN KEY (role_id) REFERENCES order_management_roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CACHING & PERFORMANCE
-- ============================================

-- Cache table (general purpose cache)
CREATE TABLE IF NOT EXISTS order_management_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    cache_value TEXT NULL,
    cache_type VARCHAR(100) NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_cache_type (cache_type),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache tags table (cache tag associations)
CREATE TABLE IF NOT EXISTS order_management_cache_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_id INT NOT NULL,
    tag_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (cache_id) REFERENCES order_management_cache(id) ON DELETE CASCADE,
    INDEX idx_cache_id (cache_id),
    INDEX idx_tag_name (tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BACKGROUND JOBS & QUEUE
-- ============================================

-- Jobs table (background job queue)
CREATE TABLE IF NOT EXISTS order_management_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(100) NOT NULL,
    job_data JSON NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    priority INT DEFAULT 0,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    scheduled_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_message TEXT NULL,
    result JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_type (job_type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job logs table (job execution history)
CREATE TABLE IF NOT EXISTS order_management_job_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    execution_status ENUM('started', 'completed', 'failed') NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    execution_time DECIMAL(10,3) NULL,
    memory_usage INT NULL,
    error_details TEXT NULL,
    FOREIGN KEY (job_id) REFERENCES order_management_jobs(id) ON DELETE CASCADE,
    INDEX idx_job_id (job_id),
    INDEX idx_execution_status (execution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PRINTING & DOCUMENTS
-- ============================================

-- Document templates table (document template definitions)
CREATE TABLE IF NOT EXISTS order_management_document_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    document_type ENUM('invoice', 'packing_slip', 'label', 'report', 'custom') NOT NULL,
    template_content TEXT NOT NULL,
    variables_mapping JSON NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_name (template_name),
    INDEX idx_document_type (document_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document generations table (generated document history)
CREATE TABLE IF NOT EXISTS order_management_document_generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    template_id INT NULL,
    document_type ENUM('invoice', 'packing_slip', 'label', 'report', 'custom') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_format ENUM('PDF', 'HTML') DEFAULT 'PDF',
    generated_at DATETIME NOT NULL,
    generated_by INT NULL,
    is_sent TINYINT(1) DEFAULT 0,
    sent_at DATETIME NULL,
    FOREIGN KEY (template_id) REFERENCES order_management_document_templates(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_template_id (template_id),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DASHBOARD & ANALYTICS
-- ============================================

-- Dashboard widgets table (dashboard widget configurations)
CREATE TABLE IF NOT EXISTS order_management_dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_name VARCHAR(255) NOT NULL,
    widget_type VARCHAR(100) NOT NULL,
    widget_config JSON NULL,
    position INT DEFAULT 0,
    user_id INT NULL,
    is_default TINYINT(1) DEFAULT 0,
    refresh_interval INT DEFAULT 300,
    cache_duration INT DEFAULT 300,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_widget_name (widget_name),
    INDEX idx_user_id (user_id),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard data table (cached dashboard data)
CREATE TABLE IF NOT EXISTS order_management_dashboard_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_id INT NOT NULL,
    data JSON NOT NULL,
    generated_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (widget_id) REFERENCES order_management_dashboard_widgets(id) ON DELETE CASCADE,
    INDEX idx_widget_id (widget_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ERROR HANDLING & LOGGING
-- ============================================

-- Error logs table (error logging)
CREATE TABLE IF NOT EXISTS order_management_error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_level ENUM('error', 'warning', 'info', 'debug') DEFAULT 'error',
    error_message TEXT NOT NULL,
    error_context JSON NULL,
    file VARCHAR(500) NULL,
    line INT NULL,
    function VARCHAR(255) NULL,
    user_id INT NULL,
    order_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_error_level (error_level),
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System logs table (system activity logs)
CREATE TABLE IF NOT EXISTS order_management_system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(100) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details JSON NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_type (log_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PRODUCTION QUEUE MANAGEMENT
-- ============================================

-- Production queue
CREATE TABLE IF NOT EXISTS order_management_production_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    queue_type ENUM('rush', 'normal') NOT NULL,
    queue_position INT NOT NULL,
    paid_at DATETIME NOT NULL,
    entered_queue_at DATETIME NOT NULL,
    payment_order_position INT NOT NULL,
    is_locked TINYINT(1) DEFAULT 0,
    locked_position INT NULL,
    locked_at DATETIME NULL,
    locked_by INT NULL,
    lock_reason TEXT NULL,
    lock_expires_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_order_queue (order_id, queue_type, is_active),
    INDEX idx_queue_type (queue_type),
    INDEX idx_queue_position (queue_position),
    INDEX idx_paid_at (paid_at),
    INDEX idx_payment_order_position (payment_order_position),
    INDEX idx_is_locked (is_locked),
    INDEX idx_locked_position (locked_position),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue delays
CREATE TABLE IF NOT EXISTS order_management_queue_delays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    order_id INT NOT NULL,
    delay_reason_id INT NULL,
    custom_reason TEXT NULL,
    delay_started_at DATETIME NOT NULL,
    delay_resolved_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES order_management_production_queue(id) ON DELETE CASCADE,
    INDEX idx_queue_id (queue_id),
    INDEX idx_order_id (order_id),
    INDEX idx_delay_reason_id (delay_reason_id),
    INDEX idx_delay_started_at (delay_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delay reasons
CREATE TABLE IF NOT EXISTS order_management_delay_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reason_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_custom TINYINT(1) DEFAULT 0,
    usage_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reason_name (reason_name),
    INDEX idx_is_custom (is_custom),
    INDEX idx_usage_count (usage_count),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue history
CREATE TABLE IF NOT EXISTS order_management_queue_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    queue_id INT NOT NULL,
    old_position INT NOT NULL,
    new_position INT NOT NULL,
    moved_by INT NOT NULL,
    delay_reason_id INT NULL,
    custom_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES order_management_production_queue(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_queue_id (queue_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue locks
CREATE TABLE IF NOT EXISTS order_management_queue_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    queue_id INT NOT NULL,
    locked_position INT NOT NULL,
    locked_by INT NOT NULL,
    lock_reason TEXT NOT NULL,
    lock_expires_at DATETIME NULL,
    unlocked_at DATETIME NULL,
    unlocked_by INT NULL,
    unlock_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES order_management_production_queue(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_queue_id (queue_id),
    INDEX idx_locked_position (locked_position),
    INDEX idx_locked_by (locked_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer display rules
CREATE TABLE IF NOT EXISTS order_management_customer_display_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    field_name VARCHAR(100) NOT NULL,
    display_type ENUM('always', 'never', 'conditional') NOT NULL,
    priority INT DEFAULT 0,
    conditions_json TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_field_name (field_name),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue ordering rules
CREATE TABLE IF NOT EXISTS order_management_queue_ordering_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    rule_type ENUM('payment_order', 'need_by_date', 'customer_tier', 'order_value', 'formula', 'hybrid') NOT NULL,
    priority INT DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 1.00,
    conditions_json TEXT NULL,
    config_json TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COLLECTION MANAGEMENT
-- ============================================

-- Business hours
CREATE TABLE IF NOT EXISTS order_management_business_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NOT NULL,
    business_start TIME NOT NULL,
    business_end TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_day (day_of_week),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection windows (per day of week)
CREATE TABLE IF NOT EXISTS order_management_collection_windows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NOT NULL,
    window_start TIME NOT NULL,
    window_end TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom office hours (for specific dates)
CREATE TABLE IF NOT EXISTS order_management_custom_office_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    specific_date DATE NOT NULL,
    business_start TIME NULL,
    business_end TIME NULL,
    is_out_of_office TINYINT(1) DEFAULT 0,
    reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (specific_date),
    INDEX idx_specific_date (specific_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom collection windows (for specific dates)
CREATE TABLE IF NOT EXISTS order_management_custom_collection_windows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    specific_date DATE NOT NULL,
    window_start TIME NOT NULL,
    window_end TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_specific_date (specific_date),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection settings
CREATE TABLE IF NOT EXISTS order_management_collection_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Early Bird availability
CREATE TABLE IF NOT EXISTS order_management_early_bird_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NULL,
    specific_date DATE NULL,
    is_available TINYINT(1) DEFAULT 1,
    max_hours_before TIME NULL,
    time_window_start TIME NULL,
    time_window_end TIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_specific_date (specific_date),
    INDEX idx_is_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- After Hours availability
CREATE TABLE IF NOT EXISTS order_management_after_hours_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NULL,
    specific_date DATE NULL,
    is_available TINYINT(1) DEFAULT 1,
    max_hours_after TIME NULL,
    time_window_start TIME NULL,
    time_window_end TIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_specific_date (specific_date),
    INDEX idx_is_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection reminders
CREATE TABLE IF NOT EXISTS order_management_collection_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    reminder_stage VARCHAR(50) NOT NULL,
    hours_before INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    channels_json TEXT NULL,
    status ENUM('pending', 'sent', 'skipped', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection history
CREATE TABLE IF NOT EXISTS order_management_collection_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_data_json TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection notifications
CREATE TABLE IF NOT EXISTS order_management_collection_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    channels_json TEXT NULL,
    message_template VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection capacity
CREATE TABLE IF NOT EXISTS order_management_collection_capacity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time_slot_start TIME NOT NULL,
    time_slot_end TIME NOT NULL,
    max_capacity INT NOT NULL,
    current_bookings INT DEFAULT 0,
    capacity_override TINYINT(1) DEFAULT 0,
    override_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (date, time_slot_start, time_slot_end),
    INDEX idx_date (date),
    INDEX idx_time_slot_start (time_slot_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection capacity rules
CREATE TABLE IF NOT EXISTS order_management_collection_capacity_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_type ENUM('default', 'day_of_week', 'specific_date', 'time_range') NOT NULL,
    day_of_week INT NULL,
    specific_date DATE NULL,
    time_start TIME NULL,
    time_end TIME NULL,
    max_capacity INT NOT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection items (for partial collections)
CREATE TABLE IF NOT EXISTS order_management_collection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    quantity_ordered DECIMAL(10,2) NOT NULL,
    quantity_collected DECIMAL(10,2) DEFAULT 0.00,
    collection_status ENUM('pending', 'partial', 'collected') DEFAULT 'pending',
    condition_status ENUM('good', 'damaged', 'missing', 'wrong_item') DEFAULT 'good',
    notes TEXT NULL,
    collected_at DATETIME NULL,
    collected_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_order_item_id (order_item_id),
    INDEX idx_collection_status (collection_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection locations
CREATE TABLE IF NOT EXISTS order_management_collection_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection staff
CREATE TABLE IF NOT EXISTS order_management_collection_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection staff assignments
CREATE TABLE IF NOT EXISTS order_management_collection_staff_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    staff_id INT NOT NULL,
    assigned_at DATETIME NOT NULL,
    assigned_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_assigned_at (assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection staff availability
CREATE TABLE IF NOT EXISTS order_management_collection_staff_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    day_of_week INT NULL,
    specific_date DATE NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_specific_date (specific_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection staff performance
CREATE TABLE IF NOT EXISTS order_management_collection_staff_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    collections_completed INT DEFAULT 0,
    average_collection_time INT DEFAULT 0,
    customer_rating_avg DECIMAL(3,2) DEFAULT 0.00,
    on_time_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection payments
CREATE TABLE IF NOT EXISTS order_management_collection_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_due DECIMAL(15,2) DEFAULT 0.00,
    payment_received DECIMAL(15,2) DEFAULT 0.00,
    payment_method VARCHAR(50) NULL,
    payment_received_at DATETIME NULL,
    payment_received_by INT NULL,
    receipt_number VARCHAR(100) NULL,
    is_reconciled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_is_reconciled (is_reconciled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection payment history
CREATE TABLE IF NOT EXISTS order_management_collection_payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    change_type ENUM('created', 'updated', 'refunded') NOT NULL,
    old_values_json TEXT NULL,
    new_values_json TEXT NULL,
    changed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES order_management_collection_payments(id) ON DELETE CASCADE,
    INDEX idx_payment_id (payment_id),
    INDEX idx_change_type (change_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection window overrides
CREATE TABLE IF NOT EXISTS order_management_collection_window_overrides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    specific_date DATE NOT NULL,
    window_start TIME NOT NULL,
    window_end TIME NOT NULL,
    max_collection_window_hours INT NOT NULL,
    max_collections INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (specific_date),
    INDEX idx_specific_date (specific_date),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection window override slots
CREATE TABLE IF NOT EXISTS order_management_collection_window_override_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    override_id INT NOT NULL,
    slot_start TIME NOT NULL,
    slot_end TIME NOT NULL,
    max_capacity INT NOT NULL,
    current_bookings INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (override_id) REFERENCES order_management_collection_window_overrides(id) ON DELETE CASCADE,
    INDEX idx_override_id (override_id),
    INDEX idx_slot_start (slot_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection window override history
CREATE TABLE IF NOT EXISTS order_management_collection_window_override_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    override_id INT NOT NULL,
    change_type ENUM('created', 'updated', 'deleted') NOT NULL,
    old_values_json TEXT NULL,
    new_values_json TEXT NULL,
    changed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (override_id) REFERENCES order_management_collection_window_overrides(id) ON DELETE CASCADE,
    INDEX idx_override_id (override_id),
    INDEX idx_change_type (change_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COLLECTION ANALYTICS
-- ============================================

-- Collection analytics
CREATE TABLE IF NOT EXISTS order_management_collection_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_collections INT DEFAULT 0,
    completed_collections INT DEFAULT 0,
    cancelled_collections INT DEFAULT 0,
    average_collection_time INT DEFAULT 0,
    average_wait_time INT DEFAULT 0,
    on_time_rate DECIMAL(5,2) DEFAULT 0.00,
    customer_satisfaction_score DECIMAL(3,2) DEFAULT 0.00,
    capacity_utilization DECIMAL(5,2) DEFAULT 0.00,
    peak_time_start TIME NULL,
    peak_time_end TIME NULL,
    total_revenue DECIMAL(15,2) DEFAULT 0.00,
    total_cost DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date),
    INDEX idx_total_collections (total_collections),
    INDEX idx_capacity_utilization (capacity_utilization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection forecasts
CREATE TABLE IF NOT EXISTS order_management_collection_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forecast_date DATE NOT NULL,
    forecast_type ENUM('capacity', 'demand', 'peak_time', 'revenue') NOT NULL,
    forecast_value DECIMAL(10,2) NOT NULL,
    confidence_level DECIMAL(5,2) DEFAULT 0.00,
    forecast_model VARCHAR(100) NULL,
    forecast_parameters_json TEXT NULL,
    actual_value DECIMAL(10,2) NULL,
    accuracy DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_forecast_date (forecast_date),
    INDEX idx_forecast_type (forecast_type),
    INDEX idx_confidence_level (confidence_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection optimization suggestions
CREATE TABLE IF NOT EXISTS order_management_collection_optimization_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suggestion_type ENUM('capacity', 'scheduling', 'staffing', 'pricing', 'time_slot') NOT NULL,
    suggestion_text TEXT NOT NULL,
    expected_impact TEXT NULL,
    priority INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'implemented') DEFAULT 'pending',
    implemented_at DATETIME NULL,
    actual_impact TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_suggestion_type (suggestion_type),
    INDEX idx_priority (priority),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection customer behavior
CREATE TABLE IF NOT EXISTS order_management_collection_customer_behavior (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    preferred_collection_time TIME NULL,
    preferred_collection_day INT NULL,
    average_reschedule_count DECIMAL(5,2) DEFAULT 0.00,
    no_show_rate DECIMAL(5,2) DEFAULT 0.00,
    early_arrival_rate DECIMAL(5,2) DEFAULT 0.00,
    late_arrival_rate DECIMAL(5,2) DEFAULT 0.00,
    collection_frequency INT DEFAULT 0,
    last_collection_date DATE NULL,
    behavior_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer (customer_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_preferred_collection_time (preferred_collection_time),
    INDEX idx_behavior_score (behavior_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection route optimization
CREATE TABLE IF NOT EXISTS order_management_collection_route_optimization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    route_id INT NOT NULL,
    location_sequence TEXT NULL,
    total_distance DECIMAL(10,2) DEFAULT 0.00,
    total_time INT DEFAULT 0,
    time_savings INT DEFAULT 0,
    optimization_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_route_id (route_id),
    INDEX idx_optimization_score (optimization_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COLLECTION AUTOMATION
-- ============================================

-- Collection automation rules
CREATE TABLE IF NOT EXISTS order_management_collection_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('scheduling', 'reminder', 'capacity', 'conflict', 'communication', 'verification', 'feedback', 'violation', 'pricing', 'staff_assignment', 'route', 'quality', 'reporting', 'integration', 'exception') NOT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    conditions_json TEXT NULL,
    actions_json TEXT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_trigger_event (trigger_event),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection workflows
CREATE TABLE IF NOT EXISTS order_management_collection_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    workflow_type ENUM('collection_scheduling', 'reminder_sequence', 'verification', 'feedback', 'violation_tracking') NOT NULL,
    steps_json TEXT NULL,
    conditional_logic_json TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workflow_type (workflow_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection automation log
CREATE TABLE IF NOT EXISTS order_management_collection_automation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NULL,
    workflow_id INT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    order_id INT NOT NULL,
    execution_status ENUM('success', 'failed', 'skipped') NOT NULL,
    execution_result TEXT NULL,
    execution_time INT DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rule_id (rule_id),
    INDEX idx_workflow_id (workflow_id),
    INDEX idx_trigger_event (trigger_event),
    INDEX idx_order_id (order_id),
    INDEX idx_execution_status (execution_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SMS GATEWAY SYSTEM (Global Component)
-- ============================================

-- SMS providers
CREATE TABLE IF NOT EXISTS sms_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    api_endpoint VARCHAR(255) NULL,
    api_key VARCHAR(255) NULL,
    api_secret VARCHAR(255) NULL,
    sender_id VARCHAR(50) NULL,
    config_json TEXT NULL,
    cost_per_sms DECIMAL(10,4) DEFAULT 0.0000,
    currency VARCHAR(3) DEFAULT 'AUD',
    test_mode TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_name (provider_name),
    INDEX idx_is_active (is_active),
    INDEX idx_is_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS templates
CREATE TABLE IF NOT EXISTS sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_code VARCHAR(100) UNIQUE NOT NULL,
    message TEXT NOT NULL,
    variables_json TEXT NULL,
    character_count INT DEFAULT 0,
    segment_count INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_code (template_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS queue
CREATE TABLE IF NOT EXISTS sms_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NULL,
    to_phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    template_id INT NULL,
    variables_json TEXT NULL,
    sender_id VARCHAR(50) NULL,
    priority INT DEFAULT 5,
    status ENUM('pending', 'scheduled', 'sending', 'sent', 'delivered', 'failed', 'cancelled') DEFAULT 'pending',
    scheduled_at DATETIME NULL,
    send_at DATETIME NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    failed_at DATETIME NULL,
    failure_reason TEXT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    cost DECIMAL(10,4) NULL,
    provider_message_id VARCHAR(255) NULL,
    component_name VARCHAR(100) NULL,
    component_reference_id INT NULL,
    timezone VARCHAR(50) NULL,
    schedule_type ENUM('immediate', 'scheduled', 'recurring') DEFAULT 'immediate',
    recurring_config_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES sms_providers(id) ON DELETE SET NULL,
    INDEX idx_provider_id (provider_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_to_phone (to_phone),
    INDEX idx_component_name (component_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS history
CREATE TABLE IF NOT EXISTS sms_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NULL,
    provider_id INT NULL,
    to_phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    character_count INT DEFAULT 0,
    segment_count INT DEFAULT 1,
    status ENUM('sent', 'delivered', 'failed') NOT NULL,
    cost DECIMAL(10,4) DEFAULT 0.0000,
    provider_message_id VARCHAR(255) NULL,
    delivery_receipt_json TEXT NULL,
    component_name VARCHAR(100) NULL,
    component_reference_id INT NULL,
    sent_at DATETIME NOT NULL,
    delivered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_id (provider_id),
    INDEX idx_status (status),
    INDEX idx_to_phone (to_phone),
    INDEX idx_component_name (component_name),
    INDEX idx_sent_at (sent_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS blacklist
CREATE TABLE IF NOT EXISTS sms_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    reason TEXT NULL,
    blacklisted_by INT NULL,
    blacklisted_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS opt-outs
CREATE TABLE IF NOT EXISTS sms_opt_outs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    phone_number VARCHAR(20) NOT NULL,
    opt_out_type ENUM('all', 'marketing', 'transactional', 'reminders') DEFAULT 'all',
    opted_out_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_opt_out_type (opt_out_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS conversations
CREATE TABLE IF NOT EXISTS sms_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    customer_id INT NULL,
    last_message_at DATETIME NOT NULL,
    message_count INT DEFAULT 0,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_last_message_at (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS messages
CREATE TABLE IF NOT EXISTS sms_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    queue_id INT NULL,
    direction ENUM('inbound', 'outbound') NOT NULL,
    to_phone VARCHAR(20) NOT NULL,
    from_phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'delivered', 'failed', 'received') NOT NULL,
    provider_message_id VARCHAR(255) NULL,
    received_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES sms_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_queue_id (queue_id),
    INDEX idx_direction (direction),
    INDEX idx_to_phone (to_phone),
    INDEX idx_from_phone (from_phone),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS auto-responses
CREATE TABLE IF NOT EXISTS sms_auto_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(50) UNIQUE NOT NULL,
    response_type ENUM('template', 'command', 'action') NOT NULL,
    response_template_id INT NULL,
    response_message TEXT NULL,
    action_type VARCHAR(100) NULL,
    action_config_json TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_keyword (keyword),
    INDEX idx_response_type (response_type),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS commands
CREATE TABLE IF NOT EXISTS sms_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    command_name VARCHAR(50) UNIQUE NOT NULL,
    command_description TEXT NULL,
    handler_function VARCHAR(255) NOT NULL,
    requires_auth TINYINT(1) DEFAULT 1,
    component_name VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_command_name (command_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS consents
CREATE TABLE IF NOT EXISTS sms_consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    phone_number VARCHAR(20) NOT NULL,
    consent_type ENUM('marketing', 'transactional', 'reminders', 'all') NOT NULL,
    consent_method ENUM('web_form', 'sms_keyword', 'phone_call', 'email', 'in_person') NOT NULL,
    consent_date DATETIME NOT NULL,
    consent_ip VARCHAR(45) NULL,
    consent_user_agent TEXT NULL,
    consent_text TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    revoked_at DATETIME NULL,
    revoked_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_consent_type (consent_type),
    INDEX idx_consent_date (consent_date),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS compliance checks
CREATE TABLE IF NOT EXISTS sms_compliance_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sms_id INT NOT NULL,
    check_type ENUM('spam_words', 'character_limit', 'sender_id', 'opt_out', 'consent') NOT NULL,
    check_result ENUM('pass', 'fail', 'warning') NOT NULL,
    check_details TEXT NULL,
    checked_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_id (sms_id),
    INDEX idx_check_type (check_type),
    INDEX idx_check_result (check_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS sender IDs
CREATE TABLE IF NOT EXISTS sms_sender_ids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id VARCHAR(50) UNIQUE NOT NULL,
    provider_id INT NULL,
    registration_status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    registered_at DATETIME NULL,
    expires_at DATETIME NULL,
    registration_documentation TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES sms_providers(id) ON DELETE SET NULL,
    INDEX idx_provider_id (provider_id),
    INDEX idx_registration_status (registration_status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS campaigns
CREATE TABLE IF NOT EXISTS sms_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    template_id INT NULL,
    recipient_list_json TEXT NULL,
    scheduled_at DATETIME NULL,
    status ENUM('draft', 'scheduled', 'sending', 'completed', 'cancelled') DEFAULT 'draft',
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    total_cost DECIMAL(10,4) DEFAULT 0.0000,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS spending limits
CREATE TABLE IF NOT EXISTS sms_spending_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_type ENUM('daily', 'weekly', 'monthly', 'custom') DEFAULT 'monthly',
    cycle_start_date DATE NOT NULL,
    cycle_end_date DATE NULL,
    soft_limit DECIMAL(10,2) DEFAULT 0.00,
    hard_limit DECIMAL(10,2) DEFAULT 0.00,
    current_spending DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'AUD',
    soft_limit_notified TINYINT(1) DEFAULT 0,
    hard_limit_reached TINYINT(1) DEFAULT 0,
    hard_limit_notified TINYINT(1) DEFAULT 0,
    grace_period_hours INT DEFAULT 0,
    grace_period_ends_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    auto_reset TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cycle_type (cycle_type),
    INDEX idx_cycle_start_date (cycle_start_date),
    INDEX idx_cycle_end_date (cycle_end_date),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS spending alerts
CREATE TABLE IF NOT EXISTS sms_spending_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spending_limit_id INT NOT NULL,
    alert_percentage DECIMAL(5,2) NOT NULL,
    alert_type ENUM('email', 'in_app', 'dashboard', 'all') DEFAULT 'all',
    notification_sent TINYINT(1) DEFAULT 0,
    notification_sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (spending_limit_id) REFERENCES sms_spending_limits(id) ON DELETE CASCADE,
    INDEX idx_spending_limit_id (spending_limit_id),
    INDEX idx_alert_percentage (alert_percentage),
    INDEX idx_notification_sent (notification_sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS spending history
CREATE TABLE IF NOT EXISTS sms_spending_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spending_limit_id INT NOT NULL,
    cycle_start_date DATE NOT NULL,
    cycle_end_date DATE NOT NULL,
    total_spending DECIMAL(10,2) DEFAULT 0.00,
    sms_count INT DEFAULT 0,
    soft_limit DECIMAL(10,2) DEFAULT 0.00,
    hard_limit DECIMAL(10,2) DEFAULT 0.00,
    soft_limit_reached TINYINT(1) DEFAULT 0,
    hard_limit_reached TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (spending_limit_id) REFERENCES sms_spending_limits(id) ON DELETE CASCADE,
    INDEX idx_spending_limit_id (spending_limit_id),
    INDEX idx_cycle_start_date (cycle_start_date),
    INDEX idx_cycle_end_date (cycle_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS spending overrides
CREATE TABLE IF NOT EXISTS sms_spending_overrides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spending_limit_id INT NOT NULL,
    override_type ENUM('increase_limit', 'extend_cycle', 'reset_spending', 'allow_continued_sending') NOT NULL,
    override_value DECIMAL(10,2) NULL,
    override_reason TEXT NULL,
    override_expires_at DATETIME NULL,
    overridden_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (spending_limit_id) REFERENCES sms_spending_limits(id) ON DELETE CASCADE,
    INDEX idx_spending_limit_id (spending_limit_id),
    INDEX idx_override_type (override_type),
    INDEX idx_override_expires_at (override_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS A/B tests
CREATE TABLE IF NOT EXISTS sms_ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(255) NOT NULL,
    test_type ENUM('message', 'template', 'send_time', 'sender_id') NOT NULL,
    variant_a_id INT NULL,
    variant_b_id INT NULL,
    test_audience_json TEXT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    status ENUM('draft', 'running', 'completed', 'cancelled') DEFAULT 'draft',
    winner_variant ENUM('a', 'b', 'tie') NULL,
    statistical_significance DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_test_type (test_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS A/B test results
CREATE TABLE IF NOT EXISTS sms_ab_test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    variant ENUM('a', 'b') NOT NULL,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    read_count INT DEFAULT 0,
    response_count INT DEFAULT 0,
    conversion_count INT DEFAULT 0,
    delivery_rate DECIMAL(5,2) DEFAULT 0.00,
    read_rate DECIMAL(5,2) DEFAULT 0.00,
    response_rate DECIMAL(5,2) DEFAULT 0.00,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES sms_ab_tests(id) ON DELETE CASCADE,
    INDEX idx_test_id (test_id),
    INDEX idx_variant (variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS template versions
CREATE TABLE IF NOT EXISTS sms_template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    version_number INT NOT NULL,
    message TEXT NOT NULL,
    variables_json TEXT NULL,
    performance_metrics_json TEXT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES sms_templates(id) ON DELETE CASCADE,
    INDEX idx_template_id (template_id),
    INDEX idx_version_number (version_number),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS customer engagement scores
CREATE TABLE IF NOT EXISTS sms_customer_engagement_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    phone_number VARCHAR(20) NOT NULL,
    engagement_score DECIMAL(5,2) DEFAULT 0.00,
    total_sms_received INT DEFAULT 0,
    total_sms_read INT DEFAULT 0,
    total_sms_responded INT DEFAULT 0,
    average_response_time INT DEFAULT 0,
    last_engagement_date DATE NULL,
    engagement_tier ENUM('high', 'medium', 'low', 'none') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_engagement_score (engagement_score),
    INDEX idx_engagement_tier (engagement_tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS optimal send times
CREATE TABLE IF NOT EXISTS sms_optimal_send_times (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    phone_number VARCHAR(20) NOT NULL,
    day_of_week INT NOT NULL,
    optimal_hour INT NOT NULL,
    delivery_rate DECIMAL(5,2) DEFAULT 0.00,
    read_rate DECIMAL(5,2) DEFAULT 0.00,
    response_rate DECIMAL(5,2) DEFAULT 0.00,
    confidence_level DECIMAL(5,2) DEFAULT 0.00,
    sample_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_optimal_hour (optimal_hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS automation workflows
CREATE TABLE IF NOT EXISTS sms_automation_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    trigger_type ENUM('event', 'schedule', 'condition') NOT NULL,
    trigger_config_json TEXT NULL,
    steps_json TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS schedules
CREATE TABLE IF NOT EXISTS sms_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NULL,
    schedule_type ENUM('one_time', 'recurring', 'event_based') NOT NULL,
    scheduled_at DATETIME NOT NULL,
    recurring_config_json TEXT NULL,
    event_trigger VARCHAR(100) NULL,
    status ENUM('pending', 'sent', 'cancelled', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES sms_queue(id) ON DELETE CASCADE,
    INDEX idx_queue_id (queue_id),
    INDEX idx_schedule_type (schedule_type),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS schedule templates
CREATE TABLE IF NOT EXISTS sms_schedule_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type ENUM('reminder', 'notification', 'campaign') NOT NULL,
    schedule_config_json TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

