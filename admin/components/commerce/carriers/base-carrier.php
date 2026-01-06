<?php
/**
 * Commerce Component - Base Carrier Class
 * Base class for carrier integrations
 */

/**
 * Base Carrier Class
 */
abstract class Commerce_Base_Carrier {
    protected $carrierKey;
    protected $carrierName;
    protected $config;
    protected $isTestMode;
    
    /**
     * Constructor
     * @param array $config Carrier configuration
     */
    public function __construct($config = []) {
        $this->config = $config;
        $this->isTestMode = $config['is_test_mode'] ?? true;
    }
    
    /**
     * Get carrier key
     * @return string Carrier key
     */
    public function getCarrierKey() {
        return $this->carrierKey;
    }
    
    /**
     * Get carrier name
     * @return string Carrier name
     */
    public function getCarrierName() {
        return $this->carrierName;
    }
    
    /**
     * Calculate shipping rate
     * @param array $cartItems Cart items
     * @param array $shippingAddress Shipping address
     * @param array $options Additional options
     * @return array Result with rates
     */
    abstract public function calculateRate($cartItems, $shippingAddress, $options = []);
    
    /**
     * Create shipment
     * @param array $orderData Order data
     * @param array $shippingAddress Shipping address
     * @return array Result with tracking number
     */
    abstract public function createShipment($orderData, $shippingAddress);
    
    /**
     * Track shipment
     * @param string $trackingNumber Tracking number
     * @return array Result with tracking info
     */
    abstract public function trackShipment($trackingNumber);
    
    /**
     * Get available services
     * @return array Available services
     */
    abstract public function getAvailableServices();
}

