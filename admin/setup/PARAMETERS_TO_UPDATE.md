# Parameters That Need `--` Prefix Update

## Summary

Parameters with the `--` prefix are **CSS custom properties (CSS variables)** that can be used in CSS via `var(--parameter-name)`. Parameters without the `--` prefix are currently used only in PHP as inline styles.

**Recommendation**: Add `--` prefix to ALL parameters for consistency and to enable CSS variable usage. This allows them to be used both as CSS variables AND as PHP inline styles.

---

## Parameters That SHOULD Have `--` Prefix

These parameters are used for styling/design tokens and should be CSS variables:

### Header Section
- `avatar_height` → `--avatar-height`
- `search_bar_length` → `--search-bar-length`

### Layout Section
- `header_height` → `--header-height`
- `footer_height` → `--footer-height`
- `section_heading_bg_color` → `--section-heading-bg-color`

### Menu Section
- `menu_admin_width` → `--menu-admin-width`
- `menu_frontend_width` → `--menu-frontend-width` (if exists)
- `menu_active_bg_color` → `--menu-active-bg-color`
- `menu_active_text_color` → `--menu-active-text-color`

### Layout Table Test Section (Testing parameters)
- `test_table_show_border` → `--test-table-show-border`
- `test_table_border_thickness` → `--test-table-border-thickness`
- `test_table_border_color` → `--test-table-border-color`
- `test_table_cellpadding` → `--test-table-cellpadding`

---

## SQL Update Script

Run this SQL to update all parameter names:

```sql
-- Header Section
UPDATE settings_parameters SET parameter_name = '--avatar-height' WHERE parameter_name = 'avatar_height';
UPDATE settings_parameters SET parameter_name = '--search-bar-length' WHERE parameter_name = 'search_bar_length';

-- Layout Section
UPDATE settings_parameters SET parameter_name = '--header-height' WHERE parameter_name = 'header_height';
UPDATE settings_parameters SET parameter_name = '--footer-height' WHERE parameter_name = 'footer_height';
UPDATE settings_parameters SET parameter_name = '--section-heading-bg-color' WHERE parameter_name = 'section_heading_bg_color';

-- Menu Section
UPDATE settings_parameters SET parameter_name = '--menu-admin-width' WHERE parameter_name = 'menu_admin_width';
UPDATE settings_parameters SET parameter_name = '--menu-active-bg-color' WHERE parameter_name = 'menu_active_bg_color';
UPDATE settings_parameters SET parameter_name = '--menu-active-text-color' WHERE parameter_name = 'menu_active_text_color';

-- Layout Table Test Section
UPDATE settings_parameters SET parameter_name = '--test-table-show-border' WHERE parameter_name = 'test_table_show_border';
UPDATE settings_parameters SET parameter_name = '--test-table-border-thickness' WHERE parameter_name = 'test_table_border_thickness';
UPDATE settings_parameters SET parameter_name = '--test-table-border-color' WHERE parameter_name = 'test_table_border_color';
UPDATE settings_parameters SET parameter_name = '--test-table-cellpadding' WHERE parameter_name = 'test_table_cellpadding';
```

---

## Code Updates Required

After updating the database, you'll need to update these PHP files:

### 1. `admin/includes/layout.php`
```php
// Change from:
$menuWidth = getParameter('Menu - Admin', 'menu_admin_width', '280');
$headerHeight = getParameter('Layout', 'header_height', '100');
$footerHeight = getParameter('Layout', 'footer_height', '60');
$menuActiveBgColor = getParameter('Menu', 'menu_active_bg_color', 'rgba(255, 255, 255, 0.15)');
$menuActiveTextColor = getParameter('Menu', 'menu_active_text_color', '#ffffff');

// To:
$menuWidth = getParameter('Menu - Admin', '--menu-admin-width', '280');
$headerHeight = getParameter('Layout', '--header-height', '100');
$footerHeight = getParameter('Layout', '--footer-height', '60');
$menuActiveBgColor = getParameter('Menu', '--menu-active-bg-color', 'rgba(255, 255, 255, 0.15)');
$menuActiveTextColor = getParameter('Menu', '--menu-active-text-color', '#ffffff');
```

### 2. `admin/includes/header.php`
```php
// Change from:
$searchBarLength = getParameter('Header', 'search_bar_length', '500');
$avatarHeight = getParameter('Header', 'avatar_height', '30');

// To:
$searchBarLength = getParameter('Header', '--search-bar-length', '500');
$avatarHeight = getParameter('Header', '--avatar-height', '30');
```

### 3. `admin/settings/footer.php`
```php
// Change from:
$sectionHeadingBgColor = getParameter('Layout', 'section_heading_bg_color', '#f5f5f5');

// To:
$sectionHeadingBgColor = getParameter('Layout', '--section-heading-bg-color', '#f5f5f5');
```

### 4. `config/database.php` (test table functions)
```php
// Change all instances of:
getParameter('Layout Table Test', 'test_table_show_border', ...)
getParameter('Layout Table Test', 'test_table_border_thickness', ...)
getParameter('Layout Table Test', 'test_table_border_color', ...)
getParameter('Layout Table Test', 'test_table_cellpadding', ...)

// To:
getParameter('Layout Table Test', '--test-table-show-border', ...)
getParameter('Layout Table Test', '--test-table-border-thickness', ...)
getParameter('Layout Table Test', '--test-table-border-color', ...)
getParameter('Layout Table Test', '--test-table-cellpadding', ...)
```

---

## Benefits of Adding `--` Prefix

1. **Consistency**: All design tokens use the same naming convention
2. **CSS Variable Support**: Can be used in CSS via `var(--parameter-name)`
3. **Better Organization**: Clear distinction between CSS variables and other config
4. **Future-Proof**: Easier to migrate to full CSS variable system
5. **Auto-Generation**: `generateCSSVariables()` will automatically include them in CSS

---

## Note

After making these changes, the parameters will be available as CSS variables in your generated CSS, but you can still use them in PHP the same way. The `--` prefix doesn't break PHP usage - it just enables CSS variable usage as well.

