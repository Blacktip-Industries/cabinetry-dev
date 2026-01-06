-- Commerce Component Database Schema
-- All tables prefixed with commerce_ for isolation
-- Version: 1.0.0

-- ============================================
-- CORE TABLES
-- ============================================

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS commerce_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS commerce_parameters (
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
CREATE TABLE IF NOT EXISTS commerce_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES commerce_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PRODUCT TABLES
-- ============================================

-- Product categories
CREATE TABLE IF NOT EXISTS commerce_product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    parent_id INT NULL,
    image_url VARCHAR(500) NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES commerce_product_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent_id (parent_id),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE IF NOT EXISTS commerce_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    sku VARCHAR(100) UNIQUE NULL,
    description TEXT NULL,
    short_description TEXT NULL,
    base_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    weight DECIMAL(10,3) NULL,
    weight_unit VARCHAR(10) DEFAULT 'kg',
    length DECIMAL(10,2) NULL,
    width DECIMAL(10,2) NULL,
    height DECIMAL(10,2) NULL,
    dimension_unit VARCHAR(10) DEFAULT 'cm',
    category_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_digital TINYINT(1) DEFAULT 0,
    requires_shipping TINYINT(1) DEFAULT 1,
    track_inventory TINYINT(1) DEFAULT 1,
    low_stock_threshold INT DEFAULT 10,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES commerce_product_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_sku (sku),
    INDEX idx_category_id (category_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product variants
CREATE TABLE IF NOT EXISTS commerce_product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE NULL,
    price_adjustment DECIMAL(15,2) DEFAULT 0.00,
    weight_adjustment DECIMAL(10,3) DEFAULT 0.00,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    attributes_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_sku (sku),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product images
CREATE TABLE IF NOT EXISTS commerce_product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_alt VARCHAR(255) NULL,
    display_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES commerce_product_variants(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product options linking (links products to product_options component)
CREATE TABLE IF NOT EXISTS commerce_product_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    option_id INT NOT NULL,
    is_required TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_option_id (option_id),
    UNIQUE KEY unique_product_option (product_id, option_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INVENTORY TABLES
-- ============================================

-- Warehouses
CREATE TABLE IF NOT EXISTS commerce_warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_name VARCHAR(255) NOT NULL,
    warehouse_code VARCHAR(50) UNIQUE NOT NULL,
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
    INDEX idx_warehouse_code (warehouse_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory (stock levels per product/variant per warehouse)
CREATE TABLE IF NOT EXISTS commerce_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT NULL,
    warehouse_id INT NOT NULL,
    quantity_available INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    reorder_point INT DEFAULT 0,
    reorder_quantity INT DEFAULT 0,
    last_counted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES commerce_product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES commerce_warehouses(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_warehouse_id (warehouse_id),
    UNIQUE KEY unique_product_variant_warehouse (product_id, variant_id, warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory movements (stock movement history)
CREATE TABLE IF NOT EXISTS commerce_inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT NULL,
    warehouse_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment', 'reservation', 'release') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES commerce_product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES commerce_warehouses(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Low stock alerts configuration
CREATE TABLE IF NOT EXISTS commerce_low_stock_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NULL,
    variant_id INT NULL,
    warehouse_id INT NULL,
    threshold_quantity INT NOT NULL,
    alert_email VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_alert_sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES commerce_product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES commerce_warehouses(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CART TABLES
-- ============================================

-- Shopping carts
CREATE TABLE IF NOT EXISTS commerce_carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NULL,
    account_id INT NULL,
    cart_token VARCHAR(100) UNIQUE NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_account_id (account_id),
    INDEX idx_cart_token (cart_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart items
CREATE TABLE IF NOT EXISTS commerce_cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    options_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES commerce_carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES commerce_product_variants(id) ON DELETE SET NULL,
    INDEX idx_cart_id (cart_id),
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDER TABLES
-- ============================================

-- Orders
CREATE TABLE IF NOT EXISTS commerce_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    account_id INT NULL,
    order_status ENUM('pending', 'processing', 'on_hold', 'completed', 'cancelled', 'refunded', 'failed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'partial', 'refunded', 'failed') DEFAULT 'pending',
    shipping_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    shipping_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NULL,
    billing_address JSON NULL,
    shipping_address JSON NULL,
    notes TEXT NULL,
    internal_notes TEXT NULL,
    -- Need By Date (Independent Field)
    need_by_date DATETIME NULL,
    -- Rush Order Fields (Independent Feature)
    is_rush_order TINYINT(1) DEFAULT 0,
    rush_surcharge_amount DECIMAL(15,2) DEFAULT 0.00,
    rush_surcharge_rule_id INT NULL,
    rush_order_description TEXT NULL,
    -- Collection Management Fields
    manual_completion_date DATETIME NULL,
    collection_window_start DATETIME NULL,
    collection_window_end DATETIME NULL,
    collection_status ENUM('pending', 'confirmed', 'rescheduled', 'emergency_change', 'completed', 'cancelled') DEFAULT 'pending',
    collection_confirmed_at DATETIME NULL,
    collection_confirmed_by INT NULL,
    collection_reschedule_requested_at DATETIME NULL,
    collection_reschedule_request DATETIME NULL,
    collection_reschedule_request_end DATETIME NULL,
    collection_reschedule_reason TEXT NULL,
    collection_reschedule_status ENUM('pending', 'approved', 'rejected') NULL,
    collection_early_bird TINYINT(1) DEFAULT 0,
    collection_after_hours TINYINT(1) DEFAULT 0,
    collection_early_bird_requested TINYINT(1) DEFAULT 0,
    collection_after_hours_requested TINYINT(1) DEFAULT 0,
    collection_early_bird_approved TINYINT(1) NULL,
    collection_after_hours_approved TINYINT(1) NULL,
    collection_confirmation_deadline DATETIME NULL,
    collection_confirmation_deadline_extended TINYINT(1) DEFAULT 0,
    collection_reschedule_count INT DEFAULT 0,
    collection_reschedule_limit INT DEFAULT 2,
    collection_verification_code VARCHAR(50) NULL,
    collection_verification_qr_code TEXT NULL,
    collection_verified_at DATETIME NULL,
    collection_verified_by INT NULL,
    collection_verification_method ENUM('qr_scan', 'sms_link', 'email_link', 'manual', 'signature') NULL,
    collection_signature TEXT NULL,
    collection_completion_status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    collection_completed_at DATETIME NULL,
    collection_completed_by INT NULL,
    collection_feedback_rating INT NULL,
    collection_feedback_comment TEXT NULL,
    collection_cancelled_at DATETIME NULL,
    collection_cancellation_reason TEXT NULL,
    collection_is_partial TINYINT(1) DEFAULT 0,
    collection_partial_items_json TEXT NULL,
    collection_location_id INT NULL,
    collection_staff_id INT NULL,
    collection_payment_due DECIMAL(15,2) DEFAULT 0.00,
    collection_payment_received DECIMAL(15,2) DEFAULT 0.00,
    collection_payment_method VARCHAR(50) NULL,
    collection_payment_received_at DATETIME NULL,
    collection_payment_received_by INT NULL,
    collection_payment_receipt_number VARCHAR(100) NULL,
    collection_early_bird_charge DECIMAL(15,2) DEFAULT 0.00,
    collection_after_hours_charge DECIMAL(15,2) DEFAULT 0.00,
    collection_early_bird_charge_rule_id INT NULL,
    collection_after_hours_charge_rule_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_number (order_number),
    INDEX idx_account_id (account_id),
    INDEX idx_order_status (order_status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_shipping_status (shipping_status),
    INDEX idx_created_at (created_at),
    INDEX idx_need_by_date (need_by_date),
    INDEX idx_is_rush_order (is_rush_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items (standard line items)
CREATE TABLE IF NOT EXISTS commerce_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    options_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES commerce_product_variants(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulk order tables (configurations for table-based orders)
CREATE TABLE IF NOT EXISTS commerce_bulk_order_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    table_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    config_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulk order table columns (column definitions)
CREATE TABLE IF NOT EXISTS commerce_bulk_order_table_columns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    column_key VARCHAR(100) NOT NULL,
    column_label VARCHAR(255) NOT NULL,
    column_type VARCHAR(50) NOT NULL,
    validation_rules JSON NULL,
    pricing_formula TEXT NULL,
    display_order INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 0,
    default_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES commerce_bulk_order_tables(id) ON DELETE CASCADE,
    INDEX idx_table_id (table_id),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_table_column (table_id, column_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulk order items (rows from bulk order tables)
CREATE TABLE IF NOT EXISTS commerce_bulk_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    table_id INT NOT NULL,
    row_data_json JSON NOT NULL,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES commerce_bulk_order_tables(id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_table_id (table_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order status history
CREATE TABLE IF NOT EXISTS commerce_order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status_type ENUM('order', 'payment', 'shipping') NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_status_type (status_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order payments (links to payment_processing transactions)
CREATE TABLE IF NOT EXISTS commerce_order_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    transaction_id INT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    gateway_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SHIPPING TABLES
-- ============================================

-- Shipping zones
CREATE TABLE IF NOT EXISTS commerce_shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(255) NOT NULL,
    zone_type ENUM('country', 'state', 'postcode', 'custom') NOT NULL,
    conditions_json JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipping methods
CREATE TABLE IF NOT EXISTS commerce_shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    method_name VARCHAR(255) NOT NULL,
    method_type ENUM('flat_rate', 'weight_based', 'price_based', 'carrier_api', 'free') NOT NULL,
    rate_calculation_type VARCHAR(50) NOT NULL,
    config_json JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES commerce_shipping_zones(id) ON DELETE CASCADE,
    INDEX idx_zone_id (zone_id),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipping rates
CREATE TABLE IF NOT EXISTS commerce_shipping_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_id INT NOT NULL,
    condition_type ENUM('weight', 'price', 'quantity', 'flat') NOT NULL,
    condition_value DECIMAL(15,2) NULL,
    condition_max DECIMAL(15,2) NULL,
    rate_amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (method_id) REFERENCES commerce_shipping_methods(id) ON DELETE CASCADE,
    INDEX idx_method_id (method_id),
    INDEX idx_condition_type (condition_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carriers
CREATE TABLE IF NOT EXISTS commerce_carriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_key VARCHAR(100) UNIQUE NOT NULL,
    carrier_name VARCHAR(255) NOT NULL,
    carrier_type VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    is_test_mode TINYINT(1) DEFAULT 1,
    config_json TEXT NULL,
    api_credentials_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carrier_key (carrier_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier services
CREATE TABLE IF NOT EXISTS commerce_carrier_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    service_key VARCHAR(100) NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    config_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (carrier_id) REFERENCES commerce_carriers(id) ON DELETE CASCADE,
    INDEX idx_carrier_id (carrier_id),
    INDEX idx_service_key (service_key),
    UNIQUE KEY unique_carrier_service (carrier_id, service_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipments
CREATE TABLE IF NOT EXISTS commerce_shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    carrier_id INT NULL,
    service_id INT NULL,
    tracking_number VARCHAR(255) NULL,
    shipping_method_id INT NULL,
    status ENUM('pending', 'processing', 'shipped', 'in_transit', 'delivered', 'cancelled', 'returned') DEFAULT 'pending',
    shipped_at DATETIME NULL,
    delivered_at DATETIME NULL,
    shipping_cost DECIMAL(15,2) DEFAULT 0.00,
    weight DECIMAL(10,3) NULL,
    dimensions_json JSON NULL,
    label_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (carrier_id) REFERENCES commerce_carriers(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES commerce_carrier_services(id) ON DELETE SET NULL,
    FOREIGN KEY (shipping_method_id) REFERENCES commerce_shipping_methods(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_carrier_id (carrier_id),
    INDEX idx_tracking_number (tracking_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipment tracking
CREATE TABLE IF NOT EXISTS commerce_shipment_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL,
    tracking_status VARCHAR(100) NOT NULL,
    location VARCHAR(255) NULL,
    status_date DATETIME NOT NULL,
    notes TEXT NULL,
    carrier_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES commerce_shipments(id) ON DELETE CASCADE,
    INDEX idx_shipment_id (shipment_id),
    INDEX idx_status_date (status_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RUSH ORDER & SURCHARGE TABLES
-- ============================================

-- Rush surcharge rules
CREATE TABLE IF NOT EXISTS commerce_rush_surcharge_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    calculation_type ENUM('fixed', 'percentage_subtotal', 'percentage_total', 'tiered', 'formula') NOT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    conditions_json TEXT NULL,
    config_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_calculation_type (calculation_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rush surcharge tiers (for tiered pricing)
CREATE TABLE IF NOT EXISTS commerce_rush_surcharge_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    min_order_value DECIMAL(15,2) DEFAULT 0.00,
    max_order_value DECIMAL(15,2) NULL,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    fixed_amount DECIMAL(15,2) DEFAULT 0.00,
    tier_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES commerce_rush_surcharge_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_tier_order (tier_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rush surcharge history
CREATE TABLE IF NOT EXISTS commerce_rush_surcharge_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    rule_id INT NULL,
    calculation_type VARCHAR(50) NOT NULL,
    base_amount DECIMAL(15,2) DEFAULT 0.00,
    calculated_amount DECIMAL(15,2) DEFAULT 0.00,
    final_amount DECIMAL(15,2) DEFAULT 0.00,
    conditions_met TEXT NULL,
    calculation_details_json TEXT NULL,
    customer_activity_json TEXT NULL,
    customer_discount_applied DECIMAL(5,2) DEFAULT 0.00,
    customer_tier VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (rule_id) REFERENCES commerce_rush_surcharge_rules(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_rule_id (rule_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PRICING DISPLAY RULES
-- ============================================

-- Pricing display rules
CREATE TABLE IF NOT EXISTS commerce_pricing_display_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    rule_type ENUM('global', 'quote_stage', 'charge_type', 'product', 'line_item') NOT NULL,
    target_id INT NULL,
    quote_stage VARCHAR(50) NULL,
    charge_type VARCHAR(50) NULL,
    display_state ENUM('show', 'hide', 'estimated', 'fixed') DEFAULT 'show',
    show_breakdown TINYINT(1) DEFAULT 1,
    show_total_only TINYINT(1) DEFAULT 0,
    show_both TINYINT(1) DEFAULT 0,
    disclaimer_template TEXT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_target_id (target_id),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COLLECTION PRICING RULES
-- ============================================

-- Collection pricing rules (Early Bird/After Hours)
CREATE TABLE IF NOT EXISTS commerce_collection_pricing_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    collection_type ENUM('early_bird', 'after_hours') NOT NULL,
    calculation_type ENUM('fixed', 'percentage', 'tiered', 'formula') NOT NULL,
    day_of_week INT NULL,
    specific_date DATE NULL,
    time_start TIME NULL,
    time_end TIME NULL,
    customer_tier VARCHAR(50) NULL,
    violation_score_min INT NULL,
    violation_score_max INT NULL,
    charge_amount DECIMAL(15,2) DEFAULT 0.00,
    charge_percentage DECIMAL(5,2) DEFAULT 0.00,
    config_json TEXT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_collection_type (collection_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection pricing tiers
CREATE TABLE IF NOT EXISTS commerce_collection_pricing_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    violation_score_min INT DEFAULT 0,
    violation_score_max INT NULL,
    charge_amount DECIMAL(15,2) DEFAULT 0.00,
    charge_percentage DECIMAL(5,2) DEFAULT 0.00,
    tier_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES commerce_collection_pricing_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_id (rule_id),
    INDEX idx_tier_order (tier_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COLLECTION VIOLATIONS
-- ============================================

-- Collection violations
CREATE TABLE IF NOT EXISTS commerce_collection_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    violation_type ENUM('missed_collection', 'late_arrival', 'no_notification', 'early_bird_missed', 'after_hours_missed') NOT NULL,
    violation_severity ENUM('minor', 'moderate', 'severe') DEFAULT 'moderate',
    violation_reason TEXT NULL,
    violation_score INT DEFAULT 0,
    violation_date DATETIME NOT NULL,
    expiration_date DATETIME NULL,
    is_forgiven TINYINT(1) DEFAULT 0,
    forgiven_at DATETIME NULL,
    forgiven_by INT NULL,
    forgiveness_reason TEXT NULL,
    is_appealed TINYINT(1) DEFAULT 0,
    appeal_reason TEXT NULL,
    appeal_status ENUM('pending', 'approved', 'rejected') NULL,
    appeal_processed_at DATETIME NULL,
    appeal_processed_by INT NULL,
    appeal_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_violation_type (violation_type),
    INDEX idx_violation_date (violation_date),
    INDEX idx_is_forgiven (is_forgiven),
    INDEX idx_is_appealed (is_appealed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer violation scores
CREATE TABLE IF NOT EXISTS commerce_customer_violation_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    active_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    violation_tier ENUM('none', 'low', 'medium', 'high', 'severe') DEFAULT 'none',
    violation_count INT DEFAULT 0,
    violations_this_month INT DEFAULT 0,
    violations_this_year INT DEFAULT 0,
    last_violation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer (customer_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_violation_tier (violation_tier),
    INDEX idx_active_score (active_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- QUOTE LINE ITEMS
-- ============================================

-- Quote line items
CREATE TABLE IF NOT EXISTS commerce_quote_line_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    order_id INT NULL,
    product_id INT NULL,
    line_item_type ENUM('product', 'job', 'charge') NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(15,2) DEFAULT 0.00,
    total_price DECIMAL(15,2) DEFAULT 0.00,
    calculation_type ENUM('fixed', 'percentage', 'formula') DEFAULT 'fixed',
    calculation_config_json TEXT NULL,
    display_on_quote TINYINT(1) DEFAULT 1,
    display_text TINYINT(1) DEFAULT 1,
    display_price TINYINT(1) DEFAULT 1,
    display_breakdown TINYINT(1) DEFAULT 0,
    display_total_only TINYINT(1) DEFAULT 0,
    show_both TINYINT(1) DEFAULT 0,
    is_hidden_cost TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES commerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES commerce_products(id) ON DELETE SET NULL,
    INDEX idx_quote_id (quote_id),
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id),
    INDEX idx_line_item_type (line_item_type),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quote line item history
CREATE TABLE IF NOT EXISTS commerce_quote_line_item_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_item_id INT NOT NULL,
    change_type ENUM('created', 'updated', 'deleted') NOT NULL,
    old_values_json TEXT NULL,
    new_values_json TEXT NULL,
    changed_by INT NULL,
    change_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (line_item_id) REFERENCES commerce_quote_line_items(id) ON DELETE CASCADE,
    INDEX idx_line_item_id (line_item_id),
    INDEX idx_change_type (change_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

