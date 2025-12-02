# my_crm

A simple, tailored CRM solution for fullstack web developers working with German/EU clients. Built with Laravel 12 and Filament 4.

## Purpose

This CRM eliminates the need for manual document creation (Word processors, spreadsheets) by generating quotes and invoices directly from database data. It ensures compliance with German/EU legal requirements (GoBD) for invoicing and provides essential client and project management features.

## Key Features

- **Client & Project Management** - Track clients, contacts, projects, and billable hours
- **Quote Generation** - Create professional quotes with versioning and approval workflow
- **Invoice Generation** - GoBD-compliant invoices with sequential numbering and all required legal fields
- **PDF Export** - Generate professional PDF documents from templates
- **Email Delivery** - Send quotes and invoices via email with PDF attachments
- **Task Management** - Track follow-ups, deadlines, and project tasks

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Admin Panel**: Filament 4
- **Testing**: Pest 4
- **Frontend Assets**: Vite 7 + Tailwind CSS 4
- **Database**: MySQL/PostgreSQL (configurable)

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL 8.0+ or PostgreSQL 13+

## Installation

```bash
# Clone the repository
git clone <repository-url> my_crm
cd my_crm

# Run setup (installs dependencies, generates key, runs migrations)
composer setup

# Configure your .env file
# Set database credentials, mail settings, and app details

# Run migrations
php artisan migrate

# Create admin user
php artisan make:filament-user

# Start development server
composer dev
```

## Development

```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/InvoiceTest.php

# Run specific test
php artisan test --filter=test_invoice_generation

# Format code (Laravel Pint - PSR-12)
./vendor/bin/pint

# Start dev server (runs Laravel server, queue, logs, and Vite)
composer dev
```

## Documentation

Comprehensive documentation is available in the [`docs/`](./docs/) directory:

- **[Documentation Index](./docs/README.md)** - Complete documentation overview and navigation guide
- **Quick Links:**
  - [Overview](./docs/01-Overview.md) - Purpose and key differentiators
  - [Architecture](./docs/02-Architecture.md) - Technical stack and decisions
  - [Core Domain Models](./docs/03-Core-Domain-Models.md) - Business entities and relationships
  - [Feature Implementation](./docs/05-Feature-Implementation-Details.md) - Detailed implementation guides
  - [German Legal Compliance (GoBD)](./docs/06-German-Legal-Compliance-GoBD.md) - Tax compliance requirements
  - [Testing Strategy](./docs/08-Testing-Strategy.md) - Comprehensive testing approach
  - [Deployment Guide](./docs/09-Deployment-Considerations.md) - Production deployment
- **[Agent Guidelines](AGENTS.md)** - Coding standards for AI assistants

## License

Proprietary - for personal/business use only.
