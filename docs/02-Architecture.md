# Architecture

## Tech Stack Rationale

#### Backend Framework
- **Laravel 12**: Modern PHP framework with excellent ecosystem
  - Built-in support for database transactions (crucial for GoBD compliance)
  - Robust queuing system for email delivery
  - Comprehensive ORM (Eloquent) for complex relationships
  - Strong security features (CSRF protection, SQL injection prevention)

#### Admin Panel
- **Filament 4**: Rapid admin panel development
  - Rich table builder with filtering, sorting, and bulk actions
  - Form builder with repeaters (ideal for line items)
  - Built-in relation managers (client contacts, project time entries)
  - Custom actions for quote-to-invoice conversion
  - PDF export capabilities

#### Testing Framework
- **Pest 4**: Clean, expressive testing syntax
  - More readable than PHPUnit for feature tests
  - Better developer experience with expect() assertions
  - Architecture testing support for enforcing design rules

#### Database
- **MySQL 8.0+ / PostgreSQL 13+**: Relational database
  - ACID compliance for financial data integrity
  - Row-level locking for sequential number generation
  - Native JSON support for flexible metadata storage
  - Soft deletes for audit trail compliance

#### Additional Dependencies
- **barryvdh/laravel-dompdf**: PDF generation from HTML/Blade templates
- **Laravel Mail**: Email delivery with queue support
- **Spatie/Laravel-Data** (optional): Type-safe DTOs for financial calculations

