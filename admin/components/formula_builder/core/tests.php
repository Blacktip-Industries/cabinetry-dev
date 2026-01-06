<?php
/**
 * Formula Builder Component - Test Management Functions
 * Manages test cases for formulas
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create new test case
 * @param int $formulaId Formula ID
 * @param string $testName Test name
 * @param array $inputData Input data (will be JSON encoded)
 * @param mixed $expectedResult Expected result (will be JSON encoded)
 * @return array Result with success status and test ID
 */
function formula_builder_create_test($formulaId, $testName, $inputData = [], $expectedResult = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (empty($testName)) {
        return ['success' => false, 'error' => 'Test name is required'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_tests');
        $inputDataJson = json_encode($inputData);
        $expectedResultJson = $expectedResult !== null ? json_encode($expectedResult) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, test_name, input_data, expected_result, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isss", $formulaId, $testName, $inputDataJson, $expectedResultJson);
        $stmt->execute();
        $testId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'test_id' => $testId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating test: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get test by ID
 * @param int $testId Test ID
 * @return array|null Test data or null
 */
function formula_builder_get_test($testId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_tests');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $testId);
        $stmt->execute();
        $result = $stmt->get_result();
        $test = $result->fetch_assoc();
        $stmt->close();
        
        if ($test) {
            // Decode JSON fields
            $test['input_data'] = json_decode($test['input_data'], true) ?: [];
            $test['expected_result'] = $test['expected_result'] ? json_decode($test['expected_result'], true) : null;
            $test['actual_result'] = $test['actual_result'] ? json_decode($test['actual_result'], true) : null;
        }
        
        return $test ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting test: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all tests for a formula
 * @param int $formulaId Formula ID
 * @param array $filters Filter options
 * @return array Array of tests
 */
function formula_builder_get_tests($formulaId, $filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_tests');
        
        $where = ["formula_id = ?"];
        $params = [$formulaId];
        $types = 'i';
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Sort order
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        $orderBy = "ORDER BY {$sortBy} {$sortOrder}";
        
        $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy}";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tests = [];
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            $row['input_data'] = json_decode($row['input_data'], true) ?: [];
            $row['expected_result'] = $row['expected_result'] ? json_decode($row['expected_result'], true) : null;
            $row['actual_result'] = $row['actual_result'] ? json_decode($row['actual_result'], true) : null;
            $tests[] = $row;
        }
        
        $stmt->close();
        return $tests;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting tests: " . $e->getMessage());
        return [];
    }
}

/**
 * Update test case
 * @param int $testId Test ID
 * @param array $testData Test data to update
 * @return array Result with success status
 */
function formula_builder_update_test($testId, $testData) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_tests');
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($testData['test_name'])) {
            $updates[] = "test_name = ?";
            $params[] = $testData['test_name'];
            $types .= 's';
        }
        
        if (isset($testData['input_data'])) {
            $updates[] = "input_data = ?";
            $params[] = json_encode($testData['input_data']);
            $types .= 's';
        }
        
        if (isset($testData['expected_result'])) {
            $updates[] = "expected_result = ?";
            $params[] = $testData['expected_result'] !== null ? json_encode($testData['expected_result']) : null;
            $types .= 's';
        }
        
        if (isset($testData['status'])) {
            $updates[] = "status = ?";
            $params[] = $testData['status'];
            $types .= 's';
        }
        
        if (isset($testData['actual_result'])) {
            $updates[] = "actual_result = ?";
            $params[] = $testData['actual_result'] !== null ? json_encode($testData['actual_result']) : null;
            $types .= 's';
        }
        
        if (isset($testData['execution_time_ms'])) {
            $updates[] = "execution_time_ms = ?";
            $params[] = $testData['execution_time_ms'];
            $types .= 'i';
        }
        
        if (isset($testData['last_run_at'])) {
            $updates[] = "last_run_at = ?";
            $params[] = $testData['last_run_at'];
            $types .= 's';
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $testId;
        $types .= 'i';
        
        $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error updating test: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete test case
 * @param int $testId Test ID
 * @return array Result with success status
 */
function formula_builder_delete_test($testId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_tests');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $testId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error deleting test: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get test statistics for formula
 * @param int $formulaId Formula ID
 * @return array Statistics
 */
function formula_builder_get_test_stats($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'pending' => 0,
            'error' => 0,
            'pass_rate' => 0,
            'coverage' => 0
        ];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_tests');
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM {$tableName} WHERE formula_id = ? GROUP BY status");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'pending' => 0,
            'error' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $count = (int)$row['count'];
            $stats[$status] = $count;
            $stats['total'] += $count;
        }
        
        $stmt->close();
        
        // Calculate pass rate
        $runTests = $stats['passed'] + $stats['failed'] + $stats['error'];
        $stats['pass_rate'] = $runTests > 0 ? round(($stats['passed'] / $runTests) * 100, 2) : 0;
        
        // Calculate coverage (simplified - based on number of tests)
        // This is a placeholder - real coverage would analyze formula code
        $stats['coverage'] = min(100, $stats['total'] * 10); // Simple heuristic
        
        return $stats;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting test stats: " . $e->getMessage());
        return [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'pending' => 0,
            'error' => 0,
            'pass_rate' => 0,
            'coverage' => 0
        ];
    }
}

