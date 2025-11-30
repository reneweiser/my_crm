<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'description',
        'status',
        'rate_type',
        'hourly_rate',
        'fixed_price',
        'budget_hours',
        'start_date',
        'end_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'fixed_price' => 'decimal:2',
            'budget_hours' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the time entries for the project.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    // /**
    //  * Get the tasks for the project.
    //  */
    // public function tasks(): HasMany
    // {
    //     return $this->hasMany(\App\Models\Task::class);
    // }

    // /**
    //  * Get the quotes for the project.
    //  */
    // public function quotes(): HasMany
    // {
    //     return $this->hasMany(\App\Models\Quote::class);
    // }

    // /**
    //  * Get the invoices for the project.
    //  */
    // public function invoices(): HasMany
    // {
    //     return $this->hasMany(\App\Models\Invoice::class);
    // }

    /**
     * Calculate total billable hours for this project.
     */
    public function getTotalBillableHoursAttribute(): float
    {
        return $this->timeEntries()
            ->where('billable', true)
            ->sum('hours') ?? 0.0;
    }

    /**
     * Calculate total billable amount for this project.
     */
    public function getTotalBillableAmountAttribute(): float
    {
        if ($this->rate_type === 'hourly' && $this->hourly_rate) {
            return $this->total_billable_hours * $this->hourly_rate;
        }

        if ($this->rate_type === 'fixed' && $this->fixed_price) {
            return (float) $this->fixed_price;
        }

        return 0.0;
    }

    /**
     * Check if the project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the project is over budget.
     */
    public function isOverBudget(): bool
    {
        if (! $this->budget_hours) {
            return false;
        }

        return $this->total_billable_hours > $this->budget_hours;
    }
}
