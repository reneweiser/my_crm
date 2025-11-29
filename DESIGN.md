# Design Document - my_crm

## Overview

A Laravel + Filament CRM tailored for fullstack web developers working with German/EU clients. Focuses on automated quote/invoice generation with legal compliance rather than traditional CRM features like sales pipelines.

## Architecture

### Tech Stack Rationale

- **Laravel 12**: Modern PHP framework with excellent ecosystem
- **Filament 4**: Rapid admin panel development with tables, forms, relations, and PDF export capabilities
- **Pest**: Clean, expressive testing syntax
- **MySQL/PostgreSQL**: Relational data for clients, projects, quotes, invoices

### Core Domain Models

```
Client
├── contacts (Contact)
├── projects (Project)
├── quotes (Quote)
└── invoices (Invoice)

Project
├── client (Client)
├── time_entries (TimeEntry)
└── tasks (Task)

Quote
├── client (Client)
├── project (Project - optional)
├── quote_items (QuoteItem)
└── invoices (Invoice - when converted)

Invoice
├── client (Client)
├── project (Project - optional)
├── invoice_items (InvoiceItem)
└── quote (Quote - optional, if converted)

Task
├── client (Client - optional)
├── project (Project - optional)
└── assigned_user (User)
```

## Feature Implementation Details

### 1. Client & Project Management

**Models:**
- `Client`: name, company, address, email, phone, notes
- `Contact`: client_id, name, email, phone, position, is_primary
- `Project`: client_id, name, description, status (active/completed/archived), rate_type (hourly/fixed/retainer), hourly_rate, fixed_price
- `TimeEntry`: project_id, user_id, description, hours, date, billable

**Filament Resources:**
- ClientResource with relation managers for contacts, projects, quotes, invoices
- ProjectResource with time tracking and task management

### 2. Quote Generation

**Models:**
- `Quote`: client_id, project_id, quote_number, sent_at, valid_until, status (draft/sent/accepted/rejected/converted), notes, subtotal, tax_rate, tax_amount, total
- `QuoteItem`: quote_id, description, quantity, unit_price, total

**Features:**
- Auto-generate quote numbers (Q-YYYY-####)
- Line item builder (service descriptions, quantities, unit prices)
- Tax calculation (19% MwSt for Germany, configurable)
- Version control (track quote revisions)
- Status workflow: Draft → Sent → Accepted/Rejected
- Convert accepted quotes to invoices (one-click)

**Filament Implementation:**
- QuoteResource with repeater for line items
- Custom action to convert quote to invoice
- PDF export action

### 3. Invoice Generation (GoBD Compliant)

**Models:**
- `Invoice`: client_id, project_id, quote_id, invoice_number, invoice_date, due_date, status (draft/sent/paid/overdue/cancelled), payment_terms, notes, subtotal, tax_rate, tax_amount, total
- `InvoiceItem`: invoice_id, description, quantity, unit_price, total

**German Legal Requirements (Pflichtangaben):**
- Sequential invoice numbering (no gaps allowed per GoBD)
- Invoice date and unique number
- Seller info: name, address, tax ID (Steuernummer/USt-IdNr)
- Buyer info: name, address
- Delivery/service date or period
- Description of services/products
- Net amount per tax rate
- Tax rate and tax amount
- Gross total
- Payment terms and bank details

**Features:**
- Auto-generate sequential invoice numbers (INV-YYYY-####)
- Immutable once sent (GoBD requirement - changes require credit notes)
- Due date calculation (e.g., net 30 days)
- Payment status tracking
- Late payment reminders

**Filament Implementation:**
- InvoiceResource with repeater for line items
- Custom validation to prevent editing sent invoices
- PDF generation with blade template
- Email sending action

### 4. PDF Generation

**Technology:**
- Laravel's built-in PDF capabilities or `barryvdh/laravel-dompdf`
- Blade templates for invoice/quote layouts

**Template Requirements:**
- Professional design with company branding
- All legal fields for German invoices
- Clean, printable layout
- Multi-language support (German/English)

**Implementation:**
- `app/Services/PdfService.php` - handles PDF generation
- `resources/views/pdf/invoice.blade.php` - invoice template
- `resources/views/pdf/quote.blade.php` - quote template

### 5. Email Delivery

**Technology:**
- Laravel Mail with configurable driver (SMTP/Mailgun/etc.)
- Mailable classes for quotes and invoices

**Features:**
- Email templates with placeholders (client name, amount, etc.)
- PDF attachment
- Track sent emails (sent_at timestamp on quotes/invoices)
- Optional: email queue for better performance

**Implementation:**
- `app/Mail/QuoteSent.php` - quote email
- `app/Mail/InvoiceSent.php` - invoice email
- `app/Mail/PaymentReminder.php` - overdue invoice reminder
- Filament custom actions to send emails

### 6. Task & Follow-up Management

**Models:**
- `Task`: title, description, due_date, priority (low/medium/high), status (pending/in_progress/completed/cancelled), client_id, project_id, assigned_user_id

**Features:**
- Task list with filtering and sorting
- Link tasks to clients/projects
- Assign to users (for multi-user scenarios)
- Calendar view of upcoming tasks
- Automated reminders (e.g., follow up on sent quotes after 7 days)

**Filament Implementation:**
- TaskResource with filters for status, priority, due date
- Widgets: upcoming tasks, overdue tasks
- Calendar widget (optional)

## Database Considerations

### Sequential Numbering (GoBD Compliance)

```php
// Use database transactions to ensure no gaps
DB::transaction(function () {
    $lastInvoice = Invoice::lockForUpdate()
        ->whereYear('invoice_date', now()->year)
        ->latest('invoice_number')
        ->first();
    
    $nextNumber = $lastInvoice 
        ? $lastInvoice->invoice_number + 1 
        : 1;
    
    $invoice->invoice_number = 'INV-' . now()->year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    $invoice->save();
});
```

### Immutability

Once an invoice is sent, it should not be editable. Use:
- `sent_at` field to determine if an invoice was sent

## Security Considerations

- Protect invoice PDFs from unauthorized access (signed URLs or auth middleware)
- Validate all financial calculations server-side
- Audit log for invoice creation/modification

## Future Enhancements (Low Priority)

- Activity timeline per client
- Payment tracking with bank import
- Recurring invoices for retainer clients
- Expense tracking
- Basic revenue dashboard
- Multi-currency support
- Credit note generation
- Dunning process automation (Mahnwesen)
- Export to DATEV format (German accounting software)

## Testing Strategy

- Unit tests for financial calculations (tax, totals)
- Feature tests for quote/invoice workflows
- PDF generation tests (ensure all required fields present)
- Email sending tests (mocked)
- GoBD compliance tests (sequential numbering, immutability)

## Deployment

- Automated backups (database + uploaded files)
- SSL certificate required for email sending
- Configure queue worker for background jobs
- Monitor disk space (PDF storage)
