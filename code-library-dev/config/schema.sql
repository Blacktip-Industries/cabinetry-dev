-- Code Library Database Schema
-- This file creates all tables for the reusable code library system

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS code_library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE code_library_db;

-- Categories table
CREATE TABLE IF NOT EXISTS code_library_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT NULL,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_id (parent_id),
    INDEX idx_order (order_index),
    FOREIGN KEY (parent_id) REFERENCES code_library_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Features table
CREATE TABLE IF NOT EXISTS code_library_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_order (order_index),
    FOREIGN KEY (category_id) REFERENCES code_library_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Components table
CREATE TABLE IF NOT EXISTS code_library_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    component_type ENUM('page', 'function', 'class', 'schema', 'config', 'asset') NOT NULL,
    description TEXT,
    usage_instructions TEXT,
    code_content LONGTEXT,
    file_path VARCHAR(500),
    version VARCHAR(50) DEFAULT '1.0.0',
    status ENUM('draft', 'testing', 'stable', 'deprecated') DEFAULT 'draft',
    is_production_ready TINYINT(1) DEFAULT 0,
    tested_on_projects INT DEFAULT 0,
    author VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    requires_php_version VARCHAR(20),
    requires_database TINYINT(1) DEFAULT 0,
    known_issues TEXT,
    last_tested_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feature_id (feature_id),
    INDEX idx_status (status),
    INDEX idx_production_ready (is_production_ready),
    INDEX idx_component_type (component_type),
    FULLTEXT idx_search (name, description, usage_instructions),
    FOREIGN KEY (feature_id) REFERENCES code_library_features(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dependencies table
CREATE TABLE IF NOT EXISTS code_library_dependencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    required_component_id INT NULL,
    dependency_type ENUM('file', 'function', 'class', 'database_table') NOT NULL,
    dependency_name VARCHAR(255) NOT NULL,
    is_required TINYINT(1) DEFAULT 1,
    notes TEXT,
    INDEX idx_component_id (component_id),
    INDEX idx_required_component_id (required_component_id),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE,
    FOREIGN KEY (required_component_id) REFERENCES code_library_components(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Packages table
CREATE TABLE IF NOT EXISTS code_library_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    version VARCHAR(50) DEFAULT '1.0.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Package components junction table
CREATE TABLE IF NOT EXISTS code_library_package_components (
    package_id INT NOT NULL,
    component_id INT NOT NULL,
    install_order INT DEFAULT 0,
    PRIMARY KEY (package_id, component_id),
    INDEX idx_install_order (install_order),
    FOREIGN KEY (package_id) REFERENCES code_library_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags table
CREATE TABLE IF NOT EXISTS code_library_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Component tags junction table
CREATE TABLE IF NOT EXISTS code_library_component_tags (
    component_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (component_id, tag_id),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES code_library_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files table (supporting files for components)
CREATE TABLE IF NOT EXISTS code_library_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_type ENUM('php', 'js', 'css', 'sql', 'config', 'other') NOT NULL,
    file_content LONGTEXT,
    is_main_file TINYINT(1) DEFAULT 0,
    order_index INT DEFAULT 0,
    INDEX idx_component_id (component_id),
    INDEX idx_is_main_file (is_main_file),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Examples table
CREATE TABLE IF NOT EXISTS code_library_examples (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    example_code TEXT,
    order_index INT DEFAULT 0,
    INDEX idx_component_id (component_id),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Installations table
CREATE TABLE IF NOT EXISTS code_library_installations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NULL,
    package_id INT NULL,
    project_name VARCHAR(255) NOT NULL,
    project_path VARCHAR(500),
    installed_version VARCHAR(50),
    installation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_working TINYINT(1) DEFAULT 1,
    issues_found TEXT,
    notes TEXT,
    INDEX idx_component_id (component_id),
    INDEX idx_package_id (package_id),
    INDEX idx_project_name (project_name),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES code_library_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bugs table
CREATE TABLE IF NOT EXISTS code_library_bugs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('reported', 'confirmed', 'fixing', 'fixed', 'closed') DEFAULT 'reported',
    reported_by VARCHAR(255),
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fixed_in_version VARCHAR(50),
    resolution_notes TEXT,
    INDEX idx_component_id (component_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versions table
CREATE TABLE IF NOT EXISTS code_library_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    version_number VARCHAR(50) NOT NULL,
    changelog TEXT,
    bug_fixes TEXT,
    is_stable TINYINT(1) DEFAULT 0,
    code_content LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_component_id (component_id),
    INDEX idx_version_number (version_number),
    INDEX idx_is_stable (is_stable),
    FOREIGN KEY (component_id) REFERENCES code_library_components(id) ON DELETE CASCADE,
    UNIQUE KEY unique_component_version (component_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

