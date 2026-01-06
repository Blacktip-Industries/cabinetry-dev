<?php
/**
 * Fixture Loader
 * Loads YAML and JSON fixtures, manages factories and builders
 */

/**
 * Load fixture from YAML file
 */
function load_yaml_fixture($file) {
    if (!file_exists($file)) {
        throw new Exception("Fixture file not found: {$file}");
    }
    
    // Check if yaml extension is available
    if (function_exists('yaml_parse_file')) {
        return yaml_parse_file($file);
    } elseif (function_exists('yaml_parse')) {
        return yaml_parse(file_get_contents($file));
    } else {
        // Fallback: parse as JSON if YAML not available
        // Note: This is a basic fallback, proper YAML parsing requires symfony/yaml or similar
        return json_decode(file_get_contents($file), true);
    }
}

/**
 * Load fixture from JSON file
 */
function load_json_fixture($file) {
    if (!file_exists($file)) {
        throw new Exception("Fixture file not found: {$file}");
    }
    
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON fixture: " . json_last_error_msg());
    }
    
    return $data;
}

/**
 * Load fixture (auto-detect format)
 */
function load_fixture($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    if ($ext === 'yaml' || $ext === 'yml') {
        return load_yaml_fixture($file);
    } elseif ($ext === 'json') {
        return load_json_fixture($file);
    } else {
        throw new Exception("Unknown fixture format: {$ext}");
    }
}

/**
 * Get fixture directory
 */
function get_fixture_directory() {
    return __DIR__;
}

