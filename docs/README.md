# My CRM Documentation

This directory contains comprehensive design and implementation documentation for the my_crm project, split into 10 focused documents for easier navigation and maintenance.

## Documentation Structure

### 01. [Overview](./01-Overview.md) (~1KB)
**Purpose and key differentiators of the CRM system**
- Specialized for fullstack web developers and agencies
- Focus on German/EU clients
- Automated quote and invoice generation
- GoBD compliance built-in

### 02. [Architecture](./02-Architecture.md) (~1.5KB)
**Technical stack and architectural decisions**
- Laravel 12 + Filament 4
- Pest testing framework
- Database choices (MySQL/PostgreSQL)
- Key dependencies

### 03. [Core Domain Models](./03-Core-Domain-Models.md) (~14KB)
**Business entities and their relationships**
- Client, Contact, Project
- Quote, Invoice (with line items)
- Task, TimeEntry
- Complete entity relationship diagrams
- Data attributes and business rules

### 04. [Database Schema](./04-Database-Schema.md) (~1.8KB)
**Database design and migration strategy**
- Schema design principles
- Migration execution order
- Foreign key constraints
- Indexes and unique constraints

### 05. [Feature Implementation Details](./05-Feature-Implementation-Details.md) (~74KB)
**Detailed implementation guides for all features**
- Client & Project Management
- Quote Generation
- Invoice Generation (GoBD Compliant)
- PDF Generation with templates
- Email Delivery system
- Task & Follow-up Management
- Complete code examples

### 06. [German Legal Compliance (GoBD)](./06-German-Legal-Compliance-GoBD.md) (~8.7KB)
**German tax compliance requirements**
- GoBD principles explained
- Sequential invoice numbering
- Mandatory invoice fields (Pflichtangaben)
- Immutability enforcement
- 10-year retention requirements
- Special tax cases (reverse charge, exemptions)

### 07. [Security & Data Protection](./07-Security-and-Data-Protection.md) (~9KB)
**Security best practices and GDPR compliance**
- Authentication & Authorization
- GDPR/DSGVO requirements
- Data anonymization strategies
- PDF access control
- Financial validation
- Backup & disaster recovery

### 08. [Testing Strategy](./08-Testing-Strategy.md) (~13KB)
**Comprehensive testing approach**
- Unit tests (models, services, calculations)
- Feature tests (workflows, GoBD compliance)
- PDF and email testing
- Test coverage goals
- CI/CD integration

### 09. [Deployment Considerations](./09-Deployment-Considerations.md) (~10KB)
**Production deployment guide**
- Infrastructure requirements
- Hosting options (VPS, managed, cloud)
- Deployment checklist
- Web server configuration (Nginx)
- Queue worker setup
- Monitoring and logging
- Zero-downtime deployment
- Disaster recovery plan

### 10. [Future Enhancements](./10-Future-Enhancements.md) (~7.7KB)
**Roadmap for future features**
- Phase 2: Credit notes, payment tracking, recurring invoices
- Phase 3: Multi-currency, client portal, API
- Integration possibilities (DATEV, payment gateways)
- Scalability considerations

## How to Use This Documentation

### For Developers
1. Start with **01-Overview** and **02-Architecture** for context
2. Review **03-Core-Domain-Models** to understand the data model
3. Use **05-Feature-Implementation-Details** as implementation reference
4. Follow **06-German-Legal-Compliance-GoBD** for invoice features
5. Implement **08-Testing-Strategy** alongside development

### For Project Managers
1. Read **01-Overview** for project scope
2. Check **10-Future-Enhancements** for roadmap
3. Review **09-Deployment-Considerations** for hosting needs

### For Legal/Compliance
1. Focus on **06-German-Legal-Compliance-GoBD**
2. Review **07-Security-and-Data-Protection** for GDPR

## Document Maintenance

These documents are actively maintained alongside the codebase. When implementing new features:

1. Update relevant documentation files
2. Keep code examples synchronized with actual implementation
3. Add new sections to **10-Future-Enhancements** for planned features
4. Update version history in each document

## Original DESIGN.md

The original comprehensive `DESIGN.md` file in the project root contains all sections in a single document. It is kept for reference but may become outdated. These split documents are the canonical source of truth.

## Contributing

When adding documentation:
- Follow the existing structure and formatting
- Include code examples where helpful
- Link related sections across documents
- Keep technical accuracy as top priority
- Update this README when adding new documents

## Quick Reference

**Tech Stack**: Laravel 12, Filament 4, Pest, MySQL/PostgreSQL  
**Key Features**: Quotes, Invoices (GoBD compliant), PDF generation, Email delivery  
**Target Users**: Fullstack developers, web agencies serving German/EU clients  
**Legal Compliance**: GoBD (German tax regulations), GDPR/DSGVO  

---

**Last Updated**: November 30, 2025  
**Total Documentation**: ~141KB across 10 files  
**Total Lines**: ~4,658 lines
