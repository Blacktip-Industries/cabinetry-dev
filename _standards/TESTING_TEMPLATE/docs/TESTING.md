# {COMPONENT_NAME} Component - Comprehensive Testing Guide

## Overview

This document provides comprehensive testing procedures for the {COMPONENT_NAME} Component. The testing infrastructure includes unit tests, integration tests, functional tests, performance tests, and security tests.

## Table of Contents

1. [Installation Verification](#installation-verification)
2. [Running Tests](#running-tests)
3. [Test Data Generation](#test-data-generation)
4. [Writing Tests](#writing-tests)
5. [Test Types](#test-types)
6. [Test Utilities](#test-utilities)
7. [Debugging](#debugging)
8. [Analytics](#analytics)
9. [Security Testing](#security-testing)
10. [Performance Testing](#performance-testing)
11. [Troubleshooting](#troubleshooting)

## Installation Verification

### Automated Verification

Run the verification script to check installation:

```bash
php verify.php
```

This script checks:
- Configuration file existence
- Database connection
- Database tables
- Core functions availability
- Admin pages existence
- Database operations
- Version information

### Manual Verification Checklist

- [ ] Config file exists at `admin/components/{component_name}/config.php`
- [ ] Database connection successful
- [ ] All required tables created
- [ ] Can access admin pages
- [ ] Basic CRUD operations work
- [ ] All core functions available

## Running Tests

### Run All Tests

```bash
php tests/run_tests.php
```

### Run Specific Test Types

```bash
# Unit tests only
php tests/run_tests.php --filter=unit

# Integration tests only
php tests/run_tests.php --filter=integration

# Functional tests only
php tests/run_tests.php --filter=functional

# Performance tests only
php tests/run_tests.php --filter=performance
```

### Run Specific Test File

```bash
php tests/unit/test_functions.php
```

### Parallel Execution

Run tests in parallel for faster execution:

```bash
php tests/run_tests.php --workers=4
```

### Watch Mode

Automatically run tests when files change:

```bash
php tests/run_tests.php --watch
```

### Test Output Formats

```bash
# Console (default)
php tests/run_tests.php

# JSON
php tests/run_tests.php --format=json

# HTML
php tests/run_tests.php --format=html

# JUnit XML (for CI/CD)
php tests/run_tests.php --format=junit

# Markdown
php tests/run_tests.php --format=markdown
```

## Test Data Generation

### Generate Test Data

Generate production-like test data for all tables:

```bash
php tests/generate_test_data.php --generate
```

### Generate Specific Count

Generate 100 records per table:

```bash
php tests/generate_test_data.php --generate --count=100
```

### Generate for Specific Tables

```bash
php tests/generate_test_data.php --generate --tables=users,orders
```

### Cleanup Test Data

Remove all test data:

```bash
php tests/generate_test_data.php --cleanup
```

### Regenerate Test Data

Clean and regenerate test data:

```bash
php tests/generate_test_data.php --regenerate
```

## Writing Tests

### Test File Structure

Test files must be named `test_*.php` and placed in the appropriate directory:

- `tests/unit/test_*.php` - Unit tests
- `tests/integration/test_*.php` - Integration tests
- `tests/functional/test_*.php` - Functional tests
- `tests/performance/benchmark_*.php` - Performance tests

### Basic Test Example

```php
<?php
/**
 * Test Example
 */
require_once __DIR__ . '/../bootstrap.php';

$GLOBALS['test_count'] = 0;
$GLOBALS['test_passed'] = 0;

function run_test($name, $callback) {
    $GLOBALS['test_count']++;
    try {
        $callback();
        $GLOBALS['test_passed']++;
        echo "  ✓ {$name}\n";
    } catch (Exception $e) {
        echo "  ✗ {$name}: " . $e->getMessage() . "\n";
    }
}

// Test 1: Basic assertion
run_test('Test basic assertion', function() {
    assert_true(true, 'Should be true');
});

// Test 2: Equality check
run_test('Test equality', function() {
    assert_equals(2, 1 + 1, 'Math should work');
});

// Test 3: Database operation
run_test('Test database operation', function() {
    $conn = get_test_db_connection();
    assert_not_null($conn, 'Database connection should exist');
});
```

### Using Fixtures

```php
// Load YAML fixture
$fixture = load_fixture(__DIR__ . '/../fixtures/yaml/sample_data.yaml');

// Use factory
$user = UserFactory::create();

// Use builder
$order = OrderBuilder::new()
    ->withUser($user)
    ->withItems($items)
    ->build();
```

### Using Mocks

```php
// Create mock
$mock = create_mock('ExternalService');
$mock->expects('sendEmail')->returns(true);

// Create stub
$stub = create_stub('success');

// Create spy
$spy = create_spy($realObject);
```

## Test Types

### Unit Tests

Test individual functions in isolation:

```php
run_test('Test function X', function() {
    $result = component_function_x('input');
    assert_equals('expected', $result);
});
```

### Integration Tests

Test complete workflows:

```php
run_test('Test complete workflow', function() {
    // Step 1: Create
    $item = create_item($data);
    
    // Step 2: Update
    update_item($item['id'], $newData);
    
    // Step 3: Verify
    $updated = get_item($item['id']);
    assert_equals($newData['field'], $updated['field']);
});
```

### Functional Tests

Test user-facing features:

```php
run_test('Test admin page', function() {
    // Simulate HTTP request
    $response = simulate_get_request('/admin/page.php');
    assert_contains('Expected Content', $response);
});
```

### Performance Tests

Benchmark critical functions:

```php
run_test('Benchmark function', function() {
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        expensive_function();
    }
    $duration = microtime(true) - $start;
    assert_true($duration < 1.0, 'Should complete in under 1 second');
});
```

## Test Utilities

### Assertions

- `assert_true($condition, $message)` - Assert condition is true
- `assert_false($condition, $message)` - Assert condition is false
- `assert_equals($expected, $actual, $message)` - Assert values are equal
- `assert_not_equals($expected, $actual, $message)` - Assert values are not equal
- `assert_not_null($value, $message)` - Assert value is not null
- `assert_null($value, $message)` - Assert value is null
- `assert_array_has_key($key, $array, $message)` - Assert array has key
- `assert_instance_of($class, $object, $message)` - Assert object is instance of class
- `assert_contains($needle, $haystack, $message)` - Assert string contains substring

### Database Utilities

- `get_test_db_connection()` - Get isolated test database connection
- `create_test_database()` - Create new test database
- `cleanup_test_database()` - Cleanup test database

### Fixture Utilities

- `load_fixture($file)` - Load fixture from YAML or JSON file
- `load_yaml_fixture($file)` - Load YAML fixture
- `load_json_fixture($file)` - Load JSON fixture

### Mock Utilities

- `create_mock($className)` - Create mock object
- `create_stub($returnValue)` - Create stub with return value
- `create_spy($object)` - Create spy that tracks calls

## Debugging

### Debug Mode

Run tests with verbose output:

```bash
php tests/run_tests.php --verbose
```

### Interactive Debugger

Use Xdebug for interactive debugging:

1. Set breakpoints in test files
2. Run tests with Xdebug enabled
3. Step through execution
4. Inspect variables

### Test Replay

Replay failed tests:

```php
// Record test execution
record_test_execution('test_name');

// Replay
replay_test_execution('test_name');
```

## Analytics

### View Test Analytics

```bash
php tests/analytics/dashboard.php
```

### Execution Trends

Track test execution over time:

```bash
php tests/analytics/trends.php
```

### Failure Patterns

Analyze failure patterns:

```bash
php tests/analytics/failures.php
```

## Security Testing

### Run Security Tests

```bash
php tests/security/scanner.php
```

### SQL Injection Tests

```php
run_test('Test SQL injection prevention', function() {
    $input = "'; DROP TABLE users; --";
    $result = query_with_input($input);
    // Should not execute malicious SQL
    assert_not_contains('error', $result);
});
```

### XSS Tests

```php
run_test('Test XSS prevention', function() {
    $input = '<script>alert("XSS")</script>';
    $output = sanitize_output($input);
    assert_not_contains('<script>', $output);
});
```

## Performance Testing

### Run Benchmarks

```bash
php tests/performance/benchmark_functions.php
```

### Performance Thresholds

Define performance thresholds in test:

```php
run_test('Performance test', function() {
    $start = microtime(true);
    perform_operation();
    $duration = microtime(true) - $start;
    
    // Fail if exceeds threshold
    assert_true($duration < 0.5, 'Should complete in under 500ms');
});
```

## Troubleshooting

### Common Issues

#### Database Connection Failed

- Check database credentials in config.php
- Verify database server is running
- Check database permissions

#### Tests Not Found

- Ensure test files are named `test_*.php`
- Check test files are in correct directories
- Verify auto-discovery is working

#### Test Data Generation Fails

- Install Faker library: `composer require fakerphp/faker`
- Check database connection
- Verify table structure

#### Parallel Execution Issues

- Ensure sufficient database connections
- Check process limits
- Verify database isolation

### Getting Help

1. Check this documentation
2. Review test examples
3. Check component README.md
4. Review error messages carefully
5. Enable verbose mode: `--verbose`

## Best Practices

1. **Write Clear Tests**: Test names should describe what they test
2. **Use Fixtures**: Don't hardcode test data, use fixtures
3. **Isolate Tests**: Each test should be independent
4. **Clean Up**: Always clean up test data after tests
5. **Test Edge Cases**: Test boundary conditions and error cases
6. **Keep Tests Fast**: Optimize slow tests
7. **Maintain Tests**: Update tests when code changes
8. **Document Complex Tests**: Add comments for complex test logic

## Test Coverage

Target: **80%+ function coverage**

Check coverage:

```bash
php tests/run_tests.php --coverage
```

## Continuous Integration

### GitHub Actions Example

```yaml
- name: Run Tests
  run: php tests/run_tests.php --format=junit > test-results.xml

- name: Upload Results
  uses: actions/upload-artifact@v2
  with:
    name: test-results
    path: test-results.xml
```

## Additional Resources

- Component README.md
- Component API Documentation
- Test Examples in `tests/unit/`
- Fixture Examples in `tests/fixtures/`

---

**Last Updated**: {DATE}
**Component Version**: {VERSION}

