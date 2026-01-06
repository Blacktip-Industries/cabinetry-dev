# Website Roadmap Planning System - Complete Export

**Export Date**: 2025-01-27  
**Purpose**: Standalone roadmap planning system for managing website development projects  
**Tech Stack**: PHP, HTML, JavaScript, CSS, MySQL (Vanilla - no frameworks)

---

## Chat History & Context Summary

### Initial Request
User wanted to create a detailed planning/roadmap system for website development that:
- Shows hierarchical structure (1. Main Category, 1.1 Sub-category, 1.1.1 Task)
- Has checkboxes to mark items for development
- Can add rich content (text fields, text areas, images, documents)
- Can scan existing websites and generate roadmaps
- Can start from scratch with AI generation
- Is reusable across multiple projects

### Key Decisions Made

1. **System Scope**: Hybrid master system (can be accessed from any project)
2. **AI Generation**: External API (OpenAI, Anthropic, Google) with multiple provider support
3. **Development Workflow**: Batch mode (check multiple items, then generate questions)
4. **Scan Depth**: Full analysis (database, code, config files, frontend assets)
5. **Project Management**: Config-based (master system stores project configs, connects on demand)
6. **Attachment Storage**: Master system storage (centralized)
7. **Credential Encryption**: Environment Variables + Encryption (AES-256-GCM)
8. **User Permissions**: Role-Based + Project-Based (Admin/Editor/Viewer)
9. **Versioning**: Full history tracking with revert capability
10. **Search**: Advanced search with full-text indexing
11. **Export Formats**: All formats (PDF, Excel, JSON, Markdown, HTML)

### Questions Asked & Answered

**Q: Should this be integrated or standalone?**
A: Hybrid - master system that can be accessed from any project

**Q: Which AI provider?**
A: Multiple providers with abstraction layer for flexibility

**Q: How should credentials be encrypted?**
A: Environment Variables + Encryption (most secure option)

**Q: User permissions model?**
A: Role-Based + Project-Based (most flexible and secure)

**Q: Versioning approach?**
A: Full history (most robust, allows recovery)

**Q: Export formats?**
A: All formats (most flexible)

**Q: Search depth?**
A: Advanced search (good balance of features and performance)

---

## Cursor Plan File Format

Save this section as `.cursor/plans/website_roadmap_planning_system.plan.md` in your new workspace:

```yaml
---
name: Website Roadmap Planning System
overview: Build a comprehensive, reusable roadmap planning system that can scan existing websites, generate hierarchical roadmaps, manage multiple projects, and provide detailed planning interfaces with rich content support (text, images, documents) for advanced website development.
todos:
  - id: create-db-tables
    content: Create all roadmap database tables in config/roadmap_database.php with proper relationships and indexes
    status: pending
  - id: project-management
    content: Build project management interface (admin/roadmap/projects.php) for creating, editing, and managing website projects with encrypted credential storage
    status: pending
    dependencies:
      - create-db-tables
  - id: database-scanner
    content: Implement database scanner that connects to project databases and extracts table structures, relationships, and patterns
    status: pending
    dependencies:
      - project-management
  - id: code-scanner
    content: Implement code file scanner that analyzes PHP files, detects functions, classes, routes, and frontend assets
    status: pending
    dependencies:
      - project-management
  - id: scan-results-storage
    content: Create system to store and display scan results in roadmap_scan_results table with JSON data structure
    status: pending
    dependencies:
      - database-scanner
      - code-scanner
  - id: ai-api-integration
    content: Set up AI API integration (OpenAI/Claude) with secure key storage, rate limiting, and error handling in admin/roadmap/includes/ai_functions.php
    status: pending
    dependencies:
      - create-db-tables
  - id: initial-roadmap-generation
    content: Build AI-powered initial roadmap generation from user prompts, parsing responses into hierarchical database structure
    status: pending
    dependencies:
      - ai-api-integration
  - id: scan-based-generation
    content: Implement roadmap generation from scan results, analyzing existing structure and suggesting improvements
    status: pending
    dependencies:
      - scan-results-storage
      - ai-api-integration
  - id: hierarchical-interface
    content: Create main roadmap interface (admin/roadmap/index.php) with tree view, expandable sections, and drag-and-drop reordering
    status: pending
    dependencies:
      - create-db-tables
  - id: item-editor
    content: Build roadmap item editor (admin/roadmap/editor.php) with rich text editing, custom fields, and metadata management
    status: pending
    dependencies:
      - hierarchical-interface
  - id: checkbox-system
    content: Implement checkbox system for marking items for development with batch selection and visual indicators
    status: pending
    dependencies:
      - hierarchical-interface
  - id: attachment-system
    content: Create attachment management system with file upload, storage in uploads/roadmaps/, image thumbnails, and document handling
    status: pending
    dependencies:
      - item-editor
  - id: question-generator
    content: Build batch question generation system that creates contextual questions for selected roadmap items using AI
    status: pending
    dependencies:
      - checkbox-system
      - ai-api-integration
  - id: qa-interface
    content: Create interactive Q&A interface (admin/roadmap/questions.php) for answering generated questions with AI suggestions
    status: pending
    dependencies:
      - question-generator
  - id: api-endpoints
    content: Build RESTful API endpoints in admin/roadmap/api/ for projects, items, categories, attachments, scanner, AI, and questions
    status: pending
    dependencies:
      - hierarchical-interface
      - attachment-system
  - id: admin-menu-integration
    content: Integrate roadmap system into existing admin menu system and navigation
    status: pending
    dependencies:
      - hierarchical-interface
  - id: encryption-system
    content: Implement environment variable-based encryption for database credentials with AES-256-GCM encryption
    status: pending
    dependencies:
      - create-db-tables
  - id: permissions-system
    content: Build role-based and project-based permissions system with admin/editor/viewer roles
    status: pending
    dependencies:
      - create-db-tables
      - encryption-system
  - id: history-versioning
    content: Implement full history tracking system for all roadmap changes with revert capability
    status: pending
    dependencies:
      - create-db-tables
  - id: multiple-ai-providers
    content: Build AI provider abstraction layer supporting OpenAI, Anthropic, Google with fallback mechanisms
    status: pending
    dependencies:
      - ai-api-integration
      - encryption-system
  - id: advanced-search
    content: Implement advanced search with full-text indexing, filters, saved searches, and MySQL FULLTEXT indexes
    status: pending
    dependencies:
      - hierarchical-interface
      - create-db-tables
  - id: export-system
    content: Build export system supporting PDF, Excel/CSV, JSON, Markdown, and HTML formats
    status: pending
    dependencies:
      - hierarchical-interface
---
```

---

## Complete Plan Document

[The full plan document content from the plan file would go here - see the original plan file for the complete content]

---

## Database Schema

### Complete SQL for All Tables

```sql
-- 1. roadmap_projects
CREATE TABLE IF NOT EXISTS roadmap_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    db_host VARCHAR(255),
    db_name VARCHAR(255),
    db_user VARCHAR(255),
    db_pass VARCHAR(255),
    encryption_method VARCHAR(50) DEFAULT 'AES-256-GCM',
    project_root_path VARCHAR(500),
    project_url VARCHAR(500),
    status ENUM('active', 'archived', 'planning') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_scan_at TIMESTAMP NULL,
    scan_results JSON,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. roadmap_categories
CREATE TABLE IF NOT EXISTS roadmap_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    parent_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    level INT DEFAULT 1,
    path VARCHAR(500),
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES roadmap_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES roadmap_categories(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_parent (parent_id),
    INDEX idx_path (path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. roadmap_items
CREATE TABLE IF NOT EXISTS roadmap_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    item_type ENUM('task', 'feature', 'requirement', 'database', 'page', 'api') DEFAULT 'task',
    order_index INT DEFAULT 0,
    path VARCHAR(500),
    status ENUM('pending', 'in_progress', 'completed', 'blocked') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    estimated_hours DECIMAL(10,2) DEFAULT 0,
    assigned_to VARCHAR(255),
    due_date DATE NULL,
    is_selected_for_dev TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES roadmap_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_selected (is_selected_for_dev),
    INDEX idx_path (path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. roadmap_item_fields
CREATE TABLE IF NOT EXISTS roadmap_item_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    field_type ENUM('text', 'textarea', 'number', 'date', 'select', 'checkbox') DEFAULT 'text',
    field_name VARCHAR(255) NOT NULL,
    field_label VARCHAR(255),
    field_value TEXT,
    field_order INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES roadmap_items(id) ON DELETE CASCADE,
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. roadmap_attachments
CREATE TABLE IF NOT EXISTS roadmap_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'document', 'other') DEFAULT 'other',
    mime_type VARCHAR(100),
    file_size INT DEFAULT 0,
    description TEXT,
    uploaded_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES roadmap_items(id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_type (file_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. roadmap_scan_results
CREATE TABLE IF NOT EXISTS roadmap_scan_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    scan_type ENUM('database', 'code', 'full') DEFAULT 'full',
    scan_data JSON,
    tables_found JSON,
    files_analyzed JSON,
    features_detected JSON,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES roadmap_projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. roadmap_ai_prompts
CREATE TABLE IF NOT EXISTS roadmap_ai_prompts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    prompt_type ENUM('initial_generation', 'refinement', 'questions') DEFAULT 'initial_generation',
    user_prompt TEXT,
    ai_response TEXT,
    generated_roadmap JSON,
    api_used VARCHAR(50),
    tokens_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES roadmap_projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. roadmap_development_questions
CREATE TABLE IF NOT EXISTS roadmap_development_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('clarification', 'technical', 'design', 'requirement') DEFAULT 'clarification',
    answer TEXT NULL,
    suggestions TEXT,
    order_index INT DEFAULT 0,
    is_answered TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES roadmap_items(id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_answered (is_answered)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. roadmap_user_permissions
CREATE TABLE IF NOT EXISTS roadmap_user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES roadmap_projects(id) ON DELETE CASCADE,
    UNIQUE KEY user_project (user_id, project_id),
    INDEX idx_user (user_id),
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. roadmap_history
CREATE TABLE IF NOT EXISTS roadmap_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('project', 'category', 'item', 'field', 'attachment') NOT NULL,
    entity_id INT NOT NULL,
    action ENUM('create', 'update', 'delete', 'status_change') NOT NULL,
    old_data JSON,
    new_data JSON,
    changed_by INT NOT NULL,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at),
    INDEX idx_user (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. roadmap_ai_providers
CREATE TABLE IF NOT EXISTS roadmap_ai_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(50) NOT NULL UNIQUE,
    api_key_encrypted TEXT,
    api_endpoint VARCHAR(500),
    model_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    rate_limit_per_minute INT DEFAULT 60,
    cost_per_1k_tokens DECIMAL(10,4) DEFAULT 0,
    max_tokens INT DEFAULT 4000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. roadmap_search_index
CREATE TABLE IF NOT EXISTS roadmap_search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('project', 'category', 'item') NOT NULL,
    entity_id INT NOT NULL,
    searchable_text TEXT,
    tags JSON,
    metadata JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT INDEX idx_search (searchable_text),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## File Structure

```
roadmap-system/
├── .cursor/
│   └── plans/
│       └── website_roadmap_planning_system.plan.md
├── admin/
│   ├── roadmap/
│   │   ├── index.php
│   │   ├── projects.php
│   │   ├── scanner.php
│   │   ├── scan_handler.php
│   │   ├── generate.php
│   │   ├── editor.php
│   │   ├── attachments.php
│   │   ├── questions.php
│   │   ├── api/
│   │   │   ├── projects.php
│   │   │   ├── items.php
│   │   │   ├── categories.php
│   │   │   ├── attachments.php
│   │   │   ├── scanner.php
│   │   │   ├── ai.php
│   │   │   └── questions.php
│   │   ├── includes/
│   │   │   ├── roadmap_functions.php
│   │   │   ├── scanner_functions.php
│   │   │   ├── ai_functions.php
│   │   │   └── roadmap_config.php
│   │   └── assets/
│   │       ├── css/
│   │       │   └── roadmap.css
│   │       └── js/
│   │           ├── roadmap.js
│   │           ├── editor.js
│   │           ├── scanner.js
│   │           └── questions.js
│   ├── includes/
│   │   ├── auth.php
│   │   ├── config.php
│   │   ├── header.php
│   │   └── footer.php
│   ├── login.php
│   └── dashboard.php
├── config/
│   ├── database.php
│   └── roadmap_database.php
├── uploads/
│   └── roadmaps/
├── .env.example
├── .env
├── .gitignore
└── README.md
```

---

## Environment Configuration

Create `.env` file:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=roadmap_system

# Roadmap System Encryption Key (REQUIRED)
# Generate: openssl rand -hex 32
ROADMAP_ENCRYPTION_KEY=your-32-byte-hex-key-here

# AI Provider API Keys (Optional)
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_API_KEY=...

# File Upload Settings
MAX_UPLOAD_SIZE=10485760
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif,webp,svg
ALLOWED_DOC_TYPES=pdf,doc,docx,txt

# Security Settings
SESSION_LIFETIME=7200
CSRF_TOKEN_LIFETIME=3600

# Application Settings
BASE_URL=http://localhost/roadmap-system
ADMIN_URL=http://localhost/roadmap-system/admin
```

---

## Implementation Phases

1. **Phase 1**: Core Structure (database, project management, basic CRUD)
2. **Phase 2**: Scanner (database, code, config scanning)
3. **Phase 3**: AI Integration (multiple providers, generation, questions)
4. **Phase 4**: Rich Content (attachments, custom fields, editor)
5. **Phase 5**: Development Workflow (checkboxes, batch questions, Q&A)
6. **Phase 6**: Security & Permissions (encryption, roles, access control)
7. **Phase 7**: Versioning & History (tracking, revert, audit)
8. **Phase 8**: Advanced Features (search, exports, provider abstraction)

---

## How to Import This Plan

1. **Create new workspace** in Cursor
2. **Create `.cursor/plans/` directory** in the workspace root
3. **Copy the plan YAML section** above into `website_roadmap_planning_system.plan.md`
4. **Copy this entire export file** to the workspace for reference
5. **Open the plan** in Cursor to see todos and continue development

The plan will appear in Cursor's plan view with all todos and dependencies intact.

---

**End of Complete Export**

