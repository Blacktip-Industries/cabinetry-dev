-- SEO Manager Component Database Schema
-- All tables prefixed with seo_manager_ for isolation
-- Version: 1.0.0

-- Config table (stores component configuration)
CREATE TABLE IF NOT EXISTS seo_manager_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parameters table (stores component-specific settings)
CREATE TABLE IF NOT EXISTS seo_manager_parameters (
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
CREATE TABLE IF NOT EXISTS seo_manager_parameters_configs (
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
    FOREIGN KEY (parameter_id) REFERENCES seo_manager_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pages table (SEO data per page/URL)
CREATE TABLE IF NOT EXISTS seo_manager_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    url_hash VARCHAR(64) UNIQUE NOT NULL,
    title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    meta_keywords TEXT NULL,
    canonical_url VARCHAR(500) NULL,
    robots_directive VARCHAR(100) DEFAULT 'index, follow',
    focus_keyword VARCHAR(255) NULL,
    seo_score INT DEFAULT 0,
    content_score INT DEFAULT 0,
    readability_score INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    last_analyzed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_url_hash (url_hash),
    INDEX idx_url (url(255)),
    INDEX idx_is_active (is_active),
    INDEX idx_focus_keyword (focus_keyword),
    INDEX idx_seo_score (seo_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meta tags table (Open Graph, Twitter Cards, custom meta tags)
CREATE TABLE IF NOT EXISTS seo_manager_meta_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    tag_type ENUM('og', 'twitter', 'custom', 'article', 'product') DEFAULT 'custom',
    tag_name VARCHAR(255) NOT NULL,
    tag_value TEXT NOT NULL,
    tag_property VARCHAR(255) NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE CASCADE,
    INDEX idx_page_id (page_id),
    INDEX idx_tag_type (tag_type),
    INDEX idx_tag_name (tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keywords table (keyword research and tracking)
CREATE TABLE IF NOT EXISTS seo_manager_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    keyword_hash VARCHAR(64) UNIQUE NOT NULL,
    search_volume INT DEFAULT 0,
    difficulty_score INT DEFAULT 0,
    cpc DECIMAL(10,2) NULL,
    competition_level ENUM('low', 'medium', 'high', 'very_high') DEFAULT 'medium',
    intent_type ENUM('informational', 'navigational', 'transactional', 'commercial') DEFAULT 'informational',
    related_keywords JSON NULL,
    is_tracked TINYINT(1) DEFAULT 1,
    is_target_keyword TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_keyword_hash (keyword_hash),
    INDEX idx_keyword (keyword),
    INDEX idx_is_tracked (is_tracked),
    INDEX idx_is_target_keyword (is_target_keyword),
    INDEX idx_difficulty_score (difficulty_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rankings table (search engine ranking positions over time)
CREATE TABLE IF NOT EXISTS seo_manager_rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword_id INT NOT NULL,
    page_id INT NULL,
    search_engine ENUM('google', 'bing', 'yahoo', 'duckduckgo', 'other') DEFAULT 'google',
    country_code VARCHAR(2) DEFAULT 'US',
    language_code VARCHAR(5) DEFAULT 'en',
    position INT NULL,
    url VARCHAR(500) NULL,
    title VARCHAR(255) NULL,
    snippet TEXT NULL,
    checked_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (keyword_id) REFERENCES seo_manager_keywords(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE SET NULL,
    INDEX idx_keyword_id (keyword_id),
    INDEX idx_page_id (page_id),
    INDEX idx_search_engine (search_engine),
    INDEX idx_checked_at (checked_at),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content suggestions table (AI-generated content optimization suggestions)
CREATE TABLE IF NOT EXISTS seo_manager_content_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    suggestion_type ENUM('title', 'description', 'content', 'keyword', 'structure', 'readability', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    current_value TEXT NULL,
    suggested_value TEXT NOT NULL,
    explanation TEXT NULL,
    ai_model VARCHAR(100) NULL,
    confidence_score DECIMAL(5,2) NULL,
    status ENUM('pending', 'approved', 'rejected', 'applied') DEFAULT 'pending',
    applied_at DATETIME NULL,
    applied_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE CASCADE,
    INDEX idx_page_id (page_id),
    INDEX idx_suggestion_type (suggestion_type),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optimization history table (audit trail of all optimizations)
CREATE TABLE IF NOT EXISTS seo_manager_optimization_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NULL,
    keyword_id INT NULL,
    action_type ENUM('meta_update', 'content_update', 'keyword_added', 'suggestion_applied', 'audit_run', 'other') NOT NULL,
    action_description TEXT NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    automation_mode ENUM('manual', 'scheduled', 'automated', 'hybrid') DEFAULT 'manual',
    performed_by INT NULL,
    performed_at DATETIME NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE SET NULL,
    FOREIGN KEY (keyword_id) REFERENCES seo_manager_keywords(id) ON DELETE SET NULL,
    INDEX idx_page_id (page_id),
    INDEX idx_keyword_id (keyword_id),
    INDEX idx_action_type (action_type),
    INDEX idx_automation_mode (automation_mode),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sitemap table (sitemap entries and generation settings)
CREATE TABLE IF NOT EXISTS seo_manager_sitemap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    url_hash VARCHAR(64) UNIQUE NOT NULL,
    change_frequency ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') DEFAULT 'weekly',
    priority DECIMAL(2,1) DEFAULT 0.5,
    last_modified DATETIME NULL,
    is_included TINYINT(1) DEFAULT 1,
    sitemap_type ENUM('pages', 'posts', 'products', 'custom') DEFAULT 'pages',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_url_hash (url_hash),
    INDEX idx_url (url(255)),
    INDEX idx_is_included (is_included),
    INDEX idx_sitemap_type (sitemap_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema markup table (Schema.org structured data per page)
CREATE TABLE IF NOT EXISTS seo_manager_schema_markup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    schema_type ENUM('Article', 'Product', 'Organization', 'WebSite', 'BreadcrumbList', 'FAQPage', 'Review', 'LocalBusiness', 'Person', 'Event', 'VideoObject', 'Other') NOT NULL,
    schema_json JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE CASCADE,
    INDEX idx_page_id (page_id),
    INDEX idx_schema_type (schema_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backlinks table (backlink monitoring data)
CREATE TABLE IF NOT EXISTS seo_manager_backlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_url VARCHAR(500) NOT NULL,
    target_url VARCHAR(500) NOT NULL,
    anchor_text TEXT NULL,
    link_type ENUM('dofollow', 'nofollow', 'sponsored', 'ugc') DEFAULT 'dofollow',
    domain_authority INT NULL,
    page_authority INT NULL,
    spam_score INT NULL,
    first_seen_at DATETIME NULL,
    last_checked_at DATETIME NULL,
    is_lost TINYINT(1) DEFAULT 0,
    lost_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_source_url (source_url(255)),
    INDEX idx_target_url (target_url(255)),
    INDEX idx_is_lost (is_lost),
    INDEX idx_last_checked_at (last_checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics table (analytics data from various sources)
CREATE TABLE IF NOT EXISTS seo_manager_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NULL,
    keyword_id INT NULL,
    analytics_source ENUM('google_analytics', 'google_search_console', 'bing_webmaster', 'custom') NOT NULL,
    metric_type ENUM('impressions', 'clicks', 'ctr', 'position', 'sessions', 'bounce_rate', 'avg_session_duration', 'conversions', 'other') NOT NULL,
    metric_value DECIMAL(15,4) NOT NULL,
    date DATE NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE SET NULL,
    FOREIGN KEY (keyword_id) REFERENCES seo_manager_keywords(id) ON DELETE SET NULL,
    INDEX idx_page_id (page_id),
    INDEX idx_keyword_id (keyword_id),
    INDEX idx_analytics_source (analytics_source),
    INDEX idx_metric_type (metric_type),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Technical audits table (technical SEO audit results)
CREATE TABLE IF NOT EXISTS seo_manager_technical_audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    audit_type ENUM('page_speed', 'mobile_friendly', 'crawlability', 'accessibility', 'security', 'structured_data', 'meta_tags', 'images', 'links', 'other') NOT NULL,
    audit_result ENUM('pass', 'warning', 'fail', 'info') DEFAULT 'info',
    issue_title VARCHAR(255) NOT NULL,
    issue_description TEXT NULL,
    issue_severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    current_value TEXT NULL,
    recommended_value TEXT NULL,
    fix_suggestion TEXT NULL,
    is_fixed TINYINT(1) DEFAULT 0,
    fixed_at DATETIME NULL,
    audit_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES seo_manager_pages(id) ON DELETE CASCADE,
    INDEX idx_page_id (page_id),
    INDEX idx_audit_type (audit_type),
    INDEX idx_audit_result (audit_result),
    INDEX idx_issue_severity (issue_severity),
    INDEX idx_is_fixed (is_fixed),
    INDEX idx_audit_date (audit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI configs table (AI API configurations for multiple providers)
CREATE TABLE IF NOT EXISTS seo_manager_ai_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(100) NOT NULL,
    provider_type ENUM('openai', 'claude', 'custom') DEFAULT 'custom',
    api_endpoint VARCHAR(500) NULL,
    api_key_encrypted TEXT NULL,
    api_key_hash VARCHAR(64) NULL,
    request_format ENUM('json', 'form', 'xml') DEFAULT 'json',
    auth_type ENUM('bearer', 'api_key', 'basic', 'custom') DEFAULT 'bearer',
    auth_header_name VARCHAR(100) DEFAULT 'Authorization',
    request_headers JSON NULL,
    model_name VARCHAR(100) NULL,
    temperature DECIMAL(3,2) DEFAULT 0.7,
    max_tokens INT DEFAULT 2000,
    rate_limit_per_minute INT DEFAULT 60,
    rate_limit_per_day INT DEFAULT 10000,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_name (provider_name),
    INDEX idx_provider_type (provider_type),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schedules table (scheduled optimization tasks)
CREATE TABLE IF NOT EXISTS seo_manager_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(255) NOT NULL,
    task_type ENUM('content_optimization', 'keyword_research', 'rank_tracking', 'technical_audit', 'backlink_check', 'analytics_sync', 'sitemap_generation', 'other') NOT NULL,
    automation_mode ENUM('manual', 'scheduled', 'automated', 'hybrid') DEFAULT 'scheduled',
    schedule_type ENUM('one_time', 'recurring') DEFAULT 'recurring',
    recurrence_type ENUM('daily', 'weekly', 'monthly', 'yearly') NULL,
    recurrence_day INT NULL,
    recurrence_month INT NULL,
    start_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_date DATE NULL,
    end_time TIME NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    is_active TINYINT(1) DEFAULT 1,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    run_count INT DEFAULT 0,
    success_count INT DEFAULT 0,
    failure_count INT DEFAULT 0,
    task_config JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_task_type (task_type),
    INDEX idx_automation_mode (automation_mode),
    INDEX idx_schedule_type (schedule_type),
    INDEX idx_is_active (is_active),
    INDEX idx_next_run_at (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Robots rules table (robots.txt rules and directives)
CREATE TABLE IF NOT EXISTS seo_manager_robots_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_agent VARCHAR(255) DEFAULT '*',
    path_pattern VARCHAR(500) NOT NULL,
    rule_type ENUM('allow', 'disallow', 'crawl_delay', 'sitemap') DEFAULT 'disallow',
    rule_value VARCHAR(255) NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_agent (user_agent),
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

