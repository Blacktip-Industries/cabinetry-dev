# Savepoints Component - Installation Guide

## Prerequisites

Before installing the Savepoints component, ensure you have:

1. **PHP 7.4 or higher** - Check with `php -v`
2. **Git installed** - Check with `git --version`
3. **MySQL/MariaDB database** - Access credentials required
4. **Write permissions** - For backup directories and Git operations

## Installation Methods

### Method 1: Web Installation (Recommended)

1. Navigate to: `http://your-domain/admin/components/savepoints/install.php`
2. The installer will auto-detect:
   - Database connection settings
   - Git repository status
   - GitHub remote configuration
   - Existing savepoint-related parameters
3. Review the detected settings
4. Click "Install Savepoints Component"
5. Installation will:
   - Create database tables
   - Copy relevant parameters from base system
   - Create default savepoint parameters
   - Generate configuration file
   - Create menu links (if menu_system is installed)

### Method 2: CLI Installation

```bash
# Auto-detect and install
php admin/components/savepoints/install.php --auto

# Silent mode (no prompts)
php admin/components/savepoints/install.php --silent
```

### Method 3: Manual Installation

If auto-detection fails, you can manually configure:

1. Edit `config.example.php` with your settings
2. Rename to `config.php`
3. Run database migration manually
4. Insert default parameters

## Post-Installation Setup

### 1. Git Repository Setup

If Git is not initialized:

```bash
cd /path/to/your/project
git init
git add .
git commit -m "Initial commit"
```

### 2. GitHub Remote Setup

If you want to push to GitHub:

```bash
git remote add origin https://github.com/username/repository.git
git branch -M main
git push -u origin main
```

Or use the GitHub setup wizard in the admin interface.

### 3. Configure Backup Settings

1. Navigate to Savepoints Settings page
2. Configure excluded directories (defaults: uploads, node_modules, vendor, .git)
3. Set backup frequency
4. Configure GitHub settings if needed

## Verification

After installation, verify:

1. Database tables created:
   - `savepoints_config`
   - `savepoints_parameters`
   - `savepoints_parameters_configs`
   - `savepoints_history`

2. Configuration file exists: `admin/components/savepoints/config.php`

3. Menu links created (if menu_system installed)

4. Test savepoint creation:
   - Navigate to Savepoints page
   - Create a test savepoint
   - Verify Git commit created
   - Verify database backup created

## Troubleshooting

### Git Not Found

**Error**: "Git is not available"

**Solution**: Install Git and ensure it's in your system PATH

### Database Connection Failed

**Error**: "Database connection failed"

**Solution**: 
- Check database credentials in `config/database.php`
- Ensure database user has CREATE TABLE permissions
- Verify database server is running

### Git Repository Not Found

**Error**: "Not a Git repository"

**Solution**: 
- Initialize Git: `git init`
- Or configure Git repository path in settings

### GitHub Push Failed

**Error**: "Failed to push to remote repository"

**Solution**:
- Check GitHub remote URL: `git remote -v`
- Verify GitHub credentials
- Check network connectivity
- Use GitHub API token as fallback

## Uninstallation

See `uninstall.php` for uninstallation instructions. Uninstallation will:
- Backup all savepoint data
- Remove database tables
- Remove menu links
- Remove component files

## Support

For additional help:
- Check `README.md` for usage examples
- Review `docs/API.md` for function documentation
- Check error logs for detailed error messages

