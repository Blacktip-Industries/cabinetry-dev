<?php
/**
 * Formula Builder Component - CI/CD Integration
 * Pipeline management and execution
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/tests.php';
require_once __DIR__ . '/quality.php';
require_once __DIR__ . '/test_executor.php';

/**
 * Create CI/CD pipeline
 * @param int $formulaId Formula ID
 * @param string $pipelineName Pipeline name
 * @param string $triggerType Trigger type
 * @param array $stages Pipeline stages
 * @return array Result with pipeline ID
 */
function formula_builder_create_pipeline($formulaId, $pipelineName, $triggerType, $stages = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $defaultStages = [
            ['name' => 'test', 'enabled' => true],
            ['name' => 'quality', 'enabled' => true],
            ['name' => 'security', 'enabled' => true],
            ['name' => 'deploy', 'enabled' => false]
        ];
        
        $stages = !empty($stages) ? $stages : $defaultStages;
        $stagesJson = json_encode($stages);
        
        $tableName = formula_builder_get_table_name('cicd_pipelines');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, pipeline_name, trigger_type, stages) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $formulaId, $pipelineName, $triggerType, $stagesJson);
        $stmt->execute();
        $pipelineId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'pipeline_id' => $pipelineId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating pipeline: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Run pipeline
 * @param int $pipelineId Pipeline ID
 * @return array Result with run ID
 */
function formula_builder_run_pipeline($pipelineId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get pipeline
        $tableName = formula_builder_get_table_name('cicd_pipelines');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pipelineId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pipeline = $result->fetch_assoc();
        $stmt->close();
        
        if (!$pipeline) {
            return ['success' => false, 'error' => 'Pipeline not found'];
        }
        
        // Get next run number
        $runsTable = formula_builder_get_table_name('cicd_runs');
        $stmt = $conn->prepare("SELECT MAX(run_number) as max_run FROM {$runsTable} WHERE pipeline_id = ?");
        $stmt->bind_param("i", $pipelineId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextRun = ($row['max_run'] ?? 0) + 1;
        $stmt->close();
        
        // Create run
        $stmt = $conn->prepare("INSERT INTO {$runsTable} (pipeline_id, run_number, status) VALUES (?, ?, 'running')");
        $stmt->bind_param("ii", $pipelineId, $nextRun);
        $stmt->execute();
        $runId = $conn->insert_id;
        $stmt->close();
        
        // Execute stages
        $stages = json_decode($pipeline['stages'], true) ?: [];
        $results = [];
        
        foreach ($stages as $stage) {
            if (!($stage['enabled'] ?? true)) {
                continue;
            }
            
            $stageResult = formula_builder_execute_pipeline_stage($pipeline['formula_id'], $stage['name']);
            $results[$stage['name']] = $stageResult;
        }
        
        // Update run with results
        $testResults = $results['test'] ?? null;
        $qualityResults = $results['quality'] ?? null;
        $securityResults = $results['security'] ?? null;
        
        $finalStatus = 'passed';
        foreach ($results as $stageResult) {
            if (!$stageResult['success']) {
                $finalStatus = 'failed';
                break;
            }
        }
        
        $testResultsJson = $testResults ? json_encode($testResults) : null;
        $qualityResultsJson = $qualityResults ? json_encode($qualityResults) : null;
        $securityResultsJson = $securityResults ? json_encode($securityResults) : null;
        
        $stmt = $conn->prepare("UPDATE {$runsTable} SET status = ?, test_results = ?, quality_results = ?, security_results = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssssi", $finalStatus, $testResultsJson, $qualityResultsJson, $securityResultsJson, $runId);
        $stmt->execute();
        $stmt->close();
        
        return [
            'success' => true,
            'run_id' => $runId,
            'run_number' => $nextRun,
            'status' => $finalStatus,
            'results' => $results
        ];
    } catch (Exception $e) {
        error_log("Formula Builder: Error running pipeline: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Execute pipeline stage
 * @param int $formulaId Formula ID
 * @param string $stageName Stage name
 * @return array Stage result
 */
function formula_builder_execute_pipeline_stage($formulaId, $stageName) {
    switch ($stageName) {
        case 'test':
            $result = formula_builder_run_tests($formulaId);
            return [
                'success' => $result['failed'] === 0 && $result['error'] === 0,
                'passed' => $result['passed'],
                'failed' => $result['failed'],
                'error' => $result['error']
            ];
            
        case 'quality':
            $result = formula_builder_run_quality_check($formulaId);
            if ($result['success']) {
                $report = $result['report'];
                return [
                    'success' => $report['quality_score'] >= 70,
                    'quality_score' => $report['quality_score'],
                    'issues' => count($report['issues'])
                ];
            }
            return ['success' => false];
            
        case 'security':
            $result = formula_builder_run_quality_check($formulaId);
            if ($result['success']) {
                $report = $result['report'];
                $securityIssues = array_filter($report['issues'], function($issue) {
                    return $issue['type'] === 'security';
                });
                return [
                    'success' => empty($securityIssues),
                    'security_score' => $report['security_score'],
                    'issues' => count($securityIssues)
                ];
            }
            return ['success' => false];
            
        default:
            return ['success' => false, 'error' => 'Unknown stage'];
    }
}

/**
 * Get pipeline status
 * @param int $pipelineId Pipeline ID
 * @return array Pipeline status
 */
function formula_builder_get_pipeline_status($pipelineId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('cicd_pipelines');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pipelineId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pipeline = $result->fetch_assoc();
        $stmt->close();
        
        if (!$pipeline) {
            return ['success' => false, 'error' => 'Pipeline not found'];
        }
        
        // Get latest run
        $runsTable = formula_builder_get_table_name('cicd_runs');
        $stmt = $conn->prepare("SELECT * FROM {$runsTable} WHERE pipeline_id = ? ORDER BY run_number DESC LIMIT 1");
        $stmt->bind_param("i", $pipelineId);
        $stmt->execute();
        $result = $stmt->get_result();
        $latestRun = $result->fetch_assoc();
        $stmt->close();
        
        $pipeline['stages'] = json_decode($pipeline['stages'], true) ?: [];
        if ($latestRun) {
            $latestRun['test_results'] = $latestRun['test_results'] ? json_decode($latestRun['test_results'], true) : null;
            $latestRun['quality_results'] = $latestRun['quality_results'] ? json_decode($latestRun['quality_results'], true) : null;
            $latestRun['security_results'] = $latestRun['security_results'] ? json_decode($latestRun['security_results'], true) : null;
        }
        
        return [
            'success' => true,
            'pipeline' => $pipeline,
            'latest_run' => $latestRun
        ];
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting pipeline status: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

