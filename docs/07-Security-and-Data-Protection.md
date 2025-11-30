# Security & Data Protection

## Authentication & Authorization

**User Authentication**:
- Filament's built-in authentication system
- Multi-factor authentication recommended for production
- Strong password requirements (Laravel's password validation)
- Session timeout for inactive users

**Authorization & Permissions**:
```php
// Filament Policies for resource access
// app/Policies/InvoicePolicy.php
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'accountant', 'developer']);
    }
    
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'developer']);
    }
    
    public function update(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be edited
        return $user->hasRole(['admin', 'developer']) && $invoice->isDraft();
    }
    
    public function delete(User $user, Invoice $invoice): bool
    {
        // Prevent deletion per GoBD
        return false;
    }
}
```

**Role-Based Access Control** (Optional with Spatie Permission):
```bash
composer require spatie/laravel-permission
```

Roles:
- **Admin**: Full access to all resources
- **Developer**: Manage clients, projects, quotes, invoices, time entries
- **Accountant**: View invoices, manage payments (read-only for clients)
- **Client** (future): View own quotes/invoices via portal

### Data Protection & Privacy (GDPR/DSGVO)

**GDPR Compliance Requirements**:

1. **Data Minimization**: Only collect necessary client data
2. **Purpose Limitation**: Use data only for business purposes
3. **Storage Limitation**: Delete outdated data when legally permitted
4. **Access Rights**: Clients can request their data
5. **Right to Erasure**: Honor deletion requests (with legal exceptions)
6. **Data Portability**: Provide data export in machine-readable format

**Implementation Considerations**:

**Soft Deletes vs Hard Deletes**:
```php
// Clients use soft deletes (can be recovered)
// But: GoBD requires keeping invoice data for 10 years
// Solution: Anonymize client data after relationship ends

public function anonymizeClient(Client $client)
{
    // Check if client has any active invoices/quotes
    if ($client->invoices()->where('status', '!=', 'paid')->exists()) {
        throw new \Exception('Cannot anonymize client with pending invoices');
    }
    
    // Anonymize personal data but keep business records
    $client->update([
        'name' => 'Anonymized Client #' . $client->id,
        'email' => null,
        'phone' => null,
        'notes' => null,
        // Keep: company, address (required for invoice validity)
    ]);
    
    // Anonymize contacts
    $client->contacts()->update([
        'name' => 'Anonymized Contact',
        'email' => null,
        'phone' => null,
    ]);
}
```

**Data Export for Clients** (GDPR Article 20):
```php
// app/Services/DataExportService.php
public function exportClientData(Client $client): array
{
    return [
        'client' => $client->toArray(),
        'contacts' => $client->contacts->toArray(),
        'projects' => $client->projects->toArray(),
        'quotes' => $client->quotes->map(function ($quote) {
            return [
                'quote_number' => $quote->quote_number,
                'date' => $quote->created_at,
                'total' => $quote->total,
                'status' => $quote->status,
                'items' => $quote->items->toArray(),
            ];
        }),
        'invoices' => $client->invoices->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'items' => $invoice->items->toArray(),
            ];
        }),
    ];
}
```

**Privacy Policy Requirements**:
- Document what data is collected and why
- Explain how long data is retained (10 years for invoices)
- Describe security measures in place
- Provide contact for data protection inquiries

### Application Security

**PDF Access Control**:
```php
// Only authenticated users can access invoice PDFs
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        // Optional: Check if user has permission to view this client's invoices
        if (!auth()->user()->can('view', $invoice)) {
            abort(403);
        }
        
        // Serve PDF with proper headers
        return response()->file($invoice->pdf_path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . 
                basename($invoice->pdf_path) . '"',
        ]);
    })->name('invoices.pdf');
});
```

**Alternative: Signed URLs for Email Links**:
```php
// Generate temporary signed URL (expires in 7 days)
$url = URL::temporarySignedRoute(
    'invoices.pdf',
    now()->addDays(7),
    ['invoice' => $invoice->id]
);

// In email template
<a href="{{ $url }}">View Invoice Online</a>
```

**Financial Data Validation**:
```php
// Always validate calculations server-side
// app/Services/InvoiceCalculator.php
class InvoiceCalculator
{
    public function calculateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->items->sum(function ($item) {
            return bcmul($item->quantity, $item->unit_price, 2);
        });
        
        $taxAmount = bcmul(
            $subtotal,
            bcdiv($invoice->tax_rate, 100, 4),
            2
        );
        
        $total = bcadd($subtotal, $taxAmount, 2);
        
        $invoice->subtotal = $subtotal;
        $invoice->tax_amount = $taxAmount;
        $invoice->total = $total;
        $invoice->save();
    }
}

// Use BC Math for precision with currency
// Avoid floating-point arithmetic errors
```

**SQL Injection Prevention**:
- Laravel's Eloquent ORM prevents SQL injection
- Always use parameter binding for raw queries
- Validate and sanitize user inputs

**Cross-Site Scripting (XSS)**:
- Blade templates auto-escape output by default
- Use `{{{ }}}` or `{{ }}` for output
- Sanitize rich text input if using WYSIWYG editors

**Cross-Site Request Forgery (CSRF)**:
- Laravel's CSRF protection enabled by default
- All POST/PUT/DELETE requests require CSRF token
- Filament handles this automatically

**Mass Assignment Protection**:
```php
// Use $fillable or $guarded on all models
class Invoice extends Model
{
    protected $fillable = [
        'client_id', 'invoice_number', 'total', // ... allowed fields
    ];
    
    // Never allow mass assignment of sensitive fields
    protected $guarded = ['id', 'invoice_number', 'sent_at'];
}
```

### Backup & Disaster Recovery

**Automated Backups**:
```bash
# Using spatie/laravel-backup
composer require spatie/laravel-backup
```

**Backup Strategy**:
- **Daily database backups** (retain for 30 days)
- **Weekly file backups** (PDFs, uploaded documents)
- **Monthly archive backups** (retain for 10 years per GoBD)
- Store backups off-site (AWS S3, Backblaze B2, etc.)

**Backup Configuration** (`config/backup.php`):
```php
'backup' => [
    'name' => env('APP_NAME', 'my_crm'),
    'source' => [
        'files' => [
            'include' => [
                storage_path('app/invoices'),
                storage_path('app/quotes'),
            ],
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'disks' => ['s3', 'local'],
    ],
],
```

**Backup Monitoring**:
- Set up alerts for failed backups
- Regularly test backup restoration
- Document recovery procedures

### Environment Security

**Environment Variables**:
```env
# Never commit .env to version control
# Use strong, unique values for:

APP_KEY=base64:... # Generated by `php artisan key:generate`
DB_PASSWORD=strong_random_password

# Use read-only database user for reporting queries
DB_READONLY_USERNAME=readonly_user
DB_READONLY_PASSWORD=readonly_password

# Secure session/cache
SESSION_DRIVER=database
CACHE_DRIVER=redis
```

**Server Hardening**:
- Use HTTPS only (redirect HTTP to HTTPS)
- Keep PHP, Laravel, and dependencies updated
- Disable directory listing
- Set proper file permissions (644 for files, 755 for directories)
- Use fail2ban for brute force protection
- Regular security audits

**Monitoring & Logging**:
```php
// Log security events
// app/Listeners/SecurityEventLogger.php
class SecurityEventLogger
{
    public function handle($event)
    {
        Log::channel('security')->info('Security event', [
            'event' => get_class($event),
            'user' => auth()->user()?->email,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }
}

// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 90, // Retain for 90 days
    ],
],
```

**Vulnerability Scanning**:
```bash
# Regular dependency security checks
composer audit

# Laravel security updates
composer update laravel/framework --with-dependencies
```

