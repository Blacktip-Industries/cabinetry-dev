# Component Manager - Installation Guide

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- mysqli extension
- json extension
- mbstring extension

## Installation

### Web Installation

1. Navigate to `/admin/components/component_manager/install.php`
2. The installer will auto-detect database configuration
3. Review and confirm the detected settings
4. Click "Install Component Manager"
5. Installation will create database tables and generate config.php

### CLI Installation

```bash
php admin/components/component_manager/install.php --auto
```

### Silent Installation

```bash
php admin/components/component_manager/install.php --silent
```

## Post-Installation

After installation:

1. Access the dashboard at `/admin/components/component_manager/admin/index.php`
2. Register existing components manually via the registry
3. Configure settings as needed

## Manual Component Registration

Components must be registered manually (no auto-scanning):

1. Go to Component Registry
2. Click "Register New Component"
3. Enter component name and path
4. Review installation preview
5. Confirm installation

## Troubleshooting

- **Database connection failed**: Check database credentials in detected config files
- **Permission errors**: Ensure component directory is writable
- **Menu links not created**: menu_system component may not be installed (not required)

