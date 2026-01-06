# Layout Component

A portable, self-contained layout system component for PHP applications that provides the core page structure with intelligent placeholders for header, menu, and footer components.

## Features

- **Portable Design**: Fully self-contained with isolated database tables and functions
- **Component Detection**: Automatically detects and includes header, menu_system, and footer components if installed
- **Placeholder System**: Shows helpful placeholders when components are not installed
- **Auto-Installation**: Fully automated installer with auto-detection
- **CSS Variable Standardization**: Maps to base system CSS variables with fallbacks
- **Responsive Layout**: CSS Grid-based responsive layout system

## Installation

### Web Installation

1. Navigate to `/admin/components/layout/install.php` in your browser
2. Review auto-detected settings
3. Click "Install Layout Component"

### CLI Installation

```bash
php admin/components/layout/install.php --auto
```

### Silent Installation

```bash
php admin/components/layout/install.php --silent
```

## Uninstallation

### Web Uninstallation

1. Navigate to `/admin/components/layout/uninstall.php`
2. Confirm uninstallation (backup will be created automatically)

### CLI Uninstallation

```bash
php admin/components/layout/uninstall.php --auto
```

## Usage

### Basic Usage

```php
require_once __DIR__ . '/components/layout/includes/layout.php';
layout_start_layout('Page Title', true, 'page_identifier');
// Page content here
layout_end_layout();
```

### Parameters

- `$pageTitle` (string): Page title displayed in browser tab
- `$requireAuth` (bool): Whether to require authentication (default: true)
- `$currPage` (string|null): Current page identifier for menu highlighting (optional)

## Component Integration

The layout component automatically detects and includes:

- **Header Component**: `/admin/components/header/includes/header.php`
- **Menu System Component**: `/admin/components/menu_system/includes/sidebar.php`
- **Footer Component**: `/admin/components/footer/includes/footer.php`

If a component is not installed, a placeholder will be displayed with installation instructions.

## File Structure

```
admin/components/layout/
├── admin/              # Admin interface pages (if any)
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

All tables are prefixed with `layout_`:

- `layout_config` - Component configuration and metadata
- `layout_parameters` - Component-specific parameters (if needed)

## Configuration

Configuration is stored in `config.php` (auto-generated during installation).

Key settings:
- Database connection settings
- Path and URL settings
- Component version and installation info

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB with mysqli extension
- JSON extension
- mbstring extension

## License

[Your License Here]

## Support

For issues or questions, please refer to the documentation in `/admin/components/layout/docs/`.

