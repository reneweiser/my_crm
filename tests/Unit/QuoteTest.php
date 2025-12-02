<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a quote', function () {
    $client = Client::factory()->create();
    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'quote_number' => 'Q-2025-0001',
        'status' => 'draft',
    ]);

    expect($quote->exists)->toBeTrue()
        ->and($quote->quote_number)->toBe('Q-2025-0001')
        ->and($quote->status)->toBe('draft')
        ->and($quote->client_id)->toBe($client->id);
});

test('quote has fillable attributes', function () {
    $fillable = [
        'client_id', 'project_id', 'quote_number', 'version',
        'status', 'valid_until', 'sent_at', 'accepted_at',
        'notes', 'client_notes', 'subtotal', 'tax_rate',
        'tax_amount', 'total',
    ];

    $quote = new Quote;

    expect($quote->getFillable())->toBe($fillable);
});

test('quote stores money as integers', function () {
    $quote = Quote::factory()->create([
        'subtotal' => 100000, // €1000.00 in cents
        'tax_rate' => 1900, // 19.00% in basis points
        'tax_amount' => 19000, // €190.00 in cents
        'total' => 119000, // €1190.00 in cents
    ]);

    expect($quote->subtotal)->toBe(100000)
        ->and($quote->tax_rate)->toBe(1900)
        ->and($quote->tax_amount)->toBe(19000)
        ->and($quote->total)->toBe(119000);
});

test('quote belongs to client', function () {
    $client = Client::factory()->create(['company' => 'Test Company']);
    $quote = Quote::factory()->create(['client_id' => $client->id]);

    expect($quote->client)->toBeInstanceOf(Client::class)
        ->and($quote->client->id)->toBe($client->id)
        ->and($quote->client->company)->toBe('Test Company');
});

test('quote can belong to project', function () {
    $project = Project::factory()->create(['name' => 'Website Redesign']);
    $quote = Quote::factory()->create(['project_id' => $project->id]);

    expect($quote->project)->toBeInstanceOf(Project::class)
        ->and($quote->project->id)->toBe($project->id)
        ->and($quote->project->name)->toBe('Website Redesign');
});

test('quote project is optional', function () {
    $quote = Quote::factory()->create(['project_id' => null]);

    expect($quote->project_id)->toBeNull()
        ->and($quote->project)->toBeNull();
});

test('quote has many items', function () {
    $quote = Quote::factory()->create();
    QuoteItem::factory()->count(3)->create(['quote_id' => $quote->id]);

    $quote->refresh();

    expect($quote->items())->not->toBeNull()
        ->and($quote->items()->count())->toBe(3)
        ->and($quote->items()->first())->toBeInstanceOf(QuoteItem::class);
});

test('quote has default status of draft', function () {
    $quote = new Quote([
        'client_id' => 1,
        'quote_number' => 'Q-2025-0001',
    ]);

    expect($quote->status)->toBe('draft');
});

test('quote has default version of 1', function () {
    $quote = new Quote([
        'client_id' => 1,
        'quote_number' => 'Q-2025-0001',
    ]);

    expect($quote->version)->toBe(1);
});

test('quote timestamps are nullable', function () {
    $quote = Quote::factory()->create([
        'sent_at' => null,
        'accepted_at' => null,
        'valid_until' => null,
    ]);

    expect($quote->sent_at)->toBeNull()
        ->and($quote->accepted_at)->toBeNull()
        ->and($quote->valid_until)->toBeNull();
});

test('quote can have sent_at timestamp', function () {
    $sentAt = now()->subDays(5);
    $quote = Quote::factory()->create(['sent_at' => $sentAt]);

    expect($quote->sent_at)->not->toBeNull()
        ->and($quote->sent_at->toDateTimeString())->toBe($sentAt->toDateTimeString());
});

test('quote can have accepted_at timestamp', function () {
    $acceptedAt = now()->subDays(2);
    $quote = Quote::factory()->create(['accepted_at' => $acceptedAt]);

    expect($quote->accepted_at)->not->toBeNull()
        ->and($quote->accepted_at->toDateTimeString())->toBe($acceptedAt->toDateTimeString());
});

test('quote can have notes', function () {
    $quote = Quote::factory()->create([
        'notes' => 'Internal note for team',
        'client_notes' => 'Terms and conditions',
    ]);

    expect($quote->notes)->toBe('Internal note for team')
        ->and($quote->client_notes)->toBe('Terms and conditions');
});

test('quote isDraft returns true for draft status', function () {
    $quote = Quote::factory()->draft()->create();

    expect($quote->isDraft())->toBeTrue();
});

test('quote isDraft returns false for non-draft status', function () {
    $quote = Quote::factory()->sent()->create();

    expect($quote->isDraft())->toBeFalse();
});

test('quote isExpired returns true when valid_until has passed', function () {
    $quote = Quote::factory()->create([
        'status' => 'sent',
        'valid_until' => now()->subDays(1),
    ]);

    expect($quote->isExpired())->toBeTrue();
});

test('quote isExpired returns false when valid_until is in future', function () {
    $quote = Quote::factory()->create([
        'status' => 'sent',
        'valid_until' => now()->addDays(30),
    ]);

    expect($quote->isExpired())->toBeFalse();
});

test('quote isExpired returns false for accepted quotes', function () {
    $quote = Quote::factory()->accepted()->create([
        'valid_until' => now()->subDays(1),
    ]);

    expect($quote->isExpired())->toBeFalse();
});

test('quote canBeEdited returns true for draft status', function () {
    $quote = Quote::factory()->draft()->create();

    expect($quote->canBeEdited())->toBeTrue();
});

test('quote canBeEdited returns false for sent status', function () {
    $quote = Quote::factory()->sent()->create();

    expect($quote->canBeEdited())->toBeFalse();
});

test('quote canBeEdited returns false for accepted status', function () {
    $quote = Quote::factory()->accepted()->create();

    expect($quote->canBeEdited())->toBeFalse();
});

test('quote canBeConverted returns true for accepted status without invoice', function () {
    $quote = Quote::factory()->accepted()->create();

    expect($quote->canBeConverted())->toBeTrue();
});

test('quote canBeConverted returns false for non-accepted status', function () {
    $quote = Quote::factory()->draft()->create();

    expect($quote->canBeConverted())->toBeFalse();
});

test('quote has timestamps', function () {
    $quote = Quote::factory()->create();

    expect($quote->created_at)->not->toBeNull()
        ->and($quote->updated_at)->not->toBeNull();
});

test('quote number is unique', function () {
    Quote::factory()->create(['quote_number' => 'Q-2025-0001']);

    $this->expectException(\Illuminate\Database\QueryException::class);

    Quote::factory()->create(['quote_number' => 'Q-2025-0001']);
});

test('quote status can be draft', function () {
    $quote = Quote::factory()->draft()->create();

    expect($quote->status)->toBe('draft')
        ->and($quote->sent_at)->toBeNull()
        ->and($quote->accepted_at)->toBeNull();
});

test('quote status can be sent', function () {
    $quote = Quote::factory()->sent()->create();

    expect($quote->status)->toBe('sent')
        ->and($quote->sent_at)->not->toBeNull()
        ->and($quote->accepted_at)->toBeNull();
});

test('quote status can be accepted', function () {
    $quote = Quote::factory()->accepted()->create();

    expect($quote->status)->toBe('accepted')
        ->and($quote->sent_at)->not->toBeNull()
        ->and($quote->accepted_at)->not->toBeNull();
});

test('quote status can be rejected', function () {
    $quote = Quote::factory()->rejected()->create();

    expect($quote->status)->toBe('rejected')
        ->and($quote->sent_at)->not->toBeNull();
});

test('quote status can be converted', function () {
    $quote = Quote::factory()->converted()->create();

    expect($quote->status)->toBe('converted')
        ->and($quote->sent_at)->not->toBeNull()
        ->and($quote->accepted_at)->not->toBeNull();
});

test('quote can have custom tax rate', function () {
    $quote = Quote::factory()->withTaxRate(700)->create();

    expect($quote->tax_rate)->toBe(700); // 7.00%
});

test('quote can have zero tax rate', function () {
    $quote = Quote::factory()->withoutTax()->create();

    expect($quote->tax_rate)->toBe(0)
        ->and($quote->tax_amount)->toBe(0);
});

test('quote can have reduced VAT rate', function () {
    $quote = Quote::factory()->withReducedVat()->create();

    expect($quote->tax_rate)->toBe(700); // 7.00%
});

test('quote calculates tax amount and total correctly', function () {
    $items = QuoteItem::factory()->count(10)->state([
        'quantity' => 10,
        'unit' => 'hours',
        'unit_price' => 1000,
    ]);

    $quote = Quote::factory()
        ->has($items, 'items')
        ->create();

    $quote->calculateTotals();

    expect($quote->refresh()->tax_amount)->toBe(19000) // €190.00
        ->and($quote->total)->toBe(19000 + 100000); // €1190.00
});

test('quote version can be incremented', function () {
    $quote = Quote::factory()->create(['version' => 1]);

    $quote->update(['version' => 2]);

    expect($quote->fresh()->version)->toBe(2);
});
