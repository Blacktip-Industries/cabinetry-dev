# Menu System Component

A portable, self-contained menu management system component for PHP applications.

## Features

- **Portable Design**: Fully self-contained with isolated database tables and functions
- **Icon Management**: Integrated icon system with SVG support
- **Menu Management**: Complete CRUD operations for menu items
- **File Protection**: Optional automatic file updates with backup support
- **Auto-Installation**: Fully automated installer with auto-detection
- **CSS Variable Standardization**: Maps to base system CSS variables with fallbacks

## Installation

### Web Installation

1. Navigate to `/admin/components/menu_system/install.php` in your browser
2. Review auto-detected settings
3. Click "Install Menu System Component"

### CLI Installation

```bash
php admin/components/menu_system/install.php --auto
```

### Silent Installation

```bash
php admin/components/menu_system/install.php --silent
```

## Uninstallation

### Web Uninstallation

1. Navigate to `/admin/components/menu_system/uninstall.php`
2. Confirm uninstallation (backup will be created automatically)

### CLI Uninstallation

```bash
php admin/components/menu_system/uninstall.php --auto
```

## Usage

### Including the Sidebar

```php
require_once __DIR__ . '/admin/components/menu_system/includes/sidebar.php';
```

### Accessing Menu Management

Navigate to: `/admin/components/menu_system/admin/menus.php`

### Accessing Icon Management

Navigate to: `/admin/components/menu_system/admin/icons.php`

## File Structure

```
admin/components/menu_system/
├── admin/              # Admin interface pages
├── assets/             # CSS and JavaScript
├── core/               # Core PHP functions
├── includes/           # Reusable includes
├── install/            # Installation files
├── docs/               # Documentation
├── config.php          # Component configuration (auto-generated)
├── install.php         # Installer
└── uninstall.php       # Uninstaller
```

## Database Tables

All tables are prefixed with `menu_system_`:

- `menu_system_menus` - Menu items
- `menu_system_icons` - Icon library
- `menu_system_parameters` - Component parameters
- `menu_system_config` - Component configuration
- `menu_system_file_backups` - File backups (if file protection enabled)

## Configuration

Configuration is stored in `config.php` (auto-generated during installation).

Key settings:
- `MENU_SYSTEM_FILE_PROTECTION_MODE` - File protection mode: 'full', 'update', or 'disabled'
- Database connection settings
- Path and URL settings

## File Protection

The component can automatically update PHP files with `page_identifier` values when menu items are saved.

**Modes:**
- `full` - Creates backup before updating (default)
- `update` - Updates without backup
- `disabled` - No file updates

**Smart Update Logic:** Files are only updated when the `page_identifier` field changes, not on every menu item edit.

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB with mysqli extension
- JSON extension
- mbstring extension

## License

[Your License Here]

## Support

For issues or questions, please refer to the documentation in `/admin/components/menu_system/docs/`.

