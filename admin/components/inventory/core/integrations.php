<?php
/**
 * Inventory Component - Integration Functions
 * Commerce component detection and integration
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/items.php';
require_once __DIR__ . '/stock.php';

/**
 * Check if commerce component is available and integration is enabled
 * @return bool True if available and enabled
 */
function inventory_is_commerce_integration_active() {
    return inventory_is_commerce_available() && inventory_is_commerce_integration_enabled();
}

/**
 * Link inventory item to commerce product
 * @param int $itemId Inventory item ID
 * @param int $commerceProductId Commerce product ID
 * @param int|null $commerceVariantId Commerce variant ID
 * @return array Result with success status
 */
function inventory_link_to_commerce($itemId, $commerceProductId, $commerceVariantId = null) {
    if (!inventory_is_commerce_integration_active()) {
        return ['success' => false, 'error' => 'Commerce integration is not active'];
    }
    
    $item = inventory_get_item($itemId);
    if (!$item) {
        return ['success' => false, 'error' => 'Inventory item not found'];
    }
    
    return inventory_update_item($itemId, [
        'commerce_product_id' => $commerceProductId,
        'commerce_variant_id' => $commerceVariantId
    ]);
}

/**
 * Sync stock from commerce to inventory
 * @param int $commerceProductId Commerce product ID
 * @param int|null $commerceVariantId Commerce variant ID
 * @return array Result with success status
 */
function inventory_sync_from_commerce($commerceProductId, $commerceVariantId = null) {
    if (!inventory_is_commerce_integration_active()) {
        return ['success' => false, 'error' => 'Commerce integration is not active'];
    }
    
    // Find inventory item linked to commerce product
    $items = inventory_get_items(['commerce_product_id' => $commerceProductId]);
    if (empty($items)) {
        return ['success' => false, 'error' => 'No inventory item linked to commerce product'];
    }
    
    $item = $items[0];
    
    // Get commerce inventory
    if (function_exists('commerce_get_inventory')) {
        $commerceInventory = commerce_get_inventory($commerceProductId, $commerceVariantId);
        
        if (!empty($commerceInventory)) {
            foreach ($commerceInventory as $inv) {
                // Map commerce warehouse to inventory location
                $locationId = inventory_map_commerce_warehouse_to_location($inv['warehouse_id']);
                if ($locationId) {
                    $currentStock = inventory_get_stock($item['id'], $locationId);
                    $targetQuantity = $inv['quantity_available'];
                    
                    if ($currentStock) {
                        $quantityChange = $targetQuantity - $currentStock['quantity_available'];
                        if ($quantityChange != 0) {
                            inventory_update_stock($item['id'], $locationId, $quantityChange, 'adjustment', 'commerce_sync', $commerceProductId, 'Synced from commerce');
                        }
                    } else {
                        inventory_update_stock($item['id'], $locationId, $targetQuantity, 'in', 'commerce_sync', $commerceProductId, 'Initial sync from commerce');
                    }
                }
            }
        }
    }
    
    return ['success' => true];
}

/**
 * Sync stock from inventory to commerce
 * @param int $itemId Inventory item ID
 * @return array Result with success status
 */
function inventory_sync_to_commerce($itemId) {
    if (!inventory_is_commerce_integration_active()) {
        return ['success' => false, 'error' => 'Commerce integration is not active'];
    }
    
    $item = inventory_get_item($itemId);
    if (!$item || !$item['commerce_product_id']) {
        return ['success' => false, 'error' => 'Item not linked to commerce product'];
    }
    
    // Get inventory stock
    $stock = inventory_get_item_stock($itemId);
    
    if (function_exists('commerce_update_inventory')) {
        foreach ($stock as $s) {
            // Map inventory location to commerce warehouse
            $warehouseId = inventory_map_location_to_commerce_warehouse($s['location_id']);
            if ($warehouseId) {
                $currentCommerceStock = commerce_get_inventory($item['commerce_product_id'], $item['commerce_variant_id'], $warehouseId);
                $targetQuantity = $s['quantity_available'];
                
                if (!empty($currentCommerceStock)) {
                    $currentQty = $currentCommerceStock[0]['quantity_available'];
                    $quantityChange = $targetQuantity - $currentQty;
                    if ($quantityChange != 0) {
                        commerce_update_inventory($item['commerce_product_id'], $item['commerce_variant_id'], $warehouseId, $quantityChange, 'adjustment', 'inventory_sync', $itemId, 'Synced from inventory');
                    }
                } else {
                    commerce_update_inventory($item['commerce_product_id'], $item['commerce_variant_id'], $warehouseId, $targetQuantity, 'in', 'inventory_sync', $itemId, 'Initial sync from inventory');
                }
            }
        }
    }
    
    return ['success' => true];
}

/**
 * Map commerce warehouse to inventory location
 * @param int $warehouseId Commerce warehouse ID
 * @return int|null Inventory location ID or null
 */
function inventory_map_commerce_warehouse_to_location($warehouseId) {
    // Try to find location with matching code or name
    if (function_exists('commerce_get_warehouse')) {
        // This would require a commerce function to get warehouse by ID
        // For now, return default location
        $defaultLocation = inventory_get_default_location();
        return $defaultLocation ? $defaultLocation['id'] : null;
    }
    
    return null;
}

/**
 * Map inventory location to commerce warehouse
 * @param int $locationId Inventory location ID
 * @return int|null Commerce warehouse ID or null
 */
function inventory_map_location_to_commerce_warehouse($locationId) {
    // Try to find warehouse with matching code or name
    if (function_exists('commerce_get_warehouses')) {
        $location = inventory_get_location($locationId);
        if ($location) {
            // This would require matching logic
            // For now, return default warehouse
            if (function_exists('commerce_get_default_warehouse_id')) {
                return commerce_get_default_warehouse_id();
            }
        }
    }
    
    return null;
}

/**
 * Create inventory item from commerce product
 * @param int $commerceProductId Commerce product ID
 * @param int|null $commerceVariantId Commerce variant ID
 * @return array Result with success status and item ID
 */
function inventory_create_from_commerce_product($commerceProductId, $commerceVariantId = null) {
    if (!inventory_is_commerce_integration_active()) {
        return ['success' => false, 'error' => 'Commerce integration is not active'];
    }
    
    // Get commerce product data
    if (function_exists('commerce_get_product')) {
        $product = commerce_get_product($commerceProductId);
        if (!$product) {
            return ['success' => false, 'error' => 'Commerce product not found'];
        }
        
        $itemCode = 'COM-' . $product['id'];
        if ($commerceVariantId) {
            $itemCode .= '-V' . $commerceVariantId;
        }
        
        $itemData = [
            'item_code' => $itemCode,
            'item_name' => $product['product_name'],
            'description' => $product['description'],
            'sku' => $product['sku'],
            'commerce_product_id' => $commerceProductId,
            'commerce_variant_id' => $commerceVariantId
        ];
        
        return inventory_create_item($itemData);
    }
    
    return ['success' => false, 'error' => 'Commerce functions not available'];
}

