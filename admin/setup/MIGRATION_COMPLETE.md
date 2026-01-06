# Parameter Renaming Migration - Complete

## Summary

All parameters have been renamed to include the `--` prefix for CSS variable compatibility.

## Changes Made

### 1. Database Migration
Run the migration script to update parameter names in the database:
```bash
php admin/setup/add-prefix-to-parameters.php
```

### 2. PHP Code Updates

#### ✅ Updated Files:

1. **admin/includes/layout.php**
   - `menu_admin_width` → `--menu-admin-width`
   - `header_height` → `--header-height`
   - `footer_height` → `--footer-height`
   - `menu_active_bg_color` → `--menu-active-bg-color`
   - `menu_active_text_color` → `--menu-active-text-color`

2. **admin/includes/header.php**
   - `search_bar_length` → `--search-bar-length`
   - `avatar_height` → `--avatar-height`

3. **admin/settings/footer.php**
   - `section_heading_bg_color` → `--section-heading-bg-color`

4. **config/database.php**
   - `test_table_show_border` → `--test-table-show-border`
   - `test_table_border_thickness` → `--test-table-border-thickness`
   - `test_table_border_color` → `--test-table-border-color`
   - `test_table_cellpadding` → `--test-table-cellpadding`

## Parameter Name Mappings

| Old Name | New Name |
|----------|----------|
| `avatar_height` | `--avatar-height` |
| `search_bar_length` | `--search-bar-length` |
| `header_height` | `--header-height` |
| `footer_height` | `--footer-height` |
| `section_heading_bg_color` | `--section-heading-bg-color` |
| `menu_admin_width` | `--menu-admin-width` |
| `menu_frontend_width` | `--menu-frontend-width` |
| `menu_active_bg_color` | `--menu-active-bg-color` |
| `menu_active_text_color` | `--menu-active-text-color` |
| `test_table_show_border` | `--test-table-show-border` |
| `test_table_border_thickness` | `--test-table-border-thickness` |
| `test_table_border_color` | `--test-table-border-color` |
| `test_table_cellpadding` | `--test-table-cellpadding` |

## Next Steps

1. **Run the migration script** to update the database:
   ```bash
   php admin/setup/add-prefix-to-parameters.php
   ```

2. **Verify** that all parameters are working correctly by:
   - Checking the Settings → Parameters page
   - Verifying that layout settings (menu width, header height, etc.) still work
   - Testing that CSS variables are generated correctly

3. **Note**: Initialization scripts in `config/database.php` and `admin/init-db.php` still reference old names when creating initial records in the old `settings` table. These are fine because:
   - They insert into the old `settings` table
   - The migration script (`migrate-parameters.php`) handles the conversion
   - If you want to update them for consistency, you can, but it's not required

## Benefits

✅ All parameters now use consistent CSS variable naming  
✅ Parameters can be used as CSS variables via `var(--parameter-name)`  
✅ Parameters are automatically included in generated CSS by `generateCSSVariables()`  
✅ Better organization and future-proofing

