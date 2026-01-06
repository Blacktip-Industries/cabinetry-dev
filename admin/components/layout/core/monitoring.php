<?php
/**
 * Layout Component - Monitoring Functions
 * Metrics calculation and feature tracking for advanced features
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/element_templates.php';
require_once __DIR__ . '/design_systems.php';

/**
 * Get template count
 * @return int Template count
 */
function layout_monitoring_get_template_count() {
    $templates = layout_element_template_get_all();
    return count($templates);
}

/**
 * Get design system count
 * @return int Design system count
 */
function layout_monitoring_get_design_system_count() {
    $designSystems = layout_design_system_get_all();
    return count($designSystems);
}

/**
 * Get published template count
 * @return int Published template count
 */
function layout_monitoring_get_published_template_count() {
    $templates = layout_element_template_get_all(['is_published' => 1]);
    return count($templates);
}

/**
 * Get published design system count
 * @return int Published design system count
 */
function layout_monitoring_get_published_design_system_count() {
    $designSystems = layout_design_system_get_all(['is_published' => 1]);
    return count($designSystems);
}

/**
 * Get user count from audit logs
 * @return int Unique user count (last 30 days)
 */
function layout_monitoring_get_user_count() {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = layout_get_table_name('audit_logs');
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM {$tableName} WHERE created_at >= ? AND user_id IS NOT NULL");
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("s", $thirtyDaysAgo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['count'] ?? 0);
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Monitoring: Error getting user count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get project count (manual entry stored in config)
 * @return int Project count
 */
function layout_monitoring_get_project_count() {
    $count = layout_get_config('monitoring_project_count', 0);
    return (int)$count;
}

/**
 * Set project count (manual entry)
 * @param int $count Project count
 * @return bool Success
 */
function layout_monitoring_set_project_count($count) {
    return layout_set_config('monitoring_project_count', (string)$count);
}

/**
 * Set manual user count
 * @param int $count User count
 * @return bool Success
 */
function layout_monitoring_set_manual_user_count($count) {
    return layout_set_config('monitoring_manual_user_count', (string)$count);
}

/**
 * Get manual user count
 * @return int Manual user count
 */
function layout_monitoring_get_manual_user_count() {
    $count = layout_get_config('monitoring_manual_user_count', 0);
    return (int)$count;
}

/**
 * Get all metrics
 * @return array All metrics
 */
function layout_monitoring_get_metrics() {
    $templateCount = layout_monitoring_get_template_count();
    $designSystemCount = layout_monitoring_get_design_system_count();
    $publishedTemplateCount = layout_monitoring_get_published_template_count();
    $publishedDesignSystemCount = layout_monitoring_get_published_design_system_count();
    $auditUserCount = layout_monitoring_get_user_count();
    $manualUserCount = layout_monitoring_get_manual_user_count();
    $projectCount = layout_monitoring_get_project_count();
    
    // Use manual user count if set, otherwise use audit log count
    $userCount = $manualUserCount > 0 ? $manualUserCount : $auditUserCount;
    
    return [
        'templates' => [
            'total' => $templateCount,
            'published' => $publishedTemplateCount,
            'draft' => $templateCount - $publishedTemplateCount
        ],
        'design_systems' => [
            'total' => $designSystemCount,
            'published' => $publishedDesignSystemCount,
            'draft' => $designSystemCount - $publishedDesignSystemCount
        ],
        'users' => [
            'count' => $userCount,
            'from_audit' => $auditUserCount,
            'manual' => $manualUserCount
        ],
        'projects' => [
            'count' => $projectCount
        ],
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

/**
 * Get feature recommendations based on thresholds
 * @param array $metrics Metrics array
 * @return array Recommendations
 */
function layout_monitoring_get_recommendations($metrics) {
    $recommendations = [];
    
    $templateCount = $metrics['templates']['total'];
    $designSystemCount = $metrics['design_systems']['total'];
    $userCount = $metrics['users']['count'];
    $projectCount = $metrics['projects']['count'];
    
    // Organization & Search
    if ($templateCount >= 50) {
        $recommendations['organization_search'] = [
            'phase' => 18,
            'priority' => 'high',
            'reason' => "You have {$templateCount} templates. Organization & Search features are recommended.",
            'threshold_met' => true
        ];
    } elseif ($templateCount >= 21) {
        $recommendations['organization_search'] = [
            'phase' => 18,
            'priority' => 'medium',
            'reason' => "You have {$templateCount} templates. Consider Organization & Search features soon.",
            'threshold_met' => false
        ];
    }
    
    // Collaboration
    if ($userCount >= 3) {
        $recommendations['collaboration'] = [
            'phase' => 15,
            'priority' => 'high',
            'reason' => "You have {$userCount} active users. Collaboration features are recommended.",
            'threshold_met' => true
        ];
    }
    
    // Marketplace
    if ($projectCount >= 3) {
        $recommendations['marketplace'] = [
            'phase' => 14,
            'priority' => 'medium',
            'reason' => "You have {$projectCount} projects. Consider Marketplace for template sharing.",
            'threshold_met' => true
        ];
    }
    
    // Bulk Operations
    if ($templateCount >= 20) {
        $recommendations['bulk_operations'] = [
            'phase' => 19,
            'priority' => 'medium',
            'reason' => "You have {$templateCount} templates. Bulk operations could save time.",
            'threshold_met' => true
        ];
    }
    
    return $recommendations;
}

/**
 * Get feature requests
 * @return array Feature requests
 */
function layout_monitoring_get_feature_requests() {
    $requestsJson = layout_get_config('monitoring_feature_requests', '[]');
    $requests = json_decode($requestsJson, true);
    return is_array($requests) ? $requests : [];
}

/**
 * Save feature request
 * @param array $request Feature request data
 * @return array Result
 */
function layout_monitoring_save_feature_request($request) {
    $requests = layout_monitoring_get_feature_requests();
    
    // Generate ID if not provided
    if (!isset($request['id'])) {
        $request['id'] = uniqid('req_');
    }
    
    // Set timestamp
    if (!isset($request['created_at'])) {
        $request['created_at'] = date('Y-m-d H:i:s');
    }
    $request['updated_at'] = date('Y-m-d H:i:s');
    
    // Calculate priority score if not provided
    if (!isset($request['priority_score'])) {
        $request['priority_score'] = layout_monitoring_calculate_priority_score($request);
    }
    
    // Add or update request
    $found = false;
    foreach ($requests as $key => $existing) {
        if ($existing['id'] === $request['id']) {
            $requests[$key] = $request;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $requests[] = $request;
    }
    
    // Save to config
    $success = layout_set_config('monitoring_feature_requests', json_encode($requests));
    
    return [
        'success' => $success,
        'request' => $request
    ];
}

/**
 * Delete feature request
 * @param string $requestId Request ID
 * @return bool Success
 */
function layout_monitoring_delete_feature_request($requestId) {
    $requests = layout_monitoring_get_feature_requests();
    $requests = array_filter($requests, function($req) use ($requestId) {
        return $req['id'] !== $requestId;
    });
    
    return layout_set_config('monitoring_feature_requests', json_encode(array_values($requests)));
}

/**
 * Calculate priority score for feature request
 * @param array $request Feature request data
 * @return float Priority score
 */
function layout_monitoring_calculate_priority_score($request) {
    $impactScore = 0;
    $effortScore = 0;
    
    // Impact scoring (max 16)
    $usersAffected = (int)($request['users_affected'] ?? 1);
    $frequency = (int)($request['frequency'] ?? 1);
    $timeImpact = (int)($request['time_impact'] ?? 1);
    $businessImpact = (int)($request['business_impact'] ?? 1);
    
    $impactScore = $usersAffected + $frequency + $timeImpact + $businessImpact;
    
    // Effort scoring (max 12, reversed - lower is better)
    $devTime = (int)($request['dev_time'] ?? 4);
    $complexity = (int)($request['complexity'] ?? 4);
    $dependencies = (int)($request['dependencies'] ?? 4);
    
    // Reverse the scores (4 = fast/simple/none, 1 = slow/complex/many)
    $effortScore = (5 - $devTime) + (5 - $complexity) + (5 - $dependencies);
    
    // Priority score = Impact / Effort
    if ($effortScore > 0) {
        return round($impactScore / $effortScore, 2);
    }
    
    return 0;
}

/**
 * Get pain points
 * @return array Pain points
 */
function layout_monitoring_get_pain_points() {
    $pointsJson = layout_get_config('monitoring_pain_points', '[]');
    $points = json_decode($pointsJson, true);
    return is_array($points) ? $points : [];
}

/**
 * Save pain point
 * @param array $painPoint Pain point data
 * @return array Result
 */
function layout_monitoring_save_pain_point($painPoint) {
    $points = layout_monitoring_get_pain_points();
    
    // Generate ID if not provided
    if (!isset($painPoint['id'])) {
        $painPoint['id'] = uniqid('pain_');
    }
    
    // Set timestamp
    if (!isset($painPoint['created_at'])) {
        $painPoint['created_at'] = date('Y-m-d H:i:s');
    }
    $painPoint['updated_at'] = date('Y-m-d H:i:s');
    
    // Set status if not provided
    if (!isset($painPoint['status'])) {
        $painPoint['status'] = 'open';
    }
    
    // Add or update pain point
    $found = false;
    foreach ($points as $key => $existing) {
        if ($existing['id'] === $painPoint['id']) {
            $points[$key] = $painPoint;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $points[] = $painPoint;
    }
    
    // Save to config
    $success = layout_set_config('monitoring_pain_points', json_encode($points));
    
    return [
        'success' => $success,
        'pain_point' => $painPoint
    ];
}

/**
 * Delete pain point
 * @param string $painPointId Pain point ID
 * @return bool Success
 */
function layout_monitoring_delete_pain_point($painPointId) {
    $points = layout_monitoring_get_pain_points();
    $points = array_filter($points, function($point) use ($painPointId) {
        return $point['id'] !== $painPointId;
    });
    
    return layout_set_config('monitoring_pain_points', json_encode(array_values($points)));
}

/**
 * Get monitoring checklist status
 * @param array $metrics Metrics array
 * @return array Checklist status
 */
function layout_monitoring_get_checklist_status($metrics) {
    $templateCount = $metrics['templates']['total'];
    $designSystemCount = $metrics['design_systems']['total'];
    $userCount = $metrics['users']['count'];
    $projectCount = $metrics['projects']['count'];
    
    return [
        'templates_0_20' => $templateCount <= 20,
        'templates_21_50' => $templateCount >= 21 && $templateCount <= 50,
        'templates_51_plus' => $templateCount >= 51,
        'design_systems_0_5' => $designSystemCount <= 5,
        'design_systems_6_10' => $designSystemCount >= 6 && $designSystemCount <= 10,
        'design_systems_11_plus' => $designSystemCount >= 11,
        'users_1' => $userCount == 1,
        'users_2_3' => $userCount >= 2 && $userCount <= 3,
        'users_4_plus' => $userCount >= 4,
        'projects_1' => $projectCount == 1,
        'projects_2_3' => $projectCount >= 2 && $projectCount <= 3,
        'projects_4_plus' => $projectCount >= 4
    ];
}

