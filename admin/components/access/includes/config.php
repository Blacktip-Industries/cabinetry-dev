<?php
/**
 * Access Component - Config Loader
 * Loads component configuration
 */

// Load component config
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    // Fallback to example config
    require_once __DIR__ . '/../config.example.php';
}

// Load core functions
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/authentication.php';
require_once __DIR__ . '/../core/permissions.php';
require_once __DIR__ . '/../core/registration.php';
require_once __DIR__ . '/../core/account-types.php';
require_once __DIR__ . '/../core/hooks.php';
require_once __DIR__ . '/../core/workflows.php';
require_once __DIR__ . '/../core/field-types.php';
require_once __DIR__ . '/../core/email.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/messaging.php';
require_once __DIR__ . '/../core/chat.php';
require_once __DIR__ . '/../core/notifications.php';

