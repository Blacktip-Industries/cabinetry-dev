<?php
/**
 * Layout Component - Performance Optimization
 * Caching, minification, and CDN integration
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get cache entry
 * @param int $layoutId Layout ID
 * @param string $cacheKey Cache key
 * @return array|null Cache data or null if not found/expired
 */
function layout_cache_get($layoutId, $cacheKey) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('cache');
        $stmt = $conn->prepare("SELECT cache_data, expires_at FROM {$tableName} WHERE layout_id = ? AND cache_key = ? AND expires_at > NOW()");
        $stmt->bind_param("is", $layoutId, $cacheKey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return [
                'data' => $row['cache_data'],
                'expires_at' => $row['expires_at']
            ];
        }
        
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error getting cache: " . $e->getMessage());
        return null;
    }
}

/**
 * Set cache entry
 * @param int $layoutId Layout ID
 * @param string $cacheKey Cache key
 * @param string $cacheData Cache data
 * @param int $ttl Time to live in seconds (default: 3600)
 * @return bool Success
 */
function layout_cache_set($layoutId, $cacheKey, $cacheData, $ttl = 3600) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('cache');
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (layout_id, cache_key, cache_data, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE cache_data = ?, expires_at = ?");
        $stmt->bind_param("isssss", $layoutId, $cacheKey, $cacheData, $expiresAt, $cacheData, $expiresAt);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error setting cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete cache entry
 * @param int $layoutId Layout ID
 * @param string|null $cacheKey Cache key (null to delete all for layout)
 * @return bool Success
 */
function layout_cache_delete($layoutId, $cacheKey = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('cache');
        
        if ($cacheKey) {
            $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE layout_id = ? AND cache_key = ?");
            $stmt->bind_param("is", $layoutId, $cacheKey);
        } else {
            $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE layout_id = ?");
            $stmt->bind_param("i", $layoutId);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error deleting cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear expired cache entries
 * @return int Number of entries cleared
 */
function layout_cache_clear_expired() {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = layout_get_table_name('cache');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE expires_at <= NOW()");
        $stmt->execute();
        $deleted = $conn->affected_rows;
        $stmt->close();
        return $deleted;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error clearing expired cache: " . $e->getMessage());
        return 0;
    }
}

/**
 * Minify CSS
 * @param string $css CSS content
 * @return string Minified CSS
 */
function layout_minify_css($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Remove whitespace
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
    
    // Remove spaces around selectors and properties
    $css = preg_replace('/\s*{\s*/', '{', $css);
    $css = preg_replace('/\s*}\s*/', '}', $css);
    $css = preg_replace('/\s*;\s*/', ';', $css);
    $css = preg_replace('/\s*:\s*/', ':', $css);
    $css = preg_replace('/\s*,\s*/', ',', $css);
    
    return trim($css);
}

/**
 * Minify JavaScript
 * @param string $js JavaScript content
 * @return string Minified JavaScript
 */
function layout_minify_js($js) {
    // Remove single-line comments (but preserve URLs)
    $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
    
    // Remove multi-line comments
    $js = preg_replace('/\/\*[^*]*\*+([^/][^*]*\*+)*\//', '', $js);
    
    // Remove unnecessary whitespace
    $js = preg_replace('/\s+/', ' ', $js);
    $js = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $js);
    
    return trim($js);
}

/**
 * Minify HTML
 * @param string $html HTML content
 * @return string Minified HTML
 */
function layout_minify_html($html) {
    // Remove HTML comments (except conditional comments)
    $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
    
    // Remove whitespace between tags
    $html = preg_replace('/>\s+</', '><', $html);
    
    // Remove leading/trailing whitespace
    $html = trim($html);
    
    return $html;
}

/**
 * Get CDN URL for asset
 * @param string $assetPath Asset path
 * @return string CDN URL or original path if CDN not configured
 */
function layout_get_cdn_url($assetPath) {
    $cdnBase = layout_get_parameter('Performance', 'cdn_base_url', '');
    
    if (empty($cdnBase)) {
        return $assetPath;
    }
    
    // Remove leading slash from asset path if present
    $assetPath = ltrim($assetPath, '/');
    
    // Ensure CDN base doesn't have trailing slash
    $cdnBase = rtrim($cdnBase, '/');
    
    return $cdnBase . '/' . $assetPath;
}

/**
 * Record performance metric
 * @param int $layoutId Layout ID
 * @param string $metricType Metric type (load_time, render_time, etc.)
 * @param float $metricValue Metric value
 * @param string|null $pageName Page name
 * @return bool Success
 */
function layout_performance_record_metric($layoutId, $metricType, $metricValue, $pageName = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('performance_metrics');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (layout_id, metric_type, metric_value, page_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $layoutId, $metricType, $metricValue, $pageName);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error recording metric: " . $e->getMessage());
        return false;
    }
}

/**
 * Get performance metrics for layout
 * @param int $layoutId Layout ID
 * @param string|null $metricType Metric type filter
 * @param int $limit Limit results
 * @return array Array of metrics
 */
function layout_performance_get_metrics($layoutId, $metricType = null, $limit = 100) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('performance_metrics');
        
        if ($metricType) {
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE layout_id = ? AND metric_type = ? ORDER BY created_at DESC LIMIT ?");
            $stmt->bind_param("isi", $layoutId, $metricType, $limit);
        } else {
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE layout_id = ? ORDER BY created_at DESC LIMIT ?");
            $stmt->bind_param("ii", $layoutId, $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $metrics = [];
        while ($row = $result->fetch_assoc()) {
            $metrics[] = $row;
        }
        
        $stmt->close();
        return $metrics;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error getting metrics: " . $e->getMessage());
        return [];
    }
}

/**
 * Get average performance metrics
 * @param int $layoutId Layout ID
 * @param string|null $metricType Metric type filter
 * @return array Average metrics
 */
function layout_performance_get_averages($layoutId, $metricType = null) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('performance_metrics');
        
        if ($metricType) {
            $stmt = $conn->prepare("SELECT metric_type, AVG(metric_value) as avg_value, MIN(metric_value) as min_value, MAX(metric_value) as max_value, COUNT(*) as count FROM {$tableName} WHERE layout_id = ? AND metric_type = ? GROUP BY metric_type");
            $stmt->bind_param("is", $layoutId, $metricType);
        } else {
            $stmt = $conn->prepare("SELECT metric_type, AVG(metric_value) as avg_value, MIN(metric_value) as min_value, MAX(metric_value) as max_value, COUNT(*) as count FROM {$tableName} WHERE layout_id = ? GROUP BY metric_type");
            $stmt->bind_param("i", $layoutId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $averages = [];
        while ($row = $result->fetch_assoc()) {
            $averages[$row['metric_type']] = [
                'average' => (float)$row['avg_value'],
                'min' => (float)$row['min_value'],
                'max' => (float)$row['max_value'],
                'count' => (int)$row['count']
            ];
        }
        
        $stmt->close();
        return $averages;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error getting averages: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if performance budget is exceeded
 * @param int $layoutId Layout ID
 * @param string $budgetType Budget type
 * @param float $currentValue Current metric value
 * @return array Budget check result
 */
function layout_performance_check_budget($layoutId, $budgetType, $currentValue) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['within_budget' => true];
    }
    
    try {
        $tableName = layout_get_table_name('performance_budgets');
        $stmt = $conn->prepare("SELECT budget_value, alert_threshold FROM {$tableName} WHERE layout_id = ? AND budget_type = ? AND is_active = 1");
        $stmt->bind_param("is", $layoutId, $budgetType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['within_budget' => true, 'no_budget' => true];
        }
        
        $budget = $result->fetch_assoc();
        $stmt->close();
        
        $exceeded = $currentValue > $budget['budget_value'];
        $alert = $budget['alert_threshold'] && $currentValue > $budget['alert_threshold'];
        
        return [
            'within_budget' => !$exceeded,
            'exceeded' => $exceeded,
            'alert' => $alert,
            'budget_value' => (float)$budget['budget_value'],
            'current_value' => $currentValue,
            'alert_threshold' => $budget['alert_threshold'] ? (float)$budget['alert_threshold'] : null
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Performance: Error checking budget: " . $e->getMessage());
        return ['within_budget' => true];
    }
}

/**
 * Get or generate cached layout output
 * @param int $layoutId Layout ID
 * @param string $cacheKey Cache key
 * @param callable $generator Function to generate content if not cached
 * @param int $ttl Time to live in seconds
 * @return string Cached or generated content
 */
function layout_cache_get_or_generate($layoutId, $cacheKey, $generator, $ttl = 3600) {
    $cache = layout_cache_get($layoutId, $cacheKey);
    
    if ($cache !== null) {
        return $cache['data'];
    }
    
    // Generate content
    $content = call_user_func($generator);
    
    // Cache it
    layout_cache_set($layoutId, $cacheKey, $content, $ttl);
    
    return $content;
}

/**
 * Enable/disable minification
 * @param bool $enabled Whether minification is enabled
 * @return bool Success
 */
function layout_performance_set_minification($enabled) {
    return layout_set_config('performance_minification_enabled', $enabled ? '1' : '0');
}

/**
 * Check if minification is enabled
 * @return bool True if enabled
 */
function layout_performance_is_minification_enabled() {
    return layout_get_config('performance_minification_enabled', '1') === '1';
}

/**
 * Enable/disable caching
 * @param bool $enabled Whether caching is enabled
 * @return bool Success
 */
function layout_performance_set_caching($enabled) {
    return layout_set_config('performance_caching_enabled', $enabled ? '1' : '0');
}

/**
 * Check if caching is enabled
 * @return bool True if enabled
 */
function layout_performance_is_caching_enabled() {
    return layout_get_config('performance_caching_enabled', '1') === '1';
}

