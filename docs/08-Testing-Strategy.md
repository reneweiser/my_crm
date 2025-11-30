# Testing Strategy

## Testing Philosophy

The CRM system handles financial data and must comply with legal requirements. **Comprehensive testing is critical** to ensure:
- Accurate financial calculations
- GoBD compliance (sequential numbering, immutability)
- Reliable PDF generation
- Proper workflow enforcement

### Test Structure

```
tests/
├── Unit/              # Isolated component tests
│   ├── Models/        # Model methods, relationships, scopes
│   ├── Services/      # Business logic, calculations
│   └── Helpers/       # Utility functions
├── Feature/           # Integration tests
│   ├── Client/        # Client CRUD, relationships
│   ├── Quote/         # Quote generation, conversion
│   ├── Invoice/       # Invoice generation, GoBD compliance
│   ├── Email/         # Email sending workflows
│   └── PDF/           # PDF generation tests
└── Browser/           # End-to-end tests (optional, using Dusk)
```

### Unit Tests

**Financial Calculation Tests** (`tests/Unit/Services/InvoiceCalculatorTest.php`):
```php
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceCalculator;

test('calculates invoice totals correctly', function () {
    $invoice = Invoice::factory()->create([
        'tax_rate' => 19.0,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 10,
        'unit_price' => 50.00,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 5,
        'unit_price' => 30.00,
    ]);
    
    $calculator = new InvoiceCalculator();
    $calculator->calculateInvoiceTotals($invoice);
    
    expect($invoice->subtotal)->toBe('650.00')
        ->and($invoice->tax_amount)->toBe('123.50')
        ->and($invoice->total)->toBe('773.50');
});

test('handles decimal quantities correctly', function () {
    $invoice = Invoice::factory()->create(['tax_rate' => 19.0]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 7.5, // Hours worked
        'unit_price' => 85.00,
    ]);
    
    $calculator = new InvoiceCalculator();
    $calculator->calculateInvoiceTotals($invoice);
    
    expect($invoice->subtotal)->toBe('637.50')
        ->and($invoice->tax_amount)->toBe('121.13')
        ->and($invoice->total)->toBe('758.63');
});

test('calculates tax correctly for reduced rate', function () {
    $invoice = Invoice::factory()->create(['tax_rate' => 7.0]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => 100.00,
    ]);
    
    $calculator = new InvoiceCalculator();
    $calculator->calculateInvoiceTotals($invoice);
    
    expect($invoice->tax_amount)->toBe('7.00')
        ->and($invoice->total)->toBe('107.00');
});

test('handles reverse charge (0% tax) correctly', function () {
    $invoice = Invoice::factory()->create(['tax_rate' => 0.0]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => 1000.00,
    ]);
    
    $calculator = new InvoiceCalculator();
    $calculator->calculateInvoiceTotals($invoice);
    
    expect($invoice->subtotal)->toBe('1000.00')
        ->and($invoice->tax_amount)->toBe('0.00')
        ->and($invoice->total)->toBe('1000.00');
});
```

**Model Relationship Tests** (`tests/Unit/Models/ClientTest.php`):
```php
test('client has many contacts', function () {
    $client = Client::factory()
        ->has(Contact::factory()->count(3))
        ->create();
    
    expect($client->contacts)->toHaveCount(3);
});

test('client can identify primary contact', function () {
    $client = Client::factory()->create();
    
    Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => false,
    ]);
    
    $primaryContact = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => true,
    ]);
    
    expect($client->primaryContact()->id)->toBe($primaryContact->id);
});

test('client formats full address correctly', function () {
    $client = Client::factory()->create([
        'address_line_1' => 'Musterstraße 123',
        'address_line_2' => '',
        'postal_code' => '12345',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);
    
    $expected = "Musterstraße 123\n12345 Berlin\nGermany";
    
    expect($client->fullAddress())->toBe($expected);
});
```

**Model Validation Tests** (`tests/Unit/Models/InvoiceTest.php`):
```php
test('invoice is overdue when past due date and not paid', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'sent',
        'due_date' => now()->subDays(5),
    ]);
    
    expect($invoice->is_overdue)->toBeTrue();
});

test('paid invoice is not overdue', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'paid',
        'due_date' => now()->subDays(5),
    ]);
    
    expect($invoice->is_overdue)->toBeFalse();
});

test('draft invoice cannot be edited after being sent', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'draft',
        'sent_at' => null,
    ]);
    
    expect($invoice->canBeEdited())->toBeTrue();
    
    $invoice->update(['status' => 'sent', 'sent_at' => now()]);
    
    expect($invoice->canBeEdited())->toBeFalse();
});
```

### Feature Tests

**GoBD Compliance Tests** (`tests/Feature/Invoice/GobdComplianceTest.php`):
```php
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice numbers are sequential without gaps', function () {
    $service = app(InvoiceService::class);
    
    $numbers = [];
    for ($i = 0; $i < 10; $i++) {
        $numbers[] = $service->generateInvoiceNumber();
    }
    
    // Extract numeric parts
    $extracted = array_map(function ($num) {
        return (int) substr($num, -4);
    }, $numbers);
    
    // Should be: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
    expect($extracted)->toBe(range(1, 10));
});

test('concurrent invoice number generation does not create duplicates', function () {
    $service = app(InvoiceService::class);
    
    // Simulate concurrent requests
    $numbers = collect(range(1, 5))->map(function () use ($service) {
        return $service->generateInvoiceNumber();
    });
    
    // All numbers should be unique
    expect($numbers->unique()->count())->toBe(5);
});

test('sent invoice cannot be modified', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'draft',
        'subtotal' => 100.00,
    ]);
    
    // Can edit draft
    $invoice->update(['subtotal' => 150.00]);
    expect($invoice->subtotal)->toBe('150.00');
    
    // Send invoice
    $invoice->update(['status' => 'sent', 'sent_at' => now()]);
    
    // Cannot edit sent invoice
    expect(fn () => $invoice->update(['subtotal' => 200.00]))
        ->toThrow(\Exception::class);
});

test('invoice cannot be deleted', function () {
    $invoice = Invoice::factory()->create();
    
    expect(fn () => $invoice->delete())
        ->toThrow(\Exception::class);
});
```

**Quote to Invoice Conversion Tests** (`tests/Feature/Quote/ConversionTest.php`):
```php
test('accepted quote can be converted to invoice', function () {
    $quote = Quote::factory()
        ->has(QuoteItem::factory()->count(3))
        ->create(['status' => 'accepted']);
    
    $service = app(InvoiceService::class);
    $invoice = $service->createFromQuote($quote);
    
    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->client_id)->toBe($quote->client_id)
        ->and($invoice->project_id)->toBe($quote->project_id)
        ->and($invoice->quote_id)->toBe($quote->id)
        ->and($invoice->total)->toBe($quote->total)
        ->and($invoice->items)->toHaveCount(3);
    
    // Quote should be marked as converted
    expect($quote->fresh()->status)->toBe('converted');
});

test('draft quote cannot be converted to invoice', function () {
    $quote = Quote::factory()->create(['status' => 'draft']);
    
    $service = app(InvoiceService::class);
    
    expect(fn () => $service->createFromQuote($quote))
        ->toThrow(\Exception::class);
});
```

**Email Sending Tests** (`tests/Feature/Email/InvoiceEmailTest.php`):
```php
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;

test('invoice email is sent with PDF attachment', function () {
    Mail::fake();
    
    $invoice = Invoice::factory()->create();
    
    Mail::to($invoice->client->email)->send(new InvoiceSent($invoice));
    
    Mail::assertSent(InvoiceSent::class, function ($mail) use ($invoice) {
        return $mail->invoice->id === $invoice->id &&
               $mail->hasTo($invoice->client->email) &&
               count($mail->attachments()) === 1;
    });
});

test('invoice email contains correct subject and content', function () {
    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-2025-0042',
    ]);
    
    $mailable = new InvoiceSent($invoice);
    
    $mailable->assertFrom(config('mail.from.address'));
    $mailable->assertHasSubject("Rechnung INV-2025-0042");
    $mailable->assertSeeInHtml('INV-2025-0042');
    $mailable->assertSeeInHtml($invoice->client->name);
});
```

**PDF Generation Tests** (`tests/Feature/PDF/InvoicePdfTest.php`):
```php
use App\Services\PdfService;

test('invoice PDF contains all required GoBD fields', function () {
    $invoice = Invoice::factory()
        ->for(Client::factory()->create([
            'name' => 'Test Client GmbH',
            'address_line_1' => 'Teststraße 1',
            'city' => 'Berlin',
        ]))
        ->has(InvoiceItem::factory()->count(2))
        ->create([
            'invoice_number' => 'INV-2025-0001',
            'invoice_date' => now(),
        ]);
    
    $pdfService = app(PdfService::class);
    $pdf = $pdfService->generateInvoicePdf($invoice);
    
    // Basic checks (more thorough with PDF parsing libraries)
    expect($pdf)->toContain('INV-2025-0001')
        ->and($pdf)->toContain('Test Client GmbH')
        ->and($pdf)->toContain(config('crm.company.legal_name'))
        ->and($pdf)->toContain(config('crm.tax.tax_number'))
        ->and($pdf)->toContain('Rechnungsnummer')
        ->and($pdf)->toContain('Rechnungsdatum');
});

test('quote PDF is generated successfully', function () {
    $quote = Quote::factory()
        ->has(QuoteItem::factory()->count(2))
        ->create();
    
    $pdfService = app(PdfService::class);
    $pdf = $pdfService->generateQuotePdf($quote);
    
    expect($pdf)->not->toBeEmpty()
        ->and($pdf)->toContain($quote->quote_number);
});
```

### Test Coverage Goals

**Minimum Coverage Targets**:
- **Models**: 80% coverage (business logic, relationships)
- **Services**: 90% coverage (critical business logic)
- **Controllers/Resources**: 70% coverage (integration points)
- **Overall**: 80% coverage

**Run Coverage Report**:
```bash
./vendor/bin/sail test --coverage --min=80
```

### Continuous Testing

**Pre-commit Hooks** (using Husky or Laravel Pint):
```bash
# .git/hooks/pre-commit
#!/bin/sh
./vendor/bin/pint
./vendor/bin/sail test
```

**CI/CD Pipeline** (GitHub Actions example):
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test --coverage --min=80
      - name: Run Linter
        run: ./vendor/bin/pint --test
```

### Manual Testing Checklist

**Invoice GoBD Compliance**:
- [ ] Sequential numbering works correctly
- [ ] No gaps in invoice numbers
- [ ] Sent invoices cannot be edited
- [ ] Invoices cannot be deleted
- [ ] PDF snapshot created when invoice sent
- [ ] All Pflichtangaben present on PDF

**Quote Workflow**:
- [ ] Draft quotes can be edited
- [ ] Sent quotes cannot be edited (new version created)
- [ ] Accepted quotes can be converted to invoices
- [ ] PDF generation works for quotes

**Email Delivery**:
- [ ] Emails sent with correct recipient
- [ ] PDF attached correctly
- [ ] Email content displays properly
- [ ] sent_at timestamp updated

**Client Management**:
- [ ] Clients can be created with contacts
- [ ] Primary contact designation works
- [ ] Client deletion prevents if has invoices
- [ ] Soft delete preserves relationships

