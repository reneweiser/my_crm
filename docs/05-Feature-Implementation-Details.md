# Feature Implementation Details

## 1. Client & Project Management

#### Overview
The foundation of the CRM system. All business activities revolve around clients and their projects.

#### Models & Relationships

**Client Model** (`app/Models/Client.php`):
```php
class Client extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name', 'company', 'address_line_1', 'address_line_2',
        'postal_code', 'city', 'country', 'email', 'phone', 
        'website', 'notes'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // Relationships
    public function contacts() { return $this->hasMany(Contact::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function quotes() { return $this->hasMany(Quote::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function tasks() { return $this->hasMany(Task::class); }
    
    // Helper methods
    public function primaryContact() {
        return $this->contacts()->where('is_primary', true)->first();
    }
    
    public function fullAddress() {
        return trim(implode("\n", array_filter([
            $this->address_line_1,
            $this->address_line_2,
            trim($this->postal_code . ' ' . $this->city),
            $this->country,
        ])));
    }
}
```

**Contact Model** (`app/Models/Contact.php`):
```php
class Contact extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 
        'position', 'is_primary'
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
    ];
    
    public function client() { return $this->belongsTo(Client::class); }
}
```

**Project Model** (`app/Models/Project.php` - to be implemented):
```php
class Project extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'client_id', 'name', 'description', 'status',
        'rate_type', 'hourly_rate', 'fixed_price',
        'budget_hours', 'start_date', 'end_date'
    ];
    
    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'budget_hours' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
    public function client() { return $this->belongsTo(Client::class); }
    public function timeEntries() { return $this->hasMany(TimeEntry::class); }
    public function tasks() { return $this->hasMany(Task::class); }
    public function quotes() { return $this->hasMany(Quote::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    
    // Business logic
    public function totalHoursLogged() {
        return $this->timeEntries()->sum('hours');
    }
    
    public function totalBillableHours() {
        return $this->timeEntries()->where('billable', true)->sum('hours');
    }
    
    public function isOverBudget() {
        return $this->budget_hours && 
               $this->totalHoursLogged() > $this->budget_hours;
    }
}
```

**TimeEntry Model** (`app/Models/TimeEntry.php` - to be implemented):
```php
class TimeEntry extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'project_id', 'user_id', 'description',
        'date', 'hours', 'billable', 'invoiced', 'invoice_id'
    ];
    
    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'billable' => 'boolean',
        'invoiced' => 'boolean',
    ];
    
    public function project() { return $this->belongsTo(Project::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
```

#### Filament Resources

**ClientResource** (`app/Filament/Resources/ClientResource.php`):
- Table: Searchable by name, company, email
- Filters: Country, has active projects
- Actions: View, edit, delete (with confirmation)
- Bulk actions: Delete selected
- Relation managers: ContactsRelationManager, ProjectsRelationManager, QuotesRelationManager, InvoicesRelationManager

**ContactsRelationManager**:
- Inline editing for quick updates
- Primary contact toggle
- Email/phone click-to-action links

**ProjectResource** (to be implemented):
- Table: Filter by status, client, rate type
- Forms: Dynamic fields based on rate_type selection
- Actions: Archive project, view time entries
- Widgets: Project profitability, hours vs budget

#### Business Logic & Services

**Service Layer** (`app/Services/ClientService.php` - to be implemented):
- `createClient(array $data)`: Validate and create client
- `addPrimaryContact(Client $client, array $data)`: Create and set primary contact
- `mergeClients(Client $from, Client $to)`: Merge duplicate clients
- `archiveClient(Client $client)`: Soft delete with validation

### 2. Quote Generation

#### Overview
Professional quote/proposal generation with workflow management, versioning, and one-click conversion to invoices.

#### Models & Relationships

**Quote Model** (`app/Models/Quote.php` - to be implemented):
```php
class Quote extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'client_id', 'project_id', 'quote_number', 'version',
        'status', 'valid_until', 'sent_at', 'accepted_at',
        'notes', 'client_notes', 'subtotal', 'tax_rate',
        'tax_amount', 'total'
    ];
    
    protected $casts = [
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function items() { return $this->hasMany(QuoteItem::class); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    
    // Business logic
    public function calculateTotals() {
        $this->subtotal = $this->items()->sum('total');
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount;
        $this->save();
    }
    
    public function isExpired() {
        return $this->valid_until && 
               $this->valid_until->isPast() && 
               $this->status !== 'accepted';
    }
    
    public function isDraft() {
        return $this->status === 'draft';
    }
    
    public function canBeEdited() {
        return in_array($this->status, ['draft']);
    }
    
    public function canBeConverted() {
        return $this->status === 'accepted' && !$this->invoice;
    }
}
```

**QuoteItem Model** (`app/Models/QuoteItem.php` - to be implemented):
```php
class QuoteItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'quote_id', 'description', 'quantity',
        'unit_price', 'total', 'sort_order'
    ];
    
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];
    
    public function quote() { return $this->belongsTo(Quote::class); }
    
    // Auto-calculate total on save
    protected static function booted() {
        static::saving(function ($item) {
            $item->total = $item->quantity * $item->unit_price;
        });
        
        static::saved(function ($item) {
            $item->quote->calculateTotals();
        });
    }
}
```

#### Features & Workflow

**Quote Number Generation**:
```php
// app/Services/QuoteService.php
public function generateQuoteNumber(): string
{
    $prefix = config('crm.quote.number_prefix', 'Q');
    $year = now()->year;
    $padding = config('crm.quote.number_padding', 4);
    
    $lastQuote = Quote::whereYear('created_at', $year)
        ->orderBy('quote_number', 'desc')
        ->first();
    
    $nextNumber = $lastQuote 
        ? (int) substr($lastQuote->quote_number, -$padding) + 1
        : 1;
    
    return sprintf(
        '%s-%d-%0' . $padding . 'd',
        $prefix,
        $year,
        $nextNumber
    );
}
```

**Status Workflow**:
1. **Draft**: Editable, not sent to client
2. **Sent**: Sent to client, awaiting response (sent_at timestamp set)
3. **Accepted**: Client accepted (accepted_at timestamp set)
4. **Rejected**: Client declined
5. **Converted**: Accepted and converted to invoice

**Tax Calculation**:
- Default tax rate from `config('crm.tax.default_rate')` (19% for Germany)
- Support for reduced rate (7%) for specific services
- Reverse charge (0%) for EU B2B transactions

**Version Control**:
- When editing a sent quote, create new version
- Keep previous versions for audit trail
- Version field increments: v1, v2, v3...

**Quote to Invoice Conversion**:
```php
// app/Services/InvoiceService.php
public function createFromQuote(Quote $quote): Invoice
{
    DB::transaction(function () use ($quote) {
        $invoice = Invoice::create([
            'client_id' => $quote->client_id,
            'project_id' => $quote->project_id,
            'quote_id' => $quote->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(config('crm.invoice.default_payment_terms')),
            'status' => 'draft',
            'tax_rate' => $quote->tax_rate,
            'subtotal' => $quote->subtotal,
            'tax_amount' => $quote->tax_amount,
            'total' => $quote->total,
        ]);
        
        // Copy line items
        foreach ($quote->items as $quoteItem) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $quoteItem->description,
                'quantity' => $quoteItem->quantity,
                'unit_price' => $quoteItem->unit_price,
                'total' => $quoteItem->total,
            ]);
        }
        
        // Update quote status
        $quote->update(['status' => 'converted']);
        
        return $invoice;
    });
}
```

#### Filament Implementation

**QuoteResource** (`app/Filament/Resources/QuoteResource.php` - to be implemented):

**Table Configuration**:
- Columns: Quote number, client, project, total, status, valid until
- Filters: Status, client, date range
- Search: Quote number, client name
- Badges: Status with color coding (draft=gray, sent=blue, accepted=green)

**Form Configuration**:
```php
Forms\Components\Section::make('Client Information')
    ->schema([
        Forms\Components\Select::make('client_id')
            ->relationship('client', 'name')
            ->required()
            ->searchable()
            ->reactive()
            ->afterStateUpdated(fn ($state, callable $set) => 
                $set('project_id', null)
            ),
        Forms\Components\Select::make('project_id')
            ->relationship('project', 'name', 
                fn ($query, $get) => 
                    $query->where('client_id', $get('client_id'))
            )
            ->searchable(),
    ]),

Forms\Components\Section::make('Quote Details')
    ->schema([
        Forms\Components\TextInput::make('quote_number')
            ->disabled()
            ->dehydrated(false),
        Forms\Components\DatePicker::make('valid_until')
            ->default(now()->addDays(config('crm.quote.default_validity_days')))
            ->required(),
    ]),

Forms\Components\Section::make('Line Items')
    ->schema([
        Forms\Components\Repeater::make('items')
            ->relationship()
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->columnSpan(3),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\Placeholder::make('total')
                    ->content(fn ($get) => 
                        '€' . number_format(
                            $get('quantity') * $get('unit_price'), 
                            2
                        )
                    ),
            ])
            ->columns(6)
            ->defaultItems(1)
            ->reorderable(),
    ]),

Forms\Components\Section::make('Totals')
    ->schema([
        Forms\Components\TextInput::make('tax_rate')
            ->numeric()
            ->suffix('%')
            ->default(config('crm.tax.default_rate'))
            ->required(),
        Forms\Components\Placeholder::make('calculated_total')
            ->label('Total')
            ->content(fn ($record) => 
                '€' . number_format($record?->total ?? 0, 2)
            ),
    ]),
```

**Custom Actions**:
```php
// Send quote via email
Tables\Actions\Action::make('send')
    ->icon('heroicon-o-envelope')
    ->requiresConfirmation()
    ->action(function (Quote $record) {
        Mail::to($record->client->email)
            ->send(new QuoteSent($record));
        
        $record->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        
        Notification::make()
            ->success()
            ->title('Quote sent successfully')
            ->send();
    })
    ->visible(fn (Quote $record) => $record->isDraft()),

// Convert to invoice
Tables\Actions\Action::make('convert')
    ->icon('heroicon-o-document-duplicate')
    ->requiresConfirmation()
    ->action(function (Quote $record, InvoiceService $service) {
        $invoice = $service->createFromQuote($record);
        
        Notification::make()
            ->success()
            ->title('Invoice created successfully')
            ->actions([
                NotificationAction::make('view')
                    ->url(InvoiceResource::getUrl('edit', ['record' => $invoice])),
            ])
            ->send();
    })
    ->visible(fn (Quote $record) => $record->canBeConverted()),

// Download PDF
Tables\Actions\Action::make('download')
    ->icon('heroicon-o-arrow-down-tray')
    ->action(function (Quote $record, PdfService $pdf) {
        return response()->streamDownload(
            fn () => print($pdf->generateQuotePdf($record)),
            "quote-{$record->quote_number}.pdf"
        );
    }),
```

### 3. Invoice Generation (GoBD Compliant)

#### Overview
Legally compliant German invoicing system that meets all requirements of §14 UStG (German VAT Act) and GoBD (German tax compliance regulations). Invoices are immutable once sent and must maintain a complete audit trail.

#### Models & Relationships

**Invoice Model** (`app/Models/Invoice.php` - to be implemented):
```php
class Invoice extends Model
{
    use HasFactory;
    
    // Disable soft deletes for invoices (legal requirement)
    // Once created, invoices must be preserved permanently
    
    protected $fillable = [
        'client_id', 'project_id', 'quote_id', 'invoice_number',
        'invoice_date', 'due_date', 'service_period_start', 
        'service_period_end', 'status', 'payment_terms',
        'payment_terms_text', 'sent_at', 'paid_at', 'cancelled_at',
        'notes', 'client_notes', 'subtotal', 'tax_rate',
        'tax_amount', 'total', 'pdf_path'
    ];
    
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    protected $appends = ['is_overdue'];
    
    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function quote() { return $this->belongsTo(Quote::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function timeEntries() { return $this->hasMany(TimeEntry::class); }
    
    // Business logic
    public function calculateTotals() {
        $this->subtotal = $this->items()->sum('total');
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount;
        $this->save();
    }
    
    public function getIsOverdueAttribute(): bool {
        return $this->status === 'sent' && 
               $this->due_date && 
               $this->due_date->isPast();
    }
    
    public function isDraft() {
        return $this->status === 'draft';
    }
    
    public function isSent() {
        return in_array($this->status, ['sent', 'paid', 'overdue']);
    }
    
    public function canBeEdited() {
        // GoBD compliance: invoices cannot be edited once sent
        return $this->status === 'draft';
    }
    
    public function canBeCancelled() {
        return $this->status !== 'cancelled' && $this->isSent();
    }
    
    // Update overdue status automatically
    public function updateOverdueStatus() {
        if ($this->is_overdue) {
            $this->update(['status' => 'overdue']);
        }
    }
}
```

**InvoiceItem Model** (`app/Models/InvoiceItem.php` - to be implemented):
```php
class InvoiceItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'invoice_id', 'description', 'quantity',
        'unit_price', 'tax_rate', 'total', 'sort_order'
    ];
    
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];
    
    public function invoice() { return $this->belongsTo(Invoice::class); }
    
    // Auto-calculate total on save
    protected static function booted() {
        static::saving(function ($item) {
            $item->total = $item->quantity * $item->unit_price;
        });
        
        static::saved(function ($item) {
            // Only recalculate if invoice is still draft
            if ($item->invoice->isDraft()) {
                $item->invoice->calculateTotals();
            }
        });
        
        // Prevent editing items of sent invoices
        static::updating(function ($item) {
            if ($item->invoice->isSent()) {
                throw new \Exception(
                    'Cannot edit items of a sent invoice. Create a credit note instead.'
                );
            }
        });
    }
}
```

#### German Legal Requirements (Pflichtangaben)

According to **§14 UStG** and **§14a UStG**, German invoices must include:

**Mandatory Invoice Elements**:
1. **Complete seller information** (Vollständiger Name und Anschrift):
   - Legal company name
   - Complete address (street, postal code, city, country)
   
2. **Tax identification number** (Steuernummer oder USt-IdNr):
   - Either Steuernummer (national tax ID)
   - Or USt-IdNr (EU VAT ID) for EU transactions
   
3. **Invoice date** (Rechnungsdatum):
   - Date when invoice was issued
   
4. **Unique invoice number** (Fortlaufende Rechnungsnummer):
   - Must be sequential without gaps
   - Must be unique and never reused
   - Can include year/month prefixes (e.g., INV-2025-0001)
   
5. **Complete customer information** (Name und Anschrift des Leistungsempfängers):
   - Customer name (company or person)
   - Complete billing address
   
6. **Delivery/service date** (Zeitpunkt der Lieferung/Leistung):
   - Exact date or period when service was performed
   - Can use service_period_start/end fields
   
7. **Description of services** (Art und Umfang der Leistung):
   - Clear, detailed description of each line item
   - Sufficient detail to identify the service/product
   
8. **Net amounts by tax rate** (Nettobetrag je Steuersatz):
   - Breakdown of amounts per tax rate (19%, 7%, 0%)
   
9. **Tax rate and tax amount** (Steuersatz und Steuerbetrag):
   - Percentage rate (e.g., 19%)
   - Calculated tax amount in euros
   
10. **Gross total** (Bruttobetrag):
    - Final amount to be paid (net + tax)
    
11. **Payment terms** (Zahlungsbedingungen - optional but recommended):
    - Due date or payment period
    - Bank details (IBAN, BIC, account holder)
    - Payment methods accepted

**Special Cases**:
- **Reverse charge** (Umkehrung der Steuerschuldnerschaft): For EU B2B, add text "Steuerschuldnerschaft des Leistungsempfängers" and use 0% tax
- **Small amounts** (Kleinbetragsrechnung): Invoices under €250 have reduced requirements
- **Kleinunternehmer** (Small business tax exemption §19 UStG): Add text "Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß §19 UStG"

#### GoBD Compliance Implementation

**Sequential Number Generation** (No Gaps Allowed):
```php
// app/Services/InvoiceService.php
public function generateInvoiceNumber(): string
{
    return DB::transaction(function () {
        $prefix = config('crm.invoice.number_prefix', 'INV');
        $year = now()->year;
        $padding = config('crm.invoice.number_padding', 4);
        
        // Lock the table to prevent race conditions
        $lastInvoice = Invoice::lockForUpdate()
            ->whereYear('invoice_date', $year)
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        $nextNumber = $lastInvoice 
            ? (int) substr($lastInvoice->invoice_number, -$padding) + 1
            : 1;
        
        $invoiceNumber = sprintf(
            '%s-%d-%0' . $padding . 'd',
            $prefix,
            $year,
            $nextNumber
        );
        
        // Verify uniqueness
        if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            throw new \Exception('Invoice number collision detected');
        }
        
        return $invoiceNumber;
    });
}
```

**Immutability Enforcement**:
```php
// In Invoice model
protected static function booted()
{
    // Prevent updates to sent invoices
    static::updating(function ($invoice) {
        if ($invoice->getOriginal('sent_at') && $invoice->isDirty()) {
            $allowedChanges = ['status', 'paid_at', 'cancelled_at'];
            $actualChanges = array_keys($invoice->getDirty());
            $invalidChanges = array_diff($actualChanges, $allowedChanges);
            
            if (!empty($invalidChanges)) {
                throw new \Exception(
                    'Sent invoices cannot be modified. Create a credit note instead. ' .
                    'Changed fields: ' . implode(', ', $invalidChanges)
                );
            }
        }
    });
    
    // Prevent deletion (GoBD requires permanent storage)
    static::deleting(function ($invoice) {
        throw new \Exception(
            'Invoices cannot be deleted due to GoBD compliance. ' .
            'Use cancellation status instead.'
        );
    });
}
```

**Audit Trail Requirements**:
- All invoice changes must be logged (consider `spatie/laravel-activitylog`)
- Store creation timestamp, sent timestamp, payment timestamp
- Keep PDF snapshot of invoice when sent (store in `pdf_path`)
- 10-year retention requirement (Aufbewahrungspflicht)

#### Features & Workflow

**Invoice Number Format**:
- Configurable prefix (default: "INV")
- Year-based numbering: INV-2025-0001, INV-2025-0002
- Padding configurable (default: 4 digits)
- Numbers restart each year (recommended) or continuous

**Status Workflow**:
1. **Draft**: Editable, not sent to client, no invoice number assigned yet
2. **Sent**: Sent to client via email (sent_at timestamp, PDF generated and stored)
3. **Paid**: Payment received (paid_at timestamp)
4. **Overdue**: Past due date without payment (automatically set)
5. **Cancelled**: Cancelled via credit note (cancelled_at timestamp)

**Due Date Calculation**:
```php
// Automatically set due date based on payment terms
$invoice->due_date = $invoice->invoice_date
    ->addDays($invoice->payment_terms ?? config('crm.invoice.default_payment_terms'));
```

**Payment Terms Text**:
- Configurable template: "Zahlbar innerhalb von {days} Tagen netto ohne Abzug."
- Common German terms:
  - "Zahlbar sofort ohne Abzug" (immediately)
  - "Zahlbar innerhalb von 14 Tagen" (14 days net)
  - "Zahlbar innerhalb von 30 Tagen" (30 days net)

**Time Entry Integration**:
```php
// Create invoice from unbilled time entries
public function createFromTimeEntries(Project $project, array $timeEntryIds): Invoice
{
    $timeEntries = TimeEntry::whereIn('id', $timeEntryIds)
        ->where('project_id', $project->id)
        ->where('billable', true)
        ->where('invoiced', false)
        ->get();
    
    DB::transaction(function () use ($project, $timeEntries) {
        $invoice = Invoice::create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(config('crm.invoice.default_payment_terms')),
            'service_period_start' => $timeEntries->min('date'),
            'service_period_end' => $timeEntries->max('date'),
            'status' => 'draft',
            'tax_rate' => config('crm.tax.default_rate'),
        ]);
        
        // Group time entries by description
        $grouped = $timeEntries->groupBy('description');
        
        foreach ($grouped as $description => $entries) {
            $totalHours = $entries->sum('hours');
            
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'quantity' => $totalHours,
                'unit_price' => $project->hourly_rate,
            ]);
        }
        
        // Mark time entries as invoiced
        $timeEntries->each(function ($entry) use ($invoice) {
            $entry->update([
                'invoiced' => true,
                'invoice_id' => $invoice->id,
            ]);
        });
        
        $invoice->calculateTotals();
        
        return $invoice;
    });
}
```

#### Filament Implementation

**InvoiceResource** (`app/Filament/Resources/InvoiceResource.php` - to be implemented):

**Table Configuration**:
- Columns: Invoice number, client, date, due date, total, status
- Filters: Status, date range, client, overdue
- Search: Invoice number, client name
- Badges: Status with color coding
- Sort: Default by invoice_date desc

**Form Configuration**:
```php
// Forms disabled for sent invoices
public static function canEdit(Model $record): bool
{
    return $record->isDraft();
}

Forms\Components\Section::make('Invoice Information')
    ->schema([
        Forms\Components\TextInput::make('invoice_number')
            ->disabled()
            ->label('Invoice Number')
            ->helperText('Generated automatically when invoice is sent'),
        
        Forms\Components\DatePicker::make('invoice_date')
            ->required()
            ->default(now()),
        
        Forms\Components\TextInput::make('payment_terms')
            ->numeric()
            ->suffix('days')
            ->default(config('crm.invoice.default_payment_terms'))
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, $get) {
                if ($invoiceDate = $get('invoice_date')) {
                    $set('due_date', 
                        Carbon::parse($invoiceDate)->addDays($state)
                    );
                }
            }),
        
        Forms\Components\DatePicker::make('due_date')
            ->required(),
    ])
    ->disabled(fn ($record) => $record && !$record->isDraft()),

// Rest of form similar to Quote...
```

**Custom Actions**:
```php
// Send invoice via email (generates PDF and invoice number)
Tables\Actions\Action::make('send')
    ->icon('heroicon-o-envelope')
    ->requiresConfirmation()
    ->modalDescription('This will generate a permanent invoice number and send the invoice to the client. This action cannot be undone.')
    ->action(function (Invoice $record, InvoiceService $service, PdfService $pdf) {
        DB::transaction(function () use ($record, $service, $pdf) {
            // Generate invoice number if not already assigned
            if (!$record->invoice_number) {
                $record->invoice_number = $service->generateInvoiceNumber();
            }
            
            // Generate and store PDF
            $pdfContent = $pdf->generateInvoicePdf($record);
            $pdfPath = storage_path("app/invoices/{$record->invoice_number}.pdf");
            file_put_contents($pdfPath, $pdfContent);
            
            // Update invoice
            $record->update([
                'status' => 'sent',
                'sent_at' => now(),
                'pdf_path' => $pdfPath,
            ]);
            
            // Send email
            Mail::to($record->client->email)
                ->send(new InvoiceSent($record));
        });
        
        Notification::make()
            ->success()
            ->title('Invoice sent successfully')
            ->send();
    })
    ->visible(fn (Invoice $record) => $record->isDraft()),

// Mark as paid
Tables\Actions\Action::make('mark_paid')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->action(function (Invoice $record) {
        $record->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        
        Notification::make()
            ->success()
            ->title('Invoice marked as paid')
            ->send();
    })
    ->visible(fn (Invoice $record) => 
        in_array($record->status, ['sent', 'overdue'])
    ),

// Cancel invoice (requires credit note in future)
Tables\Actions\Action::make('cancel')
    ->icon('heroicon-o-x-circle')
    ->color('danger')
    ->requiresConfirmation()
    ->modalDescription('Cancelling an invoice requires creating a credit note for GoBD compliance.')
    ->action(function (Invoice $record) {
        $record->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        // TODO: Auto-create credit note
        
        Notification::make()
            ->warning()
            ->title('Invoice cancelled')
            ->body('Remember to create a credit note for accounting records.')
            ->send();
    })
    ->visible(fn (Invoice $record) => $record->canBeCancelled()),
```

**Validation Rules**:
```php
// Ensure all Pflichtangaben are present before sending
public function validateForSending(Invoice $invoice): array
{
    $errors = [];
    
    // Company information from config
    if (!config('crm.company.name')) {
        $errors[] = 'Company name not configured';
    }
    
    if (!config('crm.tax.tax_number') && !config('crm.tax.vat_id')) {
        $errors[] = 'Tax ID (Steuernummer or USt-IdNr) not configured';
    }
    
    // Client information
    if (!$invoice->client->address_line_1 || !$invoice->client->city) {
        $errors[] = 'Client address incomplete';
    }
    
    // Invoice requirements
    if (!$invoice->invoice_date) {
        $errors[] = 'Invoice date required';
    }
    
    if ($invoice->items()->count() === 0) {
        $errors[] = 'At least one line item required';
    }
    
    if (!$invoice->service_period_start && !$invoice->service_period_end) {
        $errors[] = 'Service period required for GoBD compliance';
    }
    
    return $errors;
}
```

### 4. PDF Generation

#### Overview
Professional PDF generation for quotes and invoices using Blade templates and DomPDF. PDFs must be print-ready, legally compliant, and professionally branded.

#### Technology Stack

**PDF Library**: `barryvdh/laravel-dompdf`
```bash
composer require barryvdh/laravel-dompdf
```

**Alternative Options**:
- `spatie/laravel-pdf` (wrapper for multiple PDF engines)
- Browsershot (Puppeteer-based, better CSS support)
- wkhtmltopdf (external binary, excellent rendering)

#### PDF Service Implementation

**Service Class** (`app/Services/PdfService.php` - to be implemented):
```php
namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'company' => config('crm.company'),
            'tax' => config('crm.tax'),
            'bank' => config('crm.bank'),
        ]);
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Set additional options
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);
        
        return $pdf->output();
    }
    
    public function generateQuotePdf(Quote $quote): string
    {
        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'client' => $quote->client,
            'items' => $quote->items,
            'company' => config('crm.company'),
            'tax' => config('crm.tax'),
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->output();
    }
    
    public function downloadInvoice(Invoice $invoice): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = $this->generateInvoicePdf($invoice);
        
        return response()->streamDownload(
            fn () => print($pdf),
            "invoice-{$invoice->invoice_number}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function downloadQuote(Quote $quote): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = $this->generateQuotePdf($quote);
        
        return response()->streamDownload(
            fn () => print($pdf),
            "quote-{$quote->quote_number}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
```

#### Template Design Requirements

**Visual Design Principles**:
- Clean, professional appearance
- Consistent typography (max 2-3 font sizes)
- Adequate white space for readability
- Company branding (logo, colors)
- Print-optimized (no background colors/images)
- A4 paper size (210mm × 297mm)
- Safe print margins (minimum 15mm)

**Layout Structure**:
1. **Header**: Company logo, contact info, document title
2. **Document details**: Invoice/quote number, date, due date
3. **Client information**: Bill-to address
4. **Line items table**: Description, quantity, unit price, total
5. **Totals section**: Subtotal, tax, grand total
6. **Footer**: Payment terms, bank details, legal text, page numbers

#### Invoice Template

**Blade Template** (`resources/views/pdf/invoice.blade.php` - to be implemented):
```html
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $invoice->invoice_number }}</title>
    <style>
        /* Reset and base styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            padding: 15mm;
        }
        
        /* Header section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10mm;
        }
        
        .header-left {
            display: table-cell;
            width: 60%;
        }
        
        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        
        .company-logo {
            max-width: 150px;
            max-height: 60px;
            margin-bottom: 5mm;
        }
        
        .company-info {
            font-size: 8pt;
            color: #666;
        }
        
        /* Invoice title */
        .invoice-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5mm;
        }
        
        /* Addresses */
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 10mm;
        }
        
        .address-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .address-label {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 2mm;
        }
        
        .address-content {
            font-size: 10pt;
            line-height: 1.4;
        }
        
        /* Invoice details */
        .invoice-details {
            background: #f5f5f5;
            padding: 5mm;
            margin-bottom: 10mm;
        }
        
        .invoice-details table {
            width: 100%;
        }
        
        .invoice-details td {
            padding: 2mm 0;
        }
        
        .invoice-details .label {
            font-weight: bold;
            width: 40%;
        }
        
        /* Line items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }
        
        .items-table thead {
            background: #333;
            color: white;
        }
        
        .items-table th {
            padding: 3mm;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 3mm;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .description {
            width: 50%;
        }
        
        .items-table .quantity {
            width: 15%;
            text-align: center;
        }
        
        .items-table .unit-price,
        .items-table .total {
            width: 17.5%;
            text-align: right;
        }
        
        /* Totals section */
        .totals {
            float: right;
            width: 50%;
            margin-bottom: 10mm;
        }
        
        .totals table {
            width: 100%;
        }
        
        .totals td {
            padding: 2mm 0;
        }
        
        .totals .label {
            text-align: right;
            padding-right: 5mm;
        }
        
        .totals .value {
            text-align: right;
            font-weight: bold;
        }
        
        .totals .grand-total {
            font-size: 12pt;
            border-top: 2px solid #333;
            padding-top: 3mm;
        }
        
        /* Payment terms */
        .payment-terms {
            clear: both;
            background: #f5f5f5;
            padding: 5mm;
            margin-bottom: 10mm;
        }
        
        .payment-terms h3 {
            font-size: 11pt;
            margin-bottom: 3mm;
        }
        
        .bank-details {
            font-size: 9pt;
            line-height: 1.4;
        }
        
        /* Footer */
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 5mm;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        /* Legal text */
        .legal-text {
            font-size: 8pt;
            color: #666;
            margin-bottom: 5mm;
        }
        
        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if(config('crm.company.logo_path'))
                    <img src="{{ storage_path('app/public/' . config('crm.company.logo_path')) }}" 
                         class="company-logo" alt="{{ $company['name'] }}">
                @endif
                <div class="company-info">
                    <strong>{{ $company['legal_name'] }}</strong><br>
                    {{ $company['address_line_1'] }}<br>
                    @if($company['address_line_2'])
                        {{ $company['address_line_2'] }}<br>
                    @endif
                    {{ $company['postal_code'] }} {{ $company['city'] }}<br>
                    {{ $company['country'] }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">RECHNUNG</div>
                <div style="font-size: 9pt;">
                    {{ $company['email'] }}<br>
                    {{ $company['phone'] }}<br>
                    @if($company['website'])
                        {{ $company['website'] }}
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Addresses -->
        <div class="addresses">
            <div class="address-column">
                <div class="address-label">Rechnungsempfänger</div>
                <div class="address-content">
                    <strong>{{ $client->company ?: $client->name }}</strong><br>
                    @if($client->company && $client->name)
                        {{ $client->name }}<br>
                    @endif
                    {{ $client->address_line_1 }}<br>
                    @if($client->address_line_2)
                        {{ $client->address_line_2 }}<br>
                    @endif
                    {{ $client->postal_code }} {{ $client->city }}<br>
                    {{ $client->country }}
                </div>
            </div>
            <div class="address-column">
                <!-- Space for stamps/notes -->
            </div>
        </div>
        
        <!-- Invoice Details -->
        <div class="invoice-details">
            <table>
                <tr>
                    <td class="label">Rechnungsnummer:</td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td class="label">Kundennummer:</td>
                    <td>{{ str_pad($client->id, 5, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <td class="label">Rechnungsdatum:</td>
                    <td>{{ $invoice->invoice_date->format('d.m.Y') }}</td>
                    <td class="label">Fällig am:</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                @if($invoice->service_period_start && $invoice->service_period_end)
                <tr>
                    <td class="label">Leistungszeitraum:</td>
                    <td colspan="3">
                        {{ $invoice->service_period_start->format('d.m.Y') }} - 
                        {{ $invoice->service_period_end->format('d.m.Y') }}
                    </td>
                </tr>
                @endif
            </table>
        </div>
        
        <!-- Line Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="description">Beschreibung</th>
                    <th class="quantity">Menge</th>
                    <th class="unit-price">Einzelpreis</th>
                    <th class="total">Gesamt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td class="description">{{ $item->description }}</td>
                    <td class="quantity">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="unit-price">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td class="total">{{ number_format($item->total, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Zwischensumme:</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td class="label">
                        Umsatzsteuer ({{ number_format($invoice->tax_rate, 0) }}%):
                    </td>
                    <td class="value">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
                </tr>
                <tr class="grand-total">
                    <td class="label">Gesamtbetrag:</td>
                    <td class="value">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        
        <!-- Payment Terms -->
        <div class="payment-terms">
            <h3>Zahlungsinformationen</h3>
            <p>{{ $invoice->payment_terms_text }}</p>
            
            @if($bank['iban'])
            <div class="bank-details">
                <strong>Bankverbindung:</strong><br>
                Kontoinhaber: {{ $bank['account_holder'] ?: $company['legal_name'] }}<br>
                IBAN: {{ $bank['iban'] }}<br>
                @if($bank['bic'])
                    BIC: {{ $bank['bic'] }}<br>
                @endif
                @if($bank['name'])
                    Bank: {{ $bank['name'] }}<br>
                @endif
                Verwendungszweck: {{ $invoice->invoice_number }}
            </div>
            @endif
        </div>
        
        <!-- Legal Text -->
        <div class="legal-text">
            @if($invoice->client_notes)
                <p>{{ $invoice->client_notes }}</p>
            @endif
            
            <p>
                Vielen Dank für Ihr Vertrauen und die gute Zusammenarbeit.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>
                {{ $company['legal_name'] }} | 
                {{ $company['address_line_1'] }}, 
                {{ $company['postal_code'] }} {{ $company['city'] }}<br>
                
                @if($tax['tax_number'])
                    Steuernummer: {{ $tax['tax_number'] }}
                @endif
                
                @if($tax['vat_id'])
                    @if($tax['tax_number']) | @endif
                    USt-IdNr: {{ $tax['vat_id'] }}
                @endif
                
                <br>
                {{ $company['email'] }} | {{ $company['phone'] }}
                @if($company['website'])
                    | {{ $company['website'] }}
                @endif
            </p>
        </div>
    </div>
</body>
</html>
```

#### Quote Template

**Blade Template** (`resources/views/pdf/quote.blade.php` - to be implemented):
Similar structure to invoice template, but with:
- Title: "ANGEBOT" (Quote) instead of "RECHNUNG" (Invoice)
- Valid until date instead of due date
- Optional "Gültig bis" (Valid until) section
- Different footer text (e.g., "Wir freuen uns auf Ihre Auftragserteilung")
- No payment terms section
- Optional acceptance signature area

#### Template Localization

**Multi-language Support**:
```php
// In PdfService, pass locale parameter
public function generateInvoicePdf(Invoice $invoice, string $locale = 'de'): string
{
    $pdf = Pdf::loadView("pdf.invoice-{$locale}", [
        // ... data
        'translations' => trans('pdf', [], $locale),
    ]);
    
    return $pdf->output();
}
```

**Translation Files** (`resources/lang/de/pdf.php` and `resources/lang/en/pdf.php`):
```php
// de/pdf.php
return [
    'invoice' => 'Rechnung',
    'quote' => 'Angebot',
    'invoice_number' => 'Rechnungsnummer',
    'invoice_date' => 'Rechnungsdatum',
    'due_date' => 'Fällig am',
    'service_period' => 'Leistungszeitraum',
    'subtotal' => 'Zwischensumme',
    'tax' => 'Umsatzsteuer',
    'total' => 'Gesamtbetrag',
    // ... more translations
];
```

#### PDF Storage Strategy

**For Sent Invoices** (GoBD Compliance):
```php
// Store PDF permanently when invoice is sent
$pdfPath = storage_path("app/invoices/{$invoice->invoice_date->year}/{$invoice->invoice_number}.pdf");

// Ensure directory exists
Storage::disk('local')->makeDirectory("invoices/{$invoice->invoice_date->year}");

// Save PDF
file_put_contents($pdfPath, $pdfContent);

// Update invoice record
$invoice->update(['pdf_path' => $pdfPath]);
```

**Access Control**:
```php
// Route with authentication
Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
    abort_unless(auth()->check(), 403);
    
    return response()->file($invoice->pdf_path, [
        'Content-Type' => 'application/pdf',
    ]);
})->middleware('auth');
```

### 5. Email Delivery

#### Overview
Automated email delivery system for sending quotes, invoices, and payment reminders to clients with professional formatting and PDF attachments.

#### Technology Stack

**Laravel Mail**:
- Supports multiple drivers: SMTP, Mailgun, Postmark, Amazon SES, Sendmail
- Queue integration for asynchronous sending
- Markdown templates for clean, responsive emails
- Attachment support for PDFs

#### Configuration

**Mail Driver Setup** (`.env`):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Recommended Providers** for German businesses:
- **Mailgun**: Reliable, good EU data center options
- **Postmark**: Excellent deliverability, transactional focus
- **Amazon SES**: Cost-effective, scalable
- **Hosted SMTP**: Self-hosted or provider like Strato, 1&1, etc.

#### Mailable Classes

**Invoice Email** (`app/Mail/InvoiceSent.php` - to be implemented):
```php
namespace App\Mail;

use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public Invoice $invoice
    ) {}
    
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('crm.company.name')
            ),
            replyTo: [
                new Address(
                    config('crm.company.email'),
                    config('crm.company.name')
                ),
            ],
            subject: "Rechnung {$this->invoice->invoice_number} - {$this->invoice->client->name}",
        );
    }
    
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-sent',
            with: [
                'invoiceNumber' => $this->invoice->invoice_number,
                'clientName' => $this->invoice->client->name,
                'total' => number_format($this->invoice->total, 2, ',', '.'),
                'dueDate' => $this->invoice->due_date->format('d.m.Y'),
                'companyName' => config('crm.company.name'),
            ],
        );
    }
    
    public function attachments(): array
    {
        $pdfService = app(PdfService::class);
        
        return [
            Attachment::fromData(
                fn () => $pdfService->generateInvoicePdf($this->invoice),
                "Rechnung-{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
```

**Quote Email** (`app/Mail/QuoteSent.php` - to be implemented):
```php
namespace App\Mail;

use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSent extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public Quote $quote
    ) {}
    
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('crm.company.name')
            ),
            replyTo: [
                new Address(
                    config('crm.company.email'),
                    config('crm.company.name')
                ),
            ],
            subject: "Angebot {$this->quote->quote_number} - {$this->quote->client->name}",
        );
    }
    
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote-sent',
            with: [
                'quoteNumber' => $this->quote->quote_number,
                'clientName' => $this->quote->client->name,
                'total' => number_format($this->quote->total, 2, ',', '.'),
                'validUntil' => $this->quote->valid_until->format('d.m.Y'),
                'companyName' => config('crm.company.name'),
            ],
        );
    }
    
    public function attachments(): array
    {
        $pdfService = app(PdfService::class);
        
        return [
            Attachment::fromData(
                fn () => $pdfService->generateQuotePdf($this->quote),
                "Angebot-{$this->quote->quote_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
```

**Payment Reminder Email** (`app/Mail/PaymentReminder.php` - to be implemented):
```php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminder extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public Invoice $invoice,
        public int $reminderLevel = 1 // 1 = friendly, 2 = firm, 3 = final
    ) {}
    
    public function envelope(): Envelope
    {
        $subjects = [
            1 => "Freundliche Zahlungserinnerung - Rechnung {$this->invoice->invoice_number}",
            2 => "Zweite Zahlungserinnerung - Rechnung {$this->invoice->invoice_number}",
            3 => "Letzte Zahlungserinnerung - Rechnung {$this->invoice->invoice_number}",
        ];
        
        return new Envelope(
            subject: $subjects[$this->reminderLevel] ?? $subjects[1],
        );
    }
    
    public function content(): Content
    {
        return new Content(
            markdown: "emails.payment-reminder-{$this->reminderLevel}",
            with: [
                'invoiceNumber' => $this->invoice->invoice_number,
                'clientName' => $this->invoice->client->name,
                'total' => number_format($this->invoice->total, 2, ',', '.'),
                'dueDate' => $this->invoice->due_date->format('d.m.Y'),
                'daysOverdue' => now()->diffInDays($this->invoice->due_date),
                'companyName' => config('crm.company.name'),
            ],
        );
    }
    
    public function attachments(): array
    {
        // Attach the original invoice PDF
        return [
            Attachment::fromPath($this->invoice->pdf_path)
                ->as("Rechnung-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
```

#### Email Templates

**Invoice Sent Template** (`resources/views/emails/invoice-sent.blade.php` - to be implemented):
```blade
<x-mail::message>
# Rechnung {{ $invoiceNumber }}

Sehr geehrte Damen und Herren,

anbei erhalten Sie unsere Rechnung **{{ $invoiceNumber }}** für die erbrachten Leistungen.

**Rechnungsbetrag:** {{ $total }} €  
**Fällig am:** {{ $dueDate }}

Die Rechnung finden Sie als PDF im Anhang dieser E-Mail.

Für Rückfragen stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen  
{{ $companyName }}

<x-mail::button :url="config('app.url')">
Zum Kundenportal
</x-mail::button>

---
Diese E-Mail wurde automatisch generiert. Bei Fragen antworten Sie bitte direkt auf diese E-Mail.
</x-mail::message>
```

**Quote Sent Template** (`resources/views/emails/quote-sent.blade.php` - to be implemented):
```blade
<x-mail::message>
# Angebot {{ $quoteNumber }}

Sehr geehrte Damen und Herren,

vielen Dank für Ihre Anfrage. Gerne übersenden wir Ihnen unser Angebot **{{ $quoteNumber }}**.

**Angebotssumme:** {{ $total }} €  
**Gültig bis:** {{ $validUntil }}

Das detaillierte Angebot finden Sie als PDF im Anhang.

Bei Fragen oder Anpassungswünschen stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen  
{{ $companyName }}

<x-mail::button :url="config('app.url')">
Angebot ansehen
</x-mail::button>

---
Wir freuen uns auf Ihre Auftragserteilung!
</x-mail::message>
```

**Payment Reminder Template** (`resources/views/emails/payment-reminder-1.blade.php` - to be implemented):
```blade
<x-mail::message>
# Freundliche Zahlungserinnerung

Sehr geehrte Damen und Herren,

wir möchten Sie freundlich daran erinnern, dass die Rechnung **{{ $invoiceNumber }}** seit {{ $daysOverdue }} Tag(en) überfällig ist.

**Rechnungsbetrag:** {{ $total }} €  
**Fälligkeitsdatum:** {{ $dueDate }}

Falls die Zahlung bereits erfolgt ist, betrachten Sie diese E-Mail bitte als gegenstandslos.

Sollten Sie Fragen zur Rechnung haben oder eine Ratenzahlung wünschen, kontaktieren Sie uns bitte.

Mit freundlichen Grüßen  
{{ $companyName }}

<x-mail::button :url="config('app.url')">
Rechnung einsehen
</x-mail::button>

---
Anhang: Rechnung {{ $invoiceNumber }}
</x-mail::message>
```

#### Queue Configuration

**Enable Queues for Email Sending**:
```php
// config/mail.php
'queue' => [
    'enabled' => env('MAIL_QUEUE_ENABLED', true),
    'connection' => env('MAIL_QUEUE_CONNECTION', 'redis'),
    'queue' => env('MAIL_QUEUE_NAME', 'emails'),
],
```

**Queue Worker**:
```bash
# Start queue worker (in docker-compose or supervisor)
./vendor/bin/sail artisan queue:work --queue=emails --tries=3
```

#### Email Tracking

**Track Email Events**:
```php
// app/Models/Invoice.php
public function markAsSent()
{
    $this->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);
    
    // Log activity
    activity('invoice')
        ->performedOn($this)
        ->log("Invoice {$this->invoice_number} sent to {$this->client->email}");
}
```

**Email Event Listeners** (optional for tracking opens/clicks):
```php
// Using services like Mailgun's tracking or Postmark's webhooks
// app/Listeners/EmailSentListener.php
public function handle($event)
{
    // Log email sent event
    logger()->info('Email sent', [
        'mailable' => get_class($event->mailable),
        'to' => $event->message->getTo(),
    ]);
}
```

#### Error Handling

**Failed Email Handling**:
```php
// app/Mail/InvoiceSent.php
public function failed(\Throwable $exception)
{
    // Log failure
    logger()->error('Failed to send invoice email', [
        'invoice_id' => $this->invoice->id,
        'error' => $exception->getMessage(),
    ]);
    
    // Notify admin
    \Illuminate\Support\Facades\Notification::route('mail', config('crm.company.email'))
        ->notify(new \App\Notifications\EmailSendFailure($this->invoice, $exception));
}
```

**Retry Logic**:
```php
// Configure in queue job
class SendInvoiceEmail implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 300, 900]; // Retry after 1min, 5min, 15min
    public $timeout = 120; // 2 minutes timeout
}
```

#### Testing Email Sending

**Test Email Locally**:
```env
# Use log driver for development
MAIL_MAILER=log

# Or use Mailtrap for testing
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

**Unit Tests**:
```php
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;

test('invoice email contains correct data', function () {
    $invoice = Invoice::factory()->create();
    
    $mailable = new InvoiceSent($invoice);
    
    $mailable->assertFrom(config('mail.from.address'));
    $mailable->assertHasSubject("Rechnung {$invoice->invoice_number}");
    $mailable->assertSeeInHtml($invoice->invoice_number);
    $mailable->assertHasAttachment();
});

test('invoice email is sent when invoice is marked as sent', function () {
    Mail::fake();
    
    $invoice = Invoice::factory()->create();
    
    // Trigger sending
    $invoice->sendToClient();
    
    Mail::assertSent(InvoiceSent::class, function ($mail) use ($invoice) {
        return $mail->invoice->id === $invoice->id;
    });
});
```

### 6. Task & Follow-up Management

#### Overview
Task management system for tracking follow-ups, deadlines, and project work. Supports both standalone tasks and tasks linked to specific clients or projects.

#### Task Model Implementation

**Task Model** (`app/Models/Task.php` - to be implemented):
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'title', 'description', 'client_id', 'project_id',
        'assigned_user_id', 'due_date', 'priority', 'status',
        'completed_at'
    ];
    
    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    protected $appends = ['is_overdue'];
    
    // Relationships
    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }
    
    // Scopes
    public function scopeOverdue($query) {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
    
    public function scopeDueToday($query) {
        return $query->whereDate('due_date', today())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
    
    public function scopeUpcoming($query, $days = 7) {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
    
    public function scopePending($query) {
        return $query->where('status', 'pending');
    }
    
    public function scopeAssignedTo($query, User $user) {
        return $query->where('assigned_user_id', $user->id);
    }
    
    // Accessors
    public function getIsOverdueAttribute(): bool {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }
    
    // Business logic
    public function markComplete() {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
    
    public function snooze($days = 1) {
        $this->update([
            'due_date' => $this->due_date->addDays($days),
        ]);
    }
}
```

#### Automated Task Creation

**Quote Follow-up Tasks**:
```php
// app/Observers/QuoteObserver.php
class QuoteObserver
{
    public function sent(Quote $quote)
    {
        // Create follow-up task 7 days after quote is sent
        Task::create([
            'title' => "Follow up on quote {$quote->quote_number}",
            'description' => "Check if client has reviewed the quote and answer any questions.",
            'client_id' => $quote->client_id,
            'due_date' => now()->addDays(7),
            'priority' => 'medium',
            'status' => 'pending',
        ]);
    }
}
```

**Invoice Payment Reminders**:
```php
// app/Console/Commands/CheckOverdueInvoices.php
class CheckOverdueInvoices extends Command
{
    protected $signature = 'invoices:check-overdue';
    
    public function handle()
    {
        $overdueInvoices = Invoice::query()
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->get();
        
        foreach ($overdueInvoices as $invoice) {
            // Update status
            $invoice->update(['status' => 'overdue']);
            
            // Create reminder task
            Task::firstOrCreate([
                'title' => "Payment overdue: Invoice {$invoice->invoice_number}",
                'client_id' => $invoice->client_id,
                'due_date' => now(),
                'priority' => 'high',
                'status' => 'pending',
            ]);
        }
    }
}

// Schedule in app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('invoices:check-overdue')->daily();
}
```

#### Filament Implementation

**TaskResource** (`app/Filament/Resources/TaskResource.php` - to be implemented):

**Table Configuration**:
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('client.name')
                ->searchable()
                ->sortable()
                ->url(fn ($record) => $record->client 
                    ? ClientResource::getUrl('edit', ['record' => $record->client])
                    : null
                ),
            
            Tables\Columns\TextColumn::make('project.name')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('assignedUser.name')
                ->label('Assigned To')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('due_date')
                ->date()
                ->sortable()
                ->color(fn ($record) => match (true) {
                    $record->is_overdue => 'danger',
                    $record->due_date?->isToday() => 'warning',
                    default => 'gray',
                }),
            
            Tables\Columns\BadgeColumn::make('priority')
                ->colors([
                    'secondary' => 'low',
                    'warning' => 'medium',
                    'danger' => 'high',
                    'danger' => 'urgent',
                ]),
            
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'secondary' => 'pending',
                    'primary' => 'in_progress',
                    'success' => 'completed',
                    'danger' => 'cancelled',
                ]),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
            
            Tables\Filters\SelectFilter::make('priority')
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ]),
            
            Tables\Filters\Filter::make('overdue')
                ->query(fn ($query) => $query->overdue())
                ->toggle(),
            
            Tables\Filters\Filter::make('my_tasks')
                ->query(fn ($query) => $query->assignedTo(auth()->user()))
                ->toggle(),
            
            Tables\Filters\SelectFilter::make('assigned_user_id')
                ->relationship('assignedUser', 'name')
                ->label('Assigned To'),
        ])
        ->actions([
            Tables\Actions\Action::make('complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(fn (Task $record) => $record->markComplete())
                ->visible(fn (Task $record) => 
                    !in_array($record->status, ['completed', 'cancelled'])
                ),
            
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                
                Tables\Actions\BulkAction::make('mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records) {
                        $records->each->markComplete();
                    }),
            ]),
        ])
        ->defaultSort('due_date', 'asc');
}
```

**Form Configuration**:
```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Task Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpan(2),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),
                    
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('medium')
                        ->required(),
                    
                    Forms\Components\DateTimePicker::make('due_date')
                        ->required(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Assignment')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => 
                            $set('project_id', null)
                        ),
                    
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name', 
                            fn ($query, $get) => 
                                $get('client_id') 
                                    ? $query->where('client_id', $get('client_id'))
                                    : $query
                        )
                        ->searchable(),
                    
                    Forms\Components\Select::make('assigned_user_id')
                        ->relationship('assignedUser', 'name')
                        ->label('Assign To')
                        ->searchable(),
                ])
                ->columns(3),
        ]);
}
```

#### Dashboard Widgets

**Upcoming Tasks Widget** (`app/Filament/Widgets/UpcomingTasks.php` - to be implemented):
```php
namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingTasks extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Task::query()
                    ->upcoming(7)
                    ->assignedTo(auth()->user())
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('client.name'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\BadgeColumn::make('priority'),
            ])
            ->heading('Upcoming Tasks (Next 7 Days)')
            ->emptyStateHeading('No upcoming tasks')
            ->emptyStateDescription('You have no tasks due in the next 7 days.');
    }
}
```

**Overdue Tasks Widget** (`app/Filament/Widgets/OverdueTasks.php` - to be implemented):
```php
class OverdueTasks extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Task::query()
                    ->overdue()
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->badge()
                    ->color('danger'),
            ])
            ->heading('Overdue Tasks')
            ->emptyStateHeading('No overdue tasks');
    }
}
```

#### Calendar Integration (Optional)

Using **Filament's Calendar plugin** or custom implementation:
```php
// app/Filament/Widgets/TaskCalendar.php
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TaskCalendar extends FullCalendarWidget
{
    public function fetchEvents(array $info): array
    {
        return Task::query()
            ->whereBetween('due_date', [$info['start'], $info['end']])
            ->get()
            ->map(function (Task $task) {
                return [
                    'title' => $task->title,
                    'start' => $task->due_date,
                    'url' => TaskResource::getUrl('edit', ['record' => $task]),
                    'backgroundColor' => match ($task->priority) {
                        'urgent' => '#ef4444',
                        'high' => '#f97316',
                        'medium' => '#eab308',
                        'low' => '#6b7280',
                    },
                ];
            })
            ->toArray();
    }
}
```

