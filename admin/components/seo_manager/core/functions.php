<?php
/**
 * SEO Manager Component - Helper Functions
 * Core utility functions
 */

require_once __DIR__ . '/database.php';

/**
 * Calculate SEO score for a page
 * @param array $pageData Page data
 * @return int SEO score (0-100)
 */
function seo_manager_calculate_seo_score($pageData) {
    $score = 0;
    $maxScore = 100;
    
    // Title (25 points)
    if (!empty($pageData['title'])) {
        $titleLength = strlen($pageData['title']);
        if ($titleLength >= 30 && $titleLength <= 60) {
            $score += 25;
        } elseif ($titleLength > 0) {
            $score += 15;
        }
    }
    
    // Meta description (25 points)
    if (!empty($pageData['meta_description'])) {
        $descLength = strlen($pageData['meta_description']);
        if ($descLength >= 120 && $descLength <= 160) {
            $score += 25;
        } elseif ($descLength > 0) {
            $score += 15;
        }
    }
    
    // Focus keyword (20 points)
    if (!empty($pageData['focus_keyword'])) {
        $score += 20;
    }
    
    // Canonical URL (10 points)
    if (!empty($pageData['canonical_url'])) {
        $score += 10;
    }
    
    // Meta keywords (10 points)
    if (!empty($pageData['meta_keywords'])) {
        $score += 10;
    }
    
    // Content score (10 points)
    if (isset($pageData['content_score']) && $pageData['content_score'] > 0) {
        $score += min(10, ($pageData['content_score'] / 10));
    }
    
    return min($maxScore, $score);
}

/**
 * Generate robots.txt content
 * @return string Robots.txt content
 */
function seo_manager_generate_robots_txt() {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return "User-agent: *\nDisallow:";
    }
    
    try {
        $tableName = seo_manager_get_table_name('robots_rules');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY priority ASC, user_agent ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $robots = [];
        $currentUserAgent = null;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['user_agent'] !== $currentUserAgent) {
                if ($currentUserAgent !== null) {
                    $robots[] = '';
                }
                $robots[] = 'User-agent: ' . $row['user_agent'];
                $currentUserAgent = $row['user_agent'];
            }
            
            if ($row['rule_type'] === 'disallow') {
                $robots[] = 'Disallow: ' . $row['path_pattern'];
            } elseif ($row['rule_type'] === 'allow') {
                $robots[] = 'Allow: ' . $row['path_pattern'];
            } elseif ($row['rule_type'] === 'crawl_delay') {
                $robots[] = 'Crawl-delay: ' . ($row['rule_value'] ?? '10');
            } elseif ($row['rule_type'] === 'sitemap') {
                $robots[] = 'Sitemap: ' . $row['path_pattern'];
            }
        }
        
        $stmt->close();
        
        return implode("\n", $robots);
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error generating robots.txt: " . $e->getMessage());
        return "User-agent: *\nDisallow:";
    }
}

/**
 * Get page SEO data for frontend
 * @param string $url Page URL
 * @return array SEO data
 */
function seo_manager_get_page_seo_data($url) {
    $page = seo_manager_get_page_by_url($url);
    if (!$page) {
        return null;
    }
    
    $metaTags = seo_manager_get_meta_tags($page['id']);
    $schemaMarkup = seo_manager_get_schema_markup($page['id']);
    
    return [
        'page' => $page,
        'meta_tags' => $metaTags,
        'schema_markup' => $schemaMarkup
    ];
}

/**
 * Get schema markup for a page
 * @param int $pageId Page ID
 * @return array Array of schema markup
 */
function seo_manager_get_schema_markup($pageId) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = seo_manager_get_table_name('schema_markup');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE page_id = ? AND is_active = 1");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $pageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $schemas = [];
        
        while ($row = $result->fetch_assoc()) {
            $schemas[] = [
                'type' => $row['schema_type'],
                'json' => json_decode($row['schema_json'], true)
            ];
        }
        
        $stmt->close();
        return $schemas;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error getting schema markup: " . $e->getMessage());
        return [];
    }
}

/**
 * Render meta tags HTML
 * @param array $seoData SEO data
 * @return string HTML meta tags
 */
function seo_manager_render_meta_tags($seoData) {
    if (!$seoData || !isset($seoData['page'])) {
        return '';
    }
    
    $html = [];
    $page = $seoData['page'];
    
    // Basic meta tags
    if (!empty($page['title'])) {
        $html[] = '<title>' . htmlspecialchars($page['title']) . '</title>';
    }
    if (!empty($page['meta_description'])) {
        $html[] = '<meta name="description" content="' . htmlspecialchars($page['meta_description']) . '">';
    }
    if (!empty($page['meta_keywords'])) {
        $html[] = '<meta name="keywords" content="' . htmlspecialchars($page['meta_keywords']) . '">';
    }
    if (!empty($page['canonical_url'])) {
        $html[] = '<link rel="canonical" href="' . htmlspecialchars($page['canonical_url']) . '">';
    }
    if (!empty($page['robots_directive'])) {
        $html[] = '<meta name="robots" content="' . htmlspecialchars($page['robots_directive']) . '">';
    }
    
    // Additional meta tags
    if (isset($seoData['meta_tags'])) {
        foreach ($seoData['meta_tags'] as $tag) {
            if ($tag['tag_type'] === 'og') {
                $html[] = '<meta property="og:' . htmlspecialchars($tag['tag_name']) . '" content="' . htmlspecialchars($tag['tag_value']) . '">';
            } elseif ($tag['tag_type'] === 'twitter') {
                $html[] = '<meta name="twitter:' . htmlspecialchars($tag['tag_name']) . '" content="' . htmlspecialchars($tag['tag_value']) . '">';
            } else {
                $html[] = '<meta name="' . htmlspecialchars($tag['tag_name']) . '" content="' . htmlspecialchars($tag['tag_value']) . '">';
            }
        }
    }
    
    // Schema markup
    if (isset($seoData['schema_markup']) && !empty($seoData['schema_markup'])) {
        foreach ($seoData['schema_markup'] as $schema) {
            $html[] = '<script type="application/ld+json">' . json_encode($schema['json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        }
    }
    
    return implode("\n", $html);
}

