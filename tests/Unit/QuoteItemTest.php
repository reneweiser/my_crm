<?php

use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a quote item', function () {
    $quote = Quote::factory()->create();
    $item = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'description' => 'Web Development',
        'quantity' => 10,
        'unit' => 'hours',
        'unit_price' => 10000, // €100.00 in cents
    ]);

    expect($item->exists)->toBeTrue()
        ->and($item->description)->toBe('Web Development')
        ->and($item->quantity)->toBe(10.0)
        ->and($item->unit)->toBe('hours')
        ->and($item->unit_price)->toBe(10000);
});

test('quote item has fillable attributes', function () {
    $fillable = [
        'quote_id', 'description', 'quantity', 'unit',
        'unit_price', 'total', 'sort_order',
    ];

    $item = new QuoteItem;

    expect($item->getFillable())->toBe($fillable);
});

test('quote item stores money as integers', function () {
    $item = QuoteItem::factory()->create([
        'unit_price' => 15000, // €150.00 in cents
        'quantity' => 5,
        'total' => 75000, // €750.00 in cents
    ]);

    expect($item->unit_price)->toBe(15000)
        ->and($item->total)->toBe(75000);
});

test('quote item belongs to quote', function () {
    $quote = Quote::factory()->create(['quote_number' => 'Q-2025-0001']);
    $item = QuoteItem::factory()->create(['quote_id' => $quote->id]);

    expect($item->quote)->toBeInstanceOf(Quote::class)
        ->and($item->quote->id)->toBe($quote->id)
        ->and($item->quote->quote_number)->toBe('Q-2025-0001');
});

test('quote item has default quantity of 1', function () {
    $item = new QuoteItem([
        'quote_id' => 1,
        'description' => 'Test',
        'unit_price' => 10000,
    ]);

    expect($item->quantity)->toBe(1.0);
});

test('quote item has default unit_price of 0', function () {
    $item = new QuoteItem([
        'quote_id' => 1,
        'description' => 'Test',
    ]);

    expect($item->unit_price)->toBe(0);
});

test('quote item has default total of 0', function () {
    $item = new QuoteItem([
        'quote_id' => 1,
        'description' => 'Test',
    ]);

    expect($item->total)->toBe(0);
});

test('quote item has default sort_order of 0', function () {
    $item = new QuoteItem([
        'quote_id' => 1,
        'description' => 'Test',
    ]);

    expect($item->sort_order)->toBe(0);
});

test('quote item can have fractional quantity', function () {
    $item = QuoteItem::factory()->create([
        'quantity' => 1.5,
        'unit' => 'hours',
    ]);

    expect($item->quantity)->toBe(1.5);
});

test('quote item can have whole number quantity', function () {
    $item = QuoteItem::factory()->create([
        'quantity' => 10,
        'unit' => 'pieces',
    ]);

    expect($item->quantity)->toBe(10.0);
});

test('quote item can have different units', function () {
    $units = ['hours', 'days', 'pieces', 'license', 'month', 'year'];

    foreach ($units as $unit) {
        $item = QuoteItem::factory()->create(['unit' => $unit]);
        expect($item->unit)->toBe($unit);
    }
});

test('quote item description is required', function () {
    $this->expectException(\Illuminate\Database\QueryException::class);

    QuoteItem::factory()->create(['description' => null]);
});

test('quote item can have long description', function () {
    $longDescription = 'Complete website redesign including: homepage, about page, services page, contact form, blog integration, SEO optimization, mobile responsive design, and performance improvements.';

    $item = QuoteItem::factory()->create(['description' => $longDescription]);

    expect($item->description)->toBe($longDescription);
});

test('quote item total is calculated from quantity and unit_price', function () {
    $quantity = 10;
    $unitPrice = 15000; // €150.00
    $expectedTotal = 150000; // €1500.00

    $item = QuoteItem::factory()->create([
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total' => $expectedTotal,
    ]);

    expect($item->total)->toBe($expectedTotal);
});

test('quote item total with fractional quantity', function () {
    $quantity = 7.5;
    $unitPrice = 12000; // €120.00
    $expectedTotal = 90000; // €900.00

    $item = QuoteItem::factory()->create([
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total' => $expectedTotal,
    ]);

    expect($item->total)->toBe($expectedTotal);
});

test('quote item can be sorted by sort_order', function () {
    $quote = Quote::factory()->create();

    $item1 = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'sort_order' => 2,
    ]);
    $item2 = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'sort_order' => 1,
    ]);
    $item3 = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'sort_order' => 3,
    ]);

    $sortedItems = QuoteItem::where('quote_id', $quote->id)
        ->orderBy('sort_order')
        ->get();

    expect($sortedItems->first()->id)->toBe($item2->id)
        ->and($sortedItems->last()->id)->toBe($item3->id);
});

test('quote item has timestamps', function () {
    $item = QuoteItem::factory()->create();

    expect($item->created_at)->not->toBeNull()
        ->and($item->updated_at)->not->toBeNull();
});

test('quote item factory creates service items', function () {
    $item = QuoteItem::factory()->service()->create();

    expect($item->unit)->toBeIn(['hours', 'days'])
        ->and($item->quantity)->toBeGreaterThan(0);
});

test('quote item factory creates product items', function () {
    $item = QuoteItem::factory()->product()->create();

    expect($item->unit)->toBeIn(['piece', 'license', 'month', 'year'])
        ->and($item->quantity)->toBeGreaterThan(0);
});

test('quote item factory creates hourly items', function () {
    $item = QuoteItem::factory()->hourly()->create();

    expect($item->unit)->toBe('hours')
        ->and($item->quantity)->toBeGreaterThan(0)
        ->and($item->unit_price)->toBeGreaterThan(0);
});

test('quote item factory creates daily items', function () {
    $item = QuoteItem::factory()->daily()->create();

    expect($item->unit)->toBe('days')
        ->and($item->quantity)->toBeGreaterThan(0)
        ->and($item->unit_price)->toBeGreaterThan(0);
});

test('quote item factory can set position', function () {
    $item = QuoteItem::factory()->position(5)->create();

    expect($item->sort_order)->toBe(5);
});

test('quote item factory can set custom price', function () {
    $customPrice = 25000; // €250.00
    $item = QuoteItem::factory()->withPrice($customPrice)->create();

    expect($item->unit_price)->toBe($customPrice);
});

test('quote item factory can set custom quantity', function () {
    $customQuantity = 15.5;
    $item = QuoteItem::factory()->withQuantity($customQuantity)->create();

    expect($item->quantity)->toBe($customQuantity);
});

test('quote item with zero price', function () {
    $item = QuoteItem::factory()->create([
        'unit_price' => 0,
        'quantity' => 10,
        'total' => 0,
    ]);

    expect($item->unit_price)->toBe(0)
        ->and($item->total)->toBe(0);
});

test('multiple quote items can belong to same quote', function () {
    $quote = Quote::factory()->create();

    $item1 = QuoteItem::factory()->create(['quote_id' => $quote->id]);
    $item2 = QuoteItem::factory()->create(['quote_id' => $quote->id]);
    $item3 = QuoteItem::factory()->create(['quote_id' => $quote->id]);

    expect($quote->items()->count())->toBe(3);
});

test('quote item can be updated', function () {
    $item = QuoteItem::factory()->create([
        'description' => 'Original Description',
        'quantity' => 10,
        'unit_price' => 10000,
    ]);

    $item->update([
        'description' => 'Updated Description',
        'quantity' => 15,
        'unit_price' => 12000,
        'total' => 180000,
    ]);

    expect($item->fresh()->description)->toBe('Updated Description')
        ->and($item->fresh()->quantity)->toBe(15.0)
        ->and($item->fresh()->unit_price)->toBe(12000)
        ->and($item->fresh()->total)->toBe(180000);
});

test('quote item can be deleted', function () {
    $item = QuoteItem::factory()->create();
    $id = $item->id;

    $item->delete();

    expect(QuoteItem::find($id))->toBeNull();
});
