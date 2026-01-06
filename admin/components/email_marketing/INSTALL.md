# Email Marketing Component - Installation Guide

## Quick Start

1. Navigate to `/admin/components/email_marketing/install.php`
2. Review auto-detected settings
3. Click "Install Email Marketing Component"
4. Configure email settings

## Manual Installation

### Step 1: Database Setup

The installer will automatically create all 25 database tables. If you need to run the SQL manually:

```bash
mysql -u username -p database_name < admin/components/email_marketing/install/database.sql
```

### Step 2: Configuration

After installation, edit `config.php` to configure:
- Email sending method (SMTP or service provider)
- SMTP settings (if using SMTP)
- Service provider API keys (if using service)
- Queue settings
- Rate limits

### Step 3: Email Configuration

1. Go to Settings in the admin interface
2. Configure your email sending method:
   - **SMTP**: Enter host, port, encryption, username, password
   - **Service Provider**: Select provider (SendGrid, Mailgun, AWS SES) and enter API key
3. Set default from email and name

## CLI Installation

```bash
# Auto mode (uses detected settings)
php admin/components/email_marketing/install.php --auto

# Silent mode (no prompts)
php admin/components/email_marketing/install.php --silent --yes-to-all
```

## Post-Installation

1. **Configure Email Settings**: Go to Settings and configure your email sending method
2. **Create Templates**: Create your first email template
3. **Set Up Campaigns**: Create your first campaign
4. **Configure Data Mining**: Set up lead generation sources (if needed)
5. **Set Up Loyalty Points**: Configure loyalty point rules and tiers

## Troubleshooting

### Database Connection Failed
- Verify database credentials in `config/database.php`
- Check database server is running
- Verify database user has CREATE TABLE permissions

### Email Not Sending
- Check SMTP/service provider settings
- Verify API keys are correct
- Check email queue status
- Review error logs

### Menu Links Not Created
- Verify menu_system component is installed
- Check menu_system_menus table exists
- Re-run installer if needed

## Uninstallation

To uninstall the component:

1. Navigate to `/admin/components/email_marketing/uninstall.php`
2. Click "Uninstall Component"
3. All tables and data will be removed
4. config.php will be deleted

Or via CLI:
```bash
php admin/components/email_marketing/uninstall.php --silent --yes
```

**Warning**: Uninstallation will permanently delete all email marketing data!

