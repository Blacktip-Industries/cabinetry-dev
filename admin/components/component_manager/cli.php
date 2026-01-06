<?php
/**
 * Component Manager - CLI Interface
 * Command-line interface for component management
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Load component files
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/core/registry.php';
require_once __DIR__ . '/core/version.php';
require_once __DIR__ . '/core/changelog.php';
require_once __DIR__ . '/core/backup.php';
require_once __DIR__ . '/core/upgrade.php';
require_once __DIR__ . '/core/uninstall.php';
require_once __DIR__ . '/core/health.php';
require_once __DIR__ . '/core/dependencies.php';
require_once __DIR__ . '/includes/config.php';

// Parse command line arguments
$args = $argv ?? [];
$command = $args[1] ?? 'help';

// Command router
switch ($command) {
    case 'register':
        $componentName = $args[2] ?? null;
        if (!$componentName) {
            echo "Error: Component name required\n";
            echo "Usage: php cli.php register <component_name> [--path=<path>]\n";
            exit(1);
        }
        $componentPath = $args[3] ?? __DIR__ . '/../../' . $componentName;
        $result = component_manager_register_manual($componentName, $componentPath);
        if ($result['success']) {
            echo "Component registered successfully\n";
        } else {
            echo "Registration failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            exit(1);
        }
        break;
        
    case 'list':
        $components = component_manager_list_components();
        echo "Registered Components:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($components as $component) {
            echo sprintf("%-30s %-15s %-15s %s\n", 
                $component['component_name'],
                $component['installed_version'],
                $component['status'],
                $component['health_status']
            );
        }
        break;
        
    case 'check-updates':
        $componentName = $args[2] ?? null;
        if ($componentName) {
            if (component_manager_is_update_available($componentName)) {
                $available = component_manager_get_available_version($componentName);
                echo "Update available for {$componentName}: {$available}\n";
            } else {
                echo "{$componentName} is up to date\n";
            }
        } else {
            $components = component_manager_list_components();
            $updates = 0;
            foreach ($components as $component) {
                if (component_manager_is_update_available($component['component_name'])) {
                    $available = component_manager_get_available_version($component['component_name']);
                    echo "Update available: {$component['component_name']} ({$component['installed_version']} -> {$available})\n";
                    $updates++;
                }
            }
            if ($updates === 0) {
                echo "All components are up to date\n";
            }
        }
        break;
        
    case 'health':
        $componentName = $args[2] ?? null;
        if ($componentName) {
            $result = component_manager_check_health($componentName);
            echo "Health check for {$componentName}: {$result['status']}\n";
            echo "Message: {$result['message']}\n";
        } else {
            $results = component_manager_check_all_health();
            foreach ($results as $name => $result) {
                echo "{$name}: {$result['status']}\n";
            }
        }
        break;
        
    case 'changelog':
        $componentName = $args[2] ?? null;
        $changelog = component_manager_get_changelog($componentName, ['limit' => 20]);
        foreach ($changelog as $entry) {
            echo "[{$entry['component_name']} v{$entry['version']}] {$entry['change_type']}: {$entry['title']}\n";
        }
        break;
        
    case 'backup':
        $componentName = $args[2] ?? null;
        if (!$componentName) {
            echo "Error: Component name required\n";
            exit(1);
        }
        $reason = $args[3] ?? 'Manual backup';
        $result = component_manager_create_backup($componentName, $reason);
        if ($result['success']) {
            echo "Backup created successfully (ID: {$result['backup_id']})\n";
        } else {
            echo "Backup failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            exit(1);
        }
        break;
        
    case 'install':
        $componentName = $args[2] ?? null;
        if (!$componentName) {
            echo "Error: Component name required\n";
            exit(1);
        }
        $componentPath = $args[3] ?? __DIR__ . '/../../' . $componentName;
        $preview = component_manager_get_installation_preview($componentName, ['component_path' => $componentPath]);
        if ($preview['success']) {
            echo "Installation preview for {$componentName}:\n";
            echo "Version: {$preview['version']}\n";
            echo "Steps: " . count($preview['steps']) . "\n";
            // TODO: Implement actual installation
            echo "Installation - To be implemented\n";
        } else {
            echo "Preview failed: " . ($preview['error'] ?? 'Unknown error') . "\n";
            exit(1);
        }
        break;
        
    case 'help':
    default:
        echo "Component Manager CLI\n";
        echo "===================\n\n";
        echo "Available commands:\n";
        echo "  register <component_name> [--path=<path>]  - Register a component\n";
        echo "  list                                        - List all registered components\n";
        echo "  check-updates [component_name]             - Check for updates\n";
        echo "  health [component_name]                    - Run health check\n";
        echo "  changelog [component_name]                 - Show changelog\n";
        echo "  backup <component_name> [reason]          - Create backup\n";
        echo "  install <component_name> [path]           - Install component\n";
        echo "  help                                       - Show this help\n";
        break;
}

exit(0);

