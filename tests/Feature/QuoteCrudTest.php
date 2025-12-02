<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Quote CRUD Operations', function () {
    test('can create a quote with all fields', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $quoteData = [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'quote_number' => 'Q-2025-0001',
            'version' => 1,
            'status' => 'draft',
            'valid_until' => now()->addDays(30),
            'notes' => 'Internal notes',
            'client_notes' => 'Terms and conditions',
            'subtotal' => 100000,
            'tax_rate' => 1900,
            'tax_amount' => 19000,
            'total' => 119000,
        ];

        $quote = Quote::create($quoteData);

        expect($quote->exists)->toBeTrue()
            ->and($quote->client_id)->toBe($client->id)
            ->and($quote->project_id)->toBe($project->id)
            ->and($quote->quote_number)->toBe('Q-2025-0001')
            ->and($quote->status)->toBe('draft')
            ->and($quote->subtotal)->toBe(100000)
            ->and($quote->total)->toBe(119000);
    });

    test('can create a quote with minimal required fields', function () {
        $client = Client::factory()->create();

        $quote = Quote::create([
            'client_id' => $client->id,
            'quote_number' => 'Q-2025-0002',
        ]);

        expect($quote->exists)->toBeTrue()
            ->and($quote->client_id)->toBe($client->id)
            ->and($quote->project_id)->toBeNull()
            ->and($quote->status)->toBe('draft');
    });

    test('can read a quote', function () {
        $created = Quote::factory()->create([
            'quote_number' => 'Q-2025-0003',
        ]);

        $quote = Quote::find($created->id);

        expect($quote)->not->toBeNull()
            ->and($quote->id)->toBe($created->id)
            ->and($quote->quote_number)->toBe('Q-2025-0003');
    });

    test('can update a quote', function () {
        $quote = Quote::factory()->draft()->create([
            'notes' => 'Original notes',
            'subtotal' => 100000,
        ]);

        $quote->update([
            'notes' => 'Updated notes',
            'subtotal' => 150000,
            'tax_amount' => 28500,
            'total' => 178500,
        ]);

        expect($quote->fresh()->notes)->toBe('Updated notes')
            ->and($quote->fresh()->subtotal)->toBe(150000)
            ->and($quote->fresh()->total)->toBe(178500);
    });

    test('can delete a quote', function () {
        $quote = Quote::factory()->create();
        $id = $quote->id;

        $quote->delete();

        expect(Quote::find($id))->toBeNull();
    });

    test('can list multiple quotes', function () {
        Quote::factory()->count(5)->create();

        $quotes = Quote::all();

        expect($quotes)->toHaveCount(5);
    });

    test('can filter quotes by status', function () {
        Quote::factory()->draft()->create();
        Quote::factory()->sent()->create();
        Quote::factory()->sent()->create();
        Quote::factory()->accepted()->create();

        $sentQuotes = Quote::where('status', 'sent')->get();

        expect($sentQuotes)->toHaveCount(2);
    });

    test('can filter quotes by client', function () {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        Quote::factory()->count(3)->create(['client_id' => $client1->id]);
        Quote::factory()->count(2)->create(['client_id' => $client2->id]);

        $client1Quotes = Quote::where('client_id', $client1->id)->get();

        expect($client1Quotes)->toHaveCount(3);
    });

    test('can search quotes by quote number', function () {
        Quote::factory()->create(['quote_number' => 'Q-2025-0001']);
        Quote::factory()->create(['quote_number' => 'Q-2025-0002']);

        $quote = Quote::where('quote_number', 'Q-2025-0001')->first();

        expect($quote)->not->toBeNull()
            ->and($quote->quote_number)->toBe('Q-2025-0001');
    });

    test('can sort quotes by created date', function () {
        $quote1 = Quote::factory()->create(['quote_number' => 'Q-2025-0001']);
        sleep(1);
        $quote2 = Quote::factory()->create(['quote_number' => 'Q-2025-0002']);

        $quotes = Quote::orderBy('created_at', 'desc')->get();

        expect($quotes->first()->quote_number)->toBe('Q-2025-0002')
            ->and($quotes->last()->quote_number)->toBe('Q-2025-0001');
    });

    test('can update quote status from draft to sent', function () {
        $quote = Quote::factory()->draft()->create();

        $quote->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        expect($quote->fresh()->status)->toBe('sent')
            ->and($quote->fresh()->sent_at)->not->toBeNull();
    });

    test('can update quote status from sent to accepted', function () {
        $quote = Quote::factory()->sent()->create();

        $quote->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        expect($quote->fresh()->status)->toBe('accepted')
            ->and($quote->fresh()->accepted_at)->not->toBeNull();
    });
});

describe('Quote with Items', function () {
    test('can create quote with items', function () {
        $client = Client::factory()->create();

        $quote = Quote::factory()->create([
            'client_id' => $client->id,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        $item1 = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'description' => 'Web Development',
            'quantity' => 10,
            'unit_price' => 10000,
            'total' => 100000,
            'sort_order' => 1,
        ]);

        $item2 = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'description' => 'UI Design',
            'quantity' => 5,
            'unit_price' => 15000,
            'total' => 75000,
            'sort_order' => 2,
        ]);

        expect($quote->items()->count())->toBe(2)
            ->and($quote->items()->where('sort_order', 1)->first()->description)->toBe('Web Development')
            ->and($quote->items()->where('sort_order', 2)->first()->description)->toBe('UI Design');
    });

    test('can update quote items', function () {
        $quote = Quote::factory()->create();
        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'quantity' => 10,
            'unit_price' => 10000,
        ]);

        $item->update([
            'quantity' => 15,
            'unit_price' => 12000,
            'total' => 180000,
        ]);

        expect($item->fresh()->quantity)->toBe(15.0)
            ->and($item->fresh()->unit_price)->toBe(12000)
            ->and($item->fresh()->total)->toBe(180000);
    });

    test('can delete quote items', function () {
        $quote = Quote::factory()->create();
        $item = QuoteItem::factory()->create(['quote_id' => $quote->id]);
        $itemId = $item->id;

        $item->delete();

        expect(QuoteItem::find($itemId))->toBeNull();
    });

    test('can add new item to existing quote', function () {
        $quote = Quote::factory()->create();
        $initialCount = $quote->items()->count();

        QuoteItem::factory()->create(['quote_id' => $quote->id]);

        expect($quote->items()->count())->toBe($initialCount + 1);
    });

    test('can reorder quote items', function () {
        $quote = Quote::factory()->create();

        $item1 = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'sort_order' => 1,
        ]);
        $item2 = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'sort_order' => 2,
        ]);

        // Swap order
        $item1->update(['sort_order' => 2]);
        $item2->update(['sort_order' => 1]);

        $items = QuoteItem::where('quote_id', $quote->id)
            ->orderBy('sort_order')
            ->get();

        expect($items->first()->id)->toBe($item2->id)
            ->and($items->last()->id)->toBe($item1->id);
    });
});

describe('Quote Business Logic', function () {
    test('draft quote can be edited', function () {
        $quote = Quote::factory()->draft()->create();

        expect($quote->canBeEdited())->toBeTrue();
    });

    test('sent quote cannot be edited', function () {
        $quote = Quote::factory()->sent()->create();

        expect($quote->canBeEdited())->toBeFalse();
    });

    test('accepted quote cannot be edited', function () {
        $quote = Quote::factory()->accepted()->create();

        expect($quote->canBeEdited())->toBeFalse();
    });

    test('expired quote is detected correctly', function () {
        $quote = Quote::factory()->create([
            'status' => 'sent',
            'valid_until' => now()->subDays(1),
        ]);

        expect($quote->isExpired())->toBeTrue();
    });

    test('valid quote is not expired', function () {
        $quote = Quote::factory()->create([
            'status' => 'sent',
            'valid_until' => now()->addDays(30),
        ]);

        expect($quote->isExpired())->toBeFalse();
    });

    test('accepted quote can be converted', function () {
        $quote = Quote::factory()->accepted()->create();

        expect($quote->canBeConverted())->toBeTrue();
    });

    test('draft quote cannot be converted', function () {
        $quote = Quote::factory()->draft()->create();

        expect($quote->canBeConverted())->toBeFalse();
    });

    test('sent quote cannot be converted', function () {
        $quote = Quote::factory()->sent()->create();

        expect($quote->canBeConverted())->toBeFalse();
    });

    test('quote calculates subtotal from items', function () {
        $quote = Quote::factory()
            ->has(QuoteItem::factory()->state(['quantity' => 75, 'unit_price' => 1000])->count(2), 'items')
            ->create();

        $expectedSubtotal = 150000; // €1500.00

        $quote->refresh()->calculateTotals();
        $calculatedSubtotal = $quote->refresh()->items()->sum('total');

        expect($calculatedSubtotal)->toBe($expectedSubtotal);
    });
});

describe('Quote Versioning', function () {
    test('can create new version of quote', function () {
        $client = Client::factory()->create();

        $quote = Quote::factory()->create([
            'client_id' => $client->id,
            'quote_number' => 'Q-2025-VERS-001',
            'version' => 1,
        ]);

        $newVersion = Quote::factory()->create([
            'client_id' => $quote->client_id,
            'project_id' => $quote->project_id,
            'quote_number' => 'Q-2025-VERS-002',
            'version' => 2,
        ]);

        expect($newVersion->version)->toBe(2)
            ->and($newVersion->client_id)->toBe($quote->client_id);
    });

    test('can retrieve all versions of a quote by client', function () {
        $client = Client::factory()->create();

        Quote::factory()->create([
            'client_id' => $client->id,
            'quote_number' => 'Q-2025-VER2-001',
            'version' => 1,
        ]);

        Quote::factory()->create([
            'client_id' => $client->id,
            'quote_number' => 'Q-2025-VER2-002',
            'version' => 2,
        ]);

        Quote::factory()->create([
            'client_id' => $client->id,
            'quote_number' => 'Q-2025-VER2-003',
            'version' => 3,
        ]);

        $versions = Quote::where('client_id', $client->id)->orderBy('version')->get();

        expect($versions)->toHaveCount(3)
            ->and($versions->first()->version)->toBe(1)
            ->and($versions->last()->version)->toBe(3);
    });
});

describe('Quote Relationships', function () {
    test('quote belongs to client', function () {
        $client = Client::factory()->create(['company' => 'Test Company']);
        $quote = Quote::factory()->create(['client_id' => $client->id]);

        expect($quote->client)->not->toBeNull()
            ->and($quote->client->company)->toBe('Test Company');
    });

    test('quote can belong to project', function () {
        $project = Project::factory()->create(['name' => 'Website Redesign']);
        $quote = Quote::factory()->create(['project_id' => $project->id]);

        expect($quote->project)->not->toBeNull()
            ->and($quote->project->name)->toBe('Website Redesign');
    });

    test('quote can exist without project', function () {
        $quote = Quote::factory()->create(['project_id' => null]);

        expect($quote->project)->toBeNull();
    });

    test('deleting client deletes quotes', function () {
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);
        $quoteId = $quote->id;

        $client->forceDelete();

        expect(Quote::find($quoteId))->toBeNull();
    });

    test('deleting project nullifies quote project_id', function () {
        $project = Project::factory()->create();
        $quote = Quote::factory()->create(['project_id' => $project->id]);

        $project->forceDelete();

        expect($quote->fresh()->project_id)->toBeNull();
    });

    test('deleting quote deletes its items', function () {
        $quote = Quote::factory()->create();
        $item1 = QuoteItem::factory()->create(['quote_id' => $quote->id]);
        $item2 = QuoteItem::factory()->create(['quote_id' => $quote->id]);

        $itemId1 = $item1->id;
        $itemId2 = $item2->id;

        $quote->forceDelete();

        expect(QuoteItem::find($itemId1))->toBeNull()
            ->and(QuoteItem::find($itemId2))->toBeNull();
    });
});

describe('Quote Tax Calculations', function () {
    test('quote with 19% tax rate calculates correctly', function () {
        $subtotal = 100000; // €1000.00
        $taxRate = 1900; // 19.00%
        $expectedTaxAmount = 19000; // €190.00
        $expectedTotal = 119000; // €1190.00

        $quote = Quote::factory()->create([
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $expectedTaxAmount,
            'total' => $expectedTotal,
        ]);

        expect($quote->tax_amount)->toBe($expectedTaxAmount)
            ->and($quote->total)->toBe($expectedTotal);
    });

    test('quote with 7% reduced tax rate calculates correctly', function () {
        $subtotal = 100000; // €1000.00
        $taxRate = 700; // 7.00%
        $expectedTaxAmount = 7000; // €70.00
        $expectedTotal = 107000; // €1070.00

        $quote = Quote::factory()->withReducedVat()->create([
            'subtotal' => $subtotal,
            'tax_amount' => $expectedTaxAmount,
            'total' => $expectedTotal,
        ]);

        expect($quote->tax_rate)->toBe($taxRate)
            ->and($quote->tax_amount)->toBe($expectedTaxAmount)
            ->and($quote->total)->toBe($expectedTotal);
    });

    test('quote with 0% tax rate calculates correctly', function () {
        $subtotal = 100000; // €1000.00
        $taxRate = 0; // 0%
        $expectedTaxAmount = 0;
        $expectedTotal = 100000;

        $quote = Quote::factory()->create([
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $expectedTaxAmount,
            'total' => $expectedTotal,
        ]);

        expect($quote->tax_rate)->toBe($taxRate)
            ->and($quote->tax_amount)->toBe($expectedTaxAmount)
            ->and($quote->total)->toBe($expectedTotal);
    });
});
