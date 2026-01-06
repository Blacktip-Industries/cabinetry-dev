# URL Routing Component

A portable, reusable component for clean URL routing in PHP applications. Provides clean URLs (slugs) instead of traditional file paths.

## Features

- **Clean URLs**: Transform `/admin/users/add.php` to `/user-add`
- **Hybrid Routing**: Static routes (hardcoded) for performance + database routes for flexibility
- **Route Parameters**: Support for `/user-edit/123` → `admin/users/edit.php?id=123`
- **Admin Interface**: Manage routes via web interface
- **Auto-Installation**: Fully automated installer with auto-detection
- **Menu Integration**: Optional migration from existing menu items
- **Security**: Path validation, directory traversal protection

## Quick Start

### Installation

1. Navigate to: `http://your-domain.com/admin/components/url_routing/install.php`
2. Review auto-detected settings
3. Optionally check "Migrate existing menu items to routes"
4. Click "Install URL Routing Component"

### Usage

#### Generate Clean URLs

```php
require_once __DIR__ . '/admin/components/url_routing/core/functions.php';

// Generate URL
$url = url_routing_url('user-add');
// Returns: http://your-domain.com/user-add
```

#### Access Routes

Once installed, routes are automatically handled via `.htaccess` and `router.php`:

- `/user-add` → `admin/users/add.php`
- `/user-edit/123` → `admin/users/edit.php?id=123`

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- mysqli extension
- mod_rewrite (Apache) or equivalent (Nginx)

## Documentation

- [Installation Guide](INSTALL.md)
- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)

## Component Structure

```
/admin/components/url_routing/
├── core/           # Core routing logic
├── admin/          # Admin interface
├── install/        # Installation files
├── includes/       # Public includes
└── assets/         # CSS files
```

## License

This component follows the same license as the base system.

