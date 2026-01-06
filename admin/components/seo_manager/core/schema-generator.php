<?php
/**
 * SEO Manager Component - Schema Generator
 * Generates Schema.org structured data
 */

require_once __DIR__ . '/database.php';

/**
 * Generate Article schema
 * @param array $articleData Article data
 * @return array Schema JSON
 */
function seo_manager_generate_article_schema($articleData) {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $articleData['title'] ?? '',
        'description' => $articleData['description'] ?? '',
        'author' => [
            '@type' => 'Person',
            'name' => $articleData['author'] ?? ''
        ],
        'datePublished' => $articleData['date_published'] ?? '',
        'dateModified' => $articleData['date_modified'] ?? ''
    ];
}

/**
 * Generate Product schema
 * @param array $productData Product data
 * @return array Schema JSON
 */
function seo_manager_generate_product_schema($productData) {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $productData['name'] ?? '',
        'description' => $productData['description'] ?? '',
        'price' => $productData['price'] ?? '',
        'currency' => $productData['currency'] ?? 'USD'
    ];
}

/**
 * Save schema markup for a page
 * @param int $pageId Page ID
 * @param string $schemaType Schema type
 * @param array $schemaJson Schema JSON data
 * @return int|false Schema ID or false
 */
function seo_manager_save_schema_markup($pageId, $schemaType, $schemaJson) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = seo_manager_get_table_name('schema_markup');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (page_id, schema_type, schema_json, is_active) VALUES (?, ?, ?, 1)");
        $schemaJsonStr = json_encode($schemaJson);
        $stmt->bind_param("iss", $pageId, $schemaType, $schemaJsonStr);
        $stmt->execute();
        $schemaId = $conn->insert_id;
        $stmt->close();
        return $schemaId;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error saving schema markup: " . $e->getMessage());
        return false;
    }
}

