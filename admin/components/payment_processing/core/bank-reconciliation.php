<?php
/**
 * Payment Processing Component - Bank Reconciliation
 * Handles bank statement import and transaction matching
 */

require_once __DIR__ . '/database.php';

/**
 * Import bank statement
 * @param array $statementData Statement data
 * @return array Result with reconciliation ID
 */
function payment_processing_import_bank_statement($statementData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('bank_reconciliation');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (statement_date, bank_name, account_number, opening_balance, closing_balance, statement_data) VALUES (?, ?, ?, ?, ?, ?)");
        
        $statementDataJson = json_encode($statementData['transactions'] ?? []);
        
        $stmt->bind_param("sssdds",
            $statementData['statement_date'],
            $statementData['bank_name'] ?? null,
            $statementData['account_number'] ?? null,
            $statementData['opening_balance'],
            $statementData['closing_balance'],
            $statementDataJson
        );
        $stmt->execute();
        $reconciliationId = $conn->insert_id;
        $stmt->close();
        
        // Auto-match transactions
        $matchResult = payment_processing_match_bank_transactions($reconciliationId);
        
        return [
            'success' => true,
            'reconciliation_id' => $reconciliationId,
            'matched' => $matchResult['matched'],
            'unmatched' => $matchResult['unmatched']
        ];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Match bank transactions with payment transactions
 * @param int $reconciliationId Reconciliation ID
 * @return array Match results
 */
function payment_processing_match_bank_transactions($reconciliationId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get reconciliation
    $reconTable = payment_processing_get_table_name('bank_reconciliation');
    $stmt = $conn->prepare("SELECT * FROM {$reconTable} WHERE id = ?");
    $stmt->bind_param("i", $reconciliationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $reconciliation = $result->fetch_assoc();
    $stmt->close();
    
    if (!$reconciliation) {
        return ['success' => false, 'error' => 'Reconciliation not found'];
    }
    
    $statementTransactions = json_decode($reconciliation['statement_data'], true);
    $matched = [];
    $unmatched = [];
    
    // Get transactions in date range
    $transTable = payment_processing_get_table_name('transactions');
    $dateFrom = date('Y-m-d', strtotime($reconciliation['statement_date'] . ' -7 days'));
    $dateTo = date('Y-m-d', strtotime($reconciliation['statement_date'] . ' +7 days'));
    
    $transStmt = $conn->prepare("SELECT * FROM {$transTable} WHERE status = 'completed' AND created_at >= ? AND created_at <= ?");
    $transStmt->bind_param("ss", $dateFrom, $dateTo);
    $transStmt->execute();
    $transResult = $transStmt->get_result();
    
    $transactions = [];
    while ($row = $transResult->fetch_assoc()) {
        $transactions[] = $row;
    }
    $transStmt->close();
    
    // Match transactions
    foreach ($statementTransactions as $bankTrans) {
        $matchedTransaction = null;
        $matchScore = 0;
        
        foreach ($transactions as $trans) {
            $score = 0;
            
            // Match by amount (exact or within tolerance)
            $amountDiff = abs($bankTrans['amount'] - $trans['amount']);
            if ($amountDiff < 0.01) {
                $score += 50;
            } elseif ($amountDiff < 1.00) {
                $score += 25;
            }
            
            // Match by date (within 3 days)
            $dateDiff = abs(strtotime($bankTrans['date']) - strtotime($trans['created_at']));
            if ($dateDiff < 86400) { // 1 day
                $score += 30;
            } elseif ($dateDiff < 259200) { // 3 days
                $score += 15;
            }
            
            // Match by reference/description
            if (!empty($bankTrans['reference']) && !empty($trans['gateway_transaction_id'])) {
                if (strpos($bankTrans['reference'], $trans['gateway_transaction_id']) !== false) {
                    $score += 20;
                }
            }
            
            if ($score > $matchScore) {
                $matchScore = $score;
                $matchedTransaction = $trans;
            }
        }
        
        if ($matchScore >= 50 && $matchedTransaction) {
            $matched[] = [
                'bank_transaction' => $bankTrans,
                'transaction_id' => $matchedTransaction['id'],
                'match_score' => $matchScore
            ];
        } else {
            $unmatched[] = $bankTrans;
        }
    }
    
    // Update reconciliation with matches
    $matchedJson = json_encode($matched);
    $unmatchedJson = json_encode($unmatched);
    
    $updateStmt = $conn->prepare("UPDATE {$reconTable} SET matched_transactions = ?, discrepancies = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $matchedJson, $unmatchedJson, $reconciliationId);
    $updateStmt->execute();
    $updateStmt->close();
    
    return [
        'success' => true,
        'matched' => count($matched),
        'unmatched' => count($unmatched),
        'matches' => $matched,
        'unmatched_transactions' => $unmatched
    ];
}

