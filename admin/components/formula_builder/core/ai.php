<?php
/**
 * Formula Builder Component - AI/ML Integration
 * AI-powered features for formula development
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/parser.php';

/**
 * AI suggest code
 * @param int $formulaId Formula ID
 * @param string $context Context or partial code
 * @param string $suggestionType Suggestion type (completion, optimization, error_fix)
 * @return array Suggestions
 */
function formula_builder_ai_suggest_code($formulaId, $context, $suggestionType = 'completion') {
    // Placeholder for AI integration
    // In production, this would call an AI service (OpenAI, Anthropic, etc.)
    
    $suggestions = [];
    
    // Simple pattern-based suggestions (placeholder)
    if ($suggestionType === 'completion') {
        // Detect common patterns and suggest completions
        if (preg_match('/var\s+\w+\s*=\s*get_option\(/', $context)) {
            $suggestions[] = [
                'type' => 'completion',
                'suggestion' => 'Consider using calculate_sqm() for area calculations',
                'confidence' => 0.7
            ];
        }
    }
    
    // Save suggestion to database
    if (!empty($suggestions)) {
        formula_builder_save_ai_suggestion($formulaId, $suggestionType, $suggestions[0]['suggestion'], $suggestions[0]['confidence']);
    }
    
    return [
        'success' => true,
        'suggestions' => $suggestions
    ];
}

/**
 * AI detect errors
 * @param string $formulaCode Formula code
 * @return array Detected errors and fixes
 */
function formula_builder_ai_detect_errors($formulaCode) {
    // Placeholder for AI error detection
    // In production, this would use AI to detect logical errors, not just syntax
    
    $errors = [];
    
    // Basic pattern detection (placeholder)
    if (preg_match('/get_option\([^)]*\)\s*[+\-*\/]\s*get_option\([^)]*\)/', $formulaCode)) {
        $errors[] = [
            'type' => 'potential_error',
            'message' => 'Direct arithmetic on get_option() results - consider storing in variables first',
            'line' => 1,
            'suggestion' => 'Store option values in variables before calculations'
        ];
    }
    
    return [
        'success' => true,
        'errors' => $errors
    ];
}

/**
 * AI optimize code
 * @param string $formulaCode Formula code
 * @return array Optimization suggestions
 */
function formula_builder_ai_optimize_code($formulaCode) {
    // Placeholder for AI optimization
    $optimizations = [];
    
    // Detect repeated get_option calls
    if (preg_match_all('/get_option\([^)]+\)/', $formulaCode, $matches)) {
        $optionCalls = array_count_values($matches[0]);
        foreach ($optionCalls as $call => $count) {
            if ($count > 1) {
                $optimizations[] = [
                    'type' => 'optimization',
                    'message' => "Repeated call: {$call} - consider caching in a variable",
                    'confidence' => 0.8
                ];
            }
        }
    }
    
    return [
        'success' => true,
        'optimizations' => $optimizations
    ];
}

/**
 * Natural language to formula
 * @param string $naturalLanguage Natural language description
 * @return array Generated formula code
 */
function formula_builder_ai_natural_language_to_formula($naturalLanguage) {
    // Placeholder for NL to formula conversion
    // In production, this would use a language model to convert natural language to formula code
    
    // Simple keyword-based conversion (placeholder)
    $formulaCode = '';
    
    if (preg_match('/calculate.*price/i', $naturalLanguage)) {
        $formulaCode = "var base_price = get_option('base_price');\nreturn base_price;";
    } elseif (preg_match('/material.*cost/i', $naturalLanguage)) {
        $formulaCode = "var material = get_option('material');\nvar material_data = query_table('manufacturing_materials', {'name': material});\nreturn material_data.sell_sqm;";
    }
    
    return [
        'success' => !empty($formulaCode),
        'formula_code' => $formulaCode,
        'confidence' => 0.6
    ];
}

/**
 * Save AI suggestion
 * @param int $formulaId Formula ID
 * @param string $suggestionType Suggestion type
 * @param string $suggestionText Suggestion text
 * @param float $confidenceScore Confidence score
 * @return array Result
 */
function formula_builder_save_ai_suggestion($formulaId, $suggestionType, $suggestionText, $confidenceScore) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('ai_suggestions');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, suggestion_type, suggestion_text, confidence_score) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $formulaId, $suggestionType, $suggestionText, $confidenceScore);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error saving AI suggestion: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get AI suggestions
 * @param int $formulaId Formula ID
 * @param string|null $suggestionType Suggestion type filter
 * @return array Suggestions
 */
function formula_builder_get_ai_suggestions($formulaId, $suggestionType = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('ai_suggestions');
        
        if ($suggestionType) {
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? AND suggestion_type = ? ORDER BY created_at DESC");
            $stmt->bind_param("is", $formulaId, $suggestionType);
        } else {
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $formulaId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $suggestions = [];
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row;
        }
        
        $stmt->close();
        return $suggestions;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting AI suggestions: " . $e->getMessage());
        return [];
    }
}

