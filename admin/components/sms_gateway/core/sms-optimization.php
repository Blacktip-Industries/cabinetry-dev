<?php
/**
 * SMS Gateway Component - SMS Optimization System
 * Delivery optimization, A/B testing, personalization, template versioning, engagement scoring
 */

require_once __DIR__ . '/sms-gateway.php';

/**
 * Get optimal delivery time for SMS
 * @param string $phoneNumber Phone number
 * @param string $smsType SMS type
 * @return array Optimal time data
 */
function sms_gateway_optimize_delivery_time($phoneNumber, $smsType) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['optimal_time' => '09:00:00', 'confidence' => 0.5];
    }
    
    $tableName = sms_gateway_get_table_name('sms_optimal_send_times');
    
    // Get historical optimal times
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE phone_number = ? AND sms_type = ? ORDER BY engagement_rate DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $phoneNumber, $smsType);
        $stmt->execute();
        $result = $stmt->get_result();
        $optimal = $result->fetch_assoc();
        $stmt->close();
        
        if ($optimal) {
            return [
                'optimal_time' => $optimal['optimal_time'] ?? '09:00:00',
                'confidence' => (float)($optimal['confidence_score'] ?? 0.5),
                'engagement_rate' => (float)($optimal['engagement_rate'] ?? 0)
            ];
        }
    }
    
    // Default optimal times by type
    $defaultTimes = [
        'transactional' => '09:00:00',
        'marketing' => '10:00:00',
        'reminder' => '08:00:00',
        'notification' => '09:00:00'
    ];
    
    return [
        'optimal_time' => $defaultTimes[$smsType] ?? '09:00:00',
        'confidence' => 0.5
    ];
}

/**
 * Create A/B test
 * @param array $testData Test data
 * @return array Result
 */
function sms_gateway_create_ab_test($testData) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = sms_gateway_get_table_name('sms_ab_tests');
    
    $testName = $testData['test_name'] ?? '';
    $templateId = $testData['template_id'] ?? null;
    $variantA = $testData['variant_a'] ?? '';
    $variantB = $testData['variant_b'] ?? '';
    $testType = $testData['test_type'] ?? 'message';
    $targetAudience = json_encode($testData['target_audience'] ?? []);
    $startDate = $testData['start_date'] ?? date('Y-m-d');
    $endDate = $testData['end_date'] ?? null;
    $isActive = isset($testData['is_active']) ? (int)$testData['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (test_name, template_id, variant_a, variant_b, test_type, target_audience_json, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sissssssi", $testName, $templateId, $variantA, $variantB, $testType, $targetAudience, $startDate, $endDate, $isActive);
        if ($stmt->execute()) {
            $testId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'test_id' => $testId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Personalize message
 * @param string $templateCode Template code
 * @param array $variables Variables
 * @param int|null $customerId Customer ID
 * @return string Personalized message
 */
function sms_gateway_personalize_message($templateCode, $variables, $customerId = null) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return '';
    }
    
    $templatesTable = sms_gateway_get_table_name('sms_templates');
    $stmt = $conn->prepare("SELECT message FROM {$templatesTable} WHERE template_code = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $templateCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template) {
            $message = $template['message'];
            
            // Add customer-specific variables if customer ID provided
            if ($customerId && function_exists('commerce_get_customer')) {
                $customer = commerce_get_customer($customerId);
                if ($customer) {
                    $variables['customer_name'] = $customer['name'] ?? '';
                    $variables['customer_first_name'] = explode(' ', $customer['name'] ?? '')[0] ?? '';
                }
            }
            
            // Replace variables
            foreach ($variables as $key => $value) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
            
            return $message;
        }
    }
    
    return '';
}

/**
 * Get template version
 * @param int $templateId Template ID
 * @param int|null $versionNumber Version number or null for latest
 * @return array|null Template version
 */
function sms_gateway_get_template_version($templateId, $versionNumber = null) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = sms_gateway_get_table_name('sms_template_versions');
    $sql = "SELECT * FROM {$tableName} WHERE template_id = ?";
    
    if ($versionNumber !== null) {
        $sql .= " AND version_number = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $templateId, $versionNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            $version = $result->fetch_assoc();
            $stmt->close();
            return $version;
        }
    } else {
        $sql .= " ORDER BY version_number DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $templateId);
            $stmt->execute();
            $result = $stmt->get_result();
            $version = $result->fetch_assoc();
            $stmt->close();
            return $version;
        }
    }
    
    return null;
}

/**
 * Calculate engagement score for customer
 * @param int|null $customerId Customer ID
 * @param string $phoneNumber Phone number
 * @return float Engagement score
 */
function sms_gateway_calculate_engagement_score($customerId, $phoneNumber) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return 0.0;
    }
    
    $tableName = sms_gateway_get_table_name('sms_customer_engagement_scores');
    
    // Check if score exists
    $sql = "SELECT * FROM {$tableName} WHERE phone_number = ?";
    $params = ["s", &$phoneNumber];
    $types = "s";
    
    if ($customerId) {
        $sql .= " AND customer_id = ?";
        $params[] = &$customerId;
        $types .= "i";
    }
    
    $sql .= " LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bindParams = [$types];
        for ($i = 1; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        
        $stmt->execute();
        $result = $stmt->get_result();
        $score = $result->fetch_assoc();
        $stmt->close();
        
        if ($score) {
            return (float)($score['engagement_score'] ?? 0.0);
        }
    }
    
    // Calculate from history
    $historyTable = sms_gateway_get_table_name('sms_history');
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_sent,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        AVG(CASE WHEN delivered_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, sent_at, delivered_at) ELSE NULL END) as avg_delivery_time
        FROM {$historyTable} 
        WHERE to_phone = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $phoneNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        if ($stats && $stats['total_sent'] > 0) {
            $deliveryRate = (float)$stats['delivered'] / (float)$stats['total_sent'];
            $engagementScore = $deliveryRate * 100; // Simple score based on delivery rate
            
            // Store score
            if ($customerId) {
                $stmt = $conn->prepare("INSERT INTO {$tableName} (customer_id, phone_number, engagement_score, total_sms_received, last_engagement_date) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE engagement_score = ?, total_sms_received = ?, last_engagement_date = NOW()");
                if ($stmt) {
                    $totalSent = (int)$stats['total_sent'];
                    $stmt->bind_param("isdiidi", $customerId, $phoneNumber, $engagementScore, $totalSent, $engagementScore, $totalSent);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            return round($engagementScore, 2);
        }
    }
    
    return 0.0;
}

/**
 * Optimize message length
 * @param string $message Message text
 * @return array Optimization result
 */
function sms_gateway_optimize_message_length($message) {
    $length = mb_strlen($message);
    $segments = sms_gateway_calculate_segments($message);
    
    $optimized = $message;
    $optimizations = [];
    
    // If message is close to segment boundary, suggest optimizations
    if ($length > 150 && $length < 160) {
        $optimizations[] = 'Message is close to 2-segment threshold. Consider shortening by ' . (160 - $length) . ' characters to stay in 1 segment.';
    }
    
    // Check for common wordy phrases
    $replacements = [
        'you are' => "you're",
        'we are' => "we're",
        'cannot' => "can't",
        'will not' => "won't",
        'do not' => "don't"
    ];
    
    foreach ($replacements as $long => $short) {
        if (stripos($optimized, $long) !== false) {
            $optimized = str_ireplace($long, $short, $optimized);
            $optimizations[] = "Replaced '{$long}' with '{$short}'";
        }
    }
    
    $newLength = mb_strlen($optimized);
    $newSegments = sms_gateway_calculate_segments($optimized);
    
    return [
        'original_length' => $length,
        'original_segments' => $segments,
        'optimized_length' => $newLength,
        'optimized_segments' => $newSegments,
        'optimized_message' => $optimized,
        'savings' => $length - $newLength,
        'optimizations' => $optimizations
    ];
}

/**
 * Get intelligent retry strategy
 * @param int $queueId Queue ID
 * @return array Retry strategy
 */
function sms_gateway_get_intelligent_retry_strategy($queueId) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['should_retry' => false];
    }
    
    $queueTable = sms_gateway_get_table_name('sms_queue');
    $stmt = $conn->prepare("SELECT * FROM {$queueTable} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $queueItem = $result->fetch_assoc();
        $stmt->close();
        
        if ($queueItem) {
            $retryCount = (int)($queueItem['retry_count'] ?? 0);
            $maxRetries = (int)($queueItem['max_retries'] ?? 3);
            $failureReason = $queueItem['failure_reason'] ?? '';
            
            // Don't retry if max retries reached
            if ($retryCount >= $maxRetries) {
                return ['should_retry' => false, 'reason' => 'Max retries reached'];
            }
            
            // Don't retry certain error types
            $nonRetryableErrors = ['blacklisted', 'opted_out', 'invalid_number', 'spending_limit'];
            foreach ($nonRetryableErrors as $error) {
                if (stripos($failureReason, $error) !== false) {
                    return ['should_retry' => false, 'reason' => "Non-retryable error: {$error}"];
                }
            }
            
            // Calculate retry delay (exponential backoff)
            $delayMinutes = pow(2, $retryCount) * 5; // 5, 10, 20, 40 minutes
            
            return [
                'should_retry' => true,
                'retry_delay_minutes' => $delayMinutes,
                'retry_count' => $retryCount,
                'max_retries' => $maxRetries
            ];
        }
    }
    
    return ['should_retry' => false];
}

/**
 * Track SMS ROI
 * @param int $campaignId Campaign ID
 * @return array ROI data
 */
function sms_gateway_track_roi($campaignId) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $campaignsTable = sms_gateway_get_table_name('sms_campaigns');
    $stmt = $conn->prepare("SELECT * FROM {$campaignsTable} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $result = $stmt->get_result();
        $campaign = $result->fetch_assoc();
        $stmt->close();
        
        if ($campaign) {
            $totalCost = (float)($campaign['total_cost'] ?? 0);
            $sentCount = (int)($campaign['sent_count'] ?? 0);
            $deliveredCount = (int)($campaign['delivered_count'] ?? 0);
            
            $deliveryRate = $sentCount > 0 ? ($deliveredCount / $sentCount) * 100 : 0;
            $costPerSMS = $sentCount > 0 ? ($totalCost / $sentCount) : 0;
            $costPerDelivery = $deliveredCount > 0 ? ($totalCost / $deliveredCount) : 0;
            
            return [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign['campaign_name'],
                'total_cost' => $totalCost,
                'sent_count' => $sentCount,
                'delivered_count' => $deliveredCount,
                'delivery_rate' => round($deliveryRate, 2),
                'cost_per_sms' => round($costPerSMS, 4),
                'cost_per_delivery' => round($costPerDelivery, 4)
            ];
        }
    }
    
    return [];
}

/**
 * Create automation workflow
 * @param array $workflowData Workflow data
 * @return array Result
 */
function sms_gateway_create_automation_workflow($workflowData) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = sms_gateway_get_table_name('sms_automation_workflows');
    
    $workflowName = $workflowData['workflow_name'] ?? '';
    $triggerEvent = $workflowData['trigger_event'] ?? '';
    $conditionsJson = json_encode($workflowData['conditions'] ?? []);
    $actionsJson = json_encode($workflowData['actions'] ?? []);
    $isActive = isset($workflowData['is_active']) ? (int)$workflowData['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (workflow_name, trigger_event, conditions_json, actions_json, is_active) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssi", $workflowName, $triggerEvent, $conditionsJson, $actionsJson, $isActive);
        if ($stmt->execute()) {
            $workflowId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'workflow_id' => $workflowId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

