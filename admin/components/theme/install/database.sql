-- Theme Component Database Schema
-- All tables prefixed with theme_ for isolation

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS theme_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores all theme settings)
CREATE TABLE IF NOT EXISTS theme_parameters (
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

-- Themes table (stores theme variants: light, dark, custom)
CREATE TABLE IF NOT EXISTS theme_themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theme_name VARCHAR(100) NOT NULL,
    theme_type ENUM('light', 'dark', 'custom') NOT NULL DEFAULT 'light',
    is_active TINYINT(1) DEFAULT 0,
    config_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_theme_type (theme_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

