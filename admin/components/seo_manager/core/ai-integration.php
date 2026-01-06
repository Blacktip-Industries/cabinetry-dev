<?php
/**
 * SEO Manager Component - AI Integration
 * Flexible AI API adapter system supporting multiple providers
 */

require_once __DIR__ . '/database.php';

/**
 * SEO AI Adapter Interface
 */
interface SEO_AI_Adapter {
    public function optimizeContent($content, $keywords, $context = []);
    public function researchKeywords($seed, $context = []);
    public function generateMetaTags($content, $context = []);
    public function analyzeCompetitors($urls, $context = []);
    public function getSuggestions($pageData, $context = []);
}

/**
 * Base AI Adapter Class
 */
abstract class Base_AI_Adapter implements SEO_AI_Adapter {
    protected $config;
    protected $conn;
    
    public function __construct($config) {
        $this->config = $config;
        $this->conn = seo_manager_get_db_connection();
    }
    
    /**
     * Make API request
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array Response data
     */
    protected function makeRequest($endpoint, $data = [], $method = 'POST') {
        $ch = curl_init();
        
        $headers = [];
        if ($this->config['auth_type'] === 'bearer') {
            $apiKey = $this->decryptApiKey($this->config['api_key_encrypted'] ?? '');
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        } elseif ($this->config['auth_type'] === 'api_key') {
            $apiKey = $this->decryptApiKey($this->config['api_key_encrypted'] ?? '');
            $headerName = $this->config['auth_header_name'] ?? 'X-API-Key';
            $headers[] = $headerName . ': ' . $apiKey;
        }
        
        if (!empty($this->config['request_headers'])) {
            $customHeaders = json_decode($this->config['request_headers'], true);
            foreach ($customHeaders as $name => $value) {
                $headers[] = $name . ': ' . $value;
            }
        }
        
        $headers[] = 'Content-Type: application/json';
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        if ($httpCode >= 400) {
            return ['success' => false, 'error' => 'HTTP ' . $httpCode, 'response' => $response];
        }
        
        return ['success' => true, 'data' => json_decode($response, true), 'raw' => $response];
    }
    
    /**
     * Decrypt API key (simple implementation - should be enhanced)
     * @param string $encrypted Encrypted API key
     * @return string Decrypted API key
     */
    protected function decryptApiKey($encrypted) {
        // Simple base64 decode for now - should use proper encryption
        return base64_decode($encrypted);
    }
    
    /**
     * Check rate limit
     * @return bool True if within rate limit
     */
    protected function checkRateLimit() {
        // Implement rate limiting logic
        return true;
    }
}

/**
 * Custom AI Adapter (for custom API endpoints)
 */
class Custom_AI_Adapter extends Base_AI_Adapter {
    public function optimizeContent($content, $keywords, $context = []) {
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Rate limit exceeded'];
        }
        
        $endpoint = $this->config['api_endpoint'] . '/optimize-content';
        $data = [
            'content' => $content,
            'keywords' => $keywords,
            'context' => $context
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    public function researchKeywords($seed, $context = []) {
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Rate limit exceeded'];
        }
        
        $endpoint = $this->config['api_endpoint'] . '/research-keywords';
        $data = [
            'seed' => $seed,
            'context' => $context
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    public function generateMetaTags($content, $context = []) {
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Rate limit exceeded'];
        }
        
        $endpoint = $this->config['api_endpoint'] . '/generate-meta-tags';
        $data = [
            'content' => $content,
            'context' => $context
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    public function analyzeCompetitors($urls, $context = []) {
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Rate limit exceeded'];
        }
        
        $endpoint = $this->config['api_endpoint'] . '/analyze-competitors';
        $data = [
            'urls' => $urls,
            'context' => $context
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    public function getSuggestions($pageData, $context = []) {
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Rate limit exceeded'];
        }
        
        $endpoint = $this->config['api_endpoint'] . '/get-suggestions';
        $data = [
            'page_data' => $pageData,
            'context' => $context
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
}

/**
 * Get AI adapter instance
 * @param string $providerName Provider name
 * @return SEO_AI_Adapter|null Adapter instance or null
 */
function seo_manager_get_ai_adapter($providerName = null) {
    if ($providerName === null) {
        $config = seo_manager_get_default_ai_config();
    } else {
        $config = seo_manager_get_ai_config($providerName);
    }
    
    if (!$config) {
        return null;
    }
    
    // Create adapter based on provider type
    switch ($config['provider_type']) {
        case 'custom':
            return new Custom_AI_Adapter($config);
        default:
            return new Custom_AI_Adapter($config);
    }
}

/**
 * Optimize content using AI
 * @param string $content Content to optimize
 * @param array $keywords Keywords to focus on
 * @param array $context Additional context
 * @return array Optimization result
 */
function seo_manager_ai_optimize_content($content, $keywords = [], $context = []) {
    $adapter = seo_manager_get_ai_adapter();
    if (!$adapter) {
        return ['success' => false, 'error' => 'No AI adapter configured'];
    }
    
    return $adapter->optimizeContent($content, $keywords, $context);
}

/**
 * Research keywords using AI
 * @param string $seed Seed keyword
 * @param array $context Additional context
 * @return array Keyword research result
 */
function seo_manager_ai_research_keywords($seed, $context = []) {
    $adapter = seo_manager_get_ai_adapter();
    if (!$adapter) {
        return ['success' => false, 'error' => 'No AI adapter configured'];
    }
    
    return $adapter->researchKeywords($seed, $context);
}

/**
 * Generate meta tags using AI
 * @param string $content Content
 * @param array $context Additional context
 * @return array Meta tags result
 */
function seo_manager_ai_generate_meta_tags($content, $context = []) {
    $adapter = seo_manager_get_ai_adapter();
    if (!$adapter) {
        return ['success' => false, 'error' => 'No AI adapter configured'];
    }
    
    return $adapter->generateMetaTags($content, $context);
}

