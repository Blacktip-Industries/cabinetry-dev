# Payment Processing Component - Installation Guide

## Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB 5.7 or higher
- mysqli extension
- JSON extension
- mbstring extension
- OpenSSL extension
- curl extension

## Installation Methods

### Web Installation (Recommended)

1. Navigate to `/admin/components/payment_processing/install.php` in your browser
2. The installer will auto-detect:
   - Database configuration
   - Base system paths
   - CSS variables
   - Component dependencies
3. Review the detected settings
4. Click "Install Payment Processing Component"
5. Configure payment gateways after installation

### CLI Installation

```bash
php admin/components/payment_processing/install.php --auto
```

With custom database settings:
```bash
php admin/components/payment_processing/install.php --auto --db-host=localhost --db-user=user --db-pass=pass --db-name=database
```

### Silent Installation

```bash
php admin/components/payment_processing/install.php --silent --yes-to-all
```

## Post-Installation

1. **Configure Gateways**: Go to Admin > Payment Processing > Gateways
2. **Set Default Currency**: Configure in Settings
3. **Configure Encryption**: Encryption keys are auto-generated, but review settings
4. **Set Up Webhooks**: Configure webhook URLs for each gateway
5. **Test Connection**: Use the gateway testing tools

## Configuration

### Gateway Configuration

Each gateway requires:
- API keys (stored encrypted)
- Webhook URL configuration
- Sandbox/test mode settings
- Supported currencies and payment methods

### Security Settings

- Encryption keys (auto-generated)
- Audit log retention period
- Fraud detection sensitivity
- Transaction timeout settings

### Integration Settings

- Access component integration (automatic if installed)
- Email Marketing integration (automatic if installed)
- Product Options integration (automatic if installed)

## Troubleshooting

### Installation Fails

- Check PHP version (7.4+ required)
- Verify database connection
- Check file permissions
- Review error logs

### Gateway Connection Issues

- Verify API keys are correct
- Check network connectivity
- Review gateway-specific error messages
- Test in sandbox mode first

### Webhook Issues

- Verify webhook URLs are accessible
- Check webhook logs in admin
- Ensure SSL certificate is valid
- Review gateway webhook documentation

## Uninstallation

To uninstall the component:

1. Navigate to `/admin/components/payment_processing/uninstall.php`
2. Review what will be removed
3. Create backup if needed
4. Click "Uninstall"

Or via CLI:
```bash
php admin/components/payment_processing/uninstall.php --auto --backup --yes
```

**Warning**: Uninstallation will remove all payment data. Ensure you have backups.

