# Savepoints Component - API Documentation

## Overview

The Savepoints component provides a comprehensive API for creating, managing, and restoring savepoints. All functions are prefixed with `savepoints_` to avoid conflicts.

## Database Functions

### `savepoints_get_db_connection()`

Get database connection for savepoints component.

**Returns:** `mysqli|null` - Database connection or null on failure

**Example:**
```php
$conn = savepoints_get_db_connection();
if ($conn) {
    // Use connection
}
```

### `savepoints_get_parameter($section, $name, $default = null)`

Get parameter value from savepoints_parameters table.

**Parameters:**
- `$section` (string) - Parameter section
- `$name` (string) - Parameter name
- `$default` (mixed) - Default value if not found

**Returns:** `mixed` - Parameter value or default

**Example:**
```php
$repoUrl = savepoints_get_parameter('GitHub', 'repository_url', '');
```

### `savepoints_set_parameter($section, $name, $value, $description = null)`

Set parameter value in savepoints_parameters table.

**Parameters:**
- `$section` (string) - Parameter section
- `$name` (string) - Parameter name
- `$value` (string) - Parameter value
- `$description` (string|null) - Parameter description

**Returns:** `bool` - Success

**Example:**
```php
savepoints_set_parameter('GitHub', 'repository_url', 'https://github.com/user/repo.git');
```

### `savepoints_get_history($limit = 0, $orderBy = 'timestamp', $orderDirection = 'DESC')`

Get all savepoints from history.

**Parameters:**
- `$limit` (int) - Maximum number of savepoints (0 = all)
- `$orderBy` (string) - Order by field
- `$orderDirection` (string) - Order direction (ASC or DESC)

**Returns:** `array` - Array of savepoint records

**Example:**
```php
$savepoints = savepoints_get_history(10); // Get last 10 savepoints
```

### `savepoints_get_by_id($id)`

Get savepoint by ID.

**Parameters:**
- `$id` (int) - Savepoint ID

**Returns:** `array|null` - Savepoint record or null

**Example:**
```php
$savepoint = savepoints_get_by_id(1);
```

### `savepoints_get_by_commit_hash($commitHash)`

Get savepoint by commit hash.

**Parameters:**
- `$commitHash` (string) - Git commit hash

**Returns:** `array|null` - Savepoint record or null

**Example:**
```php
$savepoint = savepoints_get_by_commit_hash('abc123def456');
```

### `savepoints_create_history_record($commitHash, $message, $sqlFilePath, $createdBy = 'web', $pushStatus = null, $filesystemStatus = null, $databaseStatus = null)`

Create savepoint record in history.

**Parameters:**
- `$commitHash` (string|null) - Git commit hash
- `$message` (string) - Savepoint message
- `$sqlFilePath` (string) - Path to SQL backup file
- `$createdBy` (string) - Creator identifier
- `$pushStatus` (string|null) - Push status
- `$filesystemStatus` (string|null) - Filesystem backup status
- `$databaseStatus` (string|null) - Database backup status

**Returns:** `int|false` - Savepoint ID on success, false on failure

### `savepoints_delete_history_record($id)`

Delete savepoint record from history.

**Parameters:**
- `$id` (int) - Savepoint ID

**Returns:** `bool` - Success

## Backup Operations

### `savepoints_backup_database()`

Create database backup.

**Returns:** `array` - Result with success, file, relative_file, size, error

**Example:**
```php
$result = savepoints_backup_database();
if ($result['success']) {
    echo "Backup created: " . $result['relative_file'];
}
```

### `savepoints_backup_filesystem($message, $excludedDirs = null)`

Create filesystem backup (Git commit).

**Parameters:**
- `$message` (string) - Commit message
- `$excludedDirs` (array|null) - Excluded directory patterns

**Returns:** `array` - Result with success, commit_hash, output, error

**Example:**
```php
$result = savepoints_backup_filesystem('Updated admin settings');
if ($result['success']) {
    echo "Commit created: " . $result['commit_hash'];
}
```

### `savepoints_create_savepoint($message, $createdBy = 'web')`

Create complete savepoint (filesystem + database backup).

**Parameters:**
- `$message` (string) - Savepoint message
- `$createdBy` (string) - Creator identifier

**Returns:** `array` - Result with success, savepoint_id, commit_hash, sql_file, warnings, errors

**Example:**
```php
$result = savepoints_create_savepoint('Major feature update', 'web');
if ($result['success']) {
    echo "Savepoint created: " . $result['savepoint_id'];
}
```

## Restore Operations

### `savepoints_restore($savepointId, $createBackupFirst = true)`

Restore savepoint (both filesystem and database).

**Parameters:**
- `$savepointId` (int|string) - Savepoint ID or commit hash
- `$createBackupFirst` (bool) - Create backup before restore

**Returns:** `array` - Result with success, warnings, errors

**Example:**
```php
$result = savepoints_restore(1, true);
if ($result['success']) {
    echo "Savepoint restored successfully";
}
```

### `savepoints_restore_test($savepointId, $mode = 'dry_run', $targetDirectory = null, $targetDatabase = null)`

Restore savepoint to test environment.

**Parameters:**
- `$savepointId` (int|string) - Savepoint ID or commit hash
- `$mode` (string) - Test mode: 'dry_run' or 'separate_env'
- `$targetDirectory` (string|null) - Target directory for separate environment
- `$targetDatabase` (string|null) - Target database name for separate environment

**Returns:** `array` - Result with success, warnings, errors, data

**Example:**
```php
// Dry run
$result = savepoints_restore_test(1, 'dry_run');

// Separate environment
$result = savepoints_restore_test(1, 'separate_env', '/path/to/test', 'test_db');
```

### `savepoints_restore_database($sqlFilePath)`

Restore database from SQL file.

**Parameters:**
- `$sqlFilePath` (string) - Relative path to SQL file

**Returns:** `array` - Result with success, error

## Git Operations

### `savepoints_is_git_available()`

Check if Git is available.

**Returns:** `bool` - True if Git is available

### `savepoints_get_git_root()`

Get Git repository root directory.

**Returns:** `string` - Git root path

### `savepoints_get_current_branch()`

Get current Git branch name.

**Returns:** `string|null` - Branch name or null

### `savepoints_get_current_commit_hash()`

Get current Git commit hash.

**Returns:** `string|null` - Commit hash or null

### `savepoints_has_uncommitted_changes()`

Check if there are uncommitted changes.

**Returns:** `bool` - True if there are uncommitted changes

### `savepoints_git_stage_all($excludedDirs = null)`

Stage all files in Git.

**Parameters:**
- `$excludedDirs` (array|null) - Excluded directory patterns

**Returns:** `array` - Result with success, output, error

### `savepoints_git_commit($message)`

Create Git commit.

**Parameters:**
- `$message` (string) - Commit message

**Returns:** `array` - Result with success, commit_hash, output, error

### `savepoints_git_reset_hard($commitHash)`

Reset Git repository to specific commit (hard reset).

**Parameters:**
- `$commitHash` (string) - Commit hash

**Returns:** `array` - Result with success, output, error

**Note:** This function uses `git reset --hard` which will overwrite all current changes.

### `savepoints_git_push($branch = null)`

Push to GitHub remote.

**Parameters:**
- `$branch` (string|null) - Branch name (defaults to current branch)

**Returns:** `array` - Result with success, output, error

## Helper Functions

### `savepoints_get_project_root()`

Get project root directory.

**Returns:** `string` - Project root path

### `savepoints_sanitize_message($message)`

Sanitize savepoint message.

**Parameters:**
- `$message` (string) - Message to sanitize

**Returns:** `string` - Sanitized message

## Error Handling

All functions return arrays with consistent structure:

```php
[
    'success' => bool,
    'error' => string|null,
    'warnings' => array,
    // ... other fields specific to function
]
```

Always check the `success` field before using other fields.

## Examples

### Create a Savepoint

```php
require_once 'admin/components/savepoints/core/backup-operations.php';

$result = savepoints_create_savepoint('Updated user authentication system', 'web');

if ($result['success']) {
    echo "Savepoint created: ID " . $result['savepoint_id'];
    echo "Commit: " . $result['commit_hash'];
    echo "Database backup: " . $result['sql_file'];
} else {
    echo "Error: " . implode(', ', $result['errors']);
}
```

### Restore a Savepoint

```php
require_once 'admin/components/savepoints/core/restore-operations.php';

$result = savepoints_restore(5, true); // Create backup first

if ($result['success']) {
    echo "Savepoint restored successfully";
    if (!empty($result['warnings'])) {
        echo "Warnings: " . implode(', ', $result['warnings']);
    }
} else {
    echo "Error: " . implode(', ', $result['errors']);
}
```

### Test Restore (Dry Run)

```php
require_once 'admin/components/savepoints/core/restore-operations.php';

$result = savepoints_restore_test(5, 'dry_run');

if ($result['success']) {
    echo "Validation passed - restore is safe";
} else {
    echo "Validation failed: " . implode(', ', $result['errors']);
}
```

## Notes

- All database operations use prepared statements for security
- File paths are validated to prevent directory traversal
- Git commit hashes are verified before restore operations
- Functions handle errors gracefully and return detailed error messages

