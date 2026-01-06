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

# Website Roadmap Planning System

## System Architecture

The system will be a **hybrid master system** that can:
- Operate as a standalone master planning system
- Connect to existing website projects via config-based connections
- Scan existing websites to generate initial roadmaps
- Create new roadmaps from scratch using AI
- Manage multiple projects from a central interface

## Database Structure

### Master System Database Tables

#### 1. `roadmap_projects`
Stores project configurations and metadata.
- `id` (INT, PRIMARY KEY)
- `name` (VARCHAR 255) - Project name
- `description` (TEXT) - Project description
- `db_host` (VARCHAR 255) - Database host for project connection
- `db_name` (VARCHAR 255) - Database name
- `db_user` (VARCHAR 255) - Database username (encrypted with env key)
- `db_pass` (VARCHAR 255) - Database password (encrypted with env key)
- `encryption_method` (VARCHAR 50) - Encryption method used (e.g., 'AES-256-GCM')
- `project_root_path` (VARCHAR 500) - File system path to project
- `project_url` (VARCHAR 500) - Base URL of project
- `status` (ENUM: 'active', 'archived', 'planning') - Project status
- `created_at`, `updated_at` (TIMESTAMP)
- `last_scan_at` (TIMESTAMP NULL) - Last time project was scanned
- `scan_results` (JSON) - Stored scan results

#### 2. `roadmap_categories`
Hierarchical categories (1. Main Category, 1.1 Sub-category, etc.)
- `id` (INT, PRIMARY KEY)
- `project_id` (INT, FOREIGN KEY → roadmap_projects)
- `parent_id` (INT NULL, FOREIGN KEY → roadmap_categories) - For hierarchy
- `title` (VARCHAR 255) - Category title
- `description` (TEXT) - Category description
- `order_index` (INT) - Display order
- `level` (INT) - Hierarchy level (1, 2, 3, etc.)
- `path` (VARCHAR 500) - Full path like "1.2.3"
- `status` (ENUM: 'draft', 'active', 'completed', 'archived')
- `created_at`, `updated_at` (TIMESTAMP)

#### 3. `roadmap_items`
Individual roadmap items/tasks (1.1.1, 1.1.2, etc.)
- `id` (INT, PRIMARY KEY)
- `category_id` (INT, FOREIGN KEY → roadmap_categories)
- `title` (VARCHAR 255) - Item title
- `description` (TEXT) - Detailed description
- `item_type` (ENUM: 'task', 'feature', 'requirement', 'database', 'page', 'api') - Type of item
- `order_index` (INT) - Display order within category
- `path` (VARCHAR 500) - Full path like "1.1.1"
- `status` (ENUM: 'pending', 'in_progress', 'completed', 'blocked')
- `priority` (ENUM: 'low', 'medium', 'high', 'critical')
- `estimated_hours` (DECIMAL 10,2) - Time estimate
- `assigned_to` (VARCHAR 255) - Assignee
- `due_date` (DATE NULL)
- `is_selected_for_dev` (TINYINT 1 DEFAULT 0) - Checkbox for batch development
- `created_at`, `updated_at` (TIMESTAMP)

#### 4. `roadmap_item_fields`
Custom fields for roadmap items (text fields, text areas, etc.)
- `id` (INT, PRIMARY KEY)
- `item_id` (INT, FOREIGN KEY → roadmap_items)
- `field_type` (ENUM: 'text', 'textarea', 'number', 'date', 'select', 'checkbox')
- `field_name` (VARCHAR 255) - Field identifier
- `field_label` (VARCHAR 255) - Display label
- `field_value` (TEXT) - Field content/value
- `field_order` (INT) - Display order
- `is_required` (TINYINT 1 DEFAULT 0)
- `created_at`, `updated_at` (TIMESTAMP)

#### 5. `roadmap_attachments`
File attachments (images, documents) for roadmap items
- `id` (INT, PRIMARY KEY)
- `item_id` (INT, FOREIGN KEY → roadmap_items)
- `file_name` (VARCHAR 255) - Original filename
- `file_path` (VARCHAR 500) - Storage path
- `file_type` (ENUM: 'image', 'document', 'other')
- `mime_type` (VARCHAR 100)
- `file_size` (INT) - Size in bytes
- `description` (TEXT) - Attachment description
- `uploaded_by` (VARCHAR 255)
- `created_at` (TIMESTAMP)

#### 6. `roadmap_scan_results`
Stored results from website scans
- `id` (INT, PRIMARY KEY)
- `project_id` (INT, FOREIGN KEY → roadmap_projects)
- `scan_type` (ENUM: 'database', 'code', 'full')
- `scan_data` (JSON) - Detailed scan results
- `tables_found` (JSON) - Database tables discovered
- `files_analyzed` (JSON) - Code files analyzed
- `features_detected` (JSON) - Features detected
- `recommendations` (TEXT) - AI-generated recommendations
- `created_at` (TIMESTAMP)

#### 7. `roadmap_ai_prompts`
Stored AI prompts and responses for roadmap generation
- `id` (INT, PRIMARY KEY)
- `project_id` (INT, FOREIGN KEY → roadmap_projects)
- `prompt_type` (ENUM: 'initial_generation', 'refinement', 'questions')
- `user_prompt` (TEXT) - User's input prompt
- `ai_response` (TEXT) - AI generated response
- `generated_roadmap` (JSON) - Structured roadmap data
- `api_used` (VARCHAR 50) - Which AI API was used
- `tokens_used` (INT)
- `created_at` (TIMESTAMP)

#### 8. `roadmap_development_questions`
Questions generated for selected development items
- `id` (INT, PRIMARY KEY)
- `item_id` (INT, FOREIGN KEY → roadmap_items)
- `question_text` (TEXT) - The question
- `question_type` (ENUM: 'clarification', 'technical', 'design', 'requirement')
- `answer` (TEXT NULL) - User's answer
- `suggestions` (TEXT) - AI suggestions
- `order_index` (INT)
- `is_answered` (TINYINT 1 DEFAULT 0)
- `created_at`, `updated_at` (TIMESTAMP)

#### 9. `roadmap_user_permissions`
Role-based and project-based user permissions
- `id` (INT, PRIMARY KEY)
- `user_id` (INT) - References existing users table
- `project_id` (INT NULL, FOREIGN KEY → roadmap_projects) - NULL = all projects
- `role` (ENUM: 'admin', 'editor', 'viewer') - Permission level
- `permissions` (JSON) - Granular permissions override
- `created_at`, `updated_at` (TIMESTAMP)
- UNIQUE KEY `user_project` (user_id, project_id)

#### 10. `roadmap_history`
Full versioning/history tracking for all changes
- `id` (INT, PRIMARY KEY)
- `entity_type` (ENUM: 'project', 'category', 'item', 'field', 'attachment')
- `entity_id` (INT) - ID of the changed entity
- `action` (ENUM: 'create', 'update', 'delete', 'status_change')
- `old_data` (JSON) - Previous state (for updates/deletes)
- `new_data` (JSON) - New state (for creates/updates)
- `changed_by` (INT) - User ID who made the change
- `change_reason` (TEXT) - Optional reason for change
- `created_at` (TIMESTAMP)
- INDEX `idx_entity` (entity_type, entity_id)
- INDEX `idx_created` (created_at)

#### 11. `roadmap_ai_providers`
Configuration for multiple AI providers
- `id` (INT, PRIMARY KEY)
- `provider_name` (VARCHAR 50) - 'openai', 'anthropic', 'google', etc.
- `api_key_encrypted` (TEXT) - Encrypted API key
- `api_endpoint` (VARCHAR 500) - API endpoint URL
- `model_name` (VARCHAR 100) - Default model (e.g., 'gpt-4', 'claude-3')
- `is_active` (TINYINT 1 DEFAULT 1)
- `priority` (INT) - Priority order for fallback
- `rate_limit_per_minute` (INT) - Rate limiting
- `cost_per_1k_tokens` (DECIMAL 10,4) - Cost tracking
- `max_tokens` (INT) - Maximum tokens per request
- `created_at`, `updated_at` (TIMESTAMP)

#### 12. `roadmap_search_index`
Full-text search index for advanced search
- `id` (INT, PRIMARY KEY)
- `entity_type` (ENUM: 'project', 'category', 'item')
- `entity_id` (INT) - ID of indexed entity
- `searchable_text` (TEXT) - All searchable content
- `tags` (JSON) - Searchable tags
- `metadata` (JSON) - Additional searchable metadata
- `updated_at` (TIMESTAMP)
- FULLTEXT INDEX `idx_search` (searchable_text)
- INDEX `idx_entity` (entity_type, entity_id)

## Core Features

### 1. Project Management
- **Location**: `admin/roadmap/projects.php`
- Create/edit/delete projects
- Store encrypted database credentials
- Test database connections
- Manage project status and metadata

### 2. Website Scanner
- **Location**: `admin/roadmap/scanner.php`
- **Database Scanner**: Connect to project database, extract tables, columns, relationships
- **Code Scanner**: Scan PHP files for functions, classes, routes
- **Config Scanner**: Read configuration files, detect dependencies
- **Result Storage**: Save scan results to `roadmap_scan_results` table

### 3. AI Roadmap Generation
- **Location**: `admin/roadmap/generate.php`
- **Initial Generation**: From user prompts describing website
- **From Scan Results**: Analyze existing structure and suggest improvements
- **API Integration**: Multiple AI providers (OpenAI, Anthropic, Google) with abstraction layer

### 4. Roadmap Interface
- **Location**: `admin/roadmap/index.php`
- Hierarchical tree view with numbering (1.1.1, 1.1.2)
- Expandable/collapsible sections
- Drag-and-drop reordering
- Rich text editor
- Custom field management
- Checkbox system for batch development

### 5. Attachment Management
- **Location**: `admin/roadmap/attachments.php`
- Support images and documents
- File validation and security
- Thumbnail generation
- Organized storage

### 6. Development Question Generator
- **Location**: `admin/roadmap/questions.php`
- Batch processing for selected items
- AI-generated contextual questions
- Interactive Q&A interface

### 7. Advanced Features
- Full-text search with filters
- Export formats (PDF, Excel, JSON, Markdown, HTML)
- Full history tracking with revert
- Role-based permissions

## Security Implementation

- **Credential Encryption**: Environment Variables + AES-256-GCM
- **Database Security**: Prepared statements, parameterized queries
- **File Upload Security**: Whitelist validation, size limits
- **Access Control**: Role-based (Admin/Editor/Viewer) + project-based
- **API Security**: CSRF protection, rate limiting
- **Audit & Logging**: Full history tracking

## Configuration

Environment variables required:
- `ROADMAP_ENCRYPTION_KEY` (REQUIRED) - Generate with: `openssl rand -hex 32`
- AI provider API keys (optional)
- File upload settings
- Security settings

## Development Phases

1. Core Structure
2. Scanner
3. AI Integration
4. Rich Content
5. Development Workflow
6. Security & Permissions
7. Versioning & History
8. Advanced Features

