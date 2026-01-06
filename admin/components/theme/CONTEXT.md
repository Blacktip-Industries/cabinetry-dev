# Theme Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/theme/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Advanced Features Complete
- **Version**: 1.1.0 (with device preview feature)

## Component Overview
Comprehensive theme management system with CSS variable standardization, device preview functionality, and complete design system components. Serves as the foundation for all other components, ensuring consistent styling and seamless integration. Features include multi-theme support, design system preview, and database-driven parameters.

## Recent Work
- Device Preview feature implemented
- Device preset management system created
- Advanced preview features (orientation, network throttling, performance metrics)
- Global access button (Ctrl+Shift+P) implemented
- Migration system for version updates created
- Screenshot capture functionality added
- Database table for device presets created
- Global JavaScript for floating button

## Key Decisions Made
- Used `theme_` prefix for all database tables and functions
- Device preview accessible globally via floating button
- Separate preset management for default and custom presets
- Performance monitoring integrated into preview
- Migration system for database schema updates
- CSS variable system as foundation for all components
- Database-driven parameters for all theme settings
- Component classes approach for extensibility

## Files Structure
- `admin/device-preview.php` - Main preview page
- `admin/device-presets.php` - Preset management
- `admin/preview.php` - Design system preview page
- `core/device-preview-manager.php` - Core functionality
- `core/database.php` - Database functions
- `core/functions.php` - Core helper functions
- `assets/js/device-preview.js` - Preview JavaScript
- `assets/js/device-preview-global.js` - Global button script
- `assets/css/device-preview.css` - Preview styles
- `assets/css/theme.css` - Main theme CSS
- `assets/css/variables.css` - Generated CSS variables
- `run-migration.php` - Migration runner
- `install/migrations/1.1.0.php` - Version 1.1.0 migration
- `DEVICE_PREVIEW_README.md` - Feature documentation

## Next Steps
- [ ] Component is feature-complete
- [ ] Future enhancements could include:
  - Additional device presets
  - More performance metrics
  - Design system preview integration with layout component
  - Theme export/import functionality
  - Advanced theme customization UI

## Important Notes
- Device preview feature requires migration to be run (`run-migration.php`)
- Global button accessible from any admin page (Ctrl+Shift+P)
- Supports both frontend pages and design system preview
- Performance metrics include load time, DOM ready, FCP, resource count
- Screenshot capture functionality available
- CSS variables serve as foundation for all other components
- Database-driven parameters allow runtime theme customization
- Multi-theme support (light, dark, custom)

## Integration Points
- Provides CSS variables for other components
- Device preview can preview any frontend page
- Design system preview shows all UI components
- Components can extend base component classes
- Automatic menu link creation during installation

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
- **Session 1**: Initial theme component creation
- **Session 2**: Device preview feature implementation
- **Session 3**: Advanced features and migration system

