<?php
/**
 * Order Management Component - Unit Tests
 * Test core functions
 */

require_once __DIR__ . '/../bootstrap.php';

class OrderManagementUnitTests {
    
    public function test_get_parameter() {
        $value = order_management_get_parameter('test_param', 'default');
        assert($value === 'default', 'Should return default value');
    }
    
    public function test_sanitize() {
        $input = '<script>alert("xss")</script>';
        $output = order_management_sanitize($input);
        assert(strpos($output, '<script>') === false, 'Should sanitize HTML');
    }
    
    public function test_generate_token() {
        $token = order_management_generate_token(32);
        assert(strlen($token) === 64, 'Should generate 64-char token');
    }
    
    public function run_all() {
        $this->test_get_parameter();
        $this->test_sanitize();
        $this->test_generate_token();
        echo "All unit tests passed!\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $tests = new OrderManagementUnitTests();
    $tests->run_all();
}

