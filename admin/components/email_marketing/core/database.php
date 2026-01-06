<?php
/**
 * Email Marketing Component - Database Functions
 * All functions prefixed with email_marketing_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for email marketing component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function email_marketing_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('EMAIL_MARKETING_DB_HOST') && !empty(EMAIL_MARKETING_DB_HOST)) {
                $conn = new mysqli(
                    EMAIL_MARKETING_DB_HOST,
                    EMAIL_MARKETING_DB_USER ?? '',
                    EMAIL_MARKETING_DB_PASS ?? '',
                    EMAIL_MARKETING_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Email Marketing: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Email Marketing: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Email Marketing: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Get parameter value from email_marketing_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function email_marketing_get_parameter($section, $name, $default = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $stmt = $conn->prepare("SELECT value FROM email_marketing_parameters WHERE section = ? AND parameter_name = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("ss", $section, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['value'] : $default;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in email_marketing_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $value Parameter value
 * @return bool Success
 */
function email_marketing_set_parameter($section, $name, $value, $description = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $valueStr = is_array($value) ? json_encode($value) : (string)$value;
        $descriptionStr = $description ?? '';
        
        $stmt = $conn->prepare("INSERT INTO email_marketing_parameters (section, parameter_name, description, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("ssss", $section, $name, $descriptionStr, $valueStr);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get config value from email_marketing_config table
 * @param string $key Config key
 * @param mixed $default Default value if not found
 * @return mixed Config value or default
 */
function email_marketing_get_config($key, $default = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $stmt = $conn->prepare("SELECT config_value FROM email_marketing_config WHERE config_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            $value = $row['config_value'];
            // Try to decode JSON
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        
        return $default;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting config: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set config value in email_marketing_config table
 * @param string $key Config key
 * @param mixed $value Config value
 * @return bool Success
 */
function email_marketing_set_config($key, $value) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $valueStr = is_array($value) ? json_encode($value) : (string)$value;
        
        $stmt = $conn->prepare("INSERT INTO email_marketing_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("ss", $key, $valueStr);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error setting config: " . $e->getMessage());
        return false;
    }
}

// ============================================
// CAMPAIGN FUNCTIONS
// ============================================

/**
 * Get campaign by ID
 * @param int $campaignId Campaign ID
 * @return array|null Campaign data or null
 */
function email_marketing_get_campaign($campaignId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_campaigns WHERE id = ?");
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $result = $stmt->get_result();
        $campaign = $result->fetch_assoc();
        $stmt->close();
        
        if ($campaign) {
            // Decode JSON fields
            if (!empty($campaign['target_criteria'])) {
                $campaign['target_criteria'] = json_decode($campaign['target_criteria'], true);
            }
            if (!empty($campaign['account_type_ids'])) {
                $campaign['account_type_ids'] = json_decode($campaign['account_type_ids'], true);
            }
            if (!empty($campaign['schedule_settings'])) {
                $campaign['schedule_settings'] = json_decode($campaign['schedule_settings'], true);
            }
        }
        
        return $campaign;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting campaign: " . $e->getMessage());
        return null;
    }
}

/**
 * List campaigns with filters
 * @param array $filters Filters (status, campaign_type, limit, offset)
 * @return array Campaigns list
 */
function email_marketing_list_campaigns($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_campaigns WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['campaign_type'])) {
            $sql .= " AND campaign_type = ?";
            $params[] = $filters['campaign_type'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= "i";
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
                $types .= "i";
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $campaigns = [];
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            if (!empty($row['target_criteria'])) {
                $row['target_criteria'] = json_decode($row['target_criteria'], true);
            }
            if (!empty($row['account_type_ids'])) {
                $row['account_type_ids'] = json_decode($row['account_type_ids'], true);
            }
            if (!empty($row['schedule_settings'])) {
                $row['schedule_settings'] = json_decode($row['schedule_settings'], true);
            }
            $campaigns[] = $row;
        }
        
        $stmt->close();
        return $campaigns;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing campaigns: " . $e->getMessage());
        return [];
    }
}

/**
 * Create or update campaign
 * @param array $campaignData Campaign data
 * @return int|false Campaign ID on success, false on failure
 */
function email_marketing_save_campaign($campaignData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $isUpdate = !empty($campaignData['id']);
        
        // Encode JSON fields
        $targetCriteria = !empty($campaignData['target_criteria']) ? json_encode($campaignData['target_criteria']) : null;
        $accountTypeIds = !empty($campaignData['account_type_ids']) ? json_encode($campaignData['account_type_ids']) : null;
        $scheduleSettings = !empty($campaignData['schedule_settings']) ? json_encode($campaignData['schedule_settings']) : null;
        
        if ($isUpdate) {
            $sql = "UPDATE email_marketing_campaigns SET campaign_name = ?, campaign_type = ?, status = ?, template_id = ?, subject = ?, from_email = ?, from_name = ?, target_criteria = ?, account_type_ids = ?, schedule_type = ?, schedule_settings = ?, scheduled_send_at = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssissssssssi",
                $campaignData['campaign_name'],
                $campaignData['campaign_type'],
                $campaignData['status'],
                $campaignData['template_id'],
                $campaignData['subject'],
                $campaignData['from_email'],
                $campaignData['from_name'],
                $targetCriteria,
                $accountTypeIds,
                $campaignData['schedule_type'],
                $scheduleSettings,
                $campaignData['scheduled_send_at'],
                $campaignData['id']
            );
        } else {
            $sql = "INSERT INTO email_marketing_campaigns (campaign_name, campaign_type, status, template_id, subject, from_email, from_name, target_criteria, account_type_ids, schedule_type, schedule_settings, scheduled_send_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $createdBy = $campaignData['created_by'] ?? null;
            $stmt->bind_param("sssissssssssi",
                $campaignData['campaign_name'],
                $campaignData['campaign_type'],
                $campaignData['status'],
                $campaignData['template_id'],
                $campaignData['subject'],
                $campaignData['from_email'],
                $campaignData['from_name'],
                $targetCriteria,
                $accountTypeIds,
                $campaignData['schedule_type'],
                $scheduleSettings,
                $campaignData['scheduled_send_at'],
                $createdBy
            );
        }
        
        if ($stmt->execute()) {
            $campaignId = $isUpdate ? $campaignData['id'] : $conn->insert_id;
            $stmt->close();
            return $campaignId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error saving campaign: " . $e->getMessage());
        return false;
    }
}

// ============================================
// TEMPLATE FUNCTIONS
// ============================================

/**
 * Get template by ID
 * @param int $templateId Template ID
 * @return array|null Template data or null
 */
function email_marketing_get_template($templateId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_templates WHERE id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template && !empty($template['template_variables'])) {
            $template['template_variables'] = json_decode($template['template_variables'], true);
        }
        
        return $template;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting template: " . $e->getMessage());
        return null;
    }
}

/**
 * List templates with filters
 * @param array $filters Filters (template_type, is_active, limit, offset)
 * @return array Templates list
 */
function email_marketing_list_templates($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_templates WHERE 1=1";
        $params = [];
        $types = "";
        
        if (isset($filters['template_type'])) {
            $sql .= " AND template_type = ?";
            $params[] = $filters['template_type'];
            $types .= "s";
        }
        
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= "i";
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
                $types .= "i";
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['template_variables'])) {
                $row['template_variables'] = json_decode($row['template_variables'], true);
            }
            $templates[] = $row;
        }
        
        $stmt->close();
        return $templates;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Create or update template
 * @param array $templateData Template data
 * @return int|false Template ID on success, false on failure
 */
function email_marketing_save_template($templateData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $isUpdate = !empty($templateData['id']);
        $templateVariables = !empty($templateData['template_variables']) ? json_encode($templateData['template_variables']) : null;
        
        if ($isUpdate) {
            $sql = "UPDATE email_marketing_templates SET template_name = ?, template_type = ?, subject = ?, body_html = ?, body_text = ?, template_variables = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssii",
                $templateData['template_name'],
                $templateData['template_type'],
                $templateData['subject'],
                $templateData['body_html'],
                $templateData['body_text'] ?? null,
                $templateVariables,
                $templateData['is_active'],
                $templateData['id']
            );
        } else {
            $sql = "INSERT INTO email_marketing_templates (template_name, template_type, subject, body_html, body_text, template_variables, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $createdBy = $templateData['created_by'] ?? null;
            $stmt->bind_param("ssssssii",
                $templateData['template_name'],
                $templateData['template_type'],
                $templateData['subject'],
                $templateData['body_html'],
                $templateData['body_text'] ?? null,
                $templateVariables,
                $templateData['is_active'],
                $createdBy
            );
        }
        
        if ($stmt->execute()) {
            $templateId = $isUpdate ? $templateData['id'] : $conn->insert_id;
            $stmt->close();
            return $templateId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error saving template: " . $e->getMessage());
        return false;
    }
}

// ============================================
// LEAD FUNCTIONS
// ============================================

/**
 * Get lead by ID
 * @param int $leadId Lead ID
 * @return array|null Lead data or null
 */
function email_marketing_get_lead($leadId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_leads WHERE id = ?");
        $stmt->bind_param("i", $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $lead = $result->fetch_assoc();
        $stmt->close();
        return $lead;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting lead: " . $e->getMessage());
        return null;
    }
}

/**
 * List leads with filters
 * @param array $filters Filters (status, source_id, assigned_to, limit, offset)
 * @return array Leads list
 */
function email_marketing_list_leads($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_leads WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['source_id'])) {
            $sql .= " AND source_id = ?";
            $params[] = (int)$filters['source_id'];
            $types .= "i";
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= "i";
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
                $types .= "i";
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $leads = [];
        while ($row = $result->fetch_assoc()) {
            $leads[] = $row;
        }
        
        $stmt->close();
        return $leads;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing leads: " . $e->getMessage());
        return [];
    }
}

/**
 * Create or update lead
 * @param array $leadData Lead data
 * @return int|false Lead ID on success, false on failure
 */
function email_marketing_save_lead($leadData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $isUpdate = !empty($leadData['id']);
        
        if ($isUpdate) {
            $sql = "UPDATE email_marketing_leads SET company_name = ?, contact_name = ?, email = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, latitude = ?, longitude = ?, industry = ?, sector = ?, description = ?, status = ?, quality_score = ?, notes = ?, assigned_to = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssddssssisi",
                $leadData['company_name'],
                $leadData['contact_name'] ?? null,
                $leadData['email'] ?? null,
                $leadData['phone'] ?? null,
                $leadData['address_line1'] ?? null,
                $leadData['address_line2'] ?? null,
                $leadData['city'] ?? null,
                $leadData['state'] ?? null,
                $leadData['postal_code'] ?? null,
                $leadData['country'] ?? 'Australia',
                $leadData['latitude'] ?? null,
                $leadData['longitude'] ?? null,
                $leadData['industry'] ?? null,
                $leadData['sector'] ?? null,
                $leadData['description'] ?? null,
                $leadData['status'],
                $leadData['quality_score'] ?? 0,
                $leadData['notes'] ?? null,
                $leadData['assigned_to'] ?? null,
                $leadData['id']
            );
        } else {
            $sql = "INSERT INTO email_marketing_leads (source_id, company_name, contact_name, email, phone, address_line1, address_line2, city, state, postal_code, country, latitude, longitude, industry, sector, description, status, quality_score, notes, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssssssddsssssii",
                $leadData['source_id'] ?? null,
                $leadData['company_name'],
                $leadData['contact_name'] ?? null,
                $leadData['email'] ?? null,
                $leadData['phone'] ?? null,
                $leadData['address_line1'] ?? null,
                $leadData['address_line2'] ?? null,
                $leadData['city'] ?? null,
                $leadData['state'] ?? null,
                $leadData['postal_code'] ?? null,
                $leadData['country'] ?? 'Australia',
                $leadData['latitude'] ?? null,
                $leadData['longitude'] ?? null,
                $leadData['industry'] ?? null,
                $leadData['sector'] ?? null,
                $leadData['description'] ?? null,
                $leadData['status'] ?? 'pending',
                $leadData['quality_score'] ?? 0,
                $leadData['notes'] ?? null,
                $leadData['assigned_to'] ?? null
            );
        }
        
        if ($stmt->execute()) {
            $leadId = $isUpdate ? $leadData['id'] : $conn->insert_id;
            $stmt->close();
            return $leadId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error saving lead: " . $e->getMessage());
        return false;
    }
}

// ============================================
// COUPON FUNCTIONS
// ============================================

/**
 * Get coupon by ID or code
 * @param int|string $identifier Coupon ID or code
 * @return array|null Coupon data or null
 */
function email_marketing_get_coupon($identifier) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        if (is_numeric($identifier)) {
            $stmt = $conn->prepare("SELECT * FROM email_marketing_coupons WHERE id = ?");
            $stmt->bind_param("i", $identifier);
        } else {
            $stmt = $conn->prepare("SELECT * FROM email_marketing_coupons WHERE coupon_code = ?");
            $stmt->bind_param("s", $identifier);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $coupon = $result->fetch_assoc();
        $stmt->close();
        return $coupon;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting coupon: " . $e->getMessage());
        return null;
    }
}

/**
 * List coupons with filters
 * @param array $filters Filters (is_active, campaign_id, limit, offset)
 * @return array Coupons list
 */
function email_marketing_list_coupons($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_coupons WHERE 1=1";
        $params = [];
        $types = "";
        
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
            $types .= "i";
        }
        
        if (!empty($filters['campaign_id'])) {
            $sql .= " AND campaign_id = ?";
            $params[] = (int)$filters['campaign_id'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= "i";
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
                $types .= "i";
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $coupons = [];
        while ($row = $result->fetch_assoc()) {
            $coupons[] = $row;
        }
        
        $stmt->close();
        return $coupons;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing coupons: " . $e->getMessage());
        return [];
    }
}

/**
 * Create or update coupon
 * @param array $couponData Coupon data
 * @return int|false Coupon ID on success, false on failure
 */
function email_marketing_save_coupon($couponData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $isUpdate = !empty($couponData['id']);
        
        if ($isUpdate) {
            $sql = "UPDATE email_marketing_coupons SET coupon_code = ?, description = ?, discount_type = ?, discount_value = ?, minimum_order_value = ?, valid_from = ?, valid_to = ?, usage_limit_per_customer = ?, usage_limit_total = ?, campaign_id = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssddssiiiii",
                $couponData['coupon_code'],
                $couponData['description'] ?? null,
                $couponData['discount_type'],
                $couponData['discount_value'],
                $couponData['minimum_order_value'] ?? 0,
                $couponData['valid_from'],
                $couponData['valid_to'] ?? null,
                $couponData['usage_limit_per_customer'] ?? null,
                $couponData['usage_limit_total'] ?? null,
                $couponData['campaign_id'] ?? null,
                $couponData['is_active'],
                $couponData['id']
            );
        } else {
            $sql = "INSERT INTO email_marketing_coupons (coupon_code, description, discount_type, discount_value, minimum_order_value, valid_from, valid_to, usage_limit_per_customer, usage_limit_total, campaign_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssddssiiii",
                $couponData['coupon_code'],
                $couponData['description'] ?? null,
                $couponData['discount_type'],
                $couponData['discount_value'],
                $couponData['minimum_order_value'] ?? 0,
                $couponData['valid_from'],
                $couponData['valid_to'] ?? null,
                $couponData['usage_limit_per_customer'] ?? null,
                $couponData['usage_limit_total'] ?? null,
                $couponData['campaign_id'] ?? null,
                $couponData['is_active'] ?? 1
            );
        }
        
        if ($stmt->execute()) {
            $couponId = $isUpdate ? $couponData['id'] : $conn->insert_id;
            $stmt->close();
            return $couponId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error saving coupon: " . $e->getMessage());
        return false;
    }
}

// ============================================
// QUEUE FUNCTIONS
// ============================================

/**
 * Add email to queue
 * @param array $queueData Queue data
 * @return int|false Queue ID on success, false on failure
 */
function email_marketing_add_to_queue($queueData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "INSERT INTO email_marketing_queue (campaign_id, account_id, recipient_email, recipient_name, status, scheduled_send_at) VALUES (?, ?, ?, ?, 'pending', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss",
            $queueData['campaign_id'],
            $queueData['account_id'] ?? null,
            $queueData['recipient_email'],
            $queueData['recipient_name'] ?? null,
            $queueData['scheduled_send_at']
        );
        
        if ($stmt->execute()) {
            $queueId = $conn->insert_id;
            $stmt->close();
            return $queueId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error adding to queue: " . $e->getMessage());
        return false;
    }
}

/**
 * Get pending emails from queue
 * @param int $limit Limit
 * @return array Queue items
 */
function email_marketing_get_pending_queue($limit = 50) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_queue WHERE status = 'pending' AND scheduled_send_at <= NOW() ORDER BY scheduled_send_at ASC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        $stmt->close();
        return $items;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting pending queue: " . $e->getMessage());
        return [];
    }
}

/**
 * Update queue item status
 * @param int $queueId Queue ID
 * @param string $status New status
 * @param string $errorMessage Error message if failed
 * @return bool Success
 */
function email_marketing_update_queue_status($queueId, $status, $errorMessage = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        if ($status === 'sent') {
            $sql = "UPDATE email_marketing_queue SET status = ?, actual_send_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $queueId);
        } else {
            $sql = "UPDATE email_marketing_queue SET status = ?, error_message = ?, retry_count = retry_count + 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $status, $errorMessage, $queueId);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error updating queue status: " . $e->getMessage());
        return false;
    }
}

// ============================================
// LOYALTY TIER FUNCTIONS
// ============================================

/**
 * Get loyalty tier by ID
 * @param int $tierId Tier ID
 * @return array|null Tier data or null
 */
function email_marketing_get_loyalty_tier($tierId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_loyalty_tiers WHERE id = ?");
        $stmt->bind_param("i", $tierId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tier = $result->fetch_assoc();
        $stmt->close();
        
        if ($tier && !empty($tier['benefits_json'])) {
            $tier['benefits_json'] = json_decode($tier['benefits_json'], true);
        }
        if ($tier && !empty($tier['applicable_account_type_ids'])) {
            $tier['applicable_account_type_ids'] = json_decode($tier['applicable_account_type_ids'], true);
        }
        
        return $tier;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting loyalty tier: " . $e->getMessage());
        return null;
    }
}

/**
 * List loyalty tiers
 * @param array $filters Filters (is_active)
 * @return array Tiers list
 */
function email_marketing_list_loyalty_tiers($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_loyalty_tiers WHERE 1=1";
        $params = [];
        $types = "";
        
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY tier_order ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tiers = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['benefits_json'])) {
                $row['benefits_json'] = json_decode($row['benefits_json'], true);
            }
            if (!empty($row['applicable_account_type_ids'])) {
                $row['applicable_account_type_ids'] = json_decode($row['applicable_account_type_ids'], true);
            }
            $tiers[] = $row;
        }
        
        $stmt->close();
        return $tiers;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing loyalty tiers: " . $e->getMessage());
        return [];
    }
}

/**
 * Create or update loyalty tier
 * @param array $tierData Tier data
 * @return int|false Tier ID on success, false on failure
 */
function email_marketing_save_loyalty_tier($tierData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $isUpdate = !empty($tierData['id']);
        $benefitsJson = !empty($tierData['benefits_json']) ? json_encode($tierData['benefits_json']) : null;
        $accountTypeIds = !empty($tierData['applicable_account_type_ids']) ? json_encode($tierData['applicable_account_type_ids']) : null;
        
        if ($isUpdate) {
            $sql = "UPDATE email_marketing_loyalty_tiers SET tier_name = ?, tier_order = ?, minimum_spend_amount = ?, maximum_spend_amount = ?, icon_name = ?, icon_svg_path = ?, color_hex = ?, badge_text = ?, badge_style = ?, description = ?, benefits_json = ?, applicable_account_type_ids = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siddssssssssii",
                $tierData['tier_name'],
                $tierData['tier_order'],
                $tierData['minimum_spend_amount'],
                $tierData['maximum_spend_amount'] ?? null,
                $tierData['icon_name'] ?? null,
                $tierData['icon_svg_path'] ?? null,
                $tierData['color_hex'] ?? null,
                $tierData['badge_text'] ?? null,
                $tierData['badge_style'] ?? 'badge',
                $tierData['description'] ?? null,
                $benefitsJson,
                $accountTypeIds,
                $tierData['is_active'],
                $tierData['id']
            );
        } else {
            $sql = "INSERT INTO email_marketing_loyalty_tiers (tier_name, tier_order, minimum_spend_amount, maximum_spend_amount, icon_name, icon_svg_path, color_hex, badge_text, badge_style, description, benefits_json, applicable_account_type_ids, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siddssssssssi",
                $tierData['tier_name'],
                $tierData['tier_order'],
                $tierData['minimum_spend_amount'],
                $tierData['maximum_spend_amount'] ?? null,
                $tierData['icon_name'] ?? null,
                $tierData['icon_svg_path'] ?? null,
                $tierData['color_hex'] ?? null,
                $tierData['badge_text'] ?? null,
                $tierData['badge_style'] ?? 'badge',
                $tierData['description'] ?? null,
                $benefitsJson,
                $accountTypeIds,
                $tierData['is_active'] ?? 1
            );
        }
        
        if ($stmt->execute()) {
            $tierId = $isUpdate ? $tierData['id'] : $conn->insert_id;
            $stmt->close();
            return $tierId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error saving loyalty tier: " . $e->getMessage());
        return false;
    }
}

/**
 * Get milestone by ID
 * @param int $milestoneId Milestone ID
 * @return array|null Milestone data or null
 */
function email_marketing_get_milestone($milestoneId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_loyalty_milestones WHERE id = ?");
        $stmt->bind_param("i", $milestoneId);
        $stmt->execute();
        $result = $stmt->get_result();
        $milestone = $result->fetch_assoc();
        $stmt->close();
        
        if ($milestone && !empty($milestone['applicable_account_type_ids'])) {
            $milestone['applicable_account_type_ids'] = json_decode($milestone['applicable_account_type_ids'], true);
        }
        
        return $milestone;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting milestone: " . $e->getMessage());
        return null;
    }
}

/**
 * List milestones
 * @param array $filters Filters
 * @return array Milestones list
 */
function email_marketing_list_milestones($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_loyalty_milestones WHERE 1=1";
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = " . (int)$filters['is_active'];
        }
        $sql .= " ORDER BY target_spend_amount ASC";
        
        $result = $conn->query($sql);
        $milestones = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['applicable_account_type_ids'])) {
                $row['applicable_account_type_ids'] = json_decode($row['applicable_account_type_ids'], true);
            }
            $milestones[] = $row;
        }
        
        return $milestones;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing milestones: " . $e->getMessage());
        return [];
    }
}

/**
 * Get event by ID
 * @param int $eventId Event ID
 * @return array|null Event data or null
 */
function email_marketing_get_event($eventId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_loyalty_events WHERE id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();
        
        if ($event && !empty($event['applicable_account_type_ids'])) {
            $event['applicable_account_type_ids'] = json_decode($event['applicable_account_type_ids'], true);
        }
        
        return $event;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting event: " . $e->getMessage());
        return null;
    }
}

/**
 * List events
 * @param array $filters Filters
 * @return array Events list
 */
function email_marketing_list_events($filters = []) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM email_marketing_loyalty_events WHERE 1=1";
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = " . (int)$filters['is_active'];
        }
        if (!empty($filters['event_type'])) {
            $sql .= " AND event_type = '" . $conn->real_escape_string($filters['event_type']) . "'";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $result = $conn->query($sql);
        $events = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['applicable_account_type_ids'])) {
                $row['applicable_account_type_ids'] = json_decode($row['applicable_account_type_ids'], true);
            }
            $events[] = $row;
        }
        
        return $events;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error listing events: " . $e->getMessage());
        return [];
    }
}

