<?php
/**
 * Payment Processing Component - Tax Reporting
 * Handles tax calculations and reporting (GST, VAT, etc.)
 */

require_once __DIR__ . '/database.php';

/**
 * Calculate tax for transaction
 * @param float $amount Transaction amount
 * @param string $currency Currency code
 * @param string $taxType Tax type (gst, vat, sales_tax)
 * @param float|null $taxRate Tax rate (if null, uses default)
 * @return array Tax calculation
 */
function payment_processing_calculate_tax($amount, $currency, $taxType = 'gst', $taxRate = null) {
    if ($taxRate === null) {
        // Get default tax rate from parameters
        $taxRate = (float)payment_processing_get_parameter('Tax', "default_{$taxType}_rate", 0);
    }
    
    $taxAmount = $amount * ($taxRate / 100);
    $amountIncludingTax = $amount + $taxAmount;
    
    return [
        'amount' => $amount,
        'tax_rate' => $taxRate,
        'tax_amount' => $taxAmount,
        'amount_including_tax' => $amountIncludingTax,
        'tax_type' => $taxType,
        'currency' => $currency
    ];
}

/**
 * Generate tax report
 * @param string $periodStart Start date (Y-m-d)
 * @param string $periodEnd End date (Y-m-d)
 * @param string $taxType Tax type
 * @return array Tax report data
 */
function payment_processing_generate_tax_report($periodStart, $periodEnd, $taxType = 'gst') {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = payment_processing_get_table_name('transactions');
    
    // Get completed transactions in period
    $stmt = $conn->prepare("SELECT currency, SUM(amount) as total_amount, COUNT(*) as transaction_count 
                            FROM {$tableName} 
                            WHERE status = 'completed' 
                            AND created_at >= ? 
                            AND created_at <= ?
                            GROUP BY currency");
    $stmt->bind_param("ss", $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $report = [
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'tax_type' => $taxType,
        'currencies' => []
    ];
    
    $totalTax = 0;
    $totalRevenue = 0;
    
    while ($row = $result->fetch_assoc()) {
        $taxCalc = payment_processing_calculate_tax($row['total_amount'], $row['currency'], $taxType);
        
        $report['currencies'][] = [
            'currency' => $row['currency'],
            'total_revenue' => $row['total_amount'],
            'tax_rate' => $taxCalc['tax_rate'],
            'tax_amount' => $taxCalc['tax_amount'],
            'transaction_count' => $row['transaction_count']
        ];
        
        $totalRevenue += $row['total_amount'];
        $totalTax += $taxCalc['tax_amount'];
    }
    
    $stmt->close();
    
    $report['total_revenue'] = $totalRevenue;
    $report['total_tax'] = $totalTax;
    
    return [
        'success' => true,
        'report' => $report
    ];
}

/**
 * Export tax report for accounting software
 * @param array $reportData Report data
 * @param string $format Export format (csv, json, xml)
 * @return string Export data
 */
function payment_processing_export_tax_report($reportData, $format = 'csv') {
    switch ($format) {
        case 'csv':
            return payment_processing_export_tax_report_csv($reportData);
        case 'json':
            return json_encode($reportData, JSON_PRETTY_PRINT);
        case 'xml':
            return payment_processing_export_tax_report_xml($reportData);
        default:
            return '';
    }
}

/**
 * Export tax report as CSV
 * @param array $reportData Report data
 * @return string CSV data
 */
function payment_processing_export_tax_report_csv($reportData) {
    $csv = "Period,Currency,Total Revenue,Tax Rate,Tax Amount,Transaction Count\n";
    
    foreach ($reportData['currencies'] ?? [] as $currency) {
        $csv .= sprintf("%s to %s,%s,%.2f,%.2f%%,%.2f,%d\n",
            $reportData['period_start'],
            $reportData['period_end'],
            $currency['currency'],
            $currency['total_revenue'],
            $currency['tax_rate'],
            $currency['tax_amount'],
            $currency['transaction_count']
        );
    }
    
    return $csv;
}

/**
 * Export tax report as XML
 * @param array $reportData Report data
 * @return string XML data
 */
function payment_processing_export_tax_report_xml($reportData) {
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<tax_report>\n";
    $xml .= "  <period_start>{$reportData['period_start']}</period_start>\n";
    $xml .= "  <period_end>{$reportData['period_end']}</period_end>\n";
    $xml .= "  <tax_type>{$reportData['tax_type']}</tax_type>\n";
    $xml .= "  <total_revenue>{$reportData['total_revenue']}</total_revenue>\n";
    $xml .= "  <total_tax>{$reportData['total_tax']}</total_tax>\n";
    $xml .= "  <currencies>\n";
    
    foreach ($reportData['currencies'] ?? [] as $currency) {
        $xml .= "    <currency>\n";
        $xml .= "      <code>{$currency['currency']}</code>\n";
        $xml .= "      <total_revenue>{$currency['total_revenue']}</total_revenue>\n";
        $xml .= "      <tax_rate>{$currency['tax_rate']}</tax_rate>\n";
        $xml .= "      <tax_amount>{$currency['tax_amount']}</tax_amount>\n";
        $xml .= "      <transaction_count>{$currency['transaction_count']}</transaction_count>\n";
        $xml .= "    </currency>\n";
    }
    
    $xml .= "  </currencies>\n";
    $xml .= "</tax_report>\n";
    
    return $xml;
}

