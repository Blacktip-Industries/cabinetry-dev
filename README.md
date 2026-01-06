# Cabinetry Dev

Development repository for the Bespoke Cabinetry e-commerce platform - a comprehensive component-based system for custom cabinetry solutions.

## Overview

This is a custom-built e-commerce platform designed specifically for bespoke cabinetry businesses. The system features a modular component architecture that allows for flexible, scalable development and easy maintenance.

## Tech Stack

- **Backend**: PHP (Vanilla, no frameworks)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript (Vanilla)
- **Version Control**: Git
- **Server**: XAMPP (Development)

## Project Structure

```
cabinetry-dev/
├── admin/                    # Admin panel
│   ├── components/          # Modular components
│   ├── includes/            # Shared includes
│   └── tools/               # Admin tools
├── assets/                  # Frontend assets
├── config/                  # Configuration files
├── includes/                # Frontend includes
└── _standards/              # Development standards
```

## Core Components

The system is built with a modular component architecture. Key components include:

- **access** - User authentication and access control
- **commerce** - Product management and shopping cart
- **component_manager** - Component installation and management
- **email_marketing** - Email campaigns and automation
- **formula_builder** - Advanced pricing formula system
- **inventory** - Stock management and tracking
- **layout** - Page layout and design system
- **order_management** - Order processing and fulfillment
- **payment_processing** - Payment gateway integration
- **product_options** - Product customization options
- **savepoints** - Backup and restore system
- **seo_manager** - SEO optimization tools
- **theme** - Theme management system

## Development Standards

This project follows strict naming and development standards. See `_standards/` directory for:
- Component creation procedures
- Naming standards
- CSS normalization guidelines
- Testing templates

## Setup

### Prerequisites

- XAMPP (or similar PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Git

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Blacktip-Industries/cabinetry-dev.git
   cd cabinetry-dev
   ```

2. Configure database connection in `config/database.php`

3. Run database initialization scripts if needed

4. Access the admin panel at `http://localhost/cabinetry-dev/admin/`

## Database

- **Database Name**: `cabinetry_dev`
- **Configuration**: `config/database.php`

## Features

- Component-based architecture for modularity
- Comprehensive admin panel
- Advanced pricing formula system
- Order management and fulfillment
- Inventory tracking
- Email marketing automation
- SEO management tools
- Savepoint backup system
- Theme customization

## Contributing

Please follow the development standards in `_standards/` when contributing to this project.

## License

[Add license information here]

## Support

For issues and questions, please use the GitHub Issues page.
