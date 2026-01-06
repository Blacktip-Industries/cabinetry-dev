# Testing Template

This is a comprehensive testing template for components. Copy this entire directory to your component and customize it.

## Quick Start

1. **Copy template to your component:**
   ```bash
   cp -r _standards/TESTING_TEMPLATE/* admin/components/{your_component}/
   ```

2. **Customize placeholders:**
   - Replace `{COMPONENT_NAME}` with your component name (e.g., `Menu System`)
   - Replace `{component_name}` with your component name in lowercase with underscores (e.g., `menu_system`)

3. **Update bootstrap.php:**
   - Configure database connection function
   - Add component-specific core file includes
   - Customize cleanup functions

4. **Create your first test:**
   ```bash
   php tests/unit/test_functions.php
   ```

5. **Generate test data:**
   ```bash
   php tests/generate_test_data.php --generate
   ```

6. **Run all tests:**
   ```bash
   php tests/run_tests.php
   ```

## File Structure

```
TESTING_TEMPLATE/
├── verify.php                    # Installation verification script
├── tests/
│   ├── bootstrap.php            # Test environment setup
│   ├── run_tests.php            # Test suite runner
│   ├── generate_test_data.php  # Test data generator
│   ├── unit/                    # Unit tests (test_*.php)
│   ├── integration/             # Integration tests
│   ├── functional/              # Functional tests
│   ├── performance/             # Performance tests
│   ├── database/                # Database isolation utilities
│   ├── fixtures/                # Test fixtures
│   ├── mocks/                   # Mocking framework
│   ├── validation/              # Validation utilities
│   ├── watch/                   # Watch mode
│   ├── errors/                  # Error handling
│   ├── docs/                    # Documentation generator
│   ├── integrations/            # Integration utilities
│   ├── debug/                   # Debugging utilities
│   ├── analytics/               # Analytics
│   ├── security/                # Security testing
│   └── maintenance/             # Maintenance tools
└── docs/
    └── TESTING.md               # Testing documentation template
```

## Customization Guide

### 1. Update Component Name

Search and replace in all files:
- `{COMPONENT_NAME}` → Your Component Name
- `{component_name}` → your_component_name

### 2. Configure Database Connection

In `tests/bootstrap.php`, update:
```php
function get_main_db_connection() {
    return {component_name}_get_db_connection();
}
```

### 3. Add Required Tables

In `verify.php`, update:
```php
function get_required_tables($componentName) {
    return [
        $componentName . '_config',
        $componentName . '_parameters',
        // Add your component tables
    ];
}
```

### 4. Add Required Functions

In `verify.php`, update:
```php
function get_required_functions($componentName) {
    return [
        $componentName . '_get_db_connection',
        // Add your component functions
    ];
}
```

### 5. Customize Test Data Generator

In `tests/generate_test_data.php`, add component-specific data generation logic for your tables.

## Dependencies

### Required

- PHP 7.4+
- MySQL/MariaDB
- mysqli extension

### Optional (for full features)

- **Faker**: `composer require fakerphp/faker` (for test data generation)
- **Xdebug**: For code coverage and debugging
- **YAML extension**: For YAML fixture support (or use symfony/yaml)

## Features

- ✅ Auto-discovery of tests
- ✅ Parallel test execution
- ✅ Watch mode
- ✅ Multiple report formats
- ✅ Test data generation with Faker
- ✅ Comprehensive fixtures (YAML/JSON, factories, builders)
- ✅ Full mocking framework
- ✅ Database isolation
- ✅ Comprehensive error handling
- ✅ Analytics and insights
- ✅ Security testing
- ✅ Performance testing
- ✅ Maintenance tools

## Documentation

See `docs/TESTING.md` for comprehensive testing documentation.

## Support

For questions or issues:
1. Check `docs/TESTING.md`
2. Review test examples
3. Check component README.md
4. Review Component Creation Procedure

