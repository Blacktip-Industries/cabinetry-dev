# CSS Normalization - Handoff Document for New Chat

## Context Summary

This document provides complete context for continuing the CSS normalization work in a new chat session.

## Project Overview

We're working on a component-based PHP system where each component has its own CSS files. We've just completed updating the naming standards to require full component names (with hyphens) in CSS variables instead of abbreviations.

## Completed Work

### 1. Standards Updates (COMPLETED)
- ✅ Updated `_standards/NAMING_STANDARDS.md` to require `--{component_name}-{property}` format
- ✅ Updated `_standards/COMPONENT_CREATION_PROCEDURE.md` with new CSS variable format
- ✅ Updated `admin/components/NAMING_STANDARDS.md` (copy)
- ✅ Updated `admin/components/COMPONENT_CREATION_PROCEDURE.md` (copy)
- ✅ Updated `.cursorrules` with new format
- ✅ Updated `_standards/.cursorrules-template` with new format
- ✅ Updated `_standards/README.md` verification tests

### 2. CSS Audit Tool (COMPLETED)
- ✅ Created `admin/tools/css-audit.php` - Comprehensive CSS audit script
- ✅ Created `admin/tools/css-normalization-report.html` - HTML report template

## Current CSS Variable Format

**New Standard:**
- Component name: `menu_system` (underscores - for PHP/DB/files)
- CSS variable: `--menu-system-menu-width` (hyphens - for CSS)
- **Conversion Rule**: Replace underscores with hyphens when creating CSS variables

**Examples:**
- `menu_system` → `--menu-system-menu-width`
- `payment_processing` → `--payment-processing-transaction-timeout`
- `email_marketing` → `--email-marketing-campaign-bg`

## Components to Normalize

Based on directory scan, these components have CSS files that need normalization:

1. **theme** (base system - should be done first)
2. **menu_system** (currently uses `--ms-`)
3. **access** (currently uses `--ac-`)
4. **product_options** (currently uses `--po-`)
5. **email_marketing** (currently uses `--em-`)
6. **seo_manager** (currently uses `--sm-`)
7. **savepoints** (needs check)
8. **url_routing** (needs check)
9. **layout** (needs check)

## Backward Compatibility Decision

**Decision: NO backward compatibility needed**

- No components are deployed in production
- This is a development environment
- We can rename variables directly without aliases
- No need for temporary fallback variables

## Next Steps (To Do in New Chat)

### Step 1: Run CSS Audit
```bash
# Audit all components
php admin/tools/css-audit.php

# Audit specific component
php admin/tools/css-audit.php menu_system

# Generate HTML report
php admin/tools/css-audit.php --format=html
```

### Step 2: Normalize Components (in order)

1. **Normalize theme component first** (base system)
   - Ensure all base variables are properly defined
   - This is the foundation for all other components

2. **Normalize menu_system**
   - Convert `--ms-*` → `--menu-system-*`
   - Replace hardcoded values with theme variables
   - Update all references in CSS files

3. **Normalize access**
   - Convert `--ac-*` → `--access-*`
   - Replace hardcoded values

4. **Normalize remaining components**
   - product_options (`--po-` → `--product-options-*`)
   - email_marketing (`--em-` → `--email-marketing-*`)
   - seo_manager (`--sm-` → `--seo-manager-*`)
   - savepoints, url_routing, layout (check current format)

### Step 3: Update Component Installers
- Update all `install.php` files to generate CSS variables in new format
- Update variable generation logic in installers
- Ensure new format is used for future installations

### Step 4: Verify Normalization
- Run CSS audit again: `php admin/tools/css-audit.php`
- Verify all components meet 70%+ compliance
- Verify all variables use correct naming format

## Key Files Reference

### Standards Files
- `_standards/NAMING_STANDARDS.md` - Complete naming conventions
- `_standards/COMPONENT_CREATION_PROCEDURE.md` - Component creation guide
- `admin/components/NAMING_STANDARDS.md` - Copy in components folder
- `admin/components/COMPONENT_CREATION_PROCEDURE.md` - Copy in components folder

### Tools
- `admin/tools/css-audit.php` - CSS audit script
- `admin/tools/css-normalization-report.html` - HTML report template

### Component CSS Files (typical structure)
- `{component}/assets/css/{component}.css` - Main CSS file
- `{component}/assets/css/variables.css` - CSS variables (auto-generated)

## Important Notes

1. **Component Name Format**: Always use `lowercase_with_underscores` in PHP/DB/file contexts
2. **CSS Variable Format**: Convert underscores to hyphens: `menu_system` → `menu-system`
3. **No Backward Compatibility**: Since nothing is deployed, rename directly
4. **Theme Variables First**: Always normalize theme component before others
5. **Compliance Target**: Minimum 70% compliance score per component

## Example Conversion

**Before (Old Format):**
```css
:root {
    --ms-menu-width: 280px;
    --ms-menu-bg-color: var(--ms-bg-dark, #262d34);
    --ms-color-primary: var(--color-primary, #ff6c2f);
}
```

**After (New Format):**
```css
:root {
    --menu-system-menu-width: 280px;
    --menu-system-menu-bg-color: var(--menu-system-bg-dark, #262d34);
    --menu-system-color-primary: var(--color-primary, #ff6c2f);
}
```

**Note**: Component-specific aliases (like `--menu-system-color-primary`) should ideally be removed in favor of direct theme variable usage (`var(--color-primary)`), but can be kept if needed for component-specific overrides.

## Questions to Ask User (if needed)

1. Should we remove component-specific color aliases and use theme variables directly?
2. Do any components have JavaScript that references CSS variables? (Need to update those too)
3. Should we create a migration script to update database-stored CSS variable names?

## Success Criteria

- ✅ All CSS variables use format: `--{component_name}-{property}` (with hyphens)
- ✅ No hardcoded colors (use theme variables)
- ✅ No hardcoded spacing (use theme variables)
- ✅ Minimum 70% compliance score for all components
- ✅ All components pass CSS audit
- ✅ New component installers generate correct format

---

**Last Updated**: 2025-01-27  
**Status**: Ready for CSS normalization work  
**Next Action**: Run CSS audit tool to see current state

