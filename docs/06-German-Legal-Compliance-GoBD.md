# German Legal Compliance (GoBD)

## What is GoBD?

**GoBD** (Grundsätze zur ordnungsmäßigen Führung und Aufbewahrung von Büchern, Aufzeichnungen und Unterlagen in elektronischer Form sowie zum Datenzugriff) are German tax compliance regulations that govern how businesses must handle electronic accounting records.

### Key GoBD Requirements for CRM Systems

#### 1. Completeness (Vollständigkeit)
- All business transactions must be recorded without gaps
- **Invoice numbering must be sequential without missing numbers**
- Deleted invoices must be marked as cancelled, not removed

**Implementation**:
- Use database transactions with row-level locking for number generation
- Soft deletes discouraged for invoices (use status='cancelled' instead)
- Audit log for all invoice-related actions

#### 2. Accuracy (Richtigkeit)
- Data must be correct and verifiable
- Calculations must be accurate and reproducible
- No retroactive changes to sent invoices

**Implementation**:
- Server-side validation for all financial calculations
- Automated calculation of totals, tax amounts
- Unit tests for calculation accuracy

#### 3. Timeliness (Zeitgerechtheit)
- Business transactions must be recorded promptly
- Invoice dates must reflect actual transaction dates
- Creation and modification timestamps required

**Implementation**:
- Automatic timestamp tracking (created_at, updated_at, sent_at)
- Service period fields to document when work was performed
- Date validation (e.g., invoice_date cannot be in future)

#### 4. Order (Ordnung)
- Records must be organized systematically
- Easy retrieval of documents required
- Clear structure and naming conventions

**Implementation**:
- Organized file storage: `/storage/invoices/YYYY/INV-YYYY-NNNN.pdf`
- Indexed database columns for fast searches
- Consistent numbering scheme

#### 5. Immutability (Unveränderbarkeit)
- **Once an invoice is sent, it cannot be modified**
- Changes require cancellation and new invoice (credit note process)
- Original records must be preserved

**Implementation**:
```php
// Model observer prevents editing sent invoices
protected static function booted()
{
    static::updating(function ($invoice) {
        if ($invoice->getOriginal('sent_at') && $invoice->isDirty()) {
            $allowedChanges = ['status', 'paid_at', 'cancelled_at'];
            $actualChanges = array_keys($invoice->getDirty());
            $invalidChanges = array_diff($actualChanges, $allowedChanges);
            
            if (!empty($invalidChanges)) {
                throw new \Exception(
                    'Sent invoices cannot be modified per GoBD compliance. ' .
                    'Create a credit note instead.'
                );
            }
        }
    });
}
```

#### 6. Retention Period (Aufbewahrungspflicht)
- **10-year retention requirement** for accounting documents
- Invoices, credit notes, payment records must be kept
- Both digital and physical copies acceptable (digital preferred)

**Implementation**:
- No hard deletion of invoices (prevent delete via model)
- PDF snapshots stored permanently when invoice is sent
- Automated backups with long retention policy
- Archive old records but keep them accessible

### Sequential Invoice Numbering

**Critical GoBD Requirement**: Invoice numbers must be sequential without gaps.

**Compliant Implementation**:
```php
// app/Services/InvoiceService.php
public function generateInvoiceNumber(): string
{
    return DB::transaction(function () {
        $prefix = config('crm.invoice.number_prefix', 'INV');
        $year = now()->year;
        $padding = config('crm.invoice.number_padding', 4);
        
        // Lock table to prevent race conditions
        $lastInvoice = Invoice::lockForUpdate()
            ->whereYear('invoice_date', $year)
            ->orderBy('id', 'desc') // Use ID, not invoice_number for safety
            ->first();
        
        // Extract number from last invoice
        $lastNumber = $lastInvoice 
            ? (int) substr($lastInvoice->invoice_number, -$padding)
            : 0;
        
        $nextNumber = $lastNumber + 1;
        
        // Format with year and padding
        $invoiceNumber = sprintf(
            '%s-%d-%0' . $padding . 'd',
            $prefix,
            $year,
            $nextNumber
        );
        
        // Verify uniqueness (safety check)
        if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            throw new \Exception(
                'Invoice number collision detected: ' . $invoiceNumber
            );
        }
        
        return $invoiceNumber;
    });
}
```

**Numbering Strategies**:
1. **Yearly reset** (Recommended): INV-2025-0001, INV-2025-0002, ..., INV-2026-0001
   - Easier to manage
   - Common practice in Germany
   - Clear year reference

2. **Continuous**: INV-0001, INV-0002, INV-0003, ...
   - Never resets
   - Simpler logic
   - Larger numbers over time

### Mandatory Invoice Fields (Pflichtangaben)

According to **§14 UStG**, invoices must include:

| Field | German Term | Config/Model Field | Required |
|-------|-------------|-------------------|----------|
| Seller name | Vollständiger Name | `config('crm.company.legal_name')` | ✓ |
| Seller address | Anschrift | `config('crm.company.address_*')` | ✓ |
| Tax ID | Steuernummer/USt-IdNr | `config('crm.tax.tax_number')` | ✓ |
| Invoice date | Rechnungsdatum | `invoice.invoice_date` | ✓ |
| Invoice number | Rechnungsnummer | `invoice.invoice_number` | ✓ |
| Customer name | Empfängername | `client.name` / `client.company` | ✓ |
| Customer address | Empfängeranschrift | `client.address_*` | ✓ |
| Service date/period | Leistungsdatum | `invoice.service_period_*` | ✓ |
| Service description | Leistungsbeschreibung | `invoice_items.description` | ✓ |
| Net amount | Nettobetrag | `invoice.subtotal` | ✓ |
| Tax rate | Steuersatz | `invoice.tax_rate` | ✓ |
| Tax amount | Steuerbetrag | `invoice.tax_amount` | ✓ |
| Gross amount | Bruttobetrag | `invoice.total` | ✓ |
| Payment terms | Zahlungsbedingungen | `invoice.payment_terms_text` | Recommended |
| Bank details | Bankverbindung | `config('crm.bank.*')` | Recommended |

### Special Tax Cases

**Reverse Charge (Umkehrung der Steuerschuldnerschaft)**:
- For EU B2B transactions
- Tax rate: 0%
- Required text: "Steuerschuldnerschaft des Leistungsempfängers gemäß §13b UStG"

**Small Business Exemption (Kleinunternehmerregelung)**:
- If using §19 UStG exemption
- Tax rate: 0%
- Required text: "Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß §19 UStG"

**Reduced Tax Rate (Ermäßigter Steuersatz)**:
- 7% for certain goods/services (books, food, cultural events)
- Clearly specify which items use reduced rate

### GoBD-Compliant Workflows

**Creating an Invoice**:
1. Draft invoice created (status='draft', no invoice_number yet)
2. Edit line items, calculations automatic
3. Validate all Pflichtangaben present
4. **When sending**: Generate invoice_number, create PDF snapshot, mark as sent
5. PDF and record are now immutable

**Correcting Errors**:
- **Before sending**: Edit draft freely
- **After sending**: Cannot edit - must create credit note (Stornorechnung)

**Credit Note Process** (Future Implementation):
1. Original invoice remains unchanged
2. Create new invoice with negative amounts
3. Reference original invoice number
4. Both invoices maintained in system

### Audit Requirements

**What Tax Auditors Need**:
- Access to all invoices (searchable, filterable)
- Proof of sequential numbering
- Timestamps of creation and modifications
- Ability to export data (PDF, CSV, DATEV format)

**Implementation**:
```php
// app/Services/AuditExportService.php
public function exportInvoicesForAudit($year)
{
    return Invoice::whereYear('invoice_date', $year)
        ->orderBy('invoice_number')
        ->get()
        ->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'client_name' => $invoice->client->name,
                'net_amount' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'gross_amount' => $invoice->total,
                'status' => $invoice->status,
                'pdf_path' => $invoice->pdf_path,
            ];
        });
}
```

### Recommended Tools for Enhanced Compliance

**Activity Logging** (`spatie/laravel-activitylog`):
```bash
composer require spatie/laravel-activitylog
```
- Track all changes to invoices
- Log who created, sent, modified records
- Provide audit trail for tax authorities

**DATEV Export** (Future):
- DATEV is standard accounting software in Germany
- Export invoices in DATEV format for accountant
- Libraries: Custom implementation or `datev-php`

