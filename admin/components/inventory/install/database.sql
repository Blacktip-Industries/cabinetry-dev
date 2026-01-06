-- Inventory Component Database Schema
-- All tables prefixed with inventory_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS inventory_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS inventory_parameters (
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
CREATE TABLE IF NOT EXISTS inventory_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES inventory_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ITEM MANAGEMENT
-- ============================================

-- Items table (standalone items, can link to commerce products)
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(100) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    sku VARCHAR(100) UNIQUE NULL,
    category VARCHAR(100) NULL,
    unit_of_measure VARCHAR(50) DEFAULT 'unit',
    is_active TINYINT(1) DEFAULT 1,
    commerce_product_id INT NULL,
    commerce_variant_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_code (item_code),
    INDEX idx_sku (sku),
    INDEX idx_category (category),
    INDEX idx_commerce_product_id (commerce_product_id),
    INDEX idx_commerce_variant_id (commerce_variant_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOCATION MANAGEMENT
-- ============================================

-- Locations table (multi-level hierarchy: warehouse/zone/bin/shelf)
CREATE TABLE IF NOT EXISTS inventory_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_code VARCHAR(100) UNIQUE NOT NULL,
    location_name VARCHAR(255) NOT NULL,
    location_type ENUM('warehouse', 'zone', 'bin', 'shelf', 'other') DEFAULT 'warehouse',
    parent_location_id INT NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postcode VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    contact_name VARCHAR(255) NULL,
    contact_email VARCHAR(255) NULL,
    contact_phone VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_location_id) REFERENCES inventory_locations(id) ON DELETE SET NULL,
    INDEX idx_location_code (location_code),
    INDEX idx_location_type (location_type),
    INDEX idx_parent_location_id (parent_location_id),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STOCK MANAGEMENT
-- ============================================

-- Stock table (stock levels per item/location)
CREATE TABLE IF NOT EXISTS inventory_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    quantity_available INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    quantity_on_order INT DEFAULT 0,
    reorder_point INT DEFAULT 0,
    reorder_quantity INT DEFAULT 0,
    max_stock_level INT NULL,
    last_counted_at DATETIME NULL,
    last_movement_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_item_location (item_id, location_id),
    INDEX idx_item_id (item_id),
    INDEX idx_location_id (location_id),
    INDEX idx_quantity_available (quantity_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MOVEMENT TRACKING
-- ============================================

-- Movements table (complete movement history)
CREATE TABLE IF NOT EXISTS inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment', 'transfer', 'reservation', 'release', 'count') NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(15,4) NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    created_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_location_id (location_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TRANSFER MANAGEMENT
-- ============================================

-- Transfers table (stock transfer requests)
CREATE TABLE IF NOT EXISTS inventory_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transfer_number VARCHAR(100) UNIQUE NOT NULL,
    from_location_id INT NOT NULL,
    to_location_id INT NOT NULL,
    status ENUM('pending', 'approved', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    requested_by INT NULL,
    approved_by INT NULL,
    processed_by INT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    processed_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_location_id) REFERENCES inventory_locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (to_location_id) REFERENCES inventory_locations(id) ON DELETE RESTRICT,
    INDEX idx_transfer_number (transfer_number),
    INDEX idx_from_location_id (from_location_id),
    INDEX idx_to_location_id (to_location_id),
    INDEX idx_status (status),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transfer items table (transfer line items)
CREATE TABLE IF NOT EXISTS inventory_transfer_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transfer_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_requested INT NOT NULL,
    quantity_shipped INT DEFAULT 0,
    quantity_received INT DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transfer_id) REFERENCES inventory_transfers(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_transfer_id (transfer_id),
    INDEX idx_item_id (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ADJUSTMENT MANAGEMENT
-- ============================================

-- Adjustments table (stock adjustment requests)
CREATE TABLE IF NOT EXISTS inventory_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adjustment_number VARCHAR(100) UNIQUE NOT NULL,
    location_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    adjustment_type ENUM('count', 'correction', 'damage', 'expiry', 'other') DEFAULT 'count',
    requested_by INT NULL,
    approved_by INT NULL,
    processed_by INT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    processed_at DATETIME NULL,
    reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE RESTRICT,
    INDEX idx_adjustment_number (adjustment_number),
    INDEX idx_location_id (location_id),
    INDEX idx_status (status),
    INDEX idx_adjustment_type (adjustment_type),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adjustment items table (adjustment line items)
CREATE TABLE IF NOT EXISTS inventory_adjustment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adjustment_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    quantity_change INT NOT NULL,
    unit_cost DECIMAL(15,4) NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (adjustment_id) REFERENCES inventory_adjustments(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_adjustment_id (adjustment_id),
    INDEX idx_item_id (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BARCODE MANAGEMENT
-- ============================================

-- Barcodes table (barcode/QR code management)
CREATE TABLE IF NOT EXISTS inventory_barcodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    barcode_type ENUM('EAN13', 'UPC', 'CODE128', 'QR', 'other') DEFAULT 'CODE128',
    barcode_value VARCHAR(255) UNIQUE NOT NULL,
    qr_code_data TEXT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_barcode_value (barcode_value),
    INDEX idx_barcode_type (barcode_type),
    INDEX idx_is_primary (is_primary),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COST TRACKING
-- ============================================

-- Costs table (cost tracking for FIFO/LIFO/Average)
CREATE TABLE IF NOT EXISTS inventory_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    cost_method ENUM('FIFO', 'LIFO', 'Average') DEFAULT 'Average',
    unit_cost DECIMAL(15,4) NOT NULL,
    quantity INT NOT NULL,
    total_cost DECIMAL(15,4) NOT NULL,
    purchase_date DATE NULL,
    expiry_date DATE NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_location_id (location_id),
    INDEX idx_cost_method (cost_method),
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALERT SYSTEM
-- ============================================

-- Alerts table (configurable alert rules)
CREATE TABLE IF NOT EXISTS inventory_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('low_stock', 'high_stock', 'expiry', 'movement_threshold', 'other') NOT NULL,
    item_id INT NULL,
    location_id INT NULL,
    threshold_value DECIMAL(15,4) NULL,
    threshold_quantity INT NULL,
    alert_email VARCHAR(255) NULL,
    alert_recipients TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_triggered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE,
    INDEX idx_alert_type (alert_type),
    INDEX idx_item_id (item_id),
    INDEX idx_location_id (location_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORTING
-- ============================================

-- Reports table (report configurations and schedules)
CREATE TABLE IF NOT EXISTS inventory_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    parameters TEXT NULL,
    schedule VARCHAR(100) NULL,
    email_recipients TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_generated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

