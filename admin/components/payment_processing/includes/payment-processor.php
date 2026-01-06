<?php
/**
 * Payment Processing Component - Main Include
 * Loads all core functionality for easy integration
 */

// Load config
require_once __DIR__ . '/config.php';

// Load core files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/gateway-manager.php';
require_once __DIR__ . '/../core/encryption.php';
require_once __DIR__ . '/../core/transaction-processor.php';
require_once __DIR__ . '/../core/refund-processor.php';
require_once __DIR__ . '/../core/subscription-manager.php';
require_once __DIR__ . '/../core/webhook-handler.php';
require_once __DIR__ . '/../core/audit-logger.php';
require_once __DIR__ . '/../core/fraud-detection.php';
require_once __DIR__ . '/../core/payment-method-rules.php';
require_once __DIR__ . '/../core/payment-plans.php';
require_once __DIR__ . '/../core/approval-workflows.php';
require_once __DIR__ . '/../core/automation-rules.php';
require_once __DIR__ . '/../core/custom-statuses.php';
require_once __DIR__ . '/../core/report-builder.php';
require_once __DIR__ . '/../core/tax-reporting.php';
require_once __DIR__ . '/../core/bank-reconciliation.php';
require_once __DIR__ . '/../core/outbound-webhooks.php';
require_once __DIR__ . '/../core/notification-templates.php';
require_once __DIR__ . '/../core/admin-alerts.php';

