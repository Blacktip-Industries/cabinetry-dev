<?php
/**
 * SEO Manager Component - Sitemap Generator
 * Generates XML sitemaps
 */

require_once __DIR__ . '/database.php';

/**
 * Generate XML sitemap
 * @param string $baseUrl Base URL for the site
 * @return string XML sitemap content
 */
function seo_manager_generate_sitemap($baseUrl = null) {
    if ($baseUrl === null) {
        $baseUrl = defined('SEO_MANAGER_BASE_URL') ? SEO_MANAGER_BASE_URL : 'http://localhost';
    }
    
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    }
    
    try {
        $tableName = seo_manager_get_table_name('sitemap');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_included = 1 ORDER BY sitemap_type, updated_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        while ($row = $result->fetch_assoc()) {
            $url = $row['url'];
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
            }
            
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            
            if (!empty($row['last_modified'])) {
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($row['last_modified'])) . '</lastmod>' . "\n";
            }
            
            if (!empty($row['change_frequency'])) {
                $xml .= '    <changefreq>' . htmlspecialchars($row['change_frequency']) . '</changefreq>' . "\n";
            }
            
            if (isset($row['priority'])) {
                $xml .= '    <priority>' . htmlspecialchars($row['priority']) . '</priority>' . "\n";
            }
            
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        $stmt->close();
        return $xml;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error generating sitemap: " . $e->getMessage());
        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    }
}

/**
 * Save sitemap to file
 * @param string $filePath File path to save sitemap
 * @param string $baseUrl Base URL
 * @return bool Success
 */
function seo_manager_save_sitemap($filePath, $baseUrl = null) {
    $sitemap = seo_manager_generate_sitemap($baseUrl);
    
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    return file_put_contents($filePath, $sitemap) !== false;
}

