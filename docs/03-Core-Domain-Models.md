# Core Domain Models

This section describes the core business entities and their relationships. The domain model follows a hierarchical structure with Client as the root aggregate.

### Entity Relationship Overview

```
┌─────────────────────────────────────────────────────────────┐
│                          Client                              │
│  (Root Aggregate - Customer Master Data)                    │
└─────────────────────────────────────────────────────────────┘
    │
    ├──► Contact (1:N)        - People at the client organization
    ├──► Project (1:N)        - Work engagements
    │      ├──► TimeEntry (1:N) - Billable hours
    │      └──► Task (1:N)      - Project-specific todos
    ├──► Quote (1:N)          - Proposals with pricing
    │      └──► QuoteItem (1:N) - Line items on quotes
    ├──► Invoice (1:N)        - Legally compliant bills
    │      └──► InvoiceItem (1:N) - Line items on invoices
    └──► Task (1:N)           - Client-level follow-ups

┌─────────────────────────────────────────────────────────────┐
│                           User                               │
│  (System user - developer/team member)                      │
└─────────────────────────────────────────────────────────────┘
    │
    ├──► TimeEntry (1:N)      - Hours logged by this user
    └──► Task (1:N)           - Tasks assigned to this user
```

### 1. Client

**Purpose**: The central business entity representing a company or individual customer. Serves as the root aggregate for all business relationships.

**Domain Responsibilities**:
- Store customer master data (company info, address, contact details)
- Provide invoice recipient information (legally required billing address)
- Serve as the organizational unit for all projects, quotes, and invoices
- Track client-level metadata and notes

**Key Relationships**:
- **Has many** Contacts (People working at the client organization)
- **Has many** Projects (Work engagements)
- **Has many** Quotes (Proposals sent to this client)
- **Has many** Invoices (Bills issued to this client)
- **Has many** Tasks (Client-level follow-ups)

**Business Rules**:
- Must exist before creating projects, quotes, or invoices
- At least one contact is recommended for communication
- Email address used for sending quotes/invoices
- Address fields required for GoBD-compliant invoicing
- Soft delete enabled to preserve historical data

**Data Attributes** (as implemented):
```
- id: Primary key
- name: Client/person name (required)
- company: Company name (optional - for individual clients)
- address_line_1, address_line_2: Street address
- postal_code: ZIP/postal code
- city: City name
- country: Country (default: Germany)
- email: Primary email address
- phone: Phone number
- website: Company website URL
- notes: Internal notes (not visible to client)
- timestamps: created_at, updated_at
- deleted_at: Soft delete timestamp
```

### 2. Contact

**Purpose**: Individual people associated with a client company. Supports multiple contacts per client for different roles (billing, technical, management).

**Domain Responsibilities**:
- Store individual contact information for people at client organizations
- Designate primary contact for default communication
- Enable targeted communication based on role/responsibility

**Key Relationships**:
- **Belongs to** one Client (required)

**Business Rules**:
- Must be associated with a client (cascades on delete)
- Only one contact per client should be marked as primary
- At least one contact per client is recommended
- Soft delete enabled to preserve contact history

**Data Attributes** (as implemented):
```
- id: Primary key
- client_id: Foreign key to clients table (required)
- name: Contact person's name (required)
- email: Email address
- phone: Phone number
- position: Job title/role
- is_primary: Boolean flag for primary contact
- timestamps: created_at, updated_at
- deleted_at: Soft delete timestamp
```

**Indexes**: `(client_id, is_primary)` for efficient primary contact lookups

### 3. Project

**Purpose**: Represents a specific work engagement or ongoing relationship with a client. Groups related work and defines billing arrangements.

**Domain Responsibilities**:
- Define the scope and nature of work being done
- Specify billing model (hourly, fixed-price, retainer)
- Group related time entries and tasks
- Track project lifecycle (active, completed, archived)

**Key Relationships**:
- **Belongs to** one Client (required)
- **Has many** TimeEntry records (billable hours)
- **Has many** Tasks (project-specific todos)
- **Has many** Quotes (optionally linked)
- **Has many** Invoices (optionally linked)

**Business Rules**:
- Must be associated with a client
- Rate type determines how time entries are billed
- Hourly projects require hourly_rate; fixed projects require fixed_price
- Status workflow: active → completed → archived
- Archived projects should be read-only

**Data Attributes** (to be implemented):
```
- id: Primary key
- client_id: Foreign key to clients table (required)
- name: Project name/title (required)
- description: Project details and scope
- status: Enum (active, completed, archived)
- rate_type: Enum (hourly, fixed, retainer)
- hourly_rate: Decimal (for hourly projects)
- fixed_price: Decimal (for fixed-price projects)
- budget_hours: Integer (estimated hours, optional)
- start_date: Project start date
- end_date: Project completion date
- timestamps: created_at, updated_at
- deleted_at: Soft delete timestamp
```

### 4. Quote

**Purpose**: A formal proposal with pricing sent to a client. Creates a documented paper trail of what was proposed before work begins.

**Domain Responsibilities**:
- Present scope and pricing to client for approval
- Track proposal lifecycle and versioning
- Serve as basis for invoice generation when accepted
- Document pricing history for client relationship

**Key Relationships**:
- **Belongs to** one Client (required)
- **Belongs to** one Project (optional)
- **Has many** QuoteItems (line items with pricing)
- **Has one** Invoice (when converted)

**Business Rules**:
- Must have at least one quote item
- Sequential quote numbering (recommended but not legally required)
- Valid_until date calculated from creation (default: 30 days)
- Status workflow: draft → sent → accepted/rejected/converted
- Draft quotes can be edited; sent quotes should be versioned if changed
- Conversion to invoice creates immutable link

**Data Attributes** (to be implemented):
```
- id: Primary key
- client_id: Foreign key to clients table (required)
- project_id: Foreign key to projects table (optional)
- quote_number: Unique identifier (e.g., Q-2025-0001)
- version: Version number (for revisions)
- status: Enum (draft, sent, accepted, rejected, converted)
- valid_until: Expiration date
- sent_at: Timestamp when sent to client
- accepted_at: Timestamp when accepted
- notes: Internal notes
- client_notes: Notes visible to client (terms, conditions)
- subtotal: Sum of all line items (calculated)
- tax_rate: Tax percentage (default from config)
- tax_amount: Calculated tax (subtotal × tax_rate)
- total: Grand total (subtotal + tax_amount)
- timestamps: created_at, updated_at
- deleted_at: Soft delete timestamp
```

### 5. Invoice

**Purpose**: A legally compliant German invoice (Rechnung) for billing completed work. Must meet all GoBD requirements and include mandatory legal fields (Pflichtangaben).

**Domain Responsibilities**:
- Bill clients for completed work
- Meet all German tax law requirements (§14 UStG)
- Maintain immutable audit trail once sent
- Track payment status and due dates
- Provide legally valid proof of service/product delivery

**Key Relationships**:
- **Belongs to** one Client (required)
- **Belongs to** one Project (optional)
- **Belongs to** one Quote (optional - if converted)
- **Has many** InvoiceItems (line items with pricing)

**Business Rules** (Critical for GoBD Compliance):
- **Sequential numbering** without gaps (legally required)
- **Immutable once sent** - no edits allowed, only cancellation via credit note
- Must include all Pflichtangaben (see German Legal Compliance section)
- Invoice date determines tax period and audit requirements
- Due date calculated from invoice_date + payment_terms
- Status workflow: draft → sent → paid/overdue/cancelled
- Payment tracking with paid_at timestamp
- 10-year retention requirement (Aufbewahrungspflicht)

**Data Attributes** (to be implemented):
```
- id: Primary key
- client_id: Foreign key to clients table (required)
- project_id: Foreign key to projects table (optional)
- quote_id: Foreign key to quotes table (optional)
- invoice_number: Unique sequential number (e.g., INV-2025-0001)
- invoice_date: Issue date (required for tax law)
- due_date: Payment deadline
- service_period_start: Start of service period (optional)
- service_period_end: End of service period (optional)
- status: Enum (draft, sent, paid, overdue, cancelled)
- payment_terms: Payment deadline in days (default: 30)
- payment_terms_text: Human-readable payment terms
- sent_at: Timestamp when sent to client
- paid_at: Timestamp when payment received
- cancelled_at: Timestamp if cancelled
- notes: Internal notes
- client_notes: Footer text visible to client
- subtotal: Sum of all line items (calculated)
- tax_rate: Tax percentage (19% standard, 7% reduced, 0% reverse charge)
- tax_amount: Calculated tax
- total: Grand total (subtotal + tax_amount)
- pdf_path: Path to generated PDF file
- timestamps: created_at, updated_at
- deleted_at: Soft delete timestamp (discouraged for invoices)
```

### 6. QuoteItem & InvoiceItem

**Purpose**: Individual line items that break down quotes and invoices into specific services, products, or deliverables with pricing.

**Domain Responsibilities**:
- Describe individual services/products being quoted or billed
- Calculate line totals (quantity × unit_price)
- Support detailed pricing breakdowns
- Provide item-level tax rates (if different from document tax rate)

**Key Relationships**:
- QuoteItem **belongs to** one Quote
- InvoiceItem **belongs to** one Invoice

**Business Rules**:
- Quantity and unit_price must be positive numbers
- Total calculated as: quantity × unit_price
- Description should be clear and specific
- At least one item required per quote/invoice
- Item-level tax rates optional (defaults to document tax rate)

**Data Attributes** (to be implemented):
```
QuoteItem:
- id: Primary key
- quote_id: Foreign key to quotes table (required)
- description: Service/product description (required)
- quantity: Number of units (default: 1)
- unit_price: Price per unit
- total: Calculated (quantity × unit_price)
- sort_order: Display order
- timestamps: created_at, updated_at

InvoiceItem:
- id: Primary key
- invoice_id: Foreign key to invoices table (required)
- description: Service/product description (required)
- quantity: Number of units (default: 1)
- unit_price: Price per unit
- tax_rate: Item-specific tax rate (optional)
- total: Calculated (quantity × unit_price)
- sort_order: Display order
- timestamps: created_at, updated_at
```

### 7. Task

**Purpose**: A to-do item, follow-up action, or reminder. Supports both client-level and project-level task management.

**Domain Responsibilities**:
- Track work to be done (implementation tasks, deliverables)
- Schedule follow-ups (e.g., "Follow up on quote after 7 days")
- Assign responsibilities to team members
- Prioritize and organize work

**Key Relationships**:
- **Belongs to** one Client (optional)
- **Belongs to** one Project (optional)
- **Assigned to** one User (optional)

**Business Rules**:
- Can exist as standalone, client-linked, or project-linked
- Tasks without assignment are visible to all users
- Due date triggers notifications/reminders
- Status workflow: pending → in_progress → completed/cancelled
- Priority levels: low, medium, high, urgent

**Data Attributes** (to be implemented):
```
- id: Primary key
- title: Task title/summary (required)
- description: Detailed description
- client_id: Foreign key to clients table (optional)
- project_id: Foreign key to projects table (optional)
- assigned_user_id: Foreign key to users table (optional)
- due_date: Deadline for completion
- priority: Enum (low, medium, high, urgent)
- status: Enum (pending, in_progress, completed, cancelled)
- completed_at: Timestamp when marked complete
- timestamps: created_at, updated_at
- deleted_at: Soft delete timestamp
```

### 8. TimeEntry

**Purpose**: Records billable or non-billable hours worked on a project. Used for billing hourly projects and productivity analysis.

**Domain Responsibilities**:
- Track time spent on project work
- Categorize as billable or non-billable
- Support invoice generation for hourly projects
- Provide productivity and profitability metrics

**Key Relationships**:
- **Belongs to** one Project (required)
- **Belongs to** one User (who logged the time)
- Indirectly related to Client through Project

**Business Rules**:
- Must be associated with a project
- Hours must be positive number
- Date required for accurate tracking
- Billable flag determines if included in invoices
- Once invoiced, time entry should be locked

**Data Attributes** (to be implemented):
```
- id: Primary key
- project_id: Foreign key to projects table (required)
- user_id: Foreign key to users table (required)
- description: Work performed
- date: Date work was performed
- hours: Number of hours worked (decimal)
- billable: Boolean flag (default: true)
- invoiced: Boolean flag (set when included in invoice)
- invoice_id: Foreign key to invoices table (optional)
- timestamps: created_at, updated_at
```

