<?php
/**
 * {COMPONENT_NAME} Component - Test Data Generator
 * Generates production-like test data using Faker library
 * 
 * Usage:
 *   php generate_test_data.php --generate          # Generate test data
 *   php generate_test_data.php --cleanup          # Remove test data
 *   php generate_test_data.php --regenerate       # Clean and regenerate
 *   php generate_test_data.php --count=100         # Generate 100 records per table
 *   php generate_test_data.php --tables=users,orders  # Generate for specific tables
 */

require_once __DIR__ . '/bootstrap.php';

// Check if Faker is available
if (!class_exists('Faker\Factory')) {
    echo "ERROR: Faker library not found. Install with: composer require fakerphp/faker\n";
    exit(1);
}

use Faker\Factory;

// Parse command line arguments
$options = parse_generator_args($argv ?? []);

$componentName = get_component_name();
echo "{$componentName} Component - Test Data Generator\n";
echo str_repeat("=", 60) . "\n\n";

// Initialize Faker
$faker = Factory::create();

// Get component tables
$conn = get_test_db_connection();
$tables = get_component_tables($conn, $componentName);

if (empty($tables)) {
    echo "No component tables found.\n";
    exit(1);
}

// Filter tables if specified
if (!empty($options['tables'])) {
    $requestedTables = explode(',', $options['tables']);
    $tables = array_intersect($tables, $requestedTables);
}

// Execute action
if ($options['cleanup']) {
    cleanup_test_data_for_tables($conn, $tables);
    echo "Test data cleaned up.\n";
} elseif ($options['regenerate']) {
    cleanup_test_data_for_tables($conn, $tables);
    echo "Generating test data...\n";
    generate_test_data_for_tables($conn, $tables, $faker, $options['count']);
    echo "Test data generated.\n";
} else {
    // Default: generate
    echo "Generating test data...\n";
    generate_test_data_for_tables($conn, $tables, $faker, $options['count']);
    echo "Test data generated.\n";
}

// ============================================================================
// FUNCTIONS
// ============================================================================

/**
 * Parse generator arguments
 */
function parse_generator_args($argv) {
    $options = [
        'generate' => false,
        'cleanup' => false,
        'regenerate' => false,
        'count' => 10,
        'tables' => null
    ];
    
    foreach ($argv as $arg) {
        if ($arg === '--generate' || $arg === '-g') {
            $options['generate'] = true;
        } elseif ($arg === '--cleanup' || $arg === '-c') {
            $options['cleanup'] = true;
        } elseif ($arg === '--regenerate' || $arg === '-r') {
            $options['regenerate'] = true;
        } elseif (strpos($arg, '--count=') === 0) {
            $options['count'] = (int)substr($arg, 8);
        } elseif (strpos($arg, '--tables=') === 0) {
            $options['tables'] = substr($arg, 9);
        }
    }
    
    // Default to generate if no action specified
    if (!$options['cleanup'] && !$options['regenerate']) {
        $options['generate'] = true;
    }
    
    return $options;
}

/**
 * Generate test data for tables
 */
function generate_test_data_for_tables($conn, $tables, $faker, $count) {
    foreach ($tables as $table) {
        echo "Generating data for {$table}...\n";
        generate_test_data_for_table($conn, $table, $faker, $count);
    }
}

/**
 * Generate test data for a single table
 */
function generate_test_data_for_table($conn, $table, $faker, $count) {
    // Get table structure
    $columns = get_table_columns($conn, $table);
    
    // Generate records
    for ($i = 0; $i < $count; $i++) {
        $data = generate_record_data($columns, $faker, $table);
        insert_test_record($conn, $table, $data);
    }
    
    echo "  Generated {$count} records for {$table}\n";
}

/**
 * Get table columns
 */
function get_table_columns($conn, $table) {
    $columns = [];
    $result = $conn->query("DESCRIBE `{$table}`");
    
    while ($row = $result->fetch_assoc()) {
        $columns[] = [
            'name' => $row['Field'],
            'type' => $row['Type'],
            'null' => $row['Null'] === 'YES',
            'key' => $row['Key'],
            'default' => $row['Default'],
            'extra' => $row['Extra']
        ];
    }
    
    return $columns;
}

/**
 * Generate record data based on column types
 */
function generate_record_data($columns, $faker, $table) {
    $data = [];
    
    foreach ($columns as $column) {
        $name = $column['name'];
        
        // Skip auto-increment and timestamps
        if ($column['extra'] === 'auto_increment') {
            continue;
        }
        
        if (in_array($name, ['created_at', 'updated_at', 'deleted_at'])) {
            $data[$name] = date('Y-m-d H:i:s');
            continue;
        }
        
        // Generate based on column name patterns
        $value = generate_column_value($name, $column, $faker, $table);
        if ($value !== null) {
            $data[$name] = $value;
        }
    }
    
    return $data;
}

/**
 * Generate value for a column
 */
function generate_column_value($columnName, $column, $faker, $table) {
    $name = strtolower($columnName);
    
    // Email
    if (strpos($name, 'email') !== false) {
        return $faker->email();
    }
    
    // Name
    if (strpos($name, 'name') !== false || strpos($name, 'title') !== false) {
        if (strpos($name, 'first') !== false) {
            return $faker->firstName();
        } elseif (strpos($name, 'last') !== false) {
            return $faker->lastName();
        } else {
            return $faker->name();
        }
    }
    
    // Phone
    if (strpos($name, 'phone') !== false) {
        return $faker->phoneNumber();
    }
    
    // Address
    if (strpos($name, 'address') !== false) {
        return $faker->address();
    }
    
    // Text/Description
    if (strpos($name, 'description') !== false || strpos($name, 'text') !== false || strpos($name, 'content') !== false) {
        return $faker->text(200);
    }
    
    // URL
    if (strpos($name, 'url') !== false || strpos($name, 'link') !== false) {
        return $faker->url();
    }
    
    // Date
    if (strpos($name, 'date') !== false) {
        return $faker->date('Y-m-d');
    }
    
    // Boolean
    if (strpos($column['type'], 'tinyint(1)') !== false || strpos($name, 'is_') === 0 || strpos($name, 'has_') === 0) {
        return $faker->boolean() ? 1 : 0;
    }
    
    // Integer
    if (strpos($column['type'], 'int') !== false) {
        return $faker->numberBetween(1, 1000);
    }
    
    // Decimal/Float
    if (strpos($column['type'], 'decimal') !== false || strpos($column['type'], 'float') !== false) {
        return $faker->randomFloat(2, 0, 1000);
    }
    
    // String/Varchar
    if (strpos($column['type'], 'varchar') !== false || strpos($column['type'], 'text') !== false) {
        $length = 50;
        if (preg_match('/varchar\((\d+)\)/', $column['type'], $matches)) {
            $length = (int)$matches[1];
        }
        return $faker->text(min($length, 200));
    }
    
    // Default: null (let database handle it)
    return null;
}

/**
 * Insert test record
 */
function insert_test_record($conn, $table, $data) {
    if (empty($data)) {
        return;
    }
    
    $columns = array_keys($data);
    $values = array_values($data);
    $placeholders = str_repeat('?,', count($values) - 1) . '?';
    
    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES ({$placeholders})";
    $stmt = $conn->prepare($sql);
    
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();
}

/**
 * Cleanup test data for tables
 */
function cleanup_test_data_for_tables($conn, $tables) {
    foreach ($tables as $table) {
        // Delete records with 'Test' prefix or generated test data
        // This is component-specific and should be customized
        $conn->query("DELETE FROM `{$table}` WHERE 1=1"); // Simple cleanup - customize as needed
        echo "  Cleaned up {$table}\n";
    }
}

