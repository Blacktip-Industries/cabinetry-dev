<?php
/**
 * Formula Builder Component - Quality Checks
 * Provides quality analysis for formulas
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/parser.php';
require_once __DIR__ . '/security.php';

/**
 * Run quality check on formula
 * @param int $formulaId Formula ID
 * @return array Quality report
 */
function formula_builder_run_quality_check($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        return ['success' => false, 'error' => 'Formula not found'];
    }
    
    $report = [
        'formula_id' => $formulaId,
        'quality_score' => 0,
        'complexity_score' => 0,
        'security_score' => 0,
        'performance_score' => 0,
        'issues' => [],
        'suggestions' => []
    ];
    
    // Calculate complexity
    $complexity = formula_builder_calculate_complexity($formula['formula_code']);
    $report['complexity_score'] = $complexity['score'];
    
    // Security audit
    $security = formula_builder_audit_security($formula['formula_code']);
    $report['security_score'] = $security['score'];
    $report['issues'] = array_merge($report['issues'], $security['issues']);
    
    // Performance analysis
    $performance = formula_builder_analyze_performance($formula['formula_code']);
    $report['performance_score'] = $performance['score'];
    $report['issues'] = array_merge($report['issues'], $performance['issues']);
    
    // Code quality suggestions
    $suggestions = formula_builder_get_quality_suggestions($formula['formula_code']);
    $report['suggestions'] = $suggestions;
    
    // Calculate overall quality score
    $report['quality_score'] = (
        (100 - $complexity['score']) * 0.3 +
        $security['score'] * 0.4 +
        $performance['score'] * 0.3
    );
    
    // Save report
    formula_builder_save_quality_report($formulaId, $report);
    
    return [
        'success' => true,
        'report' => $report
    ];
}

/**
 * Calculate code complexity
 * @param string $formulaCode Formula code
 * @return array Complexity analysis
 */
function formula_builder_calculate_complexity($formulaCode) {
    $complexity = [
        'score' => 0,
        'cyclomatic_complexity' => 1, // Base complexity
        'lines_of_code' => 0,
        'nesting_depth' => 0
    ];
    
    $lines = explode("\n", $formulaCode);
    $complexity['lines_of_code'] = count(array_filter($lines, function($line) {
        return trim($line) && !preg_match('/^\s*\/\//', $line);
    }));
    
    // Count decision points (if, for, while, etc.)
    $decisionPoints = preg_match_all('/\b(if|else|for|while|foreach|switch|case)\b/i', $formulaCode);
    $complexity['cyclomatic_complexity'] += $decisionPoints;
    
    // Calculate nesting depth
    $maxDepth = 0;
    $currentDepth = 0;
    foreach ($lines as $line) {
        if (preg_match('/\{/', $line)) {
            $currentDepth++;
            $maxDepth = max($maxDepth, $currentDepth);
        }
        if (preg_match('/\}/', $line)) {
            $currentDepth = max(0, $currentDepth - 1);
        }
    }
    $complexity['nesting_depth'] = $maxDepth;
    
    // Calculate complexity score (0-100, higher is more complex)
    $complexity['score'] = min(100, 
        ($complexity['cyclomatic_complexity'] * 5) +
        ($complexity['lines_of_code'] / 10) +
        ($complexity['nesting_depth'] * 10)
    );
    
    return $complexity;
}

/**
 * Audit security
 * @param string $formulaCode Formula code
 * @return array Security audit
 */
function formula_builder_audit_security($formulaCode) {
    $audit = [
        'score' => 100,
        'issues' => []
    ];
    
    // Check for dangerous functions
    $dangerousFunctions = [
        'eval', 'exec', 'system', 'shell_exec', 'passthru',
        'file_get_contents', 'fopen', 'curl_exec', 'file_put_contents'
    ];
    
    foreach ($dangerousFunctions as $func) {
        if (preg_match('/\b' . preg_quote($func) . '\s*\(/i', $formulaCode)) {
            $audit['score'] -= 20;
            $audit['issues'][] = [
                'type' => 'security',
                'severity' => 'high',
                'message' => "Use of dangerous function: {$func}",
                'recommendation' => "Remove or replace with safe alternative"
            ];
        }
    }
    
    // Check for SQL injection risks
    if (preg_match('/\$.*\s*\.\s*["\']/i', $formulaCode)) {
        $audit['score'] -= 15;
        $audit['issues'][] = [
            'type' => 'security',
            'severity' => 'medium',
            'message' => 'Potential SQL injection risk',
            'recommendation' => 'Use parameterized queries'
        ];
    }
    
    // Check for XSS risks
    if (preg_match('/echo\s+\$|print\s+\$/i', $formulaCode)) {
        $audit['score'] -= 10;
        $audit['issues'][] = [
            'type' => 'security',
            'severity' => 'medium',
            'message' => 'Potential XSS risk',
            'recommendation' => 'Sanitize output with htmlspecialchars()'
        ];
    }
    
    $audit['score'] = max(0, $audit['score']);
    
    return $audit;
}

/**
 * Analyze performance
 * @param string $formulaCode Formula code
 * @return array Performance analysis
 */
function formula_builder_analyze_performance($formulaCode) {
    $analysis = [
        'score' => 100,
        'issues' => []
    ];
    
    // Check for nested loops
    $loopCount = preg_match_all('/\b(for|while|foreach)\s*\(/i', $formulaCode);
    if ($loopCount > 2) {
        $analysis['score'] -= 15;
        $analysis['issues'][] = [
            'type' => 'performance',
            'severity' => 'medium',
            'message' => 'Multiple nested loops detected',
            'recommendation' => 'Consider optimizing loop structure'
        ];
    }
    
    // Check for recursive calls
    if (preg_match('/function\s+\w+\s*\([^)]*\)\s*\{[^}]*\w+\s*\(/i', $formulaCode)) {
        $analysis['score'] -= 10;
        $analysis['issues'][] = [
            'type' => 'performance',
            'severity' => 'medium',
            'message' => 'Recursive function calls detected',
            'recommendation' => 'Ensure proper termination conditions'
        ];
    }
    
    // Check for large array operations
    $codeLength = strlen($formulaCode);
    if ($codeLength > 2000 && preg_match('/\.\s*(map|filter|reduce)\s*\(/i', $formulaCode)) {
        $analysis['score'] -= 5;
        $analysis['issues'][] = [
            'type' => 'performance',
            'severity' => 'low',
            'message' => 'Large array operations may be slow',
            'recommendation' => 'Consider caching or optimization'
        ];
    }
    
    $analysis['score'] = max(0, $analysis['score']);
    
    return $analysis;
}

/**
 * Get quality suggestions
 * @param string $formulaCode Formula code
 * @return array Suggestions
 */
function formula_builder_get_quality_suggestions($formulaCode) {
    $suggestions = [];
    
    // Check for magic numbers
    if (preg_match_all('/\b\d{3,}\b/', $formulaCode, $matches)) {
        $suggestions[] = [
            'type' => 'code_quality',
            'message' => 'Consider using named constants instead of magic numbers',
            'severity' => 'low'
        ];
    }
    
    // Check for long functions
    $lines = explode("\n", $formulaCode);
    if (count($lines) > 50) {
        $suggestions[] = [
            'type' => 'code_quality',
            'message' => 'Function is quite long - consider breaking into smaller functions',
            'severity' => 'low'
        ];
    }
    
    // Check for code duplication
    $tokens = formula_builder_tokenize($formulaCode);
    $tokenStrings = array_map(function($t) { return $t->value; }, $tokens);
    $uniqueTokens = array_unique($tokenStrings);
    if (count($tokenStrings) > count($uniqueTokens) * 2) {
        $suggestions[] = [
            'type' => 'code_quality',
            'message' => 'Potential code duplication detected',
            'severity' => 'low'
        ];
    }
    
    return $suggestions;
}

/**
 * Get quality score
 * @param int $formulaId Formula ID
 * @return float Quality score (0-100)
 */
function formula_builder_get_quality_score($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = formula_builder_get_table_name('quality_reports');
        $stmt = $conn->prepare("SELECT quality_score FROM {$tableName} WHERE formula_id = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (float)$row['quality_score'] : 0;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting quality score: " . $e->getMessage());
        return 0;
    }
}

/**
 * Save quality report
 * @param int $formulaId Formula ID
 * @param array $report Report data
 * @return array Result
 */
function formula_builder_save_quality_report($formulaId, $report) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('quality_reports');
        $issuesJson = json_encode($report['issues']);
        $suggestionsJson = json_encode($report['suggestions']);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, quality_score, complexity_score, security_score, performance_score, issues, suggestions) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idddss", 
            $formulaId,
            $report['quality_score'],
            $report['complexity_score'],
            $report['security_score'],
            $report['performance_score'],
            $issuesJson,
            $suggestionsJson
        );
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error saving quality report: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get security issues
 * @param int $formulaId Formula ID
 * @return array Security issues
 */
function formula_builder_get_security_issues($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('quality_reports');
        $stmt = $conn->prepare("SELECT issues FROM {$tableName} WHERE formula_id = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row && $row['issues']) {
            $issues = json_decode($row['issues'], true);
            return array_filter($issues, function($issue) {
                return $issue['type'] === 'security';
            });
        }
        
        return [];
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting security issues: " . $e->getMessage());
        return [];
    }
}

/**
 * Get performance issues
 * @param int $formulaId Formula ID
 * @return array Performance issues
 */
function formula_builder_get_performance_issues($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('quality_reports');
        $stmt = $conn->prepare("SELECT issues FROM {$tableName} WHERE formula_id = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row && $row['issues']) {
            $issues = json_decode($row['issues'], true);
            return array_filter($issues, function($issue) {
                return $issue['type'] === 'performance';
            });
        }
        
        return [];
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting performance issues: " . $e->getMessage());
        return [];
    }
}

