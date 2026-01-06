<?php
/**
 * Product Options Component - Config Loader
 * Loads component configuration
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// Load core functions
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/datatypes.php';
require_once __DIR__ . '/../core/functions.php';

