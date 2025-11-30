<?php

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Contact CRUD Operations', function () {
    test('can create a contact with all fields', function () {
        $client = Client::factory()->create();

        $contactData = [
            'client_id' => $client->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+49 30 12345678',
            'position' => 'CEO',
            'is_primary' => true,
        ];

        $contact = Contact::create($contactData);

        expect($contact->exists)->toBeTrue()
            ->and($contact->name)->toBe('Jane Doe')
            ->and($contact->email)->toBe('jane@example.com')
            ->and($contact->phone)->toBe('+49 30 12345678')
            ->and($contact->position)->toBe('CEO')
            ->and($contact->is_primary)->toBeTrue()
            ->and(Contact::where('name', 'Jane Doe')->first())->not->toBeNull();

    });

    test('can create a contact with minimal required fields', function () {
        $client = Client::factory()->create();

        $contact = Contact::create([
            'client_id' => $client->id,
            'name' => 'John Smith',
        ])->refresh();

        expect($contact->exists)->toBeTrue()
            ->and($contact->name)->toBe('John Smith')
            ->and($contact->email)->toBeNull()
            ->and($contact->phone)->toBeNull()
            ->and($contact->position)->toBeNull()
            ->and($contact->is_primary)->toBeFalse();
    });

    test('can read a contact', function () {
        $client = Client::factory()->create();
        $created = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Test Contact',
            'position' => 'Manager',
        ]);

        $contact = Contact::find($created->id);

        expect($contact)->not->toBeNull()
            ->and($contact->id)->toBe($created->id)
            ->and($contact->name)->toBe('Test Contact')
            ->and($contact->position)->toBe('Manager');
    });

    test('can update a contact', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Original Name',
            'email' => 'old@example.com',
            'position' => 'Developer',
        ]);

        $contact->update([
            'name' => 'Updated Name',
            'email' => 'new@example.com',
            'position' => 'Senior Developer',
        ]);

        expect($contact->fresh()->name)->toBe('Updated Name')
            ->and($contact->fresh()->email)->toBe('new@example.com')
            ->and($contact->fresh()->position)->toBe('Senior Developer');

        $dbContact = Contact::find($contact->id);
        expect($dbContact->name)->toBe('Updated Name');
    });

    test('can partially update a contact', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'position' => 'Manager',
        ]);

        $contact->update([
            'position' => 'Senior Manager',
        ]);

        expect($contact->fresh()->name)->toBe('Jane Doe')
            ->and($contact->fresh()->email)->toBe('jane@example.com')
            ->and($contact->fresh()->position)->toBe('Senior Manager');
    });

    test('can soft delete a contact', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'To Be Deleted',
        ]);
        $id = $contact->id;

        $contact->delete();

        expect($contact->trashed())->toBeTrue();
        expect(Contact::find($id))->toBeNull()
            ->and(Contact::withTrashed()->find($id))->not->toBeNull();
    });

    test('can restore a soft deleted contact', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Restored Contact',
        ]);
        $id = $contact->id;

        $contact->delete();
        expect(Contact::find($id))->toBeNull();

        $contact->restore();

        expect(Contact::find($id))->not->toBeNull()
            ->and($contact->fresh()->trashed())->toBeFalse()
            ->and($contact->fresh()->deleted_at)->toBeNull();
    });

    test('can permanently delete a contact', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Permanent Delete',
        ]);
        $id = $contact->id;

        $contact->forceDelete();

        expect(Contact::withTrashed()->find($id))->toBeNull();
    });

    test('can list multiple contacts', function () {
        $client = Client::factory()->create();
        Contact::factory()->count(5)->create(['client_id' => $client->id]);

        $contacts = Contact::all();

        expect($contacts)->toHaveCount(5);
    });

    test('can filter contacts by client', function () {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        Contact::factory()->count(3)->create(['client_id' => $client1->id]);
        Contact::factory()->count(2)->create(['client_id' => $client2->id]);

        $client1Contacts = Contact::where('client_id', $client1->id)->get();
        $client2Contacts = Contact::where('client_id', $client2->id)->get();

        expect($client1Contacts)->toHaveCount(3)
            ->and($client2Contacts)->toHaveCount(2);
    });

    test('can search contacts by name', function () {
        $client = Client::factory()->create();
        Contact::factory()->create(['client_id' => $client->id, 'name' => 'Alice Johnson']);
        Contact::factory()->create(['client_id' => $client->id, 'name' => 'Bob Smith']);
        Contact::factory()->create(['client_id' => $client->id, 'name' => 'Alice Brown']);

        $results = Contact::where('name', 'like', '%Alice%')->get();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('name')->toArray())->toContain('Alice Johnson', 'Alice Brown');
    });

    test('can search contacts by email', function () {
        $client = Client::factory()->create();
        Contact::factory()->create(['client_id' => $client->id, 'email' => 'contact@example.com']);
        Contact::factory()->create(['client_id' => $client->id, 'email' => 'info@test.com']);

        $contact = Contact::where('email', 'contact@example.com')->first();

        expect($contact)->not->toBeNull()
            ->and($contact->email)->toBe('contact@example.com');
    });

    test('can filter contacts by position', function () {
        $client = Client::factory()->create();
        Contact::factory()->create(['client_id' => $client->id, 'position' => 'CEO']);
        Contact::factory()->create(['client_id' => $client->id, 'position' => 'CTO']);
        Contact::factory()->create(['client_id' => $client->id, 'position' => 'CEO']);

        $results = Contact::where('position', 'CEO')->get();

        expect($results)->toHaveCount(2);
    });

    test('can filter primary contacts', function () {
        $client = Client::factory()->create();
        Contact::factory()->create(['client_id' => $client->id, 'is_primary' => true]);
        Contact::factory()->count(3)->create(['client_id' => $client->id, 'is_primary' => false]);

        $primaryContacts = Contact::where('is_primary', true)->get();

        expect($primaryContacts)->toHaveCount(1)
            ->and($primaryContacts->first()->is_primary)->toBeTrue();
    });

    test('can sort contacts by name', function () {
        $client = Client::factory()->create();
        Contact::factory()->create(['client_id' => $client->id, 'name' => 'Charlie']);
        Contact::factory()->create(['client_id' => $client->id, 'name' => 'Alice']);
        Contact::factory()->create(['client_id' => $client->id, 'name' => 'Bob']);

        $contacts = Contact::orderBy('name')->get();

        expect($contacts->first()->name)->toBe('Alice')
            ->and($contacts->last()->name)->toBe('Charlie');
    });
});

describe('Contact Relationships', function () {
    test('contact belongs to a client', function () {
        $client = Client::factory()->create(['company' => 'Acme Corp']);
        $contact = Contact::factory()->create(['client_id' => $client->id]);

        expect($contact->client)->toBeInstanceOf(Client::class)
            ->and($contact->client->company)->toBe('Acme Corp')
            ->and($contact->client_id)->toBe($client->id);
    });

    test('client can have multiple contacts', function () {
        $client = Client::factory()->create();
        Contact::factory()->count(4)->create(['client_id' => $client->id]);

        $client->refresh();

        expect($client->contacts)->toHaveCount(4);
    });

    test('deleting client force deletes contacts', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create(['client_id' => $client->id]);
        $contactId = $contact->id;

        $client->forceDelete();

        expect(Contact::withTrashed()->find($contactId))->toBeNull();
    });
});

describe('Contact Validation', function () {
    test('contact requires client_id', function () {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Contact::create([
            'name' => 'Test Contact',
            'client_id' => null,
        ]);
    });

    test('contact requires name', function () {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $client = Client::factory()->create();
        Contact::create([
            'client_id' => $client->id,
            'name' => null,
        ]);
    });

    test('contact email can be null', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'email' => null,
        ]);

        expect($contact->exists)->toBeTrue()
            ->and($contact->email)->toBeNull();
    });

    test('contact phone can be null', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'phone' => null,
        ]);

        expect($contact->exists)->toBeTrue()
            ->and($contact->phone)->toBeNull();
    });

    test('contact position can be null', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'position' => null,
        ]);

        expect($contact->exists)->toBeTrue()
            ->and($contact->position)->toBeNull();
    });
});

describe('Contact Business Logic', function () {
    test('can make contact primary', function () {
        $client = Client::factory()->create();
        $contact1 = Contact::factory()->create([
            'client_id' => $client->id,
            'is_primary' => true,
        ]);
        $contact2 = Contact::factory()->create([
            'client_id' => $client->id,
            'is_primary' => false,
        ]);

        $contact2->makePrimary();

        expect($contact2->fresh()->is_primary)->toBeTrue()
            ->and($contact1->fresh()->is_primary)->toBeFalse();
    });

    test('making contact primary only affects contacts from same client', function () {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $contact1 = Contact::factory()->create([
            'client_id' => $client1->id,
            'is_primary' => true,
        ]);
        $contact2 = Contact::factory()->create([
            'client_id' => $client2->id,
            'is_primary' => false,
        ]);

        $contact2->makePrimary();

        expect($contact2->fresh()->is_primary)->toBeTrue()
            ->and($contact1->fresh()->is_primary)->toBeTrue();
    });

    test('client can get primary contact', function () {
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

    test('client returns null when no primary contact exists', function () {
        $client = Client::factory()->create();
        Contact::factory()->count(2)->create([
            'client_id' => $client->id,
            'is_primary' => false,
        ]);

        $primaryContact = $client->primaryContact();

        expect($primaryContact)->toBeNull();
    });

    test('full contact info attribute formats correctly', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Jane Doe',
            'position' => 'CEO',
            'email' => 'jane@example.com',
            'phone' => '+49 30 12345678',
        ]);

        $expected = 'Jane Doe | CEO | jane@example.com | +49 30 12345678';

        expect($contact->full_contact_info)->toBe($expected);
    });

    test('full contact info handles missing optional fields', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Jane Doe',
            'position' => null,
            'email' => 'jane@example.com',
            'phone' => null,
        ]);

        $expected = 'Jane Doe | jane@example.com';

        expect($contact->full_contact_info)->toBe($expected);
    });

    test('is_primary casts to boolean', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'is_primary' => true,
        ]);

        expect($contact->is_primary)->toBeTrue()
            ->and($contact->is_primary)->toBeBool();

        $contact2 = Contact::factory()->create([
            'client_id' => $client->id,
            'is_primary' => false,
        ]);

        expect($contact2->is_primary)->toBeFalse()
            ->and($contact2->is_primary)->toBeBool();
    });

    test('timestamps are automatically managed', function () {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create(['client_id' => $client->id]);

        expect($contact->created_at)->not->toBeNull()
            ->and($contact->updated_at)->not->toBeNull()
            ->and($contact->created_at->toDateTimeString())->toBe($contact->updated_at->toDateTimeString());

        sleep(1);
        $contact->update(['name' => 'Updated Name']);

        expect($contact->updated_at->toDateTimeString())->not->toBe($contact->created_at->toDateTimeString());
    });
});
