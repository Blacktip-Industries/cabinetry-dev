<?php
/**
 * Formula Builder Component - Formula Executor
 * Executes formulas in a secure sandboxed environment
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/parser.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/ast_nodes.php';

/**
 * Execute formula
 * @param int $formulaId Formula ID
 * @param array $inputData Input data (option values)
 * @return array Result with success status, result value, and error message
 */
function formula_builder_execute_formula($formulaId, $inputData = []) {
    // Get formula
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        return [
            'success' => false,
            'result' => null,
            'error' => 'Formula not found'
        ];
    }
    
    // Check cache
    if ($formula['cache_enabled']) {
        $cached = formula_builder_get_cached_result($formulaId, $inputData);
        if ($cached !== false) {
            return [
                'success' => true,
                'result' => $cached,
                'cached' => true
            ];
        }
    }
    
    // Validate formula code
    $validation = formula_builder_validate_formula($formula['formula_code']);
    if (!$validation['success']) {
        return [
            'success' => false,
            'result' => null,
            'error' => 'Formula validation failed: ' . implode(', ', $validation['errors'])
        ];
    }
    
    // Sanitize formula code
    $formulaCode = formula_builder_sanitize_formula_code($formula['formula_code']);
    
    // Execute formula (simplified version - full implementation would use proper parser)
    try {
        $startTime = microtime(true);
        $result = formula_builder_execute_formula_code($formulaCode, $inputData, $formulaId);
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Record analytics
        require_once __DIR__ . '/analytics.php';
        formula_builder_record_metric('execution', $executionTime, [
            'formula_id' => $formulaId,
            'success' => $result['success'],
            'execution_time' => $executionTime,
            'error' => $result['error'] ?? null
        ], $formulaId);
        
        // Emit event
        require_once __DIR__ . '/events.php';
        formula_builder_emit_event('formula.executed', $formulaId, $_SESSION['user_id'] ?? null, [
            'success' => $result['success'],
            'execution_time' => $executionTime
        ]);
        
        // Cache result if enabled
        if ($formula['cache_enabled'] && $result['success']) {
            formula_builder_cache_result($formulaId, $inputData, $result['result'], $formula['cache_duration']);
        }
        
        // Log execution
        formula_builder_log_execution($formulaId, $inputData, $result);
        
        return $result;
    } catch (Exception $e) {
        return [
            'success' => false,
            'result' => null,
            'error' => 'Execution error: ' . $e->getMessage()
        ];
    }
}

/**
 * Execute formula code directly using AST parser
 * @param string $formulaCode Formula code
 * @param array $inputData Input data
 * @param int $formulaId Formula ID (for context)
 * @return array Result with success status and result value
 */
function formula_builder_execute_formula_code($formulaCode, $inputData = [], $formulaId = null) {
    try {
        // Parse formula into AST
        if (!function_exists('formula_builder_parse_ast')) {
            // Fallback to old simple executor if parser not available
            return formula_builder_execute_formula_code_simple($formulaCode, $inputData, $formulaId);
        }
        
        $ast = formula_builder_parse_ast($formulaCode);
        
        // Create execution context
        $context = [
            'variables' => [],
            'inputData' => $inputData,
            'formulaId' => $formulaId,
            'functions' => formula_builder_get_function_map(),
            'scope' => 'global'
        ];
        
        // Execute AST statements
        $returnValue = null;
        foreach ($ast as $statement) {
            $result = formula_builder_execute_node($statement, $context);
            
            // If this is a return statement, capture the value
            if ($statement instanceof ReturnNode) {
                $returnValue = $result;
                break;
            }
        }
        
        if ($returnValue === null) {
            return [
                'success' => false,
                'result' => null,
                'error' => 'No return statement found or return value is null'
            ];
        }
        
        return [
            'success' => true,
            'result' => $returnValue
        ];
        
    } catch (Exception $e) {
        $errorMessage = 'Execution error: ' . $e->getMessage();
        if (isset($e->line) && is_numeric($e->line)) {
            $errorMessage .= ' at line ' . $e->line;
        }
        
        // Log error for debugging
        error_log("Formula Builder Execution Error: {$errorMessage}");
        
        return [
            'success' => false,
            'result' => null,
            'error' => $errorMessage,
            'stack_trace' => $e->getTraceAsString()
        ];
    }
}

/**
 * Simple executor fallback (for backward compatibility)
 */
function formula_builder_execute_formula_code_simple($formulaCode, $inputData = [], $formulaId = null) {
    if (preg_match('/return\s+(.+?);?$/s', $formulaCode, $matches)) {
        $expression = trim($matches[1]);
        
        foreach ($inputData as $key => $value) {
            $expression = str_replace('get_option(\'' . $key . '\')', $value, $expression);
            $expression = str_replace('$' . $key, $value, $expression);
        }
        
        if (preg_match('/^[0-9+\-*\/\(\)\s\.]+$/', $expression)) {
            try {
                $result = @eval("return {$expression};");
                return [
                    'success' => true,
                    'result' => is_numeric($result) ? (float)$result : $result
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'result' => null,
                    'error' => 'Evaluation error: ' . $e->getMessage()
                ];
            }
        }
    }
    
    return [
        'success' => false,
        'result' => null,
        'error' => 'No return statement found'
    ];
}

/**
 * Execute AST node
 * @param ASTNode $node AST node to execute
 * @param array $context Execution context
 * @return mixed Result value
 */
function formula_builder_execute_node($node, &$context) {
    if ($node === null) {
        return null;
    }
    
    try {
        switch ($node->type) {
        case 'VariableDeclaration':
            return formula_builder_execute_variable_declaration($node, $context);
        
        case 'FunctionCall':
            return formula_builder_execute_function_call($node, $context);
        
        case 'BinaryExpression':
            return formula_builder_execute_binary_expression($node, $context);
        
        case 'Conditional':
            return formula_builder_execute_conditional($node, $context);
        
        case 'Loop':
            return formula_builder_execute_loop($node, $context);
        
        case 'Return':
            return formula_builder_execute_return($node, $context);
        
        case 'Identifier':
            return formula_builder_execute_identifier($node, $context);
        
        case 'Literal':
            return $node->value;
        
        case 'ObjectLiteral':
            return formula_builder_execute_object_literal($node, $context);
        
        case 'ArrayLiteral':
            return formula_builder_execute_array_literal($node, $context);
        
        case 'MemberAccess':
            return formula_builder_execute_member_access($node, $context);
        
        default:
            throw new Exception("Unknown node type: {$node->type}");
        }
    } catch (Exception $e) {
        // Add line/column info to error
        $line = isset($node->line) ? $node->line : 'unknown';
        $column = isset($node->column) ? $node->column : 'unknown';
        throw new Exception($e->getMessage() . " (at line {$line}, column {$column})");
    }
}

/**
 * Execute variable declaration
 */
function formula_builder_execute_variable_declaration($node, &$context) {
    $value = null;
    if ($node->value !== null) {
        $value = formula_builder_execute_node($node->value, $context);
    }
    $context['variables'][$node->name] = $value;
    return $value;
}

/**
 * Execute function call
 */
function formula_builder_execute_function_call($node, &$context) {
    // Security check
    if (!formula_builder_is_function_allowed($node->name)) {
        formula_builder_log_security_event('unauthorized_function', "Attempted to call unauthorized function: {$node->name}", [
            'formula_id' => $context['formulaId'],
            'function_name' => $node->name
        ]);
        throw new Exception("Unauthorized function call: {$node->name} at line {$node->line}");
    }
    
    // Evaluate arguments
    $args = [];
    foreach ($node->arguments as $arg) {
        $args[] = formula_builder_execute_node($arg, $context);
    }
    
    // Map function name to PHP function
    $functionMap = $context['functions'];
    if (isset($functionMap[$node->name])) {
        $phpFunction = $functionMap[$node->name];
        if (function_exists($phpFunction)) {
            // Some functions need inputData as last parameter
            if (in_array($node->name, ['get_option', 'get_all_options'])) {
                $args[] = $context['inputData'];
            }
            return call_user_func_array($phpFunction, $args);
        }
    }
    
    throw new Exception("Function not found: {$node->name} at line {$node->line}");
}

/**
 * Execute binary expression
 */
function formula_builder_execute_binary_expression($node, &$context) {
    $left = formula_builder_execute_node($node->left, $context);
    $right = formula_builder_execute_node($node->right, $context);
    
    // Handle unary operators
    if ($node->left === null) {
        switch ($node->operator) {
            case '-': return -$right;
            case '!': return !$right;
            default: throw new Exception("Unknown unary operator: {$node->operator}");
        }
    }
    
    switch ($node->operator) {
        case '+': return $left + $right;
        case '-': return $left - $right;
        case '*': return $left * $right;
        case '/': 
            if ($right == 0) {
                throw new Exception("Division by zero at line {$node->line}");
            }
            return $left / $right;
        case '%': return $left % $right;
        case '==': return $left == $right;
        case '!=': return $left != $right;
        case '===': return $left === $right;
        case '!==': return $left !== $right;
        case '<': return $left < $right;
        case '>': return $left > $right;
        case '<=': return $left <= $right;
        case '>=': return $left >= $right;
        case '&&': return $left && $right;
        case '||': return $left || $right;
        default: throw new Exception("Unknown operator: {$node->operator} at line {$node->line}");
    }
}

/**
 * Execute conditional
 */
function formula_builder_execute_conditional($node, &$context) {
    $condition = formula_builder_execute_node($node->condition, $context);
    
    if ($condition) {
        return formula_builder_execute_statements($node->thenBranch, $context);
    } elseif ($node->elseBranch !== null) {
        return formula_builder_execute_statements($node->elseBranch, $context);
    }
    
    return null;
}

/**
 * Execute loop
 */
function formula_builder_execute_loop($node, &$context) {
    $maxIterations = 10000; // Safety limit
    $iterations = 0;
    
    if ($node->loopType === 'for') {
        // Execute init
        if ($node->init !== null) {
            formula_builder_execute_node($node->init, $context);
        }
        
        // Loop while condition is true
        while ($iterations < $maxIterations) {
            $iterations++;
            
            if ($node->condition !== null) {
                $condition = formula_builder_execute_node($node->condition, $context);
                if (!$condition) {
                    break;
                }
            }
            
            // Execute body
            $bodyResult = formula_builder_execute_statements($node->body, $context);
            
            // Check if return was executed
            if ($bodyResult !== null && isset($context['_return_value'])) {
                return $context['_return_value'];
            }
            
            // Execute update
            if ($node->update !== null) {
                formula_builder_execute_node($node->update, $context);
            }
        }
        
        if ($iterations >= $maxIterations) {
            throw new Exception("Loop exceeded maximum iterations ({$maxIterations}) at line {$node->line}");
        }
    } elseif ($node->loopType === 'while') {
        while ($iterations < $maxIterations) {
            $iterations++;
            
            if ($node->condition !== null) {
                $condition = formula_builder_execute_node($node->condition, $context);
                if (!$condition) {
                    break;
                }
            }
            
            $bodyResult = formula_builder_execute_statements($node->body, $context);
            
            // Check if return was executed
            if ($bodyResult !== null && isset($context['_return_value'])) {
                return $context['_return_value'];
            }
        }
        
        if ($iterations >= $maxIterations) {
            throw new Exception("Loop exceeded maximum iterations ({$maxIterations}) at line {$node->line}");
        }
    }
    
    return null;
}

/**
 * Execute return statement
 */
function formula_builder_execute_return($node, &$context) {
    if ($node->value !== null) {
        $value = formula_builder_execute_node($node->value, $context);
        $context['_return_value'] = $value;
        return $value;
    }
    $context['_return_value'] = null;
    return null;
}

/**
 * Execute identifier (variable lookup)
 */
function formula_builder_execute_identifier($node, &$context) {
    $name = $node->name;
    
    // Check in variables first
    if (isset($context['variables'][$name])) {
        return $context['variables'][$name];
    }
    
    // Check in inputData
    if (isset($context['inputData'][$name])) {
        return $context['inputData'][$name];
    }
    
    throw new Exception("Undefined variable: {$name} at line {$node->line}");
}

/**
 * Execute object literal
 */
function formula_builder_execute_object_literal($node, &$context) {
    $obj = [];
    foreach ($node->properties as $key => $valueNode) {
        $obj[$key] = formula_builder_execute_node($valueNode, $context);
    }
    return $obj;
}

/**
 * Execute array literal
 */
function formula_builder_execute_array_literal($node, &$context) {
    $arr = [];
    foreach ($node->elements as $element) {
        $arr[] = formula_builder_execute_node($element, $context);
    }
    return $arr;
}

/**
 * Execute member access
 */
function formula_builder_execute_member_access($node, &$context) {
    $object = formula_builder_execute_node($node->object, $context);
    
    if ($node->isComputed) {
        $index = formula_builder_execute_node($node->property, $context);
        return isset($object[$index]) ? $object[$index] : null;
    } else {
        $property = $node->property->name;
        return isset($object[$property]) ? $object[$property] : null;
    }
}

/**
 * Execute statements (block)
 */
function formula_builder_execute_statements($statements, &$context) {
    if (!is_array($statements)) {
        $statements = [$statements];
    }
    
    $lastValue = null;
    foreach ($statements as $stmt) {
        $result = formula_builder_execute_node($stmt, $context);
        
        // Check if return was executed
        if (isset($context['_return_value'])) {
            return $context['_return_value'];
        }
        
        $lastValue = $result;
    }
    
    return $lastValue;
}

/**
 * Get function name mapping
 */
function formula_builder_get_function_map() {
    return [
        'get_option' => 'formula_builder_get_option',
        'get_all_options' => 'formula_builder_get_all_options',
        'query_table' => 'formula_builder_query_table',
        'calculate_sqm' => 'formula_builder_calculate_sqm',
        'calculate_linear_meters' => 'formula_builder_calculate_linear_meters',
        'calculate_volume' => 'formula_builder_calculate_volume'
    ];
}

/**
 * Log formula execution
 * @param int $formulaId Formula ID
 * @param array $inputData Input data
 * @param array $result Execution result
 */
function formula_builder_log_execution($formulaId, $inputData, $result) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    try {
        $tableName = formula_builder_get_table_name('execution_log');
        $executionTime = isset($result['execution_time_ms']) ? $result['execution_time_ms'] : 0;
        $inputDataJson = json_encode($inputData);
        $outputDataJson = json_encode($result);
        $errorMessage = isset($result['error']) ? $result['error'] : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, execution_time_ms, input_data, output_data, error_message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $formulaId, $executionTime, $inputDataJson, $outputDataJson, $errorMessage);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Formula Builder: Error logging execution: " . $e->getMessage());
    }
}

