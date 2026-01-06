# Theme Component

A comprehensive theme component that provides a robust CSS variable system and complete design system components. This component serves as the foundation for all other components, ensuring consistent styling and seamless integration.

## Features

- **Comprehensive CSS Variable System** - All design tokens (colors, typography, spacing, shadows, borders, transitions, breakpoints, z-index)
- **Multi-Theme Support** - Light, dark, and custom theme variants
- **Complete Design System Components** - All UI components (buttons, forms, cards, modals, tables, badges, alerts, navigation, dropdowns, tooltips, progress, avatars, dividers, empty states)
- **Design System Preview Page** - Interactive preview of all components based on current theme settings
- **Component Classes Approach** - Base component classes that other components can extend
- **Database-Driven Parameters** - All theme settings stored in `theme_parameters` table
- **Optional Modular JavaScript** - Base JS functionality that components can extend
- **Automatic Menu Link Creation** - Creates menu entries in menu_system during installation

## Installation

### Web Installation

1. Navigate to `/admin/components/theme/install.php` in your browser
2. Review auto-detected settings
3. Click "Install Theme Component"

### CLI Installation

```bash
php admin/components/theme/install.php --auto
```

### Silent Installation

```bash
php admin/components/theme/install.php --silent
```

## Uninstallation

### Web Uninstallation

1. Navigate to `/admin/components/theme/uninstall.php`
2. Confirm uninstallation (backup will be created automatically)

### CLI Uninstallation

```bash
php admin/components/theme/uninstall.php --auto
```

## Usage

### Loading Theme Assets

```php
require_once __DIR__ . '/admin/components/theme/includes/theme-loader.php';

// Load CSS and JS
echo theme_load_assets(true);
```

### Accessing Theme Parameters

```php
require_once __DIR__ . '/admin/components/theme/core/database.php';

// Get a parameter
$primaryColor = theme_get_parameter('colors', '--color-primary', '#FF6C2F');

// Get all parameters in a section
$colorParams = theme_get_section_parameters('colors');
```

### Accessing Design System Preview

Navigate to: `/admin/components/theme/admin/preview.php`

## File Structure

```
admin/components/theme/
├── admin/              # Admin interface pages
│   └── preview.php     # Design system preview page
├── assets/             # CSS and JavaScript
│   ├── css/
│   │   ├── theme.css   # Main theme CSS
│   │   ├── variables.css # Generated CSS variables
│   │   └── components/ # Component CSS files
│   └── js/
│       ├── theme.js     # Theme switching JS
│       └── preview.js   # Preview page JS
├── core/               # Core PHP functions
├── includes/           # Reusable includes
├── install/            # Installation files
└── docs/               # Documentation
```

## Database Tables

All tables are prefixed with `theme_`:

- `theme_config` - Component configuration and metadata
- `theme_parameters` - All theme settings (colors, typography, spacing, etc.)
- `theme_themes` - Theme variants (light, dark, custom)

## Integration

Components can reference theme CSS variables:

```css
.my-component {
    color: var(--color-primary);
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
}
```

Components can extend base component classes:

```css
.my-button {
    /* Extends .btn */
    @extend .btn;
    /* Add custom styles */
}
```

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB with mysqli extension
- JSON extension
- mbstring extension

## License

[Your License Here]

## Support

For issues or questions, please refer to the documentation in `/admin/components/theme/docs/`.

