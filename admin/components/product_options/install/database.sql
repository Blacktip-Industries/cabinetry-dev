-- Product Options Component Database Schema
-- All tables prefixed with product_options_ for isolation
-- Version: 1.0.0

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS product_options_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS product_options_parameters (
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

-- Datatypes table (registered datatype definitions)
CREATE TABLE IF NOT EXISTS product_options_datatypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datatype_key VARCHAR(100) UNIQUE NOT NULL,
    datatype_name VARCHAR(255) NOT NULL,
    description TEXT,
    config_schema JSON NULL,
    render_function VARCHAR(255) NULL,
    js_handler VARCHAR(255) NULL,
    validation_rules JSON NULL,
    default_config JSON NULL,
    is_builtin TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_datatype_key (datatype_key),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Option groups table (for organizing options)
CREATE TABLE IF NOT EXISTS product_options_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_options_groups(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent_id (parent_id),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Options table (main options table)
CREATE TABLE IF NOT EXISTS product_options_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    datatype_id INT NOT NULL,
    group_id INT NULL,
    config JSON NOT NULL,
    is_required TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    pricing_enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (datatype_id) REFERENCES product_options_datatypes(id) ON DELETE RESTRICT,
    FOREIGN KEY (group_id) REFERENCES product_options_groups(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_datatype_id (datatype_id),
    INDEX idx_group_id (group_id),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Option values table (for static options like dropdown, radio, checkbox)
CREATE TABLE IF NOT EXISTS product_options_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_id INT NOT NULL,
    value_key VARCHAR(255) NOT NULL,
    value_label VARCHAR(255) NOT NULL,
    value_data TEXT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES product_options_options(id) ON DELETE CASCADE,
    INDEX idx_option_id (option_id),
    INDEX idx_value_key (value_key),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom queries table (for database-driven options)
CREATE TABLE IF NOT EXISTS product_options_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    query_sql TEXT NOT NULL,
    parameter_placeholders JSON NULL,
    result_value_column VARCHAR(255) NOT NULL,
    result_label_column VARCHAR(255) NULL,
    result_data_columns JSON NULL,
    validation_rules JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Option queries relationship (link options to queries)
CREATE TABLE IF NOT EXISTS product_options_option_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_id INT NOT NULL,
    query_id INT NOT NULL,
    parameter_mapping JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES product_options_options(id) ON DELETE CASCADE,
    FOREIGN KEY (query_id) REFERENCES product_options_queries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_option_query (option_id, query_id),
    INDEX idx_option_id (option_id),
    INDEX idx_query_id (query_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conditional logic rules table
CREATE TABLE IF NOT EXISTS product_options_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_id INT NOT NULL,
    rule_type VARCHAR(50) NOT NULL,
    rule_config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES product_options_options(id) ON DELETE CASCADE,
    INDEX idx_option_id (option_id),
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pricing formulas table
CREATE TABLE IF NOT EXISTS product_options_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_id INT NOT NULL,
    formula TEXT NOT NULL,
    formula_type VARCHAR(50) DEFAULT 'expression',
    variables JSON NULL,
    conditions JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES product_options_options(id) ON DELETE CASCADE,
    INDEX idx_option_id (option_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Option templates table (reusable option configurations)
CREATE TABLE IF NOT EXISTS product_options_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    template_data JSON NOT NULL,
    category VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

