# Code Library Development Workspace

This is the dedicated development workspace for the Reusable Code Library System. Components are developed, tested, and refined here before being marked as production-ready in the library database.

## Structure

```
code-library-dev/
├── admin/
│   └── code-library/     # Library management interface
├── config/                # Database configuration and schema
├── includes/             # Shared includes
└── uploads/              # File uploads
```

## Setup

1. **Initialize Database**
   - Visit: `http://localhost/code-library-dev/admin/code-library/init.php`
   - Or run: `php config/init-database.php` (if PHP CLI is available)

2. **Access Management Interface**
   - Main Library: `http://localhost/code-library-dev/admin/code-library/index.php`
   - Components: `http://localhost/code-library-dev/admin/code-library/components.php`
   - Categories: `http://localhost/code-library-dev/admin/code-library/categories.php`

## Database

- **Database Name**: `code_library_db`
- **Host**: localhost
- **User**: root
- **Password**: (empty)

The database is separate from project databases and can be accessed from any workspace.

## Workflow

1. **Develop Components** in this workspace
2. **Test and Refine** until working perfectly
3. **Add to Library** via management interface
4. **Mark as Production Ready** when stable
5. **Install in Projects** from library database

## Technology Stack

- PHP (vanilla)
- HTML
- CSS (vanilla, with CSS variables)
- JavaScript (vanilla)
- MySQL

All components use the vanilla stack for maximum compatibility.

