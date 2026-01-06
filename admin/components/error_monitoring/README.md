# Error Monitoring Component

A comprehensive error monitoring component that provides system-wide error logging, monitoring, and notification capabilities for the entire website and all installed components.

## Features

- **System-Wide Error Monitoring** - Monitor all PHP errors, exceptions, warnings, and notices
- **Database Logging** - Store all errors in database with full context
- **Error Display for Admins** - Notification bar and floating widget with error count
- **Email Notifications** - Configurable immediate, digest, and threshold-based notifications
- **Error Log Interface** - Comprehensive admin interface for viewing and managing errors
- **Error Diagnosis Tools** - Code context, variable values, stack traces, and suggested fixes
- **Error Severity Levels** - Critical, High, Medium, Low with configurable monitoring
- **Error Configuration** - Configurable error types, retention periods, and auto-cleanup
- **Component Detection** - Automatically monitors all installed components
- **Advanced Features** - Error grouping, correlation, analytics, forecasting, and more

## Installation

### Web Installation

1. Navigate to `/admin/components/error_monitoring/install.php` in your browser
2. Review auto-detected settings
3. Click "Install Error Monitoring Component"

### CLI Installation

```bash
php admin/components/error_monitoring/install.php --auto
```

### Silent Installation

```bash
php admin/components/error_monitoring/install.php --silent --yes-to-all
```

## Uninstallation

### Web Uninstallation

1. Navigate to `/admin/components/error_monitoring/uninstall.php`
2. Confirm uninstallation (backup will be created automatically)

### CLI Uninstallation

```bash
php admin/components/error_monitoring/uninstall.php --auto --backup --yes
```

## Usage

### Automatic Error Monitoring

Once installed, the component automatically:
- Registers PHP error and exception handlers
- Monitors all components installed on the website
- Logs errors to the database
- Displays notifications to admin users
- Sends email notifications based on configured rules

### Admin Interface

Access the error monitoring interface at:
- **Error Log**: `/admin/components/error_monitoring/admin/error-log.php`
- **Settings**: `/admin/components/error_monitoring/admin/settings.php`
- **Analytics**: `/admin/components/error_monitoring/admin/analytics.php`

### Notification Bar & Widget

When logged in as admin:
- **Notification Bar**: Displays at top of page with recent critical errors
- **Floating Widget**: Persistent icon with error count badge, click to view details

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)
- Access to database configuration
- Admin access to the website

## Documentation

- **Installation Guide**: See `INSTALL.md`
- **API Documentation**: See `docs/API.md`
- **Integration Guide**: See `docs/INTEGRATION.md`

## Support

For issues or questions, please refer to the documentation or contact the component maintainer.

---

**Version**: 1.0.0  
**Last Updated**: 2025-01-27

