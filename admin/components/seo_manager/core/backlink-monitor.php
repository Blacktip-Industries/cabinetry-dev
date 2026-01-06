<?php
/**
 * SEO Manager Component - Backlink Monitor
 * Tracks and analyzes backlinks
 */

require_once __DIR__ . '/database.php';

/**
 * Save backlink data
 * @param array $backlinkData Backlink data
 * @return int|false Backlink ID or false
 */
function seo_manager_save_backlink($backlinkData) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('backlinks');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (source_url, target_url, anchor_text, link_type, domain_authority, page_authority, spam_score, first_seen_at, last_checked_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $firstSeen = $backlinkData['first_seen_at'] ?? date('Y-m-d H:i:s');
        $lastChecked = date('Y-m-d H:i:s');
        $stmt->bind_param("ssssiiiss",
            $backlinkData['source_url'] ?? '',
            $backlinkData['target_url'] ?? '',
            $backlinkData['anchor_text'] ?? null,
            $backlinkData['link_type'] ?? 'dofollow',
            $backlinkData['domain_authority'] ?? null,
            $backlinkData['page_authority'] ?? null,
            $backlinkData['spam_score'] ?? null,
            $firstSeen,
            $lastChecked
        );
        $stmt->execute();
        $backlinkId = $conn->insert_id;
        $stmt->close();
        return $backlinkId;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving backlink: " . $e->getMessage());
        return false;
    }
}

