<?php
/**
 * Payment Processing Component - Gateway Adapter Interface
 * Defines the interface that all payment gateways must implement
 */

/**
 * Payment Gateway Interface
 * All payment gateways must implement this interface
 */
interface PaymentGatewayInterface {
    /**
     * Process a payment
     * @param array $transaction Transaction data
     * @return array Result with success status and transaction details
     */
    public function processPayment($transaction);
    
    /**
     * Process a refund
     * @param array $refund Refund data
     * @return array Result with success status and refund details
     */
    public function processRefund($refund);
    
    /**
     * Create a subscription
     * @param array $subscription Subscription data
     * @return array Result with success status and subscription details
     */
    public function createSubscription($subscription);
    
    /**
     * Cancel a subscription
     * @param string $subscriptionId Gateway subscription ID
     * @return array Result with success status
     */
    public function cancelSubscription($subscriptionId);
    
    /**
     * Update a subscription
     * @param string $subscriptionId Gateway subscription ID
     * @param array $updates Update data
     * @return array Result with success status
     */
    public function updateSubscription($subscriptionId, $updates);
    
    /**
     * Handle webhook event
     * @param array $payload Webhook payload
     * @param string $signature Webhook signature
     * @return array Result with success status and event details
     */
    public function handleWebhook($payload, $signature = null);
    
    /**
     * Test gateway connection
     * @return array Result with success status
     */
    public function testConnection();
    
    /**
     * Get supported currencies
     * @return array List of supported currency codes
     */
    public function getSupportedCurrencies();
    
    /**
     * Get supported payment methods
     * @return array List of supported payment methods
     */
    public function getSupportedPaymentMethods();
    
    /**
     * Verify webhook signature
     * @param array $payload Webhook payload
     * @param string $signature Webhook signature
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature($payload, $signature);
}

/**
 * Base Gateway Abstract Class
 * Provides common functionality for all gateways
 */
abstract class BaseGateway implements PaymentGatewayInterface {
    protected $config;
    protected $gatewayConfig;
    protected $conn;
    protected $isTestMode;
    
    /**
     * Constructor
     * @param array $gatewayConfig Gateway configuration from database
     */
    public function __construct($gatewayConfig) {
        $this->gatewayConfig = $gatewayConfig;
        $this->isTestMode = !empty($gatewayConfig['is_test_mode']);
        
        // Load gateway-specific config
        if (!empty($gatewayConfig['config_json'])) {
            $this->config = json_decode($gatewayConfig['config_json'], true);
        } else {
            $this->config = [];
        }
        
        // Get database connection
        require_once __DIR__ . '/database.php';
        $this->conn = payment_processing_get_db_connection();
    }
    
    /**
     * Get gateway configuration value
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    protected function getConfig($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Get encrypted configuration value (decrypts automatically)
     * @param string $key Configuration key
     * @return mixed Decrypted value
     */
    protected function getEncryptedConfig($key) {
        require_once __DIR__ . '/encryption.php';
        $encryptedValue = $this->getConfig($key);
        if (empty($encryptedValue)) {
            return null;
        }
        return payment_processing_decrypt($encryptedValue);
    }
    
    /**
     * Log gateway activity
     * @param string $action Action performed
     * @param array $data Related data
     * @param string $status Status (success, error, etc.)
     * @return void
     */
    protected function logActivity($action, $data = [], $status = 'success') {
        require_once __DIR__ . '/audit-logger.php';
        payment_processing_log_audit(
            $action,
            'gateway',
            $this->gatewayConfig['id'],
            null,
            $data,
            ['gateway_key' => $this->gatewayConfig['gateway_key'], 'status' => $status]
        );
    }
    
    /**
     * Create transaction record
     * @param array $data Transaction data
     * @return array Result with transaction ID
     */
    protected function createTransactionRecord($data) {
        require_once __DIR__ . '/database.php';
        $data['gateway_id'] = $this->gatewayConfig['id'];
        return payment_processing_create_transaction($data);
    }
    
    /**
     * Update transaction record
     * @param int $transactionId Transaction ID
     * @param array $data Update data
     * @return bool Success
     */
    protected function updateTransactionRecord($transactionId, $data) {
        require_once __DIR__ . '/database.php';
        return payment_processing_update_transaction($transactionId, $data);
    }
    
    /**
     * Make HTTP request
     * @param string $url URL
     * @param array $data Request data
     * @param string $method HTTP method
     * @param array $headers Additional headers
     * @return array Response data
     */
    protected function makeHttpRequest($url, $data = [], $method = 'POST', $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded !== null ? $decoded : $response,
            'raw' => $response
        ];
    }
    
    /**
     * Default implementation for getSupportedCurrencies
     * Override in child class if needed
     */
    public function getSupportedCurrencies() {
        if (!empty($this->gatewayConfig['supported_currencies'])) {
            return json_decode($this->gatewayConfig['supported_currencies'], true);
        }
        return ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];
    }
    
    /**
     * Default implementation for getSupportedPaymentMethods
     * Override in child class if needed
     */
    public function getSupportedPaymentMethods() {
        if (!empty($this->gatewayConfig['supported_payment_methods'])) {
            return json_decode($this->gatewayConfig['supported_payment_methods'], true);
        }
        return ['card', 'bank_transfer'];
    }
    
    /**
     * Default implementation for verifyWebhookSignature
     * Override in child class with gateway-specific verification
     */
    public function verifyWebhookSignature($payload, $signature) {
        // Default: no verification (should be overridden)
        return true;
    }
}

