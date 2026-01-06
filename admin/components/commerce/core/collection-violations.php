<?php
/**
 * Commerce Component - Collection Violation System
 * Functions for recording violations, calculating scores, managing forgiveness and appeals
 */

require_once __DIR__ . '/database.php';

/**
 * Record collection violation
 * @param int $orderId Order ID
 * @param int $customerId Customer ID
 * @param string $violationType Violation type
 * @param string $violationSeverity Violation severity
 * @param string|null $violationReason Violation reason
 * @return array Result with violation_id
 */
function commerce_record_collection_violation($orderId, $customerId, $violationType, $violationSeverity, $violationReason = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $violationScore = commerce_get_violation_score($violationType, $violationSeverity);
    $violationDate = date('Y-m-d H:i:s');
    
    $tableName = commerce_get_table_name('collection_violations');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, customer_id, violation_type, violation_severity, violation_reason, violation_score, violation_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisssis", $orderId, $customerId, $violationType, $violationSeverity, $violationReason, $violationScore, $violationDate);
        if ($stmt->execute()) {
            $violationId = $conn->insert_id;
            $stmt->close();
            
            // Update customer violation score
            commerce_update_customer_violation_score($customerId);
            
            return ['success' => true, 'violation_id' => $violationId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get violation score
 * @param string $violationType Violation type
 * @param string $violationSeverity Violation severity
 * @return int Violation score points
 */
function commerce_get_violation_score($violationType, $violationSeverity) {
    // Default scoring system
    $scores = [
        'missed_collection' => ['minor' => 3, 'moderate' => 7, 'severe' => 10],
        'late_arrival' => ['minor' => 1, 'moderate' => 3, 'severe' => 5],
        'no_notification' => ['minor' => 2, 'moderate' => 5, 'severe' => 8],
        'early_bird_missed' => ['minor' => 5, 'moderate' => 10, 'severe' => 15],
        'after_hours_missed' => ['minor' => 5, 'moderate' => 10, 'severe' => 15]
    ];
    
    return $scores[$violationType][$violationSeverity] ?? 5;
}

/**
 * Update customer violation score
 * @param int $customerId Customer ID
 * @return bool Success
 */
function commerce_update_customer_violation_score($customerId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Get active violations (not expired, not forgiven)
    $violationsTable = commerce_get_table_name('collection_violations');
    $stmt = $conn->prepare("SELECT SUM(violation_score) as active_score, COUNT(*) as violation_count FROM {$violationsTable} WHERE customer_id = ? AND is_forgiven = 0 AND (expiration_date IS NULL OR expiration_date > NOW())");
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $activeScore = (int)($row['active_score'] ?? 0);
        $violationCount = (int)($row['violation_count'] ?? 0);
        
        // Get total score (all violations)
        $stmt = $conn->prepare("SELECT SUM(violation_score) as total_score FROM {$violationsTable} WHERE customer_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $totalScore = (int)($row['total_score'] ?? 0);
        }
        
        // Get last violation date
        $stmt = $conn->prepare("SELECT MAX(violation_date) as last_violation_date FROM {$violationsTable} WHERE customer_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $lastViolationDate = $row['last_violation_date'] ? date('Y-m-d', strtotime($row['last_violation_date'])) : null;
        }
        
        // Calculate violation tier
        $violationTier = 'none';
        if ($activeScore >= 20) {
            $violationTier = 'severe';
        } elseif ($activeScore >= 10) {
            $violationTier = 'high';
        } elseif ($activeScore >= 5) {
            $violationTier = 'medium';
        } elseif ($activeScore > 0) {
            $violationTier = 'low';
        }
        
        // Get violations this month/year
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$violationsTable} WHERE customer_id = ? AND violation_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $violationsThisMonth = (int)($row['count'] ?? 0);
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$violationsTable} WHERE customer_id = ? AND violation_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $violationsThisYear = (int)($row['count'] ?? 0);
        }
        
        // Update or insert customer violation score
        $scoresTable = commerce_get_table_name('customer_violation_scores');
        $checkStmt = $conn->prepare("SELECT id FROM {$scoresTable} WHERE customer_id = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("i", $customerId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $exists = $result->num_rows > 0;
            $checkStmt->close();
            
            if ($exists) {
                $updateStmt = $conn->prepare("UPDATE {$scoresTable} SET active_score = ?, total_score = ?, violation_tier = ?, violation_count = ?, violations_this_month = ?, violations_this_year = ?, last_violation_date = ? WHERE customer_id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("iisiiisi", $activeScore, $totalScore, $violationTier, $violationCount, $violationsThisMonth, $violationsThisYear, $lastViolationDate, $customerId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            } else {
                $insertStmt = $conn->prepare("INSERT INTO {$scoresTable} (customer_id, active_score, total_score, violation_tier, violation_count, violations_this_month, violations_this_year, last_violation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("iiisiiis", $customerId, $activeScore, $totalScore, $violationTier, $violationCount, $violationsThisMonth, $violationsThisYear, $lastViolationDate);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
            }
        }
        
        return true;
    }
    
    return false;
}

/**
 * Get customer violation score
 * @param int $customerId Customer ID
 * @return array Violation score data
 */
function commerce_get_customer_violation_score($customerId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [
            'active_score' => 0,
            'total_score' => 0,
            'violation_tier' => 'none',
            'violation_count' => 0,
            'last_violation_date' => null
        ];
    }
    
    $tableName = commerce_get_table_name('customer_violation_scores');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE customer_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $score = $result->fetch_assoc();
        $stmt->close();
        
        if ($score) {
            return [
                'active_score' => (int)$score['active_score'],
                'total_score' => (int)$score['total_score'],
                'violation_tier' => $score['violation_tier'],
                'violation_count' => (int)$score['violation_count'],
                'last_violation_date' => $score['last_violation_date']
            ];
        }
    }
    
    return [
        'active_score' => 0,
        'total_score' => 0,
        'violation_tier' => 'none',
        'violation_count' => 0,
        'last_violation_date' => null
    ];
}

/**
 * Get customer violations
 * @param int $customerId Customer ID
 * @param bool $includeExpired Include expired violations
 * @param bool $includeForgiven Include forgiven violations
 * @return array Violations
 */
function commerce_get_customer_violations($customerId, $includeExpired = false, $includeForgiven = false) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('collection_violations');
    $sql = "SELECT * FROM {$tableName} WHERE customer_id = ?";
    
    if (!$includeExpired) {
        $sql .= " AND (expiration_date IS NULL OR expiration_date > NOW())";
    }
    
    if (!$includeForgiven) {
        $sql .= " AND is_forgiven = 0";
    }
    
    $sql .= " ORDER BY violation_date DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $violations = [];
        while ($row = $result->fetch_assoc()) {
            $violations[] = $row;
        }
        $stmt->close();
        return $violations;
    }
    
    return [];
}

/**
 * Check and expire violations (cron job)
 * @return int Number of violations expired
 */
function commerce_check_violation_expiration() {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    $tableName = commerce_get_table_name('collection_violations');
    $stmt = $conn->prepare("UPDATE {$tableName} SET expiration_date = NOW() WHERE expiration_date IS NOT NULL AND expiration_date <= NOW() AND expiration_date != '0000-00-00 00:00:00'");
    if ($stmt) {
        $stmt->execute();
        $affected = $conn->affected_rows;
        $stmt->close();
        
        // Recalculate all customer scores
        $customersStmt = $conn->prepare("SELECT DISTINCT customer_id FROM {$tableName} WHERE expiration_date IS NOT NULL");
        if ($customersStmt) {
            $customersStmt->execute();
            $result = $customersStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                commerce_update_customer_violation_score($row['customer_id']);
            }
            $customersStmt->close();
        }
        
        return $affected;
    }
    
    return 0;
}

/**
 * Forgive violation
 * @param int $violationId Violation ID
 * @param int $forgivenBy Admin user ID
 * @param string|null $reason Forgiveness reason
 * @return bool Success
 */
function commerce_forgive_violation($violationId, $forgivenBy, $reason = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('collection_violations');
    $forgivenAt = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_forgiven = 1, forgiven_at = ?, forgiven_by = ?, forgiveness_reason = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sisi", $forgivenAt, $forgivenBy, $reason, $violationId);
        if ($stmt->execute()) {
            // Get customer ID to recalculate score
            $getStmt = $conn->prepare("SELECT customer_id FROM {$tableName} WHERE id = ? LIMIT 1");
            if ($getStmt) {
                $getStmt->bind_param("i", $violationId);
                $getStmt->execute();
                $result = $getStmt->get_result();
                $row = $result->fetch_assoc();
                $getStmt->close();
                
                if ($row) {
                    commerce_update_customer_violation_score($row['customer_id']);
                }
            }
            
            $stmt->close();
            return true;
        }
        $stmt->close();
    }
    
    return false;
}

/**
 * Check if customer qualifies for auto-forgiveness
 * @param int $customerId Customer ID
 * @return array Forgiveness eligibility
 */
function commerce_check_forgiveness_rules($customerId) {
    // TODO: Implement forgiveness rules (e.g., X successful collections after violation = auto-forgive)
    return [
        'qualifies_for_forgiveness' => false,
        'violations_to_forgive' => []
    ];
}

/**
 * Customer appeals violation
 * @param int $violationId Violation ID
 * @param int $customerId Customer ID
 * @param string $appealReason Appeal reason
 * @return bool Success
 */
function commerce_appeal_violation($violationId, $customerId, $appealReason) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('collection_violations');
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_appealed = 1, appeal_reason = ?, appeal_status = 'pending' WHERE id = ? AND customer_id = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $appealReason, $violationId, $customerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Process violation appeal
 * @param int $violationId Violation ID
 * @param bool $approved Whether appeal is approved
 * @param int $processedBy Admin user ID
 * @param string|null $notes Processing notes
 * @return bool Success
 */
function commerce_process_violation_appeal($violationId, $approved, $processedBy, $notes = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = commerce_get_table_name('collection_violations');
    $appealStatus = $approved ? 'approved' : 'rejected';
    $processedAt = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET appeal_status = ?, appeal_processed_at = ?, appeal_processed_by = ?, appeal_notes = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssisi", $appealStatus, $processedAt, $processedBy, $notes, $violationId);
        if ($stmt->execute()) {
            // If approved, forgive the violation
            if ($approved) {
                commerce_forgive_violation($violationId, $processedBy, 'Appeal approved');
            }
            
            $stmt->close();
            return true;
        }
        $stmt->close();
    }
    
    return false;
}

/**
 * Get violation impact on pricing
 * @param int $customerId Customer ID
 * @param string $collectionType Collection type ('early_bird' or 'after_hours')
 * @return array Pricing impact data
 */
function commerce_get_violation_impact_on_pricing($customerId, $collectionType) {
    $violationScore = commerce_get_customer_violation_score($customerId);
    
    // Get applicable pricing rules
    // TODO: Implement collection pricing rules lookup
    // For now, return default
    return [
        'pricing_rule_id' => null,
        'charge_amount' => 0.00,
        'pricing_tier' => $violationScore['violation_tier'],
        'violation_message' => null
    ];
}

/**
 * Get violation pricing message
 * @param int $customerId Customer ID
 * @param string $collectionType Collection type
 * @param float $chargeAmount Charge amount
 * @return string Formatted message
 */
function commerce_get_violation_pricing_message($customerId, $collectionType, $chargeAmount) {
    $violations = commerce_get_customer_violations($customerId);
    $violationCount = count($violations);
    
    if ($violationCount > 0) {
        return "Due to {$violationCount} previous missed collection(s), a charge applies for this {$collectionType} collection.";
    }
    
    return null;
}

