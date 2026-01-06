<?php
/**
 * Mock Base Class
 * Base class for creating mock objects
 */

class MockBase {
    protected $methods = [];
    protected $calls = [];
    protected $returnValues = [];
    protected $exceptions = [];
    
    /**
     * Set method expectation
     */
    public function expects($method, $times = 1) {
        if (!isset($this->methods[$method])) {
            $this->methods[$method] = [];
        }
        
        $this->methods[$method]['times'] = $times;
        $this->methods[$method]['called'] = 0;
        
        return $this;
    }
    
    /**
     * Set return value
     */
    public function returns($value) {
        $lastMethod = $this->get_last_method();
        if ($lastMethod) {
            $this->returnValues[$lastMethod] = $value;
        }
        return $this;
    }
    
    /**
     * Set exception to throw
     */
    public function throws($exception) {
        $lastMethod = $this->get_last_method();
        if ($lastMethod) {
            $this->exceptions[$lastMethod] = $exception;
        }
        return $this;
    }
    
    /**
     * Call method
     */
    public function __call($method, $args) {
        $this->calls[] = [
            'method' => $method,
            'args' => $args,
            'time' => microtime(true)
        ];
        
        if (isset($this->exceptions[$method])) {
            throw $this->exceptions[$method];
        }
        
        if (isset($this->returnValues[$method])) {
            return $this->returnValues[$method];
        }
        
        return null;
    }
    
    /**
     * Verify expectations
     */
    public function verify() {
        foreach ($this->methods as $method => $expectation) {
            $expected = $expectation['times'];
            $actual = $expectation['called'];
            
            if ($actual !== $expected) {
                throw new Exception("Mock expectation failed: {$method} expected {$expected} calls, got {$actual}");
            }
        }
    }
    
    /**
     * Get last method in chain
     */
    protected function get_last_method() {
        $methods = array_keys($this->methods);
        return end($methods);
    }
    
    /**
     * Get call history
     */
    public function get_calls() {
        return $this->calls;
    }
}

/**
 * Create a mock object
 */
function create_mock($className) {
    return new MockBase();
}

/**
 * Create a stub (simple mock with return value)
 */
function create_stub($returnValue) {
    $stub = new MockBase();
    $stub->returns($returnValue);
    return $stub;
}

/**
 * Create a spy (tracks calls)
 */
function create_spy($object) {
    // Spy wraps real object and tracks calls
    return new class($object) extends MockBase {
        private $realObject;
        
        public function __construct($realObject) {
            $this->realObject = $realObject;
        }
        
        public function __call($method, $args) {
            parent::__call($method, $args);
            return call_user_func_array([$this->realObject, $method], $args);
        }
    };
}

