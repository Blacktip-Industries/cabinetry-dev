<?php
/**
 * Access Component - Default Account Types
 * Creates default account types: Retail, Business, Trade
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'inserted' => int, 'errors' => array]
 */
function access_insert_default_account_types($conn) {
    $inserted = 0;
    $errors = [];
    
    // Define default account types
    $accountTypes = [
        [
            'name' => 'Retail',
            'slug' => 'retail',
            'description' => 'Retail customer account type for individual consumers',
            'requires_approval' => 0,
            'auto_approve' => 1,
            'special_requirements' => null,
            'registration_workflow' => null,
            'custom_validation_hook' => null,
            'is_active' => 1,
            'display_order' => 1,
            'icon' => 'person',
            'color' => '#4CAF50'
        ],
        [
            'name' => 'Business',
            'slug' => 'business',
            'description' => 'Business account type for companies and organizations with multiple users',
            'requires_approval' => 1,
            'auto_approve' => 0,
            'special_requirements' => json_encode(['business_registration_number' => 'required']),
            'registration_workflow' => null,
            'custom_validation_hook' => null,
            'is_active' => 1,
            'display_order' => 2,
            'icon' => 'business',
            'color' => '#2196F3'
        ],
        [
            'name' => 'Trade',
            'slug' => 'trade',
            'description' => 'Trade account type for trade professionals and contractors',
            'requires_approval' => 1,
            'auto_approve' => 0,
            'special_requirements' => json_encode(['trade_license_number' => 'required', 'abn' => 'required']),
            'registration_workflow' => null,
            'custom_validation_hook' => null,
            'is_active' => 1,
            'display_order' => 3,
            'icon' => 'build',
            'color' => '#FF9800'
        ]
    ];
    
    // Insert each account type
    foreach ($accountTypes as $type) {
        try {
            $stmt = $conn->prepare("INSERT INTO access_account_types (name, slug, description, requires_approval, auto_approve, special_requirements, registration_workflow, custom_validation_hook, is_active, display_order, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description), requires_approval = VALUES(requires_approval), auto_approve = VALUES(auto_approve), special_requirements = VALUES(special_requirements), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("sssiisssiiss",
                $type['name'],
                $type['slug'],
                $type['description'],
                $type['requires_approval'],
                $type['auto_approve'],
                $type['special_requirements'],
                $type['registration_workflow'],
                $type['custom_validation_hook'],
                $type['is_active'],
                $type['display_order'],
                $type['icon'],
                $type['color']
            );
            
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = "Failed to insert account type: " . $type['name'];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting account type {$type['name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

