# Sprint Plan - my_crm

Development broken into 6 focused sprints, each delivering a working, testable feature set.

---

## Sprint 1: Foundation & Client Management (Week 1)

**Goal:** Set up project foundation and implement core client/contact management.

### Tasks
- [x] Configure application settings (company details, tax settings)
- [x] Create Client model, migration, factory, tests
- [x] Create Contact model, migration, factory, tests
- [x] Write feature tests for client/contact CRUD operations
- [x] Build ClientResource in Filament with full CRUD
- [x] Implement client search, filtering, and sorting
- [x] Add ContactsRelationManager to ClientResource
- [x] Add validation rules (email, etc.)

### Deliverables
- Fully functional client and contact management
- Ability to add/edit/delete clients and their contacts
- Clean UI with search and filters
- Test coverage: Client and Contact models

### Acceptance Criteria
- Can create client with all required fields
- Can add multiple contacts per client
- Can search and filter clients
- All tests pass

---

## Sprint 2: Project & Time Tracking (Week 2)

**Goal:** Enable project management and time tracking for billable work.

### Tasks
- [x] Create Project model, migration, factory, tests
- [x] Create TimeEntry model, migration, factory, tests
- [x] Build ProjectResource in Filament
- [x] Add ProjectsRelationManager to ClientResource
- [x] Add TimeEntriesRelationManager to ProjectResource
- [x] Implement project status workflow (active/completed/archived)
- [x] Add hourly rate calculation logic
- [x] Create widget showing total billable hours per project
- [x] Write feature tests for project and time entry CRUD

### Deliverables
- Project management linked to clients
- Time tracking with billable hours calculation
- Visual indicators for project status
- Test coverage: Project and TimeEntry models

### Acceptance Criteria
- Can create projects with different rate types (hourly/fixed/retainer)
- Can log time entries with hours and descriptions
- Can see total billable amount per project
- All tests pass

---

## Sprint 3: Quote Generation (Week 3)

**Goal:** Implement quote creation with line items and basic PDF export.

### Tasks
- [ ] Create Quote model, migration, factory, tests
- [ ] Create QuoteItem model, migration, factory, tests
- [ ] Build QuoteResource in Filament with repeater for line items
- [ ] Implement auto-numbering (Q-YYYY-####)
- [ ] Add tax calculation logic (configurable VAT rate)
- [ ] Implement quote status workflow (draft/sent/accepted/rejected/converted)
- [ ] Create basic PDF template for quotes (Blade view)
- [ ] Implement PDF generation service
- [ ] Add "Generate PDF" action to QuoteResource
- [ ] Add QuotesRelationManager to ClientResource
- [ ] Write feature tests for quote creation and PDF generation

### Deliverables
- Quote creation with line items
- Automatic calculations (subtotal, tax, total)
- PDF export with professional template
- Quote versioning and status tracking
- Test coverage: Quote model, calculations, PDF generation

### Acceptance Criteria
- Can create quote with multiple line items
- Tax and totals calculate correctly
- Can generate PDF from quote data
- Quote numbers are sequential and unique
- All tests pass

---

## Sprint 4: Invoice Generation & GoBD Compliance (Week 4)

**Goal:** Implement legally compliant invoice generation for German/EU market.

### Tasks
- [ ] Create Invoice model, migration, factory, tests
- [ ] Create InvoiceItem model, migration, factory, tests
- [ ] Build InvoiceResource in Filament with repeater for line items
- [ ] Implement GoBD-compliant sequential numbering with gap prevention
- [ ] Add immutability logic (prevent editing sent invoices)
- [ ] Create invoice PDF template with all Pflichtangaben (legal fields)
- [ ] Implement "Convert Quote to Invoice" action
- [ ] Add payment status tracking (draft/sent/paid/overdue/cancelled)
- [ ] Add due date calculation logic
- [ ] Add InvoicesRelationManager to ClientResource
- [ ] Create invoice observer for audit logging
- [ ] Write comprehensive tests for GoBD compliance (numbering, immutability)

### Deliverables
- GoBD-compliant invoice generation
- Sequential numbering without gaps
- Immutable invoices (once sent)
- Professional PDF with all legal requirements
- Quote-to-invoice conversion
- Test coverage: Invoice model, GoBD compliance, calculations

### Acceptance Criteria
- Invoice numbers are strictly sequential per year
- Sent invoices cannot be edited (validation prevents it)
- PDF contains all German legal requirements (Pflichtangaben)
- Can convert accepted quote to invoice with one click
- All tests pass, especially GoBD compliance tests

---

## Sprint 5: Email Delivery System (Week 5)

**Goal:** Automate sending quotes and invoices via email with PDF attachments.

### Tasks
- [ ] Configure mail settings in .env and config
- [ ] Create QuoteSent mailable with Blade template
- [ ] Create InvoiceSent mailable with Blade template
- [ ] Create PaymentReminder mailable with Blade template
- [ ] Add "Send via Email" action to QuoteResource
- [ ] Add "Send via Email" action to InvoiceResource
- [ ] Implement email tracking (sent_at, sent_by fields)
- [ ] Add email preview functionality
- [ ] Configure queue for background email sending
- [ ] Create email templates (German and English)
- [ ] Write feature tests for email sending (mocked)

### Deliverables
- Email sending for quotes and invoices
- Professional email templates
- PDF attachments
- Email tracking and audit trail
- Queue-based sending for performance
- Test coverage: Mailable classes, email actions

### Acceptance Criteria
- Can send quote via email with PDF attached
- Can send invoice via email with PDF attached
- Email templates are professional and include all necessary info
- System tracks when emails were sent and by whom
- All tests pass

---

## Sprint 6: Task Management & Polish (Week 6)

**Goal:** Complete task/follow-up system and polish the entire application.

### Tasks
- [ ] Create Task model, migration, factory, tests
- [ ] Build TaskResource in Filament
- [ ] Add TasksRelationManager to ClientResource and ProjectResource
- [ ] Implement task priorities and statuses
- [ ] Create "Upcoming Tasks" widget for dashboard
- [ ] Create "Overdue Tasks" widget for dashboard
- [ ] Add task assignment functionality
- [ ] Implement task filtering by status, priority, due date
- [ ] Create notification system for task reminders
- [ ] Add automated follow-up tasks (e.g., after sending quote)
- [ ] Polish all UIs (consistent styling, labels, help text)
- [ ] Review and improve validation messages
- [ ] Add comprehensive seeder for demo data
- [ ] Write documentation for common workflows
- [ ] Final end-to-end testing

### Deliverables
- Complete task management system
- Dashboard with useful widgets
- Automated follow-up reminders
- Polished, production-ready UI
- Demo data seeder
- Test coverage: Task model, notifications

### Acceptance Criteria
- Can create and manage tasks linked to clients/projects
- Dashboard shows upcoming and overdue tasks
- Task reminders work correctly
- All features are polished and user-friendly
- Demo data seeder populates realistic test data
- All tests pass (100+ tests total)

---

## Post-Launch (Future Sprints)

### Sprint 7: Enhanced Features (Optional)
- [ ] Activity timeline per client
- [ ] Payment tracking with status updates
- [ ] Recurring invoices for retainer clients
- [ ] Document storage per client
- [ ] Basic revenue dashboard

### Sprint 8: Advanced Features (Optional)
- [ ] Credit note generation (Gutschrift)
- [ ] Expense tracking
- [ ] Multi-currency support
- [ ] DATEV export for accountants
- [ ] Advanced reporting and analytics

---

## Testing Strategy

Each sprint should maintain:
- **Unit Tests**: Models, services, calculations
- **Feature Tests**: Complete workflows (e.g., create quote → convert to invoice → send email)
- **Browser Tests**: Critical user paths (optional, using Laravel Dusk)
- **Minimum Coverage**: 80% code coverage

## Definition of Done

For each sprint, a feature is "done" when:
1. All code is written and follows PSR-12 (Pint passes)
2. All tests are written and passing
3. Feature is manually tested in browser
4. Code is committed with clear commit messages
5. Documentation is updated if needed

## Sprint Duration

- Each sprint: **1 week** (5 working days)
- Total core development: **6 weeks**
- Buffer for bugs/polish: **1 week**
- **Total timeline: ~7 weeks to MVP**

## Dependencies

- Sprint 2 depends on Sprint 1 (projects need clients)
- Sprint 3 depends on Sprint 1 (quotes need clients)
- Sprint 4 depends on Sprint 1 & 3 (invoices need clients and optionally quotes)
- Sprint 5 depends on Sprint 3 & 4 (email needs quotes/invoices)
- Sprint 6 depends on Sprint 1 & 2 (tasks link to clients/projects)

Sprints 1-2 can run in parallel if multiple developers.
Sprints 3-4 should be sequential.
Sprint 5-6 can start once 3-4 are complete.
