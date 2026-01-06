# Access Component

Advanced, highly customizable access and user management system component for comprehensive control over all user access and accounts.

## Overview

The Access Component is an enterprise-level system designed for maximum flexibility and extensibility. It provides:

- **Advanced Configurable Account Types**: Create unlimited account types with custom fields, validation rules, conditional logic, and workflow customization
- **Multi-User Account Support**: Business accounts with multiple users, each with individual roles and custom permissions
- **Granular Permission System**: Hierarchical permissions, role-based access, custom permission overrides, time-based permissions, and account-scoped permissions
- **Customizable Registration Workflows**: Multi-step registration, conditional approval paths, custom workflows, and email template customization
- **Advanced Authentication**: Frontend/backend login, 2FA support, session management, account locking, and security features
- **Extensibility System**: Hook/event system, custom field types, workflow builder, and API integration
- **Comprehensive Audit & Compliance**: Full audit logging, GDPR compliance features, and security monitoring

## Installation

1. Copy the component to `/admin/components/access/`
2. Navigate to `/admin/components/access/install.php` in your browser
3. Follow the installation wizard
4. The installer will auto-detect your database configuration and create all necessary tables

For CLI installation:
```bash
php admin/components/access/install.php --auto
```

## Features

### Account Types
- Create unlimited account types (Retail, Business, Trade, etc.)
- Custom fields per account type with extensive field types
- Conditional field display
- Field validation rules
- Approval workflows per account type

### User Management
- Multi-user accounts (business accounts with multiple users)
- User roles and permissions
- Custom permission overrides per user
- Account-scoped permissions
- User profile management

### Registration System
- Public registration forms
- Dynamic form generation based on account type
- Auto-approve or manual approval workflows
- Email verification
- Custom registration workflows

### Permission System
- Role-based permissions
- Custom user permissions (grant/deny)
- Account-scoped permissions
- Permission categories
- Hierarchical permissions

### Security Features
- Password strength requirements
- Account locking after failed attempts
- Session management
- Login history tracking
- Two-factor authentication support
- Email verification

## Usage

### Admin Interface

Access the admin dashboard at:
```
/admin/components/access/admin/index.php
```

### Frontend Pages

- Registration: `/admin/components/access/frontend/register.php`
- Login: `/admin/components/access/frontend/login.php`
- Profile: `/admin/components/access/frontend/profile.php`
- Forgot Password: `/admin/components/access/frontend/forgot-password.php`

### API Functions

All functions are prefixed with `access_`. See `docs/API.md` for complete documentation.

Example:
```php
require_once 'admin/components/access/includes/config.php';

// Create user
$userId = access_create_user([
    'email' => 'user@example.com',
    'password_hash' => access_hash_password('password123'),
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Check permission
if (access_user_has_permission($userId, 'place_orders', $accountId)) {
    // User can place orders
}
```

## Configuration

Settings can be configured in the admin interface at:
```
/admin/components/access/admin/settings.php
```

## Uninstallation

To uninstall the component:

1. Navigate to `/admin/components/access/uninstall.php`
2. Review the warnings
3. Confirm uninstallation

A backup will be created automatically before uninstallation.

## Documentation

- [Installation Guide](INSTALL.md)
- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)
- [Permissions System](docs/PERMISSIONS.md)

## Version

Current version: 1.0.0

## License

This component is part of the bespokecabinetry.au project.

