<?php

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Client CRUD Operations', function () {
    test('can create a client with all fields', function () {
        $clientData = [
            'name' => 'John Doe',
            'company' => 'Acme Corporation',
            'address_line_1' => 'Hauptstraße 123',
            'address_line_2' => 'Building A',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'Germany',
            'email' => 'contact@acme.com',
            'phone' => '+49 30 12345678',
            'website' => 'https://acme.com',
            'notes' => 'Important client with preferred rates',
        ];

        $client = Client::create($clientData);

        expect($client->exists)->toBeTrue()
            ->and($client->name)->toBe('John Doe')
            ->and($client->company)->toBe('Acme Corporation')
            ->and($client->address_line_1)->toBe('Hauptstraße 123')
            ->and($client->postal_code)->toBe('10115')
            ->and($client->city)->toBe('Berlin')
            ->and($client->email)->toBe('contact@acme.com')
            ->and(Client::where('name', 'John Doe')->first())->not->toBeNull()
            ->and(Client::where('company', 'Acme Corporation')->first())->not->toBeNull()
            ->and(Client::where('email', 'contact@acme.com')->first())->not->toBeNull();
    });

    test('can create a client with minimal required fields', function () {
        $client = Client::create([
            'name' => 'Jane Smith',
        ]);

        expect($client->exists)->toBeTrue()
            ->and($client->name)->toBe('Jane Smith')
            ->and($client->company)->toBeNull()
            ->and($client->email)->toBeNull()
            ->and(Client::where('name', 'Jane Smith')->first())->not->toBeNull();

    });

    test('can read a client', function () {
        $created = Client::factory()->create([
            'name' => 'Test Client',
            'company' => 'Test Corp',
        ]);

        $client = Client::find($created->id);

        expect($client)->not->toBeNull()
            ->and($client->id)->toBe($created->id)
            ->and($client->name)->toBe('Test Client')
            ->and($client->company)->toBe('Test Corp');
    });

    test('can update a client', function () {
        $client = Client::factory()->create([
            'name' => 'Original Name',
            'company' => 'Original Company',
            'email' => 'old@example.com',
        ]);

        $client->update([
            'name' => 'Updated Name',
            'company' => 'Updated Company',
            'email' => 'new@example.com',
        ]);

        expect($client->fresh()->name)->toBe('Updated Name')
            ->and($client->fresh()->company)->toBe('Updated Company')
            ->and($client->fresh()->email)->toBe('new@example.com');

        $dbClient = Client::find($client->id);
        expect($dbClient->name)->toBe('Updated Name')
            ->and($dbClient->company)->toBe('Updated Company')
            ->and($dbClient->email)->toBe('new@example.com');

        expect(Client::where('id', $client->id)->where('name', 'Original Name')->first())->toBeNull();
    });

    test('can partially update a client', function () {
        $client = Client::factory()->create([
            'name' => 'John Doe',
            'company' => 'Original Company',
            'email' => 'john@example.com',
        ]);

        $client->update([
            'company' => 'New Company',
        ]);

        expect($client->fresh()->name)->toBe('John Doe')
            ->and($client->fresh()->company)->toBe('New Company')
            ->and($client->fresh()->email)->toBe('john@example.com');
    });

    test('can soft delete a client', function () {
        $client = Client::factory()->create(['name' => 'To Be Deleted']);
        $id = $client->id;

        $client->delete();

        expect($client->trashed())->toBeTrue();

        expect(Client::find($id))->toBeNull()
            ->and(Client::withTrashed()->find($id))->not->toBeNull();
    });

    test('can restore a soft deleted client', function () {
        $client = Client::factory()->create(['name' => 'Restored Client']);
        $id = $client->id;

        $client->delete();
        expect(Client::find($id))->toBeNull();

        $client->restore();

        expect(Client::find($id))->not->toBeNull()
            ->and($client->fresh()->trashed())->toBeFalse()
            ->and($client->fresh()->deleted_at)->toBeNull();
    });

    test('can permanently delete a client', function () {
        $client = Client::factory()->create(['name' => 'Permanent Delete']);
        $id = $client->id;

        $client->forceDelete();

        expect(Client::withTrashed()->find($id))->toBeNull();
    });

    test('can list multiple clients', function () {
        Client::factory()->count(5)->create();

        $clients = Client::all();

        expect($clients)->toHaveCount(5);
    });

    test('can search clients by name', function () {
        Client::factory()->create(['name' => 'Alice Johnson']);
        Client::factory()->create(['name' => 'Bob Smith']);
        Client::factory()->create(['name' => 'Alice Brown']);

        $results = Client::where('name', 'like', '%Alice%')->get();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('name')->toArray())->toContain('Alice Johnson', 'Alice Brown');
    });

    test('can search clients by company', function () {
        Client::factory()->create(['company' => 'Tech Solutions GmbH']);
        Client::factory()->create(['company' => 'Marketing Agency']);
        Client::factory()->create(['company' => 'Tech Innovations']);

        $results = Client::where('company', 'like', '%Tech%')->get();

        expect($results)->toHaveCount(2);
    });

    test('can search clients by email', function () {
        Client::factory()->create(['email' => 'contact@example.com']);
        Client::factory()->create(['email' => 'info@test.com']);

        $client = Client::where('email', 'contact@example.com')->first();

        expect($client)->not->toBeNull()
            ->and($client->email)->toBe('contact@example.com');
    });

    test('can filter clients by city', function () {
        Client::factory()->create(['city' => 'Berlin']);
        Client::factory()->create(['city' => 'Munich']);
        Client::factory()->create(['city' => 'Berlin']);

        $results = Client::where('city', 'Berlin')->get();

        expect($results)->toHaveCount(2);
    });

    test('can sort clients by name', function () {
        Client::factory()->create(['name' => 'Charlie']);
        Client::factory()->create(['name' => 'Alice']);
        Client::factory()->create(['name' => 'Bob']);

        $clients = Client::orderBy('name')->get();

        expect($clients->first()->name)->toBe('Alice')
            ->and($clients->last()->name)->toBe('Charlie');
    });

    test('can sort clients by created date', function () {
        Client::factory()->create(['name' => 'First']);
        sleep(1);
        Client::factory()->create(['name' => 'Second']);

        $clients = Client::orderBy('created_at', 'desc')->get();

        expect($clients->first()->name)->toBe('Second')
            ->and($clients->last()->name)->toBe('First');
    });
});

describe('Client Relationships', function () {
    test('can create client with contacts', function () {
        $client = Client::factory()
            ->has(Contact::factory()->count(3))
            ->create(['name' => 'Client with Contacts']);

        expect($client->contacts)->toHaveCount(3)
            ->and($client->contacts->first())->toBeInstanceOf(Contact::class);
    });

    test('can access client contacts', function () {
        $client = Client::factory()->create();
        Contact::factory()->count(2)->create(['client_id' => $client->id]);

        $client->refresh();

        expect($client->contacts)->toHaveCount(2);
    });

    test('can get primary contact for client', function () {
        $client = Client::factory()->create();
        Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Regular Contact',
            'is_primary' => false,
        ]);
        Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Primary Contact',
            'is_primary' => true,
        ]);

        $primaryContact = $client->primaryContact();

        expect($primaryContact)->not->toBeNull()
            ->and($primaryContact->name)->toBe('Primary Contact')
            ->and($primaryContact->is_primary)->toBeTrue();
    });

    test('deleting client soft deletes via cascade on force delete', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create(['client_id' => $client->id]);
        $contactId = $contact->id;

        $client->forceDelete();

        expect(Contact::withTrashed()->find($contactId))->toBeNull();
    });
});

describe('Client Validation', function () {
    test('client name is required for creation', function () {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Client::create([
            'name' => null,
            'company' => 'Test Company',
        ]);
    });

    test('client can have duplicate names', function () {
        Client::factory()->create(['name' => 'John Doe']);
        $client2 = Client::factory()->create(['name' => 'John Doe']);

        expect($client2->exists)->toBeTrue();
        expect(Client::where('name', 'John Doe')->count())->toBe(2);
    });

    test('client email can be null', function () {
        $client = Client::factory()->create(['email' => null]);

        expect($client->exists)->toBeTrue()
            ->and($client->email)->toBeNull();
    });
});

describe('Client Business Logic', function () {
    test('full address attribute formats address correctly', function () {
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

    test('full address attribute handles missing address line 2', function () {
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

    test('timestamps are automatically managed', function () {
        $client = Client::factory()->create();

        expect($client->created_at)->not->toBeNull()
            ->and($client->updated_at)->not->toBeNull()
            ->and($client->created_at->toDateTimeString())->toBe($client->updated_at->toDateTimeString());

        sleep(1);
        $client->update(['name' => 'Updated Name']);

        expect($client->updated_at->toDateTimeString())->not->toBe($client->created_at->toDateTimeString());
    });
});
