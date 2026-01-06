# Savepoints Component - Integration Guide

## Overview

This guide explains how to integrate the Savepoints component into your application and use it programmatically.

## Basic Integration

### 1. Load Component Files

```php
// Load component configuration
require_once __DIR__ . '/admin/components/savepoints/includes/config.php';

// Load core functions
require_once __DIR__ . '/admin/components/savepoints/core/database.php';
require_once __DIR__ . '/admin/components/savepoints/core/backup-operations.php';
require_once __DIR__ . '/admin/components/savepoints/core/restore-operations.php';
```

### 2. Check Installation

```php
// Check if component is installed
$configPath = __DIR__ . '/admin/components/savepoints/config.php';
if (!file_exists($configPath)) {
    die('Savepoints component is not installed. Please run the installer.');
}
```

## Creating Savepoints Programmatically

### Simple Savepoint Creation

```php
require_once __DIR__ . '/admin/components/savepoints/core/backup-operations.php';

$result = savepoints_create_savepoint('Automated backup before deployment', 'cli');

if ($result['success']) {
    echo "Savepoint created successfully\n";
    echo "ID: " . $result['savepoint_id'] . "\n";
    echo "Commit: " . $result['commit_hash'] . "\n";
} else {
    echo "Error: " . implode(', ', $result['errors']) . "\n";
}
```

### Savepoint with Custom Settings

```php
// Get excluded directories from parameters
$excludedDirsJson = savepoints_get_parameter('Backup', 'excluded_directories', '[]');
$excludedDirs = json_decode($excludedDirsJson, true);

// Create filesystem backup
$fsResult = savepoints_backup_filesystem('Custom backup', $excludedDirs);

// Create database backup
$dbResult = savepoints_backup_database();

// Create history record manually if needed
if ($fsResult['success'] && $dbResult['success']) {
    $savepointId = savepoints_create_history_record(
        $fsResult['commit_hash'],
        'Custom savepoint',
        $dbResult['relative_file'],
        'cli',
        null,
        'success',
        'success'
    );
}
```

## Restoring Savepoints

### Full Restore

```php
require_once __DIR__ . '/admin/components/savepoints/core/restore-operations.php';

// Restore savepoint by ID
$result = savepoints_restore(10, true); // Create backup first

if ($result['success']) {
    echo "Restore completed successfully\n";
} else {
    echo "Restore failed: " . implode(', ', $result['errors']) . "\n";
}
```

### Restore to Test Environment

```php
$result = savepoints_restore_test(
    10,
    'separate_env',
    '/path/to/test/directory',
    'test_database_name'
);

if ($result['success']) {
    echo "Test restore completed\n";
    echo "Target directory: " . $result['data']['target_directory'] . "\n";
    echo "Target database: " . $result['data']['target_database'] . "\n";
}
```

## Scheduled Backups

### Using Cron

Create a cron job to run scheduled backups:

```bash
# Run backup every day at 2 AM
0 2 * * * php /path/to/project/admin/components/savepoints/scripts/scheduled-backup.php
```

Create `admin/components/savepoints/scripts/scheduled-backup.php`:

```php
<?php
/**
 * Scheduled Backup Script
 * Run via cron for automated backups
 */

require_once __DIR__ . '/../../core/backup-operations.php';

// Check if scheduled backups are enabled
$frequency = savepoints_get_parameter('Backup', 'backup_frequency', 'manual');

if ($frequency === 'scheduled') {
    $message = 'Scheduled backup - ' . date('Y-m-d H:i:s');
    $result = savepoints_create_savepoint($message, 'scheduled');
    
    if ($result['success']) {
        error_log("Savepoints: Scheduled backup created - ID " . $result['savepoint_id']);
    } else {
        error_log("Savepoints: Scheduled backup failed - " . implode(', ', $result['errors']));
    }
}
```

## Integration with Deployment Scripts

### Pre-Deployment Backup

```php
// Before deployment
require_once __DIR__ . '/admin/components/savepoints/core/backup-operations.php';

echo "Creating pre-deployment savepoint...\n";
$result = savepoints_create_savepoint('Pre-deployment backup', 'deployment');

if (!$result['success']) {
    die("Failed to create backup. Deployment aborted.\n");
}

echo "Backup created: " . $result['savepoint_id'] . "\n";

// Continue with deployment...
```

### Post-Deployment Verification

```php
// After deployment
require_once __DIR__ . '/admin/components/savepoints/core/backup-operations.php';

// Create post-deployment savepoint
$result = savepoints_create_savepoint('Post-deployment state', 'deployment');

if ($result['success']) {
    echo "Post-deployment savepoint created\n";
} else {
    echo "Warning: Failed to create post-deployment savepoint\n";
}
```

## Integration with Git Workflows

### Before Git Operations

```php
require_once __DIR__ . '/admin/components/savepoints/core/git-operations.php';

// Check Git status before operations
if (savepoints_has_uncommitted_changes()) {
    echo "Warning: Uncommitted changes detected\n";
    
    // Create savepoint before proceeding
    $result = savepoints_create_savepoint('Backup before Git operation', 'git');
}
```

### After Git Operations

```php
// After merge, pull, or other Git operations
$result = savepoints_create_savepoint('State after Git merge', 'git');
```

## Custom Backup Strategies

### Selective File Backup

```php
// Get custom excluded directories
$customExcluded = ['uploads', 'cache', 'logs', 'tmp'];

// Create filesystem backup with custom exclusions
$result = savepoints_backup_filesystem('Selective backup', $customExcluded);
```

### Database-Only Backup

```php
// Create only database backup
$result = savepoints_backup_database();

if ($result['success']) {
    // Store backup info manually
    $savepointId = savepoints_create_history_record(
        null, // No commit hash
        'Database-only backup',
        $result['relative_file'],
        'custom',
        null,
        'skipped',
        'success'
    );
}
```

## Error Handling Best Practices

### Comprehensive Error Handling

```php
$result = savepoints_create_savepoint('Important backup', 'web');

if ($result['success']) {
    // Success
    if (!empty($result['warnings'])) {
        // Log warnings but continue
        error_log("Savepoints warnings: " . implode(', ', $result['warnings']));
    }
} else {
    // Handle errors
    $errors = $result['errors'];
    
    // Critical errors
    if (in_array('Database backup failed', $errors)) {
        // Send alert, abort operation
        sendAlert('Critical: Database backup failed');
        die("Cannot proceed without database backup\n");
    }
    
    // Non-critical errors
    if (in_array('Failed to push to remote repository', $errors)) {
        // Log but continue
        error_log("Warning: GitHub push failed");
    }
}
```

## Webhook Integration

### GitHub Webhook Handler

```php
// Handle GitHub webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payload'])) {
    $payload = json_decode($_POST['payload'], true);
    
    if ($payload['ref'] === 'refs/heads/main') {
        // Create savepoint after GitHub push
        require_once __DIR__ . '/admin/components/savepoints/core/backup-operations.php';
        
        $message = 'GitHub webhook triggered - ' . $payload['head_commit']['message'];
        $result = savepoints_create_savepoint($message, 'webhook');
    }
}
```

## Performance Considerations

### Large Projects

For large projects, consider:

1. **Exclude large directories** in backup settings:
```php
$excluded = ['node_modules', 'vendor', 'uploads', '.git', 'cache'];
savepoints_set_parameter('Backup', 'excluded_directories', json_encode($excluded));
```

2. **Limit savepoint history**:
```php
// Keep only last 50 savepoints
$savepoints = savepoints_get_history(50);
```

3. **Schedule backups during off-peak hours**

## Security Considerations

### Protect Sensitive Data

```php
// Never commit sensitive data
$excluded = ['config/secrets.php', '.env', 'private_keys/'];
```

### Validate Restore Operations

```php
// Always validate before restore
$savepoint = savepoints_get_by_id($id);
if (!$savepoint) {
    die("Invalid savepoint ID\n");
}

// Additional validation
if (empty($savepoint['sql_file_path'])) {
    die("Database backup file missing\n");
}
```

## Troubleshooting

### Common Issues

1. **Git not available**
   - Install Git and ensure it's in PATH
   - Check with `savepoints_is_git_available()`

2. **Database backup fails**
   - Check mysqldump path
   - Verify database credentials
   - Check file permissions

3. **Git push fails**
   - Verify GitHub remote URL
   - Check GitHub credentials
   - Use Personal Access Token as fallback

## Advanced Usage

### Custom Backup Script

```php
<?php
/**
 * Custom Backup Script
 */

require_once __DIR__ . '/admin/components/savepoints/core/backup-operations.php';
require_once __DIR__ . '/admin/components/savepoints/core/database.php';

// Custom backup logic
function customBackup() {
    // 1. Create database backup
    $dbResult = savepoints_backup_database();
    
    // 2. Create filesystem backup
    $fsResult = savepoints_backup_filesystem('Custom backup script');
    
    // 3. Create history record
    if ($dbResult['success'] && $fsResult['success']) {
        $savepointId = savepoints_create_history_record(
            $fsResult['commit_hash'],
            'Custom backup',
            $dbResult['relative_file'],
            'custom_script',
            null,
            'success',
            'success'
        );
        
        return $savepointId;
    }
    
    return false;
}

// Run backup
$savepointId = customBackup();
if ($savepointId) {
    echo "Backup created: " . $savepointId . "\n";
}
```

## Support

For additional help:
- Check `README.md` for component overview
- Review `API.md` for function documentation
- Check error logs for detailed error messages

