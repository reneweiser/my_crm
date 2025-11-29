<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a client', function () {
    $client = Client::factory()->create([
        'name' => 'John Doe',
        'company' => 'Acme Corp',
        'email' => 'john@acme.com',
    ]);

    expect($client->name)->toBe('John Doe')
        ->and($client->company)->toBe('Acme Corp')
        ->and($client->email)->toBe('john@acme.com')
        ->and($client->exists)->toBeTrue();
});

test('client has fillable attributes', function () {
    $fillable = [
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

    $client = new Client;

    expect($client->getFillable())->toBe($fillable);
});

test('client name is required', function () {
    $this->expectException(\Illuminate\Database\QueryException::class);

    Client::factory()->create(['name' => null]);
});

test('client can have optional company', function () {
    $client = Client::factory()->create(['company' => null]);

    expect($client->company)->toBeNull()
        ->and($client->exists)->toBeTrue();
});

test('client can have full address', function () {
    $client = Client::factory()->create([
        'address_line_1' => 'Hauptstraße 123',
        'address_line_2' => 'Apartment 4B',
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);

    expect($client->address_line_1)->toBe('Hauptstraße 123')
        ->and($client->postal_code)->toBe('10115')
        ->and($client->city)->toBe('Berlin')
        ->and($client->country)->toBe('Germany');
});

test('client default country is Germany', function () {
    $client = Client::factory()->create();

    expect($client->country)->toBe('Germany');
});

test('client can have contact information', function () {
    $client = Client::factory()->create([
        'email' => 'test@example.com',
        'phone' => '+49 30 12345678',
        'website' => 'https://example.com',
    ]);

    expect($client->email)->toBe('test@example.com')
        ->and($client->phone)->toBe('+49 30 12345678')
        ->and($client->website)->toBe('https://example.com');
});

test('client can have notes', function () {
    $client = Client::factory()->create([
        'notes' => 'Important client - preferred rate applies',
    ]);

    expect($client->notes)->toBe('Important client - preferred rate applies');
});

test('client uses soft deletes', function () {
    $client = Client::factory()->create();
    $id = $client->id;

    $client->delete();

    expect($client->trashed())->toBeTrue()
        ->and(Client::find($id))->toBeNull()
        ->and(Client::withTrashed()->find($id))->not->toBeNull();
});

test('client can be restored after soft delete', function () {
    $client = Client::factory()->create();
    $id = $client->id;

    $client->delete();
    $client->restore();

    expect(Client::find($id))->not->toBeNull()
        ->and($client->trashed())->toBeFalse();
});

test('client has timestamps', function () {
    $client = Client::factory()->create();

    expect($client->created_at)->not->toBeNull()
        ->and($client->updated_at)->not->toBeNull();
});

test('client full address attribute formats correctly', function () {
    $client = Client::factory()->create([
        'address_line_1' => 'Hauptstraße 123',
        'address_line_2' => 'Building A',
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);

    $expected = "Hauptstraße 123\nBuilding A\n10115 Berlin\nGermany";

    expect($client->full_address)->toBe($expected);
});

test('client full address handles missing address line 2', function () {
    $client = Client::factory()->create([
        'address_line_1' => 'Hauptstraße 123',
        'address_line_2' => null,
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);

    $expected = "Hauptstraße 123\n10115 Berlin\nGermany";

    expect($client->full_address)->toBe($expected);
});

test('client can be found by name', function () {
    Client::factory()->create(['name' => 'Jane Smith']);
    Client::factory()->create(['name' => 'John Doe']);

    $client = Client::where('name', 'Jane Smith')->first();

    expect($client)->not->toBeNull()
        ->and($client->name)->toBe('Jane Smith');
});

test('client can be found by company', function () {
    Client::factory()->create(['company' => 'Acme Corp']);
    Client::factory()->create(['company' => 'Tech Solutions GmbH']);

    $client = Client::where('company', 'Acme Corp')->first();

    expect($client)->not->toBeNull()
        ->and($client->company)->toBe('Acme Corp');
});

test('client can be found by email', function () {
    Client::factory()->create(['email' => 'contact@example.com']);
    Client::factory()->create(['email' => 'info@test.com']);

    $client = Client::where('email', 'contact@example.com')->first();

    expect($client)->not->toBeNull()
        ->and($client->email)->toBe('contact@example.com');
});
