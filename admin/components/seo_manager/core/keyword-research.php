<?php
/**
 * SEO Manager Component - Keyword Research
 * AI-powered keyword discovery and tracking
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/ai-integration.php';

/**
 * Research keywords using AI
 * @param string $seed Seed keyword
 * @param array $context Additional context
 * @return array Research result
 */
function seo_manager_research_keywords($seed, $context = []) {
    $result = seo_manager_ai_research_keywords($seed, $context);
    
    if ($result['success'] && isset($result['data']['keywords'])) {
        // Save discovered keywords
        foreach ($result['data']['keywords'] as $keywordData) {
            seo_manager_save_keyword([
                'keyword' => $keywordData['keyword'] ?? '',
                'search_volume' => $keywordData['search_volume'] ?? 0,
                'difficulty_score' => $keywordData['difficulty'] ?? 0,
                'cpc' => $keywordData['cpc'] ?? null,
                'competition_level' => $keywordData['competition'] ?? 'medium',
                'intent_type' => $keywordData['intent'] ?? 'informational',
                'related_keywords' => $keywordData['related'] ?? []
            ]);
        }
        
        return ['success' => true, 'keywords_found' => count($result['data']['keywords'])];
    }
    
    return ['success' => false, 'error' => $result['error'] ?? 'Unknown error'];
}

