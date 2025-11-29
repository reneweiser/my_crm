<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'country',
        'email',
        'phone',
        'website',
        'notes',
    ];

    /**
     * Get the contacts for the client.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(\App\Models\Contact::class);
    }

    /**
     * Get the projects for the client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(\App\Models\Project::class);
    }

    /**
     * Get the quotes for the client.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(\App\Models\Quote::class);
    }

    /**
     * Get the invoices for the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    /**
     * Get the primary contact for the client.
     */
    public function primaryContact()
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    /**
     * Get the full address as a formatted string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            trim("{$this->postal_code} {$this->city}"),
            $this->country,
        ]);

        return implode("\n", $parts);
    }
}
