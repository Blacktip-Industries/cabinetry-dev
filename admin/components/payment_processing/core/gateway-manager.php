<?php
/**
 * Payment Processing Component - Gateway Manager
 * Manages gateway plugins and provides gateway instances
 */

require_once __DIR__ . '/gateway-adapter.php';
require_once __DIR__ . '/database.php';

/**
 * Gateway Manager Class
 * Handles loading and managing payment gateways
 */
class PaymentGatewayManager {
    private static $instance = null;
    private $gateways = [];
    private $gatewayClasses = [];
    
    /**
     * Get singleton instance
     * @return PaymentGatewayManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->loadGatewayClasses();
    }
    
    /**
     * Load all gateway classes
     */
    private function loadGatewayClasses() {
        $gatewaysDir = __DIR__ . '/../gateways';
        
        if (!is_dir($gatewaysDir)) {
            return;
        }
        
        $gatewayDirs = array_filter(glob($gatewaysDir . '/*'), 'is_dir');
        
        foreach ($gatewayDirs as $gatewayDir) {
            $gatewayName = basename($gatewayDir);
            
            // Skip base directory
            if ($gatewayName === 'base') {
                continue;
            }
            
            // Look for gateway class file
            $gatewayFile = $gatewayDir . '/' . ucfirst($gatewayName) . 'Gateway.php';
            if (file_exists($gatewayFile)) {
                require_once $gatewayFile;
                $className = ucfirst($gatewayName) . 'Gateway';
                if (class_exists($className)) {
                    $this->gatewayClasses[$gatewayName] = $className;
                }
            }
        }
    }
    
    /**
     * Get gateway instance
     * @param string $gatewayKey Gateway key
     * @return PaymentGatewayInterface|null Gateway instance or null
     */
    public function getGateway($gatewayKey) {
        // Check cache
        if (isset($this->gateways[$gatewayKey])) {
            return $this->gateways[$gatewayKey];
        }
        
        // Get gateway config from database
        $gatewayConfig = payment_processing_get_gateway_by_key($gatewayKey);
        if (!$gatewayConfig) {
            return null;
        }
        
        // Get gateway type
        $gatewayType = $gatewayConfig['gateway_type'] ?? $gatewayKey;
        
        // Check if gateway class exists
        if (!isset($this->gatewayClasses[$gatewayType])) {
            error_log("Payment Processing: Gateway class not found for type: {$gatewayType}");
            return null;
        }
        
        // Instantiate gateway
        $className = $this->gatewayClasses[$gatewayType];
        try {
            $gateway = new $className($gatewayConfig);
            $this->gateways[$gatewayKey] = $gateway;
            return $gateway;
        } catch (Exception $e) {
            error_log("Payment Processing: Error instantiating gateway {$gatewayKey}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get gateway instance by ID
     * @param int $gatewayId Gateway ID
     * @return PaymentGatewayInterface|null Gateway instance or null
     */
    public function getGatewayById($gatewayId) {
        $gatewayConfig = payment_processing_get_gateway($gatewayId);
        if (!$gatewayConfig) {
            return null;
        }
        
        return $this->getGateway($gatewayConfig['gateway_key']);
    }
    
    /**
     * Get all available gateway types
     * @return array List of gateway types
     */
    public function getAvailableGatewayTypes() {
        return array_keys($this->gatewayClasses);
    }
    
    /**
     * Get all active gateways
     * @return array Array of gateway instances
     */
    public function getActiveGateways() {
        $activeGateways = [];
        $gatewayConfigs = payment_processing_get_active_gateways();
        
        foreach ($gatewayConfigs as $config) {
            $gateway = $this->getGateway($config['gateway_key']);
            if ($gateway) {
                $activeGateways[] = [
                    'config' => $config,
                    'instance' => $gateway
                ];
            }
        }
        
        return $activeGateways;
    }
    
    /**
     * Register a gateway class
     * @param string $gatewayType Gateway type
     * @param string $className Class name
     * @return bool Success
     */
    public function registerGateway($gatewayType, $className) {
        if (!class_exists($className)) {
            return false;
        }
        
        // Check if class implements interface
        $reflection = new ReflectionClass($className);
        if (!$reflection->implementsInterface('PaymentGatewayInterface')) {
            return false;
        }
        
        $this->gatewayClasses[$gatewayType] = $className;
        return true;
    }
}

/**
 * Get gateway manager instance
 * @return PaymentGatewayManager
 */
function payment_processing_get_gateway_manager() {
    return PaymentGatewayManager::getInstance();
}

/**
 * Get gateway instance
 * @param string $gatewayKey Gateway key
 * @return PaymentGatewayInterface|null
 */
function payment_processing_get_gateway_instance($gatewayKey) {
    $manager = payment_processing_get_gateway_manager();
    return $manager->getGateway($gatewayKey);
}

/**
 * Get gateway instance by ID
 * @param int $gatewayId Gateway ID
 * @return PaymentGatewayInterface|null
 */
function payment_processing_get_gateway_instance_by_id($gatewayId) {
    $manager = payment_processing_get_gateway_manager();
    return $manager->getGatewayById($gatewayId);
}

