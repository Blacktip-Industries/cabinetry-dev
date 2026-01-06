# Menu System Component - Installation Guide

## Quick Start

### Web Installation (Recommended)

1. Navigate to: `http://your-domain.com/admin/components/menu_system/install.php`
2. Review the auto-detected settings
3. Click "Install Menu System Component"
4. Wait for installation to complete

### CLI Installation

```bash
# Auto-install with all defaults
php admin/components/menu_system/install.php --auto

# Silent mode (JSON output)
php admin/components/menu_system/install.php --silent
```

## Installation Process

The installer automatically:

1. **Detects System Configuration**
   - Database connection settings
   - Project paths
   - Base URL
   - CSS variables

2. **Creates Database Tables**
   - `menu_system_menus` - Menu items
   - `menu_system_icons` - Icon library
   - `menu_system_parameters` - Component parameters
   - `menu_system_config` - Component configuration
   - `menu_system_file_backups` - File backups (if enabled)

3. **Inserts Default Parameters**
   - Menu styling parameters
   - Icon size parameters
   - Indent parameters

4. **Generates Configuration Files**
   - `config.php` - Component configuration
   - `assets/css/variables.css` - CSS variables mapping

## Post-Installation

### Access Menu Management

Navigate to: `/admin/components/menu_system/admin/menus.php`

### Access Icon Management

Navigate to: `/admin/components/menu_system/admin/icons.php`

### Include Sidebar in Your Layout

```php
require_once __DIR__ . '/admin/components/menu_system/includes/sidebar.php';
```

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- mysqli extension
- json extension
- mbstring extension

## Troubleshooting

### Database Connection Failed

- Check that database credentials are correct
- Ensure database exists
- Verify mysqli extension is enabled

### File Permissions

If installation fails due to file permissions:

```bash
chmod -R 755 admin/components/menu_system
chmod -R 775 admin/components/menu_system/assets
```

### CSS Variables Not Detected

The installer will use fallback values if base system CSS variables cannot be detected. You can manually update `assets/css/variables.css` after installation.

## Uninstallation

See `README.md` for uninstallation instructions.

