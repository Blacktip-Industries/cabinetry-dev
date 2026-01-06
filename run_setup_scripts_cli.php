<?php
/**
 * Run Setup Scripts and Cleanup (CLI - Direct Database Operations)
 * Executes setup operations directly without web layout dependencies
 * 
 * Usage: php run_setup_scripts_cli.php
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();
$error = '';
$success = '';
$actions = [];
$deletedFiles = [];

if ($conn === null) {
    $error = 'Database connection failed';
    echo "ERROR: {$error}\n";
    exit(1);
}

echo "\n=== Setup Scripts Runner (CLI) ===\n\n";

// Ensure tables exist
createSettingsParametersTable($conn);
createSettingsParametersConfigsTable($conn);
createTimezonesTable($conn);

// ============================================
// 1. Seed Timezones Table
// ============================================
echo "→ Step 1: Seeding timezones table...\n";

$timezones = [
    ['timezone_identifier' => 'Australia/Brisbane', 'city_name' => 'Brisbane', 'country' => 'Australia', 'utc_offset' => 10.0, 'display_label' => 'Brisbane, Australia (AEST - UTC+10)', 'is_active' => 1, 'display_order' => 1],
    ['timezone_identifier' => 'Australia/Sydney', 'city_name' => 'Sydney', 'country' => 'Australia', 'utc_offset' => 10.0, 'display_label' => 'Sydney, Australia (AEDT/AEST - UTC+10/+11)', 'is_active' => 1, 'display_order' => 2],
    ['timezone_identifier' => 'Australia/Melbourne', 'city_name' => 'Melbourne', 'country' => 'Australia', 'utc_offset' => 10.0, 'display_label' => 'Melbourne, Australia (AEDT/AEST - UTC+10/+11)', 'is_active' => 1, 'display_order' => 3],
    ['timezone_identifier' => 'Australia/Adelaide', 'city_name' => 'Adelaide', 'country' => 'Australia', 'utc_offset' => 9.5, 'display_label' => 'Adelaide, Australia (ACDT/ACST - UTC+9.5/+10.5)', 'is_active' => 1, 'display_order' => 4],
    ['timezone_identifier' => 'Australia/Perth', 'city_name' => 'Perth', 'country' => 'Australia', 'utc_offset' => 8.0, 'display_label' => 'Perth, Australia (AWST - UTC+8)', 'is_active' => 1, 'display_order' => 5],
    ['timezone_identifier' => 'Australia/Darwin', 'city_name' => 'Darwin', 'country' => 'Australia', 'utc_offset' => 9.5, 'display_label' => 'Darwin, Australia (ACST - UTC+9.5)', 'is_active' => 1, 'display_order' => 6],
    ['timezone_identifier' => 'Australia/Hobart', 'city_name' => 'Hobart', 'country' => 'Australia', 'utc_offset' => 10.0, 'display_label' => 'Hobart, Australia (AEDT/AEST - UTC+10/+11)', 'is_active' => 1, 'display_order' => 7],
    ['timezone_identifier' => 'Pacific/Auckland', 'city_name' => 'Auckland', 'country' => 'New Zealand', 'utc_offset' => 12.0, 'display_label' => 'Auckland, New Zealand (NZDT/NZST - UTC+12/+13)', 'is_active' => 1, 'display_order' => 10],
    ['timezone_identifier' => 'Pacific/Wellington', 'city_name' => 'Wellington', 'country' => 'New Zealand', 'utc_offset' => 12.0, 'display_label' => 'Wellington, New Zealand (NZDT/NZST - UTC+12/+13)', 'is_active' => 1, 'display_order' => 11],
    ['timezone_identifier' => 'UTC', 'city_name' => 'UTC', 'country' => 'Global', 'utc_offset' => 0.0, 'display_label' => 'UTC (Coordinated Universal Time)', 'is_active' => 1, 'display_order' => 50],
    ['timezone_identifier' => 'America/New_York', 'city_name' => 'New York', 'country' => 'United States', 'utc_offset' => -5.0, 'display_label' => 'New York, USA (EST/EDT - UTC-5/-4)', 'is_active' => 1, 'display_order' => 100],
    ['timezone_identifier' => 'America/Chicago', 'city_name' => 'Chicago', 'country' => 'United States', 'utc_offset' => -6.0, 'display_label' => 'Chicago, USA (CST/CDT - UTC-6/-5)', 'is_active' => 1, 'display_order' => 101],
    ['timezone_identifier' => 'America/Denver', 'city_name' => 'Denver', 'country' => 'United States', 'utc_offset' => -7.0, 'display_label' => 'Denver, USA (MST/MDT - UTC-7/-6)', 'is_active' => 1, 'display_order' => 102],
    ['timezone_identifier' => 'America/Los_Angeles', 'city_name' => 'Los Angeles', 'country' => 'United States', 'utc_offset' => -8.0, 'display_label' => 'Los Angeles, USA (PST/PDT - UTC-8/-7)', 'is_active' => 1, 'display_order' => 103],
    ['timezone_identifier' => 'Europe/London', 'city_name' => 'London', 'country' => 'United Kingdom', 'utc_offset' => 0.0, 'display_label' => 'London, UK (GMT/BST - UTC+0/+1)', 'is_active' => 1, 'display_order' => 200],
    ['timezone_identifier' => 'Asia/Tokyo', 'city_name' => 'Tokyo', 'country' => 'Japan', 'utc_offset' => 9.0, 'display_label' => 'Tokyo, Japan (JST - UTC+9)', 'is_active' => 1, 'display_order' => 300],
    ['timezone_identifier' => 'Asia/Seoul', 'city_name' => 'Seoul', 'country' => 'South Korea', 'utc_offset' => 9.0, 'display_label' => 'Seoul, South Korea (KST - UTC+9)', 'is_active' => 1, 'display_order' => 301],
    ['timezone_identifier' => 'Asia/Singapore', 'city_name' => 'Singapore', 'country' => 'Singapore', 'utc_offset' => 8.0, 'display_label' => 'Singapore (SGT - UTC+8)', 'is_active' => 1, 'display_order' => 304],
];

$inserted = 0;
$updated = 0;

foreach ($timezones as $tz) {
    $checkStmt = $conn->prepare("SELECT id FROM timezones WHERE timezone_identifier = ?");
    $checkStmt->bind_param("s", $tz['timezone_identifier']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        $updateStmt = $conn->prepare("UPDATE timezones SET city_name = ?, country = ?, utc_offset = ?, display_label = ?, is_active = ?, display_order = ? WHERE timezone_identifier = ?");
        $updateStmt->bind_param("ssdsiss", 
            $tz['city_name'], 
            $tz['country'], 
            $tz['utc_offset'], 
            $tz['display_label'], 
            $tz['is_active'], 
            $tz['display_order'],
            $tz['timezone_identifier']
        );
        if ($updateStmt->execute()) {
            $updated++;
        }
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO timezones (timezone_identifier, city_name, country, utc_offset, display_label, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("sssdssi",
            $tz['timezone_identifier'],
            $tz['city_name'],
            $tz['country'],
            $tz['utc_offset'],
            $tz['display_label'],
            $tz['is_active'],
            $tz['display_order']
        );
        if ($insertStmt->execute()) {
            $inserted++;
        }
        $insertStmt->close();
    }
}

echo "✓  Timezones table seeded: {$inserted} inserted, {$updated} updated\n\n";

// ============================================
// 2. Add/Update System Timezone Parameters
// ============================================
echo "→ Step 2: Adding/updating system timezone parameters...\n";

$parameters = [
    [
        'section' => 'System',
        'parameter_name' => '--system-timezone',
        'value' => 'Australia/Brisbane',
        'description' => 'System timezone used for timestamps, backups, and date/time displays throughout the application'
    ],
    [
        'section' => 'System',
        'parameter_name' => '--system-date-format',
        'value' => 'Y-m-d',
        'description' => 'Date format used throughout the system (PHP date format: Y=year, m=month, d=day)'
    ],
    [
        'section' => 'System',
        'parameter_name' => '--system-time-format',
        'value' => 'H:i:s',
        'description' => 'Time format used throughout the system (PHP time format: H=24-hour, i=minutes, s=seconds)'
    ],
    [
        'section' => 'System',
        'parameter_name' => '--system-datetime-format',
        'value' => 'Y-m-d H:i:s',
        'description' => 'Combined date and time format used throughout the system'
    ]
];

foreach ($parameters as $param) {
    $checkStmt = $conn->prepare("SELECT id, value FROM settings_parameters WHERE section = ? AND parameter_name = ?");
    $checkStmt->bind_param("ss", $param['section'], $param['parameter_name']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        $paramId = $existing['id'];
    } else {
        $upsertResult = upsertParameter($param['section'], $param['parameter_name'], $param['value'], $param['description']);
        if ($upsertResult) {
            $getIdStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
            $getIdStmt->bind_param("ss", $param['section'], $param['parameter_name']);
            $getIdStmt->execute();
            $idResult = $getIdStmt->get_result();
            $paramRow = $idResult->fetch_assoc();
            $paramId = $paramRow['id'] ?? null;
            $getIdStmt->close();
        } else {
            $paramId = null;
        }
    }
    
    if ($paramId) {
        if ($param['parameter_name'] === '--system-timezone') {
            // Load timezones from database for dropdown
            $timezoneOptions = [];
            $tzStmt = $conn->prepare("SELECT timezone_identifier, display_label FROM timezones WHERE is_active = 1 ORDER BY display_order ASC, country ASC, city_name ASC");
            $tzStmt->execute();
            $tzResult = $tzStmt->get_result();
            
            while ($tz = $tzResult->fetch_assoc()) {
                $timezoneOptions[] = [
                    'value' => $tz['timezone_identifier'],
                    'label' => $tz['display_label']
                ];
            }
            $tzStmt->close();
            
            $optionsJson = json_encode($timezoneOptions);
            $inputType = 'dropdown';
            $helpText = 'Select your local timezone. This affects all timestamps, backups, and date/time displays in the system.';
            
            $configStmt = $conn->prepare("INSERT INTO settings_parameters_configs (parameter_id, input_type, options_json, help_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE input_type = ?, options_json = ?, help_text = ?");
            $configStmt->bind_param("issssss", $paramId, $inputType, $optionsJson, $helpText, $inputType, $optionsJson, $helpText);
            $configStmt->execute();
            $configStmt->close();
        } else {
            $inputType = 'text';
            $placeholder = $param['value'];
            $helpText = 'Use PHP date format syntax. Examples: Y-m-d (2025-12-27), d/m/Y (27/12/2025), H:i:s (21:04:02)';
            
            $configStmt = $conn->prepare("INSERT INTO settings_parameters_configs (parameter_id, input_type, placeholder, help_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE input_type = ?, placeholder = ?, help_text = ?");
            $configStmt->bind_param("issssss", $paramId, $inputType, $placeholder, $helpText, $inputType, $placeholder, $helpText);
            $configStmt->execute();
            $configStmt->close();
        }
    }
}

echo "✓  System timezone parameters added/updated\n\n";

// ============================================
// 3. Cleanup: Delete setup scripts
// ============================================
echo "→ Step 3: Cleaning up setup scripts...\n";

$scriptsToDelete = [
    'admin/setup/add_timezones_table.php',
    'admin/setup/add_system_timezone_parameters.php',
    'admin/setup/update_timezone_parameter_dropdown.php'
];

foreach ($scriptsToDelete as $script) {
    $scriptPath = __DIR__ . '/' . $script;
    if (file_exists($scriptPath)) {
        if (unlink($scriptPath)) {
            $deletedFiles[] = $script;
            echo "  ✓ Deleted: {$script}\n";
        } else {
            echo "  ⚠ Could not delete: {$script}\n";
        }
    } else {
        echo "  ⊘ Not found (already deleted?): {$script}\n";
    }
}

echo "\n=== Summary ===\n\n";
echo "✓  Timezones table: {$inserted} inserted, {$updated} updated\n";
echo "✓  System parameters: Added/updated\n";
echo "✓  Deleted files: " . count($deletedFiles) . "\n";
echo "\nDone!\n\n";

exit(0);

