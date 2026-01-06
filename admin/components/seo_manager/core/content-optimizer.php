<?php
/**
 * SEO Manager Component - Content Optimizer
 * AI-powered content optimization
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/ai-integration.php';

/**
 * Analyze and optimize page content
 * @param int $pageId Page ID
 * @return array Optimization result
 */
function seo_manager_optimize_page_content($pageId) {
    $page = seo_manager_get_page_by_url(''); // Get by ID would be better
    if (!$page) {
        return ['success' => false, 'error' => 'Page not found'];
    }
    
    // Get AI suggestions
    $adapter = seo_manager_get_ai_adapter();
    if (!$adapter) {
        return ['success' => false, 'error' => 'No AI adapter configured'];
    }
    
    $context = [
        'page_id' => $pageId,
        'url' => $page['url'] ?? '',
        'focus_keyword' => $page['focus_keyword'] ?? ''
    ];
    
    $result = $adapter->getSuggestions($page, $context);
    
    if ($result['success'] && isset($result['data']['suggestions'])) {
        // Save suggestions
        foreach ($result['data']['suggestions'] as $suggestion) {
            seo_manager_save_content_suggestion([
                'page_id' => $pageId,
                'suggestion_type' => $suggestion['type'] ?? 'other',
                'priority' => $suggestion['priority'] ?? 'medium',
                'current_value' => $suggestion['current'] ?? null,
                'suggested_value' => $suggestion['suggested'] ?? '',
                'explanation' => $suggestion['explanation'] ?? null,
                'ai_model' => $suggestion['model'] ?? null,
                'confidence_score' => $suggestion['confidence'] ?? null,
                'status' => 'pending'
            ]);
        }
        
        return ['success' => true, 'suggestions' => count($result['data']['suggestions'])];
    }
    
    return ['success' => false, 'error' => 'Failed to get suggestions'];
}

