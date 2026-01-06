<?php
/**
 * Payment Processing Component - Payment Plans Manager
 * Handles payment plans and installments
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/transaction-processor.php';
require_once __DIR__ . '/functions.php';

/**
 * Create payment plan from template
 * @param int $planId Payment plan template ID
 * @param array $transactionData Initial transaction data
 * @return array Result with plan and installments
 */
function payment_processing_create_payment_plan($planId, $transactionData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get plan template
    $tableName = payment_processing_get_table_name('payment_plans');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $planId);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();
    $stmt->close();
    
    if (!$plan) {
        return ['success' => false, 'error' => 'Payment plan not found'];
    }
    
    // Create initial transaction
    $transactionData['transaction_type'] = 'partial_payment';
    $transactionData['amount'] = $plan['installment_amount'];
    $transactionResult = payment_processing_process_payment($transactionData);
    
    if (!$transactionResult['success']) {
        return $transactionResult;
    }
    
    $transactionId = $transactionResult['transaction_id'];
    
    // Create installments
    $installments = [];
    $installmentTable = payment_processing_get_table_name('installments');
    
    $dueDate = date('Y-m-d', strtotime("+{$plan['first_payment_days']} days"));
    
    for ($i = 1; $i <= $plan['number_of_installments']; $i++) {
        // Calculate due date based on frequency
        if ($i > 1) {
            $dueDate = payment_processing_calculate_next_due_date($dueDate, $plan['frequency']);
        }
        
        $stmt = $conn->prepare("INSERT INTO {$installmentTable} (transaction_id, payment_plan_id, installment_number, total_installments, amount, currency, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiiisss",
            $transactionId,
            $planId,
            $i,
            $plan['number_of_installments'],
            $plan['installment_amount'],
            $plan['currency'],
            $dueDate
        );
        $stmt->execute();
        $installmentId = $conn->insert_id;
        $stmt->close();
        
        $installments[] = [
            'id' => $installmentId,
            'installment_number' => $i,
            'amount' => $plan['installment_amount'],
            'due_date' => $dueDate
        ];
    }
    
    // Mark first installment as processing/completed if initial payment succeeded
    if ($transactionResult['status'] === 'completed') {
        $firstInstallment = $installments[0];
        $updateStmt = $conn->prepare("UPDATE {$installmentTable} SET status = 'completed', transaction_id_for_payment = ?, paid_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("ii", $transactionId, $firstInstallment['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    return [
        'success' => true,
        'transaction_id' => $transactionId,
        'plan_id' => $planId,
        'installments' => $installments
    ];
}

/**
 * Calculate next due date based on frequency
 * @param string $currentDate Current date (Y-m-d)
 * @param string $frequency Frequency (daily, weekly, biweekly, monthly, quarterly)
 * @return string Next due date (Y-m-d)
 */
function payment_processing_calculate_next_due_date($currentDate, $frequency) {
    $date = new DateTime($currentDate);
    
    switch ($frequency) {
        case 'daily':
            $date->modify('+1 day');
            break;
        case 'weekly':
            $date->modify('+1 week');
            break;
        case 'biweekly':
            $date->modify('+2 weeks');
            break;
        case 'monthly':
            $date->modify('+1 month');
            break;
        case 'quarterly':
            $date->modify('+3 months');
            break;
    }
    
    return $date->format('Y-m-d');
}

/**
 * Process due installments
 * @return int Number of installments processed
 */
function payment_processing_process_due_installments() {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = payment_processing_get_table_name('installments');
        $today = date('Y-m-d');
        
        // Get due installments
        $stmt = $conn->prepare("SELECT i.*, t.gateway_id, t.account_id, t.customer_email, t.customer_name FROM {$tableName} i 
                                INNER JOIN " . payment_processing_get_table_name('transactions') . " t ON i.transaction_id = t.id 
                                WHERE i.status = 'pending' AND i.due_date <= ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processed = 0;
        while ($installment = $result->fetch_assoc()) {
            // Create payment transaction for installment
            $paymentResult = payment_processing_process_payment([
                'gateway_id' => $installment['gateway_id'],
                'account_id' => $installment['account_id'],
                'amount' => $installment['amount'],
                'currency' => $installment['currency'],
                'payment_method' => 'card', // Default, could be stored in plan
                'customer_email' => $installment['customer_email'],
                'customer_name' => $installment['customer_name'],
                'metadata' => [
                    'installment_id' => $installment['id'],
                    'installment_number' => $installment['installment_number']
                ]
            ]);
            
            // Update installment
            $updateStmt = $conn->prepare("UPDATE {$tableName} SET status = ?, transaction_id_for_payment = ?, paid_at = ?, failed_at = ?, failure_reason = ? WHERE id = ?");
            
            if ($paymentResult['success']) {
                $status = 'completed';
                $paidAt = date('Y-m-d H:i:s');
                $failedAt = null;
                $failureReason = null;
            } else {
                $status = 'failed';
                $paidAt = null;
                $failedAt = date('Y-m-d H:i:s');
                $failureReason = $paymentResult['error'] ?? 'Payment failed';
            }
            
            $updateStmt->bind_param("sisssi", $status, $paymentResult['transaction_id'], $paidAt, $failedAt, $failureReason, $installment['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $processed++;
        }
        
        $stmt->close();
        return $processed;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error processing due installments: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get payment plan template
 * @param int $planId Plan ID
 * @return array|null Plan data or null
 */
function payment_processing_get_payment_plan($planId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('payment_plans');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $result = $stmt->get_result();
        $plan = $result->fetch_assoc();
        $stmt->close();
        
        return $plan;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting payment plan: " . $e->getMessage());
        return null;
    }
}

