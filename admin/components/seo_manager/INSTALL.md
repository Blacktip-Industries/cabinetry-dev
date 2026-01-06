# SEO Manager Component - Installation Guide

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Required PHP extensions: mysqli, json, mbstring, curl

## Installation Methods

### Web Installation

1. Navigate to `/admin/components/seo_manager/install.php` in your browser
2. Review the auto-detected configuration
3. Click "Install SEO Manager Component"
4. Follow the installation steps

### CLI Installation

```bash
php admin/components/seo_manager/install.php --auto
```

### Silent Installation

```bash
php admin/components/seo_manager/install.php --silent --yes-to-all
```

## Post-Installation

1. Configure AI API settings in the admin panel
2. Set up your first pages
3. Configure automation modes
4. Start optimizing!

## Uninstallation

To uninstall, navigate to `/admin/components/seo_manager/uninstall.php` or run:

```bash
php admin/components/seo_manager/uninstall.php --yes
```

A backup will be created before uninstallation.

