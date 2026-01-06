<?php
/**
 * Payment Processing Component - Stripe Gateway
 * Stripe payment gateway implementation
 */

require_once __DIR__ . '/../../core/gateway-adapter.php';

/**
 * Stripe Gateway Class
 */
class StripeGateway extends BaseGateway {
    private $apiKey;
    private $apiVersion = '2023-10-16';
    
    /**
     * Constructor
     */
    public function __construct($gatewayConfig) {
        parent::__construct($gatewayConfig);
        
        // Get API key (encrypted)
        $this->apiKey = $this->getEncryptedConfig('api_key');
        if (empty($this->apiKey)) {
            throw new Exception('Stripe API key not configured');
        }
    }
    
    /**
     * Process payment
     */
    public function processPayment($transaction) {
        try {
            // Create payment intent
            $paymentIntent = $this->createPaymentIntent([
                'amount' => $this->convertAmountToCents($transaction['amount']),
                'currency' => strtolower($transaction['currency'] ?? 'usd'),
                'payment_method' => $transaction['payment_method_id'] ?? null,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'transaction_id' => $transaction['transaction_id'] ?? '',
                    'account_id' => $transaction['account_id'] ?? ''
                ]
            ]);
            
            if (!$paymentIntent['success']) {
                return [
                    'success' => false,
                    'error' => $paymentIntent['error'] ?? 'Payment failed',
                    'gateway_response' => $paymentIntent
                ];
            }
            
            $intent = $paymentIntent['data'];
            
            // Update transaction record
            $this->updateTransactionRecord($transaction['id'], [
                'gateway_transaction_id' => $intent['id'],
                'gateway_response' => $intent,
                'status' => $intent['status'] === 'succeeded' ? 'completed' : 'processing'
            ]);
            
            // Log activity
            $this->logActivity('payment_processed', [
                'transaction_id' => $transaction['transaction_id'],
                'gateway_transaction_id' => $intent['id'],
                'status' => $intent['status']
            ]);
            
            return [
                'success' => $intent['status'] === 'succeeded',
                'gateway_transaction_id' => $intent['id'],
                'status' => $intent['status'],
                'requires_action' => $intent['status'] === 'requires_action',
                'client_secret' => $intent['client_secret'] ?? null,
                'gateway_response' => $intent
            ];
            
        } catch (Exception $e) {
            error_log("Stripe Gateway Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($refund) {
        try {
            $transaction = payment_processing_get_transaction($refund['transaction_id']);
            if (!$transaction || empty($transaction['gateway_transaction_id'])) {
                return [
                    'success' => false,
                    'error' => 'Transaction not found or missing gateway transaction ID'
                ];
            }
            
            $refundData = [
                'payment_intent' => $transaction['gateway_transaction_id'],
                'amount' => $this->convertAmountToCents($refund['amount']),
                'reason' => $refund['reason'] ?? 'requested_by_customer'
            ];
            
            $result = $this->makeStripeRequest('refunds', $refundData);
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Refund failed',
                    'gateway_response' => $result
                ];
            }
            
            $this->logActivity('refund_processed', [
                'refund_id' => $refund['refund_id'],
                'gateway_refund_id' => $result['data']['id'],
                'amount' => $refund['amount']
            ]);
            
            return [
                'success' => true,
                'gateway_refund_id' => $result['data']['id'],
                'status' => $result['data']['status'],
                'gateway_response' => $result['data']
            ];
            
        } catch (Exception $e) {
            error_log("Stripe Gateway Refund Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create subscription
     */
    public function createSubscription($subscription) {
        try {
            // Create customer if needed
            $customerId = $this->getOrCreateCustomer($subscription);
            
            // Create subscription
            $subscriptionData = [
                'customer' => $customerId,
                'items' => [[
                    'price' => $subscription['price_id'] ?? null,
                    'price_data' => $subscription['price_data'] ?? null
                ]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'payment_method_types' => ['card'],
                    'save_default_payment_method' => 'on_subscription'
                ],
                'expand' => ['latest_invoice.payment_intent']
            ];
            
            $result = $this->makeStripeRequest('subscriptions', $subscriptionData);
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Subscription creation failed',
                    'gateway_response' => $result
                ];
            }
            
            return [
                'success' => true,
                'gateway_subscription_id' => $result['data']['id'],
                'status' => $result['data']['status'],
                'client_secret' => $result['data']['latest_invoice']['payment_intent']['client_secret'] ?? null,
                'gateway_response' => $result['data']
            ];
            
        } catch (Exception $e) {
            error_log("Stripe Gateway Subscription Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription($subscriptionId) {
        try {
            $result = $this->makeStripeRequest("subscriptions/{$subscriptionId}", [], 'DELETE');
            
            return [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
                'gateway_response' => $result['data'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update subscription
     */
    public function updateSubscription($subscriptionId, $updates) {
        try {
            $result = $this->makeStripeRequest("subscriptions/{$subscriptionId}", $updates, 'POST');
            
            return [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
                'gateway_response' => $result['data'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle webhook
     */
    public function handleWebhook($payload, $signature = null) {
        try {
            // Verify signature
            if (!$this->verifyWebhookSignature($payload, $signature)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            $event = is_string($payload) ? json_decode($payload, true) : $payload;
            
            // Handle different event types
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentIntentSucceeded($event);
                case 'payment_intent.payment_failed':
                    return $this->handlePaymentIntentFailed($event);
                case 'charge.refunded':
                    return $this->handleRefunded($event);
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionEvent($event);
                default:
                    return [
                        'success' => true,
                        'message' => 'Event type not handled',
                        'event_type' => $event['type']
                    ];
            }
            
        } catch (Exception $e) {
            error_log("Stripe Webhook Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test connection
     */
    public function testConnection() {
        try {
            $result = $this->makeStripeRequest('charges', ['limit' => 1], 'GET');
            return [
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies() {
        // Stripe supports many currencies, return common ones
        return ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY', 'CHF', 'NZD', 'SGD', 'HKD'];
    }
    
    /**
     * Get supported payment methods
     */
    public function getSupportedPaymentMethods() {
        return ['card', 'bank_transfer', 'ach_direct_debit'];
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature) {
        if (empty($signature)) {
            return false;
        }
        
        $webhookSecret = $this->getEncryptedConfig('webhook_secret');
        if (empty($webhookSecret)) {
            return false;
        }
        
        $payloadString = is_string($payload) ? $payload : json_encode($payload);
        
        // Stripe signature verification
        $expectedSignature = hash_hmac('sha256', $payloadString, $webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Make Stripe API request
     */
    private function makeStripeRequest($endpoint, $data = [], $method = 'POST') {
        $baseUrl = $this->isTestMode ? 'https://api.stripe.com/v1' : 'https://api.stripe.com/v1';
        $url = $baseUrl . '/' . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: ' . $this->apiVersion
        ];
        
        // Convert data to form-encoded for Stripe
        $postData = http_build_query($data);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $postData
        ]);
        
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
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $decoded,
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'error' => $decoded['error']['message'] ?? 'Request failed',
                'http_code' => $httpCode,
                'data' => $decoded
            ];
        }
    }
    
    /**
     * Create payment intent
     */
    private function createPaymentIntent($data) {
        return $this->makeStripeRequest('payment_intents', $data);
    }
    
    /**
     * Get or create customer
     */
    private function getOrCreateCustomer($subscription) {
        // Check if customer exists
        if (!empty($subscription['customer_id'])) {
            return $subscription['customer_id'];
        }
        
        // Create new customer
        $customerData = [
            'email' => $subscription['customer_email'] ?? '',
            'name' => $subscription['customer_name'] ?? '',
            'metadata' => [
                'account_id' => $subscription['account_id'] ?? ''
            ]
        ];
        
        $result = $this->makeStripeRequest('customers', $customerData);
        if ($result['success']) {
            return $result['data']['id'];
        }
        
        throw new Exception('Failed to create customer: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    /**
     * Convert amount to cents (Stripe uses cents)
     */
    private function convertAmountToCents($amount) {
        return (int)round($amount * 100);
    }
    
    /**
     * Handle payment intent succeeded event
     */
    private function handlePaymentIntentSucceeded($event) {
        $paymentIntent = $event['data']['object'];
        
        // Find transaction by gateway_transaction_id
        $transaction = payment_processing_get_transaction_by_gateway_id($paymentIntent['id']);
        if ($transaction) {
            payment_processing_update_transaction($transaction['id'], [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'gateway_response' => $paymentIntent
            ]);
        }
        
        return [
            'success' => true,
            'event_type' => 'payment_intent.succeeded',
            'transaction_id' => $transaction['id'] ?? null
        ];
    }
    
    /**
     * Handle payment intent failed event
     */
    private function handlePaymentIntentFailed($event) {
        $paymentIntent = $event['data']['object'];
        
        $transaction = payment_processing_get_transaction_by_gateway_id($paymentIntent['id']);
        if ($transaction) {
            payment_processing_update_transaction($transaction['id'], [
            'status' => 'failed',
            'failed_at' => date('Y-m-d H:i:s'),
            'failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
            'gateway_response' => $paymentIntent
        ]);
        }
        
        return [
            'success' => true,
            'event_type' => 'payment_intent.payment_failed'
        ];
    }
    
    /**
     * Handle refunded event
     */
    private function handleRefunded($event) {
        // Handle refund webhook
        return [
            'success' => true,
            'event_type' => 'charge.refunded'
        ];
    }
    
    /**
     * Handle subscription event
     */
    private function handleSubscriptionEvent($event) {
        // Handle subscription webhooks
        return [
            'success' => true,
            'event_type' => $event['type']
        ];
    }
}

