# Savepoints Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/savepoints/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Complete
- **Version**: 1.0.0

## Component Overview
Portable, self-contained savepoint management system component for automated backup and restore functionality. Provides Git-based filesystem backups, database backups, GitHub integration, restore functionality, restore testing, configurable backup scope, and auto-installation.

## Recent Work
- Complete savepoint management system implemented
- Git-based filesystem backups with automatic Git commits
- Database backups with each savepoint
- GitHub integration with automatic push
- Restore functionality for both filesystem and database
- Restore testing with dry-run and separate environment testing
- Configurable backup scope (include/exclude directories)
- Auto-installation with auto-detection
- Automatic backup creation before uninstallation

## Key Decisions Made
- Used `savepoints_` prefix for all database tables and functions
- Git-based filesystem backups for version control
- Database backups stored with each savepoint
- GitHub integration for remote backup storage
- Restore testing capabilities for safety
- Configurable backup scope for flexibility
- Automatic backup before uninstallation

## Files Structure
- `core/` - 6 core PHP files (savepoints, git operations, database backups, restore, etc.)
- `admin/` - 5 admin interface files
- `assets/` - 3 assets (2 CSS, 1 JavaScript)
- `docs/` - Documentation (API, INTEGRATION)

## Next Steps
- [ ] Component is complete
- [ ] Future enhancements could include:
  - Additional backup storage options
  - Advanced restore options
  - Backup scheduling
  - Backup compression

## Important Notes
- Git-based filesystem backups require Git to be installed and configured
- Database backups are SQL dumps stored with each savepoint
- GitHub integration requires repository URL and optional Personal Access Token
- Restore functionality supports both filesystem and database restore
- Restore testing allows dry-run and separate environment testing
- Configurable backup scope allows excluding directories (uploads, node_modules, vendor, .git)
- Automatic backup creation before uninstallation for safety

## Integration Points
- **component_manager**: Coordinates with component_manager for component backups
- **Git**: Uses Git for filesystem version control
- **GitHub**: Pushes backups to GitHub repository

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
- **Session 1**: Initial savepoints component creation
- **Session 2**: Git integration and database backups
- **Session 3**: Restore functionality and testing

