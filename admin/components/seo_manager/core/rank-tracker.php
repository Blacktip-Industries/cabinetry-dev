<?php
/**
 * SEO Manager Component - Rank Tracker
 * Monitors keyword positions across search engines
 */

require_once __DIR__ . '/database.php';

/**
 * Track ranking for a keyword
 * @param int $keywordId Keyword ID
 * @param string $searchEngine Search engine
 * @param int $position Position
 * @param array $data Additional data
 * @return int|false Ranking ID or false
 */
function seo_manager_track_ranking($keywordId, $searchEngine = 'google', $position = null, $data = []) {
    return seo_manager_save_ranking([
        'keyword_id' => $keywordId,
        'page_id' => $data['page_id'] ?? null,
        'search_engine' => $searchEngine,
        'country_code' => $data['country_code'] ?? 'US',
        'language_code' => $data['language_code'] ?? 'en',
        'position' => $position,
        'url' => $data['url'] ?? null,
        'title' => $data['title'] ?? null,
        'snippet' => $data['snippet'] ?? null,
        'checked_at' => date('Y-m-d H:i:s')
    ]);
}

