<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory;

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
        'subtotal' => 'integer',
        'tax_rate' => 'integer',
        'tax_amount' => 'integer',
        'total' => 'integer',
        'version' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
        'subtotal' => 0,
        'tax_rate' => 1900,
        'tax_amount' => 0,
        'total' => 0,
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function items() { return $this->hasMany(QuoteItem::class); }
    // public function invoice() { return $this->hasOne(Invoice::class); } // TODO: Uncomment in Sprint 4

    // Business logic
    public function calculateTotals() {
        $this->subtotal = $this->items()->sum('total');
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100) / 100;
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
        return $this->status === 'accepted'; // && !$this->invoice; // TODO: Uncomment in Sprint 4
    }
}
