# Layout Component - API Documentation

## Functions

### layout_start_layout()

Starts the layout wrapper, outputs HTML head and opens body tag.

**Signature:**
```php
layout_start_layout(string $pageTitle = 'Admin', bool $requireAuth = true, string $currPage = null): void
```

**Parameters:**
- `$pageTitle` (string): Page title displayed in browser tab. Default: 'Admin'
- `$requireAuth` (bool): Whether to require authentication. Default: true
- `$currPage` (string|null): Current page identifier for menu highlighting. Default: null

**Example:**
```php
layout_start_layout('Dashboard', true, 'dashboard');
```

### layout_end_layout()

Ends the layout wrapper, closes content areas and outputs closing HTML tags.

**Signature:**
```php
layout_end_layout(): void
```

**Example:**
```php
layout_end_layout();
```

### layout_is_component_installed()

Checks if a component is installed.

**Signature:**
```php
layout_is_component_installed(string $componentName): bool
```

**Parameters:**
- `$componentName` (string): Component name (e.g., 'header', 'menu_system', 'footer')

**Returns:** bool - True if component is installed

**Example:**
```php
if (layout_is_component_installed('header')) {
    // Header component is available
}
```

### layout_get_component_include_path()

Gets the include path for a component's main file.

**Signature:**
```php
layout_get_component_include_path(string $componentName, string $includeFile): string|null
```

**Parameters:**
- `$componentName` (string): Component name
- `$includeFile` (string): Include file name (e.g., 'header.php')

**Returns:** string|null - Full path to include file or null if not found

### layout_render_component_placeholder()

Renders a placeholder for a missing component.

**Signature:**
```php
layout_render_component_placeholder(string $componentName, string $gridArea): string
```

**Parameters:**
- `$componentName` (string): Component name
- `$gridArea` (string): CSS grid area name

**Returns:** string - HTML for placeholder

### layout_include_component_or_placeholder()

Includes a component if available, or renders placeholder.

**Signature:**
```php
layout_include_component_or_placeholder(string $componentName, string $includeFile, string $gridArea): void
```

**Parameters:**
- `$componentName` (string): Component name
- `$includeFile` (string): Include file name
- `$gridArea` (string): CSS grid area name

**Example:**
```php
layout_include_component_or_placeholder('header', 'header.php', 'header');
```

### layout_get_parameter()

Gets parameter value from layout_parameters table or base system.

**Signature:**
```php
layout_get_parameter(string $section, string $name, mixed $default = null): mixed
```

**Parameters:**
- `$section` (string): Parameter section
- `$name` (string): Parameter name
- `$default` (mixed): Default value if not found

**Returns:** mixed - Parameter value or default

### layout_get_db_connection()

Gets database connection (tries base system first, then component's own).

**Signature:**
```php
layout_get_db_connection(): mysqli|null
```

**Returns:** mysqli|null - Database connection or null on failure

## CSS Classes

### Layout Structure

- `.admin-layout` - Main layout container (CSS Grid)
- `.admin-main` - Main content area
- `.admin-content` - Content wrapper
- `.admin-content-grid` - Dynamic column grid

### Placeholders

- `.layout-placeholder` - Placeholder container
- `.layout-placeholder--header` - Header placeholder
- `.layout-placeholder--menu` - Menu placeholder
- `.layout-placeholder--footer` - Footer placeholder

## CSS Variables

The component uses CSS variables that map to base system variables:

- `--menu-width` - Menu width (default: 280px)
- `--header-height` - Header height (default: 100px)
- `--footer-height` - Footer height (default: 60px)
- `--menu-active-text-color` - Active menu text color

## Usage Examples

### Basic Page

```php
<?php
require_once __DIR__ . '/components/layout/includes/layout.php';
layout_start_layout('My Page');
?>
<h1>Page Content</h1>
<p>This is my page content.</p>
<?php
layout_end_layout();
?>
```

### Page with Custom Identifier

```php
<?php
require_once __DIR__ . '/components/layout/includes/layout.php';
layout_start_layout('Dashboard', true, 'dashboard');
?>
<div>Dashboard content</div>
<?php
layout_end_layout();
?>
```

### Page Without Authentication

```php
<?php
require_once __DIR__ . '/components/layout/includes/layout.php';
layout_start_layout('Public Page', false);
?>
<div>Public content</div>
<?php
layout_end_layout();
?>
```

