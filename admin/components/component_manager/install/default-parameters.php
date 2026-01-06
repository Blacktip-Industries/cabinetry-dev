<?php
/**
 * Component Manager - Default Parameters
 * Default component parameters
 */

/**
 * Get default parameters
 * @return array Array of default parameters
 */
function component_manager_get_default_parameters() {
    return [
        ['section' => 'General', 'parameter_name' => 'auto_check_updates', 'value' => 'yes', 'description' => 'Enable automatic update checking'],
        ['section' => 'General', 'parameter_name' => 'auto_backup_before_update', 'value' => 'yes', 'description' => 'Create backup before updates'],
        ['section' => 'General', 'parameter_name' => 'savepoints_integration', 'value' => 'yes', 'description' => 'Enable savepoints integration'],
        ['section' => 'General', 'parameter_name' => 'update_notification', 'value' => 'yes', 'description' => 'Enable update notifications'],
        ['section' => 'General', 'parameter_name' => 'health_check_interval', 'value' => '24', 'description' => 'Health check interval in hours'],
        ['section' => 'General', 'parameter_name' => 'dependency_check_mode', 'value' => 'warn', 'description' => 'Dependency check mode - warn or enforce'],
        ['section' => 'General', 'parameter_name' => 'batch_update_enabled', 'value' => 'yes', 'description' => 'Enable batch update operations'],
        ['section' => 'General', 'parameter_name' => 'installation_mode', 'value' => 'both', 'description' => 'Installation mode - track, orchestrate, or both'],
        ['section' => 'General', 'parameter_name' => 'metadata_extraction_level', 'value' => 'standard', 'description' => 'Metadata extraction - basic, standard, or comprehensive'],
        ['section' => 'General', 'parameter_name' => 'rollback_preview_level', 'value' => 'summary', 'description' => 'Rollback preview - none, summary, or detailed'],
        ['section' => 'General', 'parameter_name' => 'missing_dependency_handling', 'value' => 'auto_install', 'description' => 'Missing dependency handling - auto_install, prompt, or error'],
        ['section' => 'General', 'parameter_name' => 'dependency_version_mode', 'value' => 'warn', 'description' => 'Dependency version checking - track, warn, or enforce'],
        ['section' => 'General', 'parameter_name' => 'circular_dependency_handling', 'value' => 'error', 'description' => 'Circular dependency handling - error'],
        ['section' => 'General', 'parameter_name' => 'uninstall_reverse_order', 'value' => 'yes', 'description' => 'Uninstall in reverse dependency order'],
        ['section' => 'General', 'parameter_name' => 'update_dependency_order', 'value' => 'yes', 'description' => 'Update in dependency order'],
        ['section' => 'General', 'parameter_name' => 'backup_scope_default', 'value' => 'component_only', 'description' => 'Default backup scope'],
        ['section' => 'General', 'parameter_name' => 'rollback_dependency_mode', 'value' => 'warn', 'description' => 'Rollback dependency handling - warn, block, or auto_rollback'],
        ['section' => 'General', 'parameter_name' => 'logging_level', 'value' => 'essential', 'description' => 'Logging level - essential, standard, or verbose'],
        ['section' => 'General', 'parameter_name' => 'component_discovery_mode', 'value' => 'manual', 'description' => 'Component discovery - manual only'],
        ['section' => 'General', 'parameter_name' => 'status_tracking_mode', 'value' => 'basic', 'description' => 'Status tracking - basic, detailed, or custom'],
        ['section' => 'General', 'parameter_name' => 'performance_tracking_enabled', 'value' => 'no', 'description' => 'Enable performance tracking globally'],
        ['section' => 'General', 'parameter_name' => 'performance_tracking_level', 'value' => 'no', 'description' => 'Performance tracking - no, basic, or comprehensive'],
        ['section' => 'General', 'parameter_name' => 'security_scanning_enabled', 'value' => 'no', 'description' => 'Enable security scanning globally'],
        ['section' => 'General', 'parameter_name' => 'security_scanning_level', 'value' => 'no', 'description' => 'Security scanning - no, basic, or comprehensive'],
        ['section' => 'General', 'parameter_name' => 'scheduled_operations_enabled', 'value' => 'no', 'description' => 'Enable scheduled operations - no, basic, or comprehensive'],
        ['section' => 'General', 'parameter_name' => 'reporting_level', 'value' => 'basic', 'description' => 'Reporting level - basic or detailed'],
        ['section' => 'General', 'parameter_name' => 'component_sharing_enabled', 'value' => 'no', 'description' => 'Component sharing - no (project-specific only)'],
        ['section' => 'General', 'parameter_name' => 'backup_retention_policy', 'value' => 'manual_cleanup', 'description' => 'Backup retention - manual_cleanup, auto_cleanup, smart_retention, or unlimited'],
        ['section' => 'General', 'parameter_name' => 'backup_retention_period_days', 'value' => '30', 'description' => 'Auto-cleanup retention period in days'],
        ['section' => 'General', 'parameter_name' => 'health_alerts_enabled', 'value' => 'error', 'description' => 'Health alerts - comma-separated list'],
        ['section' => 'General', 'parameter_name' => 'conflict_resolution_strategy', 'value' => 'manual', 'description' => 'Conflict resolution - manual only'],
        ['section' => 'General', 'parameter_name' => 'api_rate_limiting_enabled', 'value' => 'yes', 'description' => 'Enable API rate limiting'],
        ['section' => 'General', 'parameter_name' => 'api_authentication_enabled', 'value' => 'yes', 'description' => 'Enable API key authentication'],
        ['section' => 'General', 'parameter_name' => 'api_webhooks_enabled', 'value' => 'yes', 'description' => 'Enable webhook support'],
        ['section' => 'General', 'parameter_name' => 'api_versioning_enabled', 'value' => 'yes', 'description' => 'Enable API versioning'],
        ['section' => 'General', 'parameter_name' => 'api_throttling_enabled', 'value' => 'yes', 'description' => 'Enable request throttling'],
        ['section' => 'General', 'parameter_name' => 'documentation_enabled', 'value' => 'yes', 'description' => 'Enable documentation features'],
        ['section' => 'General', 'parameter_name' => 'analytics_enabled', 'value' => 'yes', 'description' => 'Enable analytics features'],
        ['section' => 'General', 'parameter_name' => 'compatibility_checking_enabled', 'value' => 'yes', 'description' => 'Enable compatibility checking'],
        ['section' => 'General', 'parameter_name' => 'resource_management_enabled', 'value' => 'yes', 'description' => 'Enable resource management'],
    ];
}

