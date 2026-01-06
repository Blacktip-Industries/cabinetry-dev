# Savepoints Component

A portable, self-contained savepoint management system component for automated backup and restore functionality. Provides Git-based filesystem backups and database backups with restore capabilities.

## Features

- **Git-Based Filesystem Backups**: Automatic Git commits for filesystem changes
- **Database Backups**: Automated SQL database backups with each savepoint
- **GitHub Integration**: Push to GitHub with automatic fallback to API if needed
- **Restore Functionality**: Full restore of both filesystem and database to any savepoint
- **Restore Testing**: Dry-run and separate environment testing capabilities
- **Configurable Backup Scope**: Include/exclude directories for filesystem backups
- **Auto-Installation**: Fully automated installer with auto-detection
- **Portable Design**: Fully self-contained with isolated database tables and functions

## Installation

### Web Installation

1. Navigate to `/admin/components/savepoints/install.php` in your browser
2. Review auto-detected settings (Git status, database, etc.)
3. Click "Install Savepoints Component"

### CLI Installation

```bash
php admin/components/savepoints/install.php --auto
```

### Silent Installation

```bash
php admin/components/savepoints/install.php --silent
```

## Uninstallation

### Web Uninstallation

1. Navigate to `/admin/components/savepoints/uninstall.php`
2. Confirm uninstallation (backup will be created automatically)

### CLI Uninstallation

```bash
php admin/components/savepoints/uninstall.php --auto
```

## Usage

### Creating a Savepoint

1. Navigate to Savepoints management page
2. Enter a descriptive message for the savepoint
3. Click "Create Savepoint"
4. System will:
   - Stage all changes in Git
   - Create a Git commit
   - Create a database backup
   - Push to GitHub (if configured)

### Restoring a Savepoint

1. Navigate to Savepoints management page
2. Select a savepoint from the list
3. Click "Restore"
4. System will:
   - Create a backup of current state (safety)
   - Restore filesystem using `git reset --hard`
   - Restore database from SQL backup

### Restore Testing

1. Navigate to Restore Test page
2. Select a savepoint
3. Choose test mode:
   - **Dry Run**: Validate restore without making changes
   - **Separate Environment**: Restore to different directory/database
4. System will create test environment and restore savepoint

## Configuration

### GitHub Setup

1. Navigate to Savepoints Settings
2. Configure GitHub repository URL
3. Set branch name
4. Optionally add Personal Access Token for API fallback
5. Enable/disable auto-push to GitHub

### Backup Configuration

1. Navigate to Savepoints Settings
2. Configure excluded directories (default: uploads, node_modules, vendor, .git)
3. Configure included directories (empty = all)
4. Set backup frequency (manual or scheduled)

## Requirements

- PHP 7.4 or higher
- Git installed and configured
- MySQL/MariaDB database
- Write permissions for backup directories

## Security

- GitHub Personal Access Tokens are encrypted in database
- All file paths are validated to prevent directory traversal
- Git commit hashes are verified before restore operations
- Database credentials are never exposed in logs

## Support

For issues or questions:
1. Check component README.md
2. Review INSTALL.md
3. Check docs/API.md for function documentation
4. Review error logs

