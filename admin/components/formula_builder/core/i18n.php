<?php
/**
 * Formula Builder Component - Internationalization
 * Translation and language management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Translate string
 * @param string $key Translation key
 * @param string $language Language code (default: en)
 * @param array $params Parameters for placeholder replacement
 * @return string Translated string
 */
function formula_builder_translate($key, $language = 'en', $params = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return $key; // Return key if translation unavailable
    }
    
    try {
        $tableName = formula_builder_get_table_name('translations');
        $stmt = $conn->prepare("SELECT translation_text FROM {$tableName} WHERE translation_key = ? AND language_code = ? LIMIT 1");
        $stmt->bind_param("ss", $key, $language);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $translation = $row ? $row['translation_text'] : $key;
        
        // Replace placeholders
        if (!empty($params)) {
            foreach ($params as $paramKey => $paramValue) {
                $translation = str_replace('{' . $paramKey . '}', $paramValue, $translation);
            }
        }
        
        return $translation;
    } catch (Exception $e) {
        error_log("Formula Builder: Error translating: " . $e->getMessage());
        return $key;
    }
}

/**
 * Get available languages
 * @return array Languages
 */
function formula_builder_get_available_languages() {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['en' => 'English'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('translations');
        $result = $conn->query("SELECT DISTINCT language_code FROM {$tableName} ORDER BY language_code");
        
        $languages = ['en' => 'English']; // Default
        
        while ($row = $result->fetch_assoc()) {
            $code = $row['language_code'];
            $languages[$code] = formula_builder_get_language_name($code);
        }
        
        return $languages;
    } catch (Exception $e) {
        return ['en' => 'English'];
    }
}

/**
 * Get language name
 * @param string $code Language code
 * @return string Language name
 */
function formula_builder_get_language_name($code) {
    $names = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ar' => 'Arabic'
    ];
    
    return $names[$code] ?? ucfirst($code);
}

/**
 * Set user language preference
 * @param int $userId User ID
 * @param string $language Language code
 * @return array Result
 */
function formula_builder_set_user_language($userId, $language) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('user_preferences');
        $preferences = ['language' => $language];
        $preferencesJson = json_encode($preferences);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (user_id, preferences) VALUES (?, ?) ON DUPLICATE KEY UPDATE preferences = ?, updated_at = NOW()");
        $stmt->bind_param("iss", $userId, $preferencesJson, $preferencesJson);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error setting user language: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get user language
 * @param int $userId User ID
 * @return string Language code
 */
function formula_builder_get_user_language($userId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return 'en';
    }
    
    try {
        $tableName = formula_builder_get_table_name('user_preferences');
        $stmt = $conn->prepare("SELECT preferences FROM {$tableName} WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            $preferences = json_decode($row['preferences'], true);
            return $preferences['language'] ?? 'en';
        }
        
        return 'en';
    } catch (Exception $e) {
        return 'en';
    }
}

/**
 * Check if language is RTL
 * @param string $language Language code
 * @return bool True if RTL
 */
function formula_builder_is_rtl($language) {
    $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
    return in_array($language, $rtlLanguages);
}

