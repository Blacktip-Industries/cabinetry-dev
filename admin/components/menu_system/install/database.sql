-- Menu System Component Database Schema
-- All tables prefixed with menu_system_ for isolation

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS menu_system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menus table
CREATE TABLE IF NOT EXISTS menu_system_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    icon_svg_path TEXT DEFAULT NULL,
    url VARCHAR(500) NOT NULL,
    parent_id INT DEFAULT NULL,
    section_heading_id INT DEFAULT NULL,
    menu_order INT DEFAULT 0,
    page_identifier VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    menu_type VARCHAR(50) DEFAULT 'admin',
    is_section_heading TINYINT(1) DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_id (parent_id),
    INDEX idx_section_heading_id (section_heading_id),
    INDEX idx_menu_type (menu_type),
    INDEX idx_is_active (is_active),
    INDEX idx_menu_order (menu_order),
    INDEX idx_page_identifier (page_identifier),
    FOREIGN KEY (parent_id) REFERENCES menu_system_menus(id) ON DELETE CASCADE,
    FOREIGN KEY (section_heading_id) REFERENCES menu_system_menus(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Icons table
CREATE TABLE IF NOT EXISTS menu_system_icons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    svg_path TEXT NOT NULL,
    description TEXT,
    category VARCHAR(50),
    display_order INT DEFAULT 0,
    style VARCHAR(20) DEFAULT NULL,
    fill TINYINT(1) DEFAULT NULL,
    weight INT DEFAULT NULL,
    grade INT DEFAULT NULL,
    opsz INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_order (display_order),
    INDEX idx_style (style),
    INDEX idx_fill (fill)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table
CREATE TABLE IF NOT EXISTS menu_system_parameters (
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

-- File backups table (for file protection feature)
CREATE TABLE IF NOT EXISTS menu_system_file_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    backup_content LONGTEXT NOT NULL,
    backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_by VARCHAR(100) DEFAULT 'menu_system',
    reason VARCHAR(255) DEFAULT 'Page identifier update',
    restored TINYINT(1) DEFAULT 0,
    INDEX idx_file_path (file_path),
    INDEX idx_timestamp (backup_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
