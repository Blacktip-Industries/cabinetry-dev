-- Mobile API Component Database Schema
-- All tables prefixed with mobile_api_ for isolation
-- Version: 1.0.0
-- Comprehensive PWA and mobile API infrastructure

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS mobile_api_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS mobile_api_parameters (
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

-- API Keys table (API key management)
CREATE TABLE IF NOT EXISTS mobile_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    api_secret VARCHAR(64) NOT NULL,
    permissions JSON NULL,
    rate_limit_per_minute INT DEFAULT 60,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- JWT Tokens table (JWT token management)
CREATE TABLE IF NOT EXISTS mobile_api_jwt_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    token_hash VARCHAR(64) UNIQUE NOT NULL,
    refresh_token_hash VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    refresh_expires_at TIMESTAMP NOT NULL,
    device_info JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Endpoints table (Discovered endpoint registry)
CREATE TABLE IF NOT EXISTS mobile_api_endpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    endpoint_path VARCHAR(255) NOT NULL,
    endpoint_method VARCHAR(10) NOT NULL,
    endpoint_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    requires_auth TINYINT(1) DEFAULT 1,
    rate_limit_per_minute INT DEFAULT 60,
    is_active TINYINT(1) DEFAULT 1,
    response_transform JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_endpoint (component_name, endpoint_path, endpoint_method),
    INDEX idx_component_name (component_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync Queue table (Background sync queue)
CREATE TABLE IF NOT EXISTS mobile_api_sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    device_id VARCHAR(255) NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    request_data JSON NULL,
    response_data JSON NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'conflict') DEFAULT 'pending',
    conflict_data JSON NULL,
    retry_count INT DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Push Subscriptions table (Push notification subscriptions)
CREATE TABLE IF NOT EXISTS mobile_api_push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    endpoint_url TEXT NOT NULL,
    p256dh_key VARCHAR(255) NOT NULL,
    auth_key VARCHAR(255) NOT NULL,
    device_info JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App Layouts table (PWA app layout configurations)
CREATE TABLE IF NOT EXISTS mobile_api_app_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layout_name VARCHAR(255) NOT NULL,
    layout_config JSON NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Component Features table (Discovered component mobile features)
CREATE TABLE IF NOT EXISTS mobile_api_component_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    feature_type ENUM('screen', 'navigation', 'endpoint', 'permission') NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    feature_config JSON NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_component_name (component_name),
    INDEX idx_feature_type (feature_type),
    INDEX idx_is_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Location Tracking table (Real-time location tracking)
CREATE TABLE IF NOT EXISTS mobile_api_location_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    tracking_session_id VARCHAR(64) UNIQUE NOT NULL,
    status ENUM('not_started', 'on_way', 'arrived', 'completed', 'cancelled') DEFAULT 'not_started',
    location_sharing_enabled TINYINT(1) DEFAULT 0,
    current_latitude DECIMAL(10,8) NULL,
    current_longitude DECIMAL(11,8) NULL,
    destination_latitude DECIMAL(10,8) NULL,
    destination_longitude DECIMAL(11,8) NULL,
    collection_address_id INT NULL,
    estimated_arrival_time TIMESTAMP NULL,
    actual_arrival_time TIMESTAMP NULL,
    location_history JSON NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_tracking_session_id (tracking_session_id),
    INDEX idx_status (status),
    INDEX idx_collection_address_id (collection_address_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Location Updates table (Location update history)
CREATE TABLE IF NOT EXISTS mobile_api_location_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_session_id VARCHAR(64) NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    accuracy DECIMAL(10,2) NULL,
    heading DECIMAL(5,2) NULL,
    speed DECIMAL(5,2) NULL,
    calculated_speed DECIMAL(5,2) NULL,
    distance_from_last DECIMAL(10,2) NULL,
    movement_state ENUM('stationary', 'slow', 'medium', 'fast') NULL,
    update_interval_used INT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tracking_session_id (tracking_session_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_movement_state (movement_state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection Addresses table (Collection addresses for orders)
CREATE TABLE IF NOT EXISTS mobile_api_collection_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address_name VARCHAR(255) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state_province VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics table (API and location tracking analytics)
CREATE TABLE IF NOT EXISTS mobile_api_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_category ENUM('api_usage', 'location_tracking', 'app_usage', 'authentication') NOT NULL,
    user_id INT NULL,
    order_id INT NULL,
    tracking_session_id VARCHAR(64) NULL,
    endpoint VARCHAR(255) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_event_category (event_category),
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Location Analytics table (Location tracking statistics)
CREATE TABLE IF NOT EXISTS mobile_api_location_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_session_id VARCHAR(64) NOT NULL,
    order_id INT NULL,
    collection_address_id INT NULL,
    total_distance_km DECIMAL(10,2) NULL,
    total_travel_time_minutes INT NULL,
    average_speed_kmh DECIMAL(5,2) NULL,
    max_speed_kmh DECIMAL(5,2) NULL,
    stops_count INT DEFAULT 0,
    total_stopped_time_minutes INT DEFAULT 0,
    route_efficiency DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tracking_session_id (tracking_session_id),
    INDEX idx_order_id (order_id),
    INDEX idx_collection_address_id (collection_address_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table (Notification queue and history)
CREATE TABLE IF NOT EXISTS mobile_api_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('location_eta', 'arrival', 'customer_on_way', 'custom') NOT NULL,
    recipient_type ENUM('admin', 'customer', 'both') NOT NULL,
    user_id INT NULL,
    order_id INT NULL,
    tracking_session_id VARCHAR(64) NULL,
    channels JSON NOT NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_type (notification_type),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Rules table (Configurable notification triggers)
CREATE TABLE IF NOT EXISTS mobile_api_notification_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    trigger_conditions JSON NOT NULL,
    notification_channels JSON NOT NULL,
    recipient_type ENUM('admin', 'customer', 'both') NOT NULL,
    message_template TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trigger_event (trigger_event),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for collection_address_id in location_tracking
ALTER TABLE mobile_api_location_tracking 
ADD CONSTRAINT fk_location_tracking_collection_address 
FOREIGN KEY (collection_address_id) REFERENCES mobile_api_collection_addresses(id) ON DELETE SET NULL;

