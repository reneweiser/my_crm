# Database Schema

## Schema Design Principles

1. **Normalization**: 3NF normalized for data integrity
2. **Soft Deletes**: Enabled on all user-facing entities for audit trail
3. **Indexes**: Strategic indexes on foreign keys and frequently queried fields
4. **Timestamps**: All tables include created_at and updated_at
5. **Cascading**: Foreign key constraints with appropriate cascade rules

### Migration Order

Due to foreign key dependencies, migrations must be executed in this order:

1. `create_users_table` - System users
2. `create_clients_table` - Root aggregate
3. `create_contacts_table` - Client dependency
4. `create_projects_table` - Client dependency
5. `create_quotes_table` - Client dependency
6. `create_quote_items_table` - Quote dependency
7. `create_invoices_table` - Client, project, quote dependencies
8. `create_invoice_items_table` - Invoice dependency
9. `create_tasks_table` - Client, project, user dependencies
10. `create_time_entries_table` - Project, user dependencies

### Key Database Constraints

**Foreign Keys**:
- All foreign keys use `constrained()` for referential integrity
- Client deletion cascades to contacts
- Quote/Invoice deletion cascades to line items
- Project deletion may need special handling (prevent if has invoices)

**Unique Constraints**:
- Invoice numbers must be unique per year (composite index)
- Quote numbers should be unique (recommended)
- Email addresses on clients (optional, for lookups)

**Check Constraints** (PostgreSQL) / Validation (MySQL):
- Positive values for prices, quantities, hours
- Valid enum values for status fields
- Date ranges (due_date >= invoice_date)

### Full Schema Reference

See detailed schema in individual migration files:
- `/database/migrations/YYYY_MM_DD_create_[table]_table.php`

