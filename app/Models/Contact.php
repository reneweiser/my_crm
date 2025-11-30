<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
        'position',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the client that owns the contact.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Set this contact as the primary contact for the client.
     */
    public function makePrimary(): void
    {
        // Remove primary status from other contacts
        $this->client->contacts()
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this contact as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Get the contact's full contact information as a formatted string.
     */
    public function getFullContactInfoAttribute(): string
    {
        $parts = array_filter([
            $this->name,
            $this->position,
            $this->email,
            $this->phone,
        ]);

        return implode(' | ', $parts);
    }
}
