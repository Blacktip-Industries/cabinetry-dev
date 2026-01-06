# Menu System Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/menu_system/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Complete and Stable
- **Version**: 1.0.0

## Component Overview
Portable menu management system with icon management, file protection, and automated installation. Fully self-contained with isolated database tables and functions. Provides complete CRUD operations for menu items with support for section headings, pinned items, parent-child relationships, and page identifiers for menu highlighting.

## Recent Work
- Component fully completed and documented
- All core features implemented and tested
- File protection system with backup functionality implemented
- Icon management system integrated with SVG support
- Automated installation/uninstallation system completed
- CSS variable standardization completed
- Smart file update logic (only updates when page_identifier changes)
- Automatic backup system (keeps last 10 per file)

## Key Decisions Made
- Used `menu_system_` prefix for all database tables and functions
- Implemented three file protection modes (full, update, disabled)
- Smart file update logic (only updates when page_identifier changes)
- Icon system with SVG support and viewBox handling
- Automatic backup system (keeps last 10 per file)
- CSS variable standardization maps to base system CSS variables
- Component-specific variables use `--menu-system-*` format
- Theme variables used directly where possible

## Files Structure
- `core/database.php` - Database functions with `menu_system_` prefix
- `core/icons.php` - Icon management functions
- `core/file_protection.php` - File protection and backup system
- `includes/sidebar.php` - Sidebar rendering component
- `includes/icon_picker.php` - Icon picker component
- `includes/config.php` - Configuration helper functions
- `admin/menus.php` - Menu management interface
- `admin/icons.php` - Icon management interface
- `assets/css/menu_system.css` - Component CSS with standardized variables
- `assets/css/variables.css` - CSS variables template
- `assets/js/sidebar.js` - Sidebar toggle functionality
- `assets/js/icon-picker.js` - Icon picker JavaScript
- `COMPLETION_SUMMARY.md` - Complete documentation

## Next Steps
- [ ] Component is complete and ready for use
- [ ] Future enhancements could include:
  - Menu item permissions/access control
  - Menu analytics/tracking
  - Multi-language menu support
  - Menu item caching for performance

## Important Notes
- Component is fully portable and self-contained
- File protection is enabled by default (full mode)
- Icon system supports SVG with proper viewBox handling
- Menu items support parent-child relationships and section headings
- Page identifier system for automatic menu highlighting
- File protection creates backups before updating files
- Automatic cleanup keeps last 10 backups per file
- CSS variables map to base system for consistency

## Integration Points
- Integrates with layout component (automatically detected and included)
- File protection can update PHP files with page_identifier values
- Uses theme CSS variables directly where possible
- Component-specific variables only for menu-specific properties (width, indents, icon sizes)

## Maintenance Instructions
**After each work session, update this file:**
1. Update "Last Updated" date
2. Add to "Recent Work" what you accomplished
3. Update "Files Structure" if new files created
4. Update "Next Steps" - check off completed items, add new ones
5. Add to "Important Notes" any gotchas or important context
6. Document any new decisions in "Key Decisions Made"

---

## Chat History Summary
- **Session 1**: Component structure and core functionality
- **Session 2**: File protection system and icon management
- **Session 3**: Finalization, testing, and documentation

