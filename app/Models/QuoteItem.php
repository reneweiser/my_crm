<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'description', 'quantity', 'unit',
        'unit_price', 'total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'integer',
        'total' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'quantity' => 1.0,
        'unit' => 'hours',
        'unit_price' => 0,
        'total' => 0,
        'sort_order' => 0,
    ];

    public function quote() { return $this->belongsTo(Quote::class); }

    // Auto-calculate total on save
     protected static function booted() {
         static::saving(function ($item) {
             $item->total = (int) ($item->quantity * $item->unit_price);
         });

         static::saved(function ($item) {
             $item->quote->calculateTotals();
         });
     }
}
