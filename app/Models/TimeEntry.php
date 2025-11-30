<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'description',
        'date',
        'hours',
        'billable',
        'invoiced',
        'invoice_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'billable' => 'boolean',
            'invoiced' => 'boolean',
        ];
    }

    /**
     * Get the project that owns the time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that owns the time entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // /**
    //  * Get the invoice that includes this time entry.
    //  */
    // public function invoice(): BelongsTo
    // {
    //     return $this->belongsTo(\App\Models\Invoice::class);
    // }

    /**
     * Calculate the billable amount for this time entry.
     */
    public function getBillableAmountAttribute(): float
    {
        if (! $this->billable || ! $this->project) {
            return 0.0;
        }

        $rate = $this->project->hourly_rate ?? 0.0;

        return $this->hours * $rate;
    }

    /**
     * Mark this time entry as invoiced.
     */
    public function markAsInvoiced(int $invoiceId): void
    {
        $this->update([
            'invoiced' => true,
            'invoice_id' => $invoiceId,
        ]);
    }
}
