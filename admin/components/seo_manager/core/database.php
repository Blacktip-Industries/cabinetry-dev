<?php
/**
 * SEO Manager Component - Database Functions
 * All functions prefixed with seo_manager_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for SEO Manager
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function seo_manager_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('SEO_MANAGER_DB_HOST') && !empty(SEO_MANAGER_DB_HOST)) {
                $conn = new mysqli(
                    SEO_MANAGER_DB_HOST,
                    SEO_MANAGER_DB_USER ?? '',
                    SEO_MANAGER_DB_PASS ?? '',
                    SEO_MANAGER_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("SEO Manager: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("SEO Manager: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("SEO Manager: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Get table name with prefix
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function seo_manager_get_table_name($tableName) {
    $prefix = defined('SEO_MANAGER_TABLE_PREFIX') ? SEO_MANAGER_TABLE_PREFIX : 'seo_manager_';
    return $prefix . $tableName;
}

/**
 * Get parameter value from seo_manager_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function seo_manager_get_parameter($section, $name, $default = null) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = seo_manager_get_table_name('parameters');
        $stmt = $conn->prepare("SELECT value FROM {$tableName} WHERE section = ? AND parameter_name = ?");
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
        error_log("SEO Manager: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in seo_manager_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param string $value Parameter value
 * @param string $description Optional description
 * @return bool Success
 */
function seo_manager_set_parameter($section, $name, $value, $description = null) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('parameters');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, parameter_value, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE parameter_value = ?, description = COALESCE(?, description), updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssss", $section, $name, $value, $description, $value, $description);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

// ==================== PAGES FUNCTIONS ====================

/**
 * Get page by URL
 * @param string $url Page URL
 * @return array|null Page data or null
 */
function seo_manager_get_page_by_url($url) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $urlHash = hash('sha256', $url);
        $tableName = seo_manager_get_table_name('pages');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE url_hash = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $urlHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $page = $result->fetch_assoc();
        $stmt->close();
        
        return $page;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting page: " . $e->getMessage());
        return null;
    }
}

/**
 * Create or update page SEO data
 * @param array $pageData Page data
 * @return int|false Page ID or false on failure
 */
function seo_manager_save_page($pageData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $url = $pageData['url'] ?? '';
        $urlHash = hash('sha256', $url);
        $tableName = seo_manager_get_table_name('pages');
        
        // Check if page exists
        $existing = seo_manager_get_page_by_url($url);
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE {$tableName} SET title = ?, meta_description = ?, meta_keywords = ?, canonical_url = ?, robots_directive = ?, focus_keyword = ?, seo_score = ?, content_score = ?, readability_score = ?, is_active = ?, last_analyzed_at = ? WHERE id = ?");
            $stmt->bind_param("ssssssiiisii",
                $pageData['title'] ?? null,
                $pageData['meta_description'] ?? null,
                $pageData['meta_keywords'] ?? null,
                $pageData['canonical_url'] ?? null,
                $pageData['robots_directive'] ?? 'index, follow',
                $pageData['focus_keyword'] ?? null,
                $pageData['seo_score'] ?? 0,
                $pageData['content_score'] ?? 0,
                $pageData['readability_score'] ?? 0,
                $pageData['is_active'] ?? 1,
                $pageData['last_analyzed_at'] ?? null,
                $existing['id']
            );
            $stmt->execute();
            $stmt->close();
            return $existing['id'];
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO {$tableName} (url, url_hash, title, meta_description, meta_keywords, canonical_url, robots_directive, focus_keyword, seo_score, content_score, readability_score, is_active, last_analyzed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssiiisi",
                $url,
                $urlHash,
                $pageData['title'] ?? null,
                $pageData['meta_description'] ?? null,
                $pageData['meta_keywords'] ?? null,
                $pageData['canonical_url'] ?? null,
                $pageData['robots_directive'] ?? 'index, follow',
                $pageData['focus_keyword'] ?? null,
                $pageData['seo_score'] ?? 0,
                $pageData['content_score'] ?? 0,
                $pageData['readability_score'] ?? 0,
                $pageData['is_active'] ?? 1,
                $pageData['last_analyzed_at'] ?? null
            );
            $stmt->execute();
            $pageId = $conn->insert_id;
            $stmt->close();
            return $pageId;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving page: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all pages
 * @param array $filters Optional filters (is_active, limit, offset)
 * @return array Array of pages
 */
function seo_manager_get_pages($filters = []) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('pages');
        $where = [];
        $params = [];
        $types = '';
        
        if (isset($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limit = isset($filters['limit']) ? "LIMIT " . intval($filters['limit']) : "";
        if (isset($filters['offset'])) {
            $limit .= " OFFSET " . intval($filters['offset']);
        }
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY updated_at DESC {$limit}";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $pages = [];
        
        while ($row = $result->fetch_assoc()) {
            $pages[] = $row;
        }
        
        $stmt->close();
        return $pages;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting pages: " . $e->getMessage());
        return [];
    }
}

// ==================== META TAGS FUNCTIONS ====================

/**
 * Get meta tags for a page
 * @param int $pageId Page ID
 * @return array Array of meta tags
 */
function seo_manager_get_meta_tags($pageId) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('meta_tags');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE page_id = ? ORDER BY display_order ASC, tag_type ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $pageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tags = [];
        
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        
        $stmt->close();
        return $tags;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting meta tags: " . $e->getMessage());
        return [];
    }
}

/**
 * Save meta tag
 * @param array $tagData Meta tag data
 * @return int|false Tag ID or false on failure
 */
function seo_manager_save_meta_tag($tagData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('meta_tags');
        $pageId = $tagData['page_id'] ?? 0;
        $tagName = $tagData['tag_name'] ?? '';
        $tagType = $tagData['tag_type'] ?? 'custom';
        
        // Check if tag exists
        $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE page_id = ? AND tag_name = ? AND tag_type = ?");
        $stmt->bind_param("iss", $pageId, $tagName, $tagType);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE {$tableName} SET tag_value = ?, tag_property = ?, display_order = ? WHERE id = ?");
            $stmt->bind_param("ssii",
                $tagData['tag_value'] ?? '',
                $tagData['tag_property'] ?? null,
                $tagData['display_order'] ?? 0,
                $existing['id']
            );
            $stmt->execute();
            $stmt->close();
            return $existing['id'];
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO {$tableName} (page_id, tag_type, tag_name, tag_value, tag_property, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi",
                $pageId,
                $tagType,
                $tagName,
                $tagData['tag_value'] ?? '',
                $tagData['tag_property'] ?? null,
                $tagData['display_order'] ?? 0
            );
            $stmt->execute();
            $tagId = $conn->insert_id;
            $stmt->close();
            return $tagId;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving meta tag: " . $e->getMessage());
        return false;
    }
}

// ==================== KEYWORDS FUNCTIONS ====================

/**
 * Get keyword by keyword string
 * @param string $keyword Keyword
 * @return array|null Keyword data or null
 */
function seo_manager_get_keyword($keyword) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $keywordHash = hash('sha256', strtolower(trim($keyword)));
        $tableName = seo_manager_get_table_name('keywords');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE keyword_hash = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $keywordHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $keywordData = $result->fetch_assoc();
        $stmt->close();
        
        return $keywordData;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting keyword: " . $e->getMessage());
        return null;
    }
}

/**
 * Save keyword
 * @param array $keywordData Keyword data
 * @return int|false Keyword ID or false on failure
 */
function seo_manager_save_keyword($keywordData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $keyword = trim($keywordData['keyword'] ?? '');
        $keywordHash = hash('sha256', strtolower($keyword));
        $tableName = seo_manager_get_table_name('keywords');
        
        // Check if keyword exists
        $existing = seo_manager_get_keyword($keyword);
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE {$tableName} SET search_volume = ?, difficulty_score = ?, cpc = ?, competition_level = ?, intent_type = ?, related_keywords = ?, is_tracked = ?, is_target_keyword = ? WHERE id = ?");
            $relatedKeywordsJson = !empty($keywordData['related_keywords']) ? json_encode($keywordData['related_keywords']) : null;
            $stmt->bind_param("iidsssiii",
                $keywordData['search_volume'] ?? 0,
                $keywordData['difficulty_score'] ?? 0,
                $keywordData['cpc'] ?? null,
                $keywordData['competition_level'] ?? 'medium',
                $keywordData['intent_type'] ?? 'informational',
                $relatedKeywordsJson,
                $keywordData['is_tracked'] ?? 1,
                $keywordData['is_target_keyword'] ?? 0,
                $existing['id']
            );
            $stmt->execute();
            $stmt->close();
            return $existing['id'];
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO {$tableName} (keyword, keyword_hash, search_volume, difficulty_score, cpc, competition_level, intent_type, related_keywords, is_tracked, is_target_keyword) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $relatedKeywordsJson = !empty($keywordData['related_keywords']) ? json_encode($keywordData['related_keywords']) : null;
            $stmt->bind_param("ssiidsssii",
                $keyword,
                $keywordHash,
                $keywordData['search_volume'] ?? 0,
                $keywordData['difficulty_score'] ?? 0,
                $keywordData['cpc'] ?? null,
                $keywordData['competition_level'] ?? 'medium',
                $keywordData['intent_type'] ?? 'informational',
                $relatedKeywordsJson,
                $keywordData['is_tracked'] ?? 1,
                $keywordData['is_target_keyword'] ?? 0
            );
            $stmt->execute();
            $keywordId = $conn->insert_id;
            $stmt->close();
            return $keywordId;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving keyword: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all keywords
 * @param array $filters Optional filters
 * @return array Array of keywords
 */
function seo_manager_get_keywords($filters = []) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('keywords');
        $where = [];
        $params = [];
        $types = '';
        
        if (isset($filters['is_tracked'])) {
            $where[] = "is_tracked = ?";
            $params[] = $filters['is_tracked'];
            $types .= 'i';
        }
        
        if (isset($filters['is_target_keyword'])) {
            $where[] = "is_target_keyword = ?";
            $params[] = $filters['is_target_keyword'];
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limit = isset($filters['limit']) ? "LIMIT " . intval($filters['limit']) : "";
        if (isset($filters['offset'])) {
            $limit .= " OFFSET " . intval($filters['offset']);
        }
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY updated_at DESC {$limit}";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $keywords = [];
        
        while ($row = $result->fetch_assoc()) {
            $keywords[] = $row;
        }
        
        $stmt->close();
        return $keywords;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting keywords: " . $e->getMessage());
        return [];
    }
}

// ==================== RANKINGS FUNCTIONS ====================

/**
 * Save ranking data
 * @param array $rankingData Ranking data
 * @return int|false Ranking ID or false on failure
 */
function seo_manager_save_ranking($rankingData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('rankings');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (keyword_id, page_id, search_engine, country_code, language_code, position, url, title, snippet, checked_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $checkedAt = $rankingData['checked_at'] ?? date('Y-m-d H:i:s');
        $stmt->bind_param("iisssiisss",
            $rankingData['keyword_id'] ?? null,
            $rankingData['page_id'] ?? null,
            $rankingData['search_engine'] ?? 'google',
            $rankingData['country_code'] ?? 'US',
            $rankingData['language_code'] ?? 'en',
            $rankingData['position'] ?? null,
            $rankingData['url'] ?? null,
            $rankingData['title'] ?? null,
            $rankingData['snippet'] ?? null,
            $checkedAt
        );
        $stmt->execute();
        $rankingId = $conn->insert_id;
        $stmt->close();
        return $rankingId;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving ranking: " . $e->getMessage());
        return false;
    }
}

/**
 * Get rankings for a keyword
 * @param int $keywordId Keyword ID
 * @param array $filters Optional filters
 * @return array Array of rankings
 */
function seo_manager_get_rankings($keywordId, $filters = []) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('rankings');
        $where = ["keyword_id = ?"];
        $params = [$keywordId];
        $types = 'i';
        
        if (isset($filters['search_engine'])) {
            $where[] = "search_engine = ?";
            $params[] = $filters['search_engine'];
            $types .= 's';
        }
        
        $whereClause = "WHERE " . implode(" AND ", $where);
        $orderBy = "ORDER BY checked_at DESC";
        $limit = isset($filters['limit']) ? "LIMIT " . intval($filters['limit']) : "";
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} {$orderBy} {$limit}";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rankings = [];
        
        while ($row = $result->fetch_assoc()) {
            $rankings[] = $row;
        }
        
        $stmt->close();
        return $rankings;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting rankings: " . $e->getMessage());
        return [];
    }
}

// ==================== CONTENT SUGGESTIONS FUNCTIONS ====================

/**
 * Save content suggestion
 * @param array $suggestionData Suggestion data
 * @return int|false Suggestion ID or false on failure
 */
function seo_manager_save_content_suggestion($suggestionData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('content_suggestions');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (page_id, suggestion_type, priority, current_value, suggested_value, explanation, ai_model, confidence_score, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssds",
            $suggestionData['page_id'] ?? 0,
            $suggestionData['suggestion_type'] ?? 'other',
            $suggestionData['priority'] ?? 'medium',
            $suggestionData['current_value'] ?? null,
            $suggestionData['suggested_value'] ?? '',
            $suggestionData['explanation'] ?? null,
            $suggestionData['ai_model'] ?? null,
            $suggestionData['confidence_score'] ?? null,
            $suggestionData['status'] ?? 'pending'
        );
        $stmt->execute();
        $suggestionId = $conn->insert_id;
        $stmt->close();
        return $suggestionId;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving content suggestion: " . $e->getMessage());
        return false;
    }
}

/**
 * Get content suggestions for a page
 * @param int $pageId Page ID
 * @param array $filters Optional filters
 * @return array Array of suggestions
 */
function seo_manager_get_content_suggestions($pageId, $filters = []) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('content_suggestions');
        $where = ["page_id = ?"];
        $params = [$pageId];
        $types = 'i';
        
        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (isset($filters['suggestion_type'])) {
            $where[] = "suggestion_type = ?";
            $params[] = $filters['suggestion_type'];
            $types .= 's';
        }
        
        $whereClause = "WHERE " . implode(" AND ", $where);
        $orderBy = "ORDER BY priority DESC, created_at DESC";
        $limit = isset($filters['limit']) ? "LIMIT " . intval($filters['limit']) : "";
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} {$orderBy} {$limit}";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $suggestions = [];
        
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row;
        }
        
        $stmt->close();
        return $suggestions;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting content suggestions: " . $e->getMessage());
        return [];
    }
}

// ==================== OPTIMIZATION HISTORY FUNCTIONS ====================

/**
 * Log optimization action
 * @param array $historyData History data
 * @return int|false History ID or false on failure
 */
function seo_manager_log_optimization($historyData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('optimization_history');
        $performedAt = $historyData['performed_at'] ?? date('Y-m-d H:i:s');
        $metadataJson = !empty($historyData['metadata']) ? json_encode($historyData['metadata']) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (page_id, keyword_id, action_type, action_description, old_value, new_value, automation_mode, performed_by, performed_at, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssiss",
            $historyData['page_id'] ?? null,
            $historyData['keyword_id'] ?? null,
            $historyData['action_type'] ?? 'other',
            $historyData['action_description'] ?? '',
            $historyData['old_value'] ?? null,
            $historyData['new_value'] ?? null,
            $historyData['automation_mode'] ?? 'manual',
            $historyData['performed_by'] ?? null,
            $performedAt,
            $metadataJson
        );
        $stmt->execute();
        $historyId = $conn->insert_id;
        $stmt->close();
        return $historyId;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error logging optimization: " . $e->getMessage());
        return false;
    }
}

// ==================== AI CONFIGS FUNCTIONS ====================

/**
 * Get AI config by provider name
 * @param string $providerName Provider name
 * @return array|null AI config or null
 */
function seo_manager_get_ai_config($providerName) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = seo_manager_get_table_name('ai_configs');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE provider_name = ? AND is_active = 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $providerName);
        $stmt->execute();
        $result = $stmt->get_result();
        $config = $result->fetch_assoc();
        $stmt->close();
        
        return $config;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting AI config: " . $e->getMessage());
        return null;
    }
}

/**
 * Get default AI config
 * @return array|null AI config or null
 */
function seo_manager_get_default_ai_config() {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = seo_manager_get_table_name('ai_configs');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_default = 1 AND is_active = 1 LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $config = $result->fetch_assoc();
        $stmt->close();
        
        return $config;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting default AI config: " . $e->getMessage());
        return null;
    }
}

// ==================== SCHEDULES FUNCTIONS ====================

/**
 * Get schedule by ID
 * @param int $scheduleId Schedule ID
 * @return array|null Schedule data or null
 */
function seo_manager_get_schedule($scheduleId) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = seo_manager_get_table_name('schedules');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $stmt->close();
        
        return $schedule;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting schedule: " . $e->getMessage());
        return null;
    }
}

/**
 * Get active schedules ready to run
 * @return array Array of schedules
 */
function seo_manager_get_active_schedules() {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('schedules');
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 AND (next_run_at IS NULL OR next_run_at <= ?) ORDER BY next_run_at ASC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("s", $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedules = [];
        
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        $stmt->close();
        return $schedules;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting active schedules: " . $e->getMessage());
        return [];
    }
}

