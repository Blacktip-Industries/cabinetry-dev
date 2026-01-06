<?php
/**
 * Formula Builder Component - Advanced Debugger
 * Provides debugging capabilities for formulas
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/executor.php';

/**
 * Create debug session
 * @param int $formulaId Formula ID
 * @param array $inputData Input data
 * @param int $createdBy User ID
 * @return array Result with session ID
 */
function formula_builder_create_debug_session($formulaId, $inputData = [], $createdBy = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if ($createdBy === null) {
        $createdBy = $_SESSION['user_id'] ?? 0;
    }
    
    try {
        $sessionData = json_encode([
            'formula_id' => $formulaId,
            'input_data' => $inputData,
            'breakpoints' => [],
            'current_line' => 0,
            'variables' => [],
            'call_stack' => []
        ]);
        
        $tableName = formula_builder_get_table_name('debug_sessions');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, session_data, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $formulaId, $sessionData, $createdBy);
        $stmt->execute();
        $sessionId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'session_id' => $sessionId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating debug session: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get debug session
 * @param int $sessionId Session ID
 * @return array|null Session data or null
 */
function formula_builder_get_debug_session($sessionId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('debug_sessions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        if ($session) {
            $session['session_data'] = json_decode($session['session_data'], true);
            $session['breakpoints'] = json_decode($session['breakpoints'], true) ?: [];
        }
        
        return $session ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting debug session: " . $e->getMessage());
        return null;
    }
}

/**
 * Set breakpoint
 * @param int $sessionId Session ID
 * @param int $lineNumber Line number
 * @return array Result
 */
function formula_builder_set_breakpoint($sessionId, $lineNumber) {
    $session = formula_builder_get_debug_session($sessionId);
    if (!$session) {
        return ['success' => false, 'error' => 'Session not found'];
    }
    
    $breakpoints = json_decode($session['breakpoints'], true) ?: [];
    if (!in_array($lineNumber, $breakpoints)) {
        $breakpoints[] = $lineNumber;
    }
    
    return formula_builder_update_debug_session($sessionId, ['breakpoints' => $breakpoints]);
}

/**
 * Remove breakpoint
 * @param int $sessionId Session ID
 * @param int $lineNumber Line number
 * @return array Result
 */
function formula_builder_remove_breakpoint($sessionId, $lineNumber) {
    $session = formula_builder_get_debug_session($sessionId);
    if (!$session) {
        return ['success' => false, 'error' => 'Session not found'];
    }
    
    $breakpoints = json_decode($session['breakpoints'], true) ?: [];
    $breakpoints = array_filter($breakpoints, function($bp) use ($lineNumber) {
        return $bp != $lineNumber;
    });
    $breakpoints = array_values($breakpoints);
    
    return formula_builder_update_debug_session($sessionId, ['breakpoints' => $breakpoints]);
}

/**
 * Update debug session
 * @param int $sessionId Session ID
 * @param array $data Data to update
 * @return array Result
 */
function formula_builder_update_debug_session($sessionId, $data) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('debug_sessions');
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['session_data'])) {
            $updates[] = "session_data = ?";
            $params[] = is_array($data['session_data']) ? json_encode($data['session_data']) : $data['session_data'];
            $types .= 's';
        }
        
        if (isset($data['breakpoints'])) {
            $updates[] = "breakpoints = ?";
            $params[] = json_encode($data['breakpoints']);
            $types .= 's';
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $updates[] = "last_accessed = NOW()";
        $params[] = $sessionId;
        $types .= 'i';
        
        $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error updating debug session: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get execution trace
 * @param int $formulaId Formula ID
 * @param array $inputData Input data
 * @return array Execution trace
 */
function formula_builder_get_execution_trace($formulaId, $inputData = []) {
    // This is a simplified trace - full implementation would require
    // instrumenting the executor to track each step
    $formula = formula_builder_get_formula_by_id($formulaId);
    if (!$formula) {
        return ['success' => false, 'error' => 'Formula not found'];
    }
    
    $trace = [
        'formula_id' => $formulaId,
        'input_data' => $inputData,
        'steps' => [],
        'variables' => [],
        'execution_time' => 0
    ];
    
    // Execute and collect trace
    $startTime = microtime(true);
    $result = formula_builder_execute_formula($formulaId, $inputData);
    $trace['execution_time'] = (microtime(true) - $startTime) * 1000;
    
    $trace['result'] = $result;
    $trace['success'] = $result['success'];
    
    return $trace;
}

/**
 * Inspect variables at current execution point
 * @param int $sessionId Session ID
 * @return array Variables
 */
function formula_builder_inspect_variables($sessionId) {
    $session = formula_builder_get_debug_session($sessionId);
    if (!$session) {
        return ['success' => false, 'error' => 'Session not found'];
    }
    
    $sessionData = $session['session_data'];
    return [
        'success' => true,
        'variables' => $sessionData['variables'] ?? [],
        'current_line' => $sessionData['current_line'] ?? 0
    ];
}

