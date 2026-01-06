<?php
/**
 * SMS Gateway System - Core Functions
 * Multi-provider SMS gateway with abstraction layer
 * Global component - can be used by any component
 */

// Database connection function (assumes shared database connection)
function sms_gateway_get_db_connection() {
    // Try to use order_management database connection (since SMS tables are there)
    if (function_exists('order_management_get_db_connection')) {
        return order_management_get_db_connection();
    }
    
    // Fallback to base database connection
    // TODO: Implement base database connection
    return null;
}

/**
 * Get table name (SMS tables are in order_management database)
 * @param string $tableName Table name without prefix
 * @return string Full table name
 */
function sms_gateway_get_table_name($tableName) {
    // SMS tables don't have a prefix in the order_management database
    return $tableName;
}

/**
 * Get all providers or only active
 * @param bool $activeOnly Only get active providers
 * @return array Providers
 */
function sms_gateway_get_providers($activeOnly = false) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = sms_gateway_get_table_name('sms_providers');
    $sql = "SELECT * FROM {$tableName}";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY is_primary DESC, provider_name ASC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $providers = [];
        while ($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }
        $stmt->close();
        return $providers;
    }
    
    return [];
}

/**
 * Get the primary active provider
 * @return array|null Provider data or null
 */
function sms_gateway_get_primary_provider() {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = sms_gateway_get_table_name('sms_providers');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 AND is_primary = 1 LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $provider = $result->fetch_assoc();
        $stmt->close();
        return $provider;
    }
    
    return null;
}

/**
 * Set primary provider (deactivates others)
 * @param int $providerId Provider ID
 * @return bool Success
 */
function sms_gateway_set_primary_provider($providerId) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = sms_gateway_get_table_name('sms_providers');
    
    // Set all providers to not primary
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_primary = 0");
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }
    
    // Set selected provider as primary
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_primary = 1, is_active = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $providerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Validate phone number format
 * @param string $phoneNumber Phone number
 * @param string $countryCode Country code (default 'AU')
 * @return array Validation result
 */
function sms_gateway_validate_phone($phoneNumber, $countryCode = 'AU') {
    // Remove all non-digit characters
    $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
    
    // Australian phone number validation
    if ($countryCode === 'AU') {
        // Remove leading +61 or 61, replace with 0
        $cleaned = preg_replace('/^\+?61/', '0', $cleaned);
        
        // Australian mobile: 04XX XXX XXX (10 digits starting with 04)
        if (preg_match('/^04\d{8}$/', $cleaned)) {
            // Format as E.164: +61XXXXXXXXX
            $e164 = '+61' . substr($cleaned, 1);
            return [
                'valid' => true,
                'normalized' => $e164,
                'formatted' => $cleaned
            ];
        }
        
        // Australian landline: 0X XXXX XXXX (10 digits)
        if (preg_match('/^0[2-9]\d{8}$/', $cleaned)) {
            $e164 = '+61' . substr($cleaned, 1);
            return [
                'valid' => true,
                'normalized' => $e164,
                'formatted' => $cleaned
            ];
        }
    }
    
    return [
        'valid' => false,
        'normalized' => null,
        'formatted' => $phoneNumber
    ];
}

/**
 * Check if phone is blacklisted
 * @param string $phoneNumber Phone number
 * @return bool True if blacklisted
 */
function sms_gateway_check_blacklist($phoneNumber) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = sms_gateway_get_table_name('sms_blacklist');
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE phone_number = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $phoneNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $blacklisted = $result->num_rows > 0;
        $stmt->close();
        return $blacklisted;
    }
    
    return false;
}

/**
 * Check opt-out status
 * @param int|null $customerId Customer ID
 * @param string $phoneNumber Phone number
 * @param string $smsType SMS type
 * @return bool True if opted out
 */
function sms_gateway_check_opt_out($customerId, $phoneNumber, $smsType = 'all') {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = sms_gateway_get_table_name('sms_opt_outs');
    $sql = "SELECT id FROM {$tableName} WHERE phone_number = ? AND is_active = 1";
    $params = ["s", &$phoneNumber];
    $types = "s";
    
    if ($customerId !== null) {
        $sql .= " AND (customer_id = ? OR customer_id IS NULL)";
        $params[] = &$customerId;
        $types .= "i";
    }
    
    if ($smsType !== 'all') {
        $sql .= " AND (opt_out_type = ? OR opt_out_type = 'all')";
        $params[] = &$smsType;
        $types .= "s";
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
        $optedOut = $result->num_rows > 0;
        $stmt->close();
        return $optedOut;
    }
    
    return false;
}

/**
 * Check spending limit
 * @param float|null $cost Cost of SMS to send
 * @return array Limit check result
 */
function sms_gateway_check_spending_limit($cost = null) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return [
            'can_send' => true,
            'limit_status' => 'none',
            'remaining_budget' => null,
            'override_active' => false
        ];
    }
    
    $tableName = sms_gateway_get_table_name('sms_spending_limits');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY cycle_start_date DESC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $limit = $result->fetch_assoc();
        $stmt->close();
        
        if ($limit) {
            // Check for active overrides
            $overridesTable = sms_gateway_get_table_name('sms_spending_overrides');
            $overrideStmt = $conn->prepare("SELECT * FROM {$overridesTable} WHERE spending_limit_id = ? AND (override_expires_at IS NULL OR override_expires_at > NOW()) ORDER BY created_at DESC LIMIT 1");
            if ($overrideStmt) {
                $overrideStmt->bind_param("i", $limit['id']);
                $overrideStmt->execute();
                $overrideResult = $overrideStmt->get_result();
                $override = $overrideResult->fetch_assoc();
                $overrideStmt->close();
                
                if ($override && $override['override_type'] === 'allow_continued_sending') {
                    return [
                        'can_send' => true,
                        'limit_status' => 'override',
                        'remaining_budget' => null,
                        'override_active' => true
                    ];
                }
            }
            
            $currentSpending = (float)$limit['current_spending'];
            $softLimit = (float)$limit['soft_limit'];
            $hardLimit = (float)$limit['hard_limit'];
            
            $newSpending = $cost ? ($currentSpending + $cost) : $currentSpending;
            
            if ($newSpending >= $hardLimit) {
                return [
                    'can_send' => false,
                    'limit_status' => 'hard_limit',
                    'remaining_budget' => max(0, $hardLimit - $currentSpending),
                    'override_active' => false
                ];
            }
            
            if ($newSpending >= $softLimit) {
                return [
                    'can_send' => true,
                    'limit_status' => 'soft_warning',
                    'remaining_budget' => max(0, $softLimit - $currentSpending),
                    'override_active' => false
                ];
            }
            
            return [
                'can_send' => true,
                'limit_status' => 'none',
                'remaining_budget' => $softLimit - $currentSpending,
                'override_active' => false
            ];
        }
    }
    
    return [
        'can_send' => true,
        'limit_status' => 'none',
        'remaining_budget' => null,
        'override_active' => false
    ];
}

/**
 * Update spending
 * @param float $cost Cost of SMS
 * @param int $smsCount Number of SMS sent
 * @return array Update result
 */
function sms_gateway_update_spending($cost, $smsCount = 1) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['limit_status' => 'none', 'spending_updated' => false];
    }
    
    $tableName = sms_gateway_get_table_name('sms_spending_limits');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY cycle_start_date DESC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $limit = $result->fetch_assoc();
        $stmt->close();
        
        if ($limit) {
            $newSpending = (float)$limit['current_spending'] + $cost;
            
            // Check limits
            $softLimitReached = $newSpending >= (float)$limit['soft_limit'];
            $hardLimitReached = $newSpending >= (float)$limit['hard_limit'];
            
            $updateStmt = $conn->prepare("UPDATE {$tableName} SET current_spending = ?, soft_limit_notified = ?, hard_limit_reached = ? WHERE id = ?");
            if ($updateStmt) {
                $softNotified = $softLimitReached ? 1 : (int)$limit['soft_limit_notified'];
                $hardReached = $hardLimitReached ? 1 : 0;
                $updateStmt->bind_param("diii", $newSpending, $softNotified, $hardReached, $limit['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Send notifications if needed
                if ($softLimitReached && !$limit['soft_limit_notified']) {
                    // TODO: Send soft limit notification
                }
                
                if ($hardLimitReached && !$limit['hard_limit_notified']) {
                    // TODO: Send hard limit notification
                }
                
                return [
                    'limit_status' => $hardLimitReached ? 'hard_limit' : ($softLimitReached ? 'soft_warning' : 'none'),
                    'spending_updated' => true
                ];
            }
        }
    }
    
    return ['limit_status' => 'none', 'spending_updated' => false];
}

/**
 * Calculate SMS cost
 * @param string $message Message text
 * @param int|null $providerId Provider ID
 * @return float Cost
 */
function sms_gateway_calculate_cost($message, $providerId = null) {
    $provider = null;
    if ($providerId) {
        $conn = sms_gateway_get_db_connection();
        if ($conn) {
            $tableName = sms_gateway_get_table_name('sms_providers');
            $stmt = $conn->prepare("SELECT cost_per_sms FROM {$tableName} WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $providerId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $provider = $row;
                }
            }
        }
    } else {
        $provider = sms_gateway_get_primary_provider();
    }
    
    if (!$provider) {
        return 0.00;
    }
    
    $costPerSms = (float)($provider['cost_per_sms'] ?? 0.00);
    $segments = sms_gateway_calculate_segments($message);
    
    return $costPerSms * $segments;
}

/**
 * Calculate SMS segments
 * @param string $message Message text
 * @return int Number of segments
 */
function sms_gateway_calculate_segments($message) {
    $length = mb_strlen($message);
    
    // Standard SMS: 160 characters = 1 segment
    // Concatenated SMS: 153 characters per segment
    if ($length <= 160) {
        return 1;
    }
    
    // Calculate segments for concatenated SMS
    return (int)ceil($length / 153);
}

/**
 * Send SMS via primary provider
 * @param string $toPhone Phone number
 * @param string $message Message text
 * @param array $options Options (template_id, variables, sender_id, scheduled_at, priority, component_name, component_reference_id)
 * @return array Result with queue_id
 */
function sms_gateway_send($toPhone, $message, $options = []) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate phone number
    $validation = sms_gateway_validate_phone($toPhone);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => 'Invalid phone number format'];
    }
    
    $normalizedPhone = $validation['normalized'];
    
    // Check blacklist
    if (sms_gateway_check_blacklist($normalizedPhone)) {
        return ['success' => false, 'error' => 'Phone number is blacklisted'];
    }
    
    // Check opt-out
    $customerId = $options['customer_id'] ?? null;
    $smsType = $options['sms_type'] ?? 'transactional';
    if (sms_gateway_check_opt_out($customerId, $normalizedPhone, $smsType)) {
        return ['success' => false, 'error' => 'Customer has opted out'];
    }
    
    // Check spending limit
    $cost = sms_gateway_calculate_cost($message);
    $limitCheck = sms_gateway_check_spending_limit($cost);
    if (!$limitCheck['can_send']) {
        return ['success' => false, 'error' => 'SMS spending limit reached'];
    }
    
    // Get primary provider
    $provider = sms_gateway_get_primary_provider();
    if (!$provider) {
        return ['success' => false, 'error' => 'No active SMS provider configured'];
    }
    
    // Add to queue
    $queueTable = sms_gateway_get_table_name('sms_queue');
    $providerId = $provider['id'];
    $templateId = $options['template_id'] ?? null;
    $variablesJson = isset($options['variables']) ? json_encode($options['variables']) : null;
    $senderId = $options['sender_id'] ?? $provider['sender_id'];
    $priority = (int)($options['priority'] ?? 5);
    $scheduledAt = $options['scheduled_at'] ?? null;
    $sendAt = $options['send_at'] ?? null;
    $componentName = $options['component_name'] ?? null;
    $componentReferenceId = $options['component_reference_id'] ?? null;
    $timezone = $options['timezone'] ?? null;
    $scheduleType = $scheduledAt ? 'scheduled' : 'immediate';
    $recurringConfigJson = isset($options['recurring_config']) ? json_encode($options['recurring_config']) : null;
    $characterCount = mb_strlen($message);
    $segmentCount = sms_gateway_calculate_segments($message);
    
    $stmt = $conn->prepare("INSERT INTO {$queueTable} (provider_id, to_phone, message, template_id, variables_json, sender_id, priority, scheduled_at, send_at, component_name, component_reference_id, timezone, schedule_type, recurring_config_json, cost, character_count, segment_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    if ($stmt) {
        $stmt->bind_param("ississsississssdiis", $providerId, $normalizedPhone, $message, $templateId, $variablesJson, $senderId, $priority, $scheduledAt, $sendAt, $componentName, $componentReferenceId, $timezone, $scheduleType, $recurringConfigJson, $cost, $characterCount, $segmentCount);
        if ($stmt->execute()) {
            $queueId = $conn->insert_id;
            $stmt->close();
            
            // Update spending
            sms_gateway_update_spending($cost, 1);
            
            // Process immediately if not scheduled
            if (!$scheduledAt && !$sendAt) {
                sms_gateway_process_queue_item($queueId);
            }
            
            return ['success' => true, 'queue_id' => $queueId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Process single queue item
 * @param int $queueId Queue ID
 * @return bool Success
 */
function sms_gateway_process_queue_item($queueId) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $queueTable = sms_gateway_get_table_name('sms_queue');
    $stmt = $conn->prepare("SELECT * FROM {$queueTable} WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("i", $queueId);
    $stmt->execute();
    $result = $stmt->get_result();
    $queueItem = $result->fetch_assoc();
    $stmt->close();
    
    if (!$queueItem || $queueItem['status'] !== 'pending') {
        return false;
    }
    
    // Get provider
    $provider = null;
    if ($queueItem['provider_id']) {
        $providersTable = sms_gateway_get_table_name('sms_providers');
        $providerStmt = $conn->prepare("SELECT * FROM {$providersTable} WHERE id = ? LIMIT 1");
        if ($providerStmt) {
            $providerStmt->bind_param("i", $queueItem['provider_id']);
            $providerStmt->execute();
            $providerResult = $providerStmt->get_result();
            $provider = $providerResult->fetch_assoc();
            $providerStmt->close();
        }
    } else {
        $provider = sms_gateway_get_primary_provider();
    }
    
    if (!$provider) {
        // Mark as failed
        $updateStmt = $conn->prepare("UPDATE {$queueTable} SET status = 'failed', failed_at = NOW(), failure_reason = 'No provider available' WHERE id = ?");
        $updateStmt->bind_param("i", $queueId);
        $updateStmt->execute();
        $updateStmt->close();
        return false;
    }
    
    // Send via provider
    $providerName = $provider['provider_name'];
    $providerConfig = json_decode($provider['config_json'], true) ?? [];
    
    // Load provider-specific implementation
    $providerFile = __DIR__ . '/sms-providers/' . $providerName . '.php';
    if (file_exists($providerFile)) {
        require_once $providerFile;
        $functionName = 'sms_provider_' . $providerName . '_send';
        
        if (function_exists($functionName)) {
            $sendResult = $functionName($queueItem['to_phone'], $queueItem['message'], $providerConfig);
            
            if ($sendResult['success']) {
                // Update queue status
                $updateStmt = $conn->prepare("UPDATE {$queueTable} SET status = 'sent', sent_at = NOW(), provider_message_id = ? WHERE id = ?");
                $providerMessageId = $sendResult['message_id'] ?? null;
                $updateStmt->bind_param("si", $providerMessageId, $queueId);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Record in history
                $historyTable = sms_gateway_get_table_name('sms_history');
                $historyStmt = $conn->prepare("INSERT INTO {$historyTable} (queue_id, provider_id, to_phone, message, character_count, segment_count, status, cost, provider_message_id, component_name, component_reference_id, sent_at) VALUES (?, ?, ?, ?, ?, ?, 'sent', ?, ?, ?, ?, NOW())");
                if ($historyStmt) {
                    $historyStmt->bind_param("iissiidsiss", $queueId, $provider['id'], $queueItem['to_phone'], $queueItem['message'], $queueItem['character_count'], $queueItem['segment_count'], $queueItem['cost'], $providerMessageId, $queueItem['component_name'], $queueItem['component_reference_id']);
                    $historyStmt->execute();
                    $historyStmt->close();
                }
                
                return true;
            } else {
                // Mark as failed
                $updateStmt = $conn->prepare("UPDATE {$queueTable} SET status = 'failed', failed_at = NOW(), failure_reason = ?, retry_count = retry_count + 1 WHERE id = ?");
                $failureReason = $sendResult['error'] ?? 'Unknown error';
                $updateStmt->bind_param("si", $failureReason, $queueId);
                $updateStmt->execute();
                $updateStmt->close();
                return false;
            }
        }
    }
    
    // Fallback: mark as failed
    $updateStmt = $conn->prepare("UPDATE {$queueTable} SET status = 'failed', failed_at = NOW(), failure_reason = 'Provider implementation not found' WHERE id = ?");
    $updateStmt->bind_param("i", $queueId);
    $updateStmt->execute();
    $updateStmt->close();
    return false;
}

/**
 * Process SMS queue
 * @param int $limit Maximum number of messages to process
 * @return int Number of messages processed
 */
function sms_gateway_process_queue($limit = 100) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    $queueTable = sms_gateway_get_table_name('sms_queue');
    $stmt = $conn->prepare("SELECT * FROM {$queueTable} WHERE status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW()) AND (send_at IS NULL OR send_at <= NOW()) ORDER BY priority ASC, created_at ASC LIMIT ?");
    if (!$stmt) {
        return 0;
    }
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $queueItems = [];
    while ($row = $result->fetch_assoc()) {
        $queueItems[] = $row;
    }
    $stmt->close();
    
    $processed = 0;
    foreach ($queueItems as $item) {
        if (sms_gateway_process_queue_item($item['id'])) {
            $processed++;
        }
    }
    
    return $processed;
}

/**
 * Send SMS using template
 * @param string $toPhone Phone number
 * @param string $templateCode Template code
 * @param array $variables Variables for template
 * @param array $options Additional options
 * @return array Result
 */
function sms_gateway_send_template($toPhone, $templateCode, $variables = [], $options = []) {
    $conn = sms_gateway_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $templatesTable = sms_gateway_get_table_name('sms_templates');
    $stmt = $conn->prepare("SELECT * FROM {$templatesTable} WHERE template_code = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    $stmt->bind_param("s", $templateCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    // Replace variables in template
    $message = $template['message'];
    foreach ($variables as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    
    $options['template_id'] = $template['id'];
    $options['variables'] = $variables;
    
    return sms_gateway_send($toPhone, $message, $options);
}

