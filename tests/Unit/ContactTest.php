<?php

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a contact', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+49 30 12345678',
        'position' => 'CEO',
    ]);

    expect($contact->name)->toBe('Jane Doe')
        ->and($contact->email)->toBe('jane@example.com')
        ->and($contact->phone)->toBe('+49 30 12345678')
        ->and($contact->position)->toBe('CEO')
        ->and($contact->exists)->toBeTrue();
});

test('contact has fillable attributes', function () {
    $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
        'position',
        'is_primary',
    ];

    $contact = new Contact;

    expect($contact->getFillable())->toBe($fillable);
});

test('contact name is required', function () {
    $this->expectException(\Illuminate\Database\QueryException::class);

    $client = Client::factory()->create();
    Contact::factory()->create([
        'client_id' => $client->id,
        'name' => null,
    ]);
});

test('contact belongs to a client', function () {
    $client = Client::factory()->create(['company' => 'Acme Corp']);
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    expect($contact->client)->toBeInstanceOf(Client::class)
        ->and($contact->client->company)->toBe('Acme Corp');
});

test('contact email is optional', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => null,
    ]);

    expect($contact->email)->toBeNull()
        ->and($contact->exists)->toBeTrue();
});

test('contact phone is optional', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'phone' => null,
    ]);

    expect($contact->phone)->toBeNull()
        ->and($contact->exists)->toBeTrue();
});

test('contact position is optional', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'position' => null,
    ]);

    expect($contact->position)->toBeNull()
        ->and($contact->exists)->toBeTrue();
});

test('contact is_primary defaults to false', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    expect($contact->is_primary)->toBeFalse();
});

test('contact is_primary casts to boolean', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => true,
    ]);

    expect($contact->is_primary)->toBeTrue()
        ->and($contact->is_primary)->toBeBool();
});

test('contact uses soft deletes', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $id = $contact->id;

    $contact->delete();

    expect($contact->trashed())->toBeTrue()
        ->and(Contact::find($id))->toBeNull()
        ->and(Contact::withTrashed()->find($id))->not->toBeNull();
});

test('contact can be restored after soft delete', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $id = $contact->id;

    $contact->delete();
    $contact->restore();

    expect(Contact::find($id))->not->toBeNull()
        ->and($contact->trashed())->toBeFalse();
});

test('contact has timestamps', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    expect($contact->created_at)->not->toBeNull()
        ->and($contact->updated_at)->not->toBeNull();
});

test('contact is hard deleted when client is force deleted', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $contactId = $contact->id;

    $client->forceDelete();

    expect(Contact::withTrashed()->find($contactId))->toBeNull();
});

test('client can have multiple contacts', function () {
    $client = Client::factory()->create();
    Contact::factory()->count(3)->create(['client_id' => $client->id]);

    expect($client->contacts)->toHaveCount(3)
        ->and($client->contacts->first())->toBeInstanceOf(Contact::class);
});

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

test('making contact primary removes primary from other contacts', function () {
    $client = Client::factory()->create();
    $contact1 = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => true,
    ]);
    $contact2 = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => false,
    ]);
    $contact3 = Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => false,
    ]);

    $contact3->makePrimary();

    $client->refresh();

    expect($client->contacts()->where('is_primary', true)->count())->toBe(1)
        ->and($contact3->fresh()->is_primary)->toBeTrue()
        ->and($contact1->fresh()->is_primary)->toBeFalse()
        ->and($contact2->fresh()->is_primary)->toBeFalse();
});

test('client primary contact method returns primary contact', function () {
    $client = Client::factory()->create();
    Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => false,
    ]);
    Contact::factory()->create([
        'client_id' => $client->id,
        'name' => 'Primary Contact',
        'is_primary' => true,
    ]);

    $result = $client->primaryContact();

    expect($result)->toBeInstanceOf(Contact::class)
        ->and($result->name)->toBe('Primary Contact')
        ->and($result->is_primary)->toBeTrue();
});

test('client primary contact returns null when no primary contact', function () {
    $client = Client::factory()->create();
    Contact::factory()->create([
        'client_id' => $client->id,
        'is_primary' => false,
    ]);

    expect($client->primaryContact())->toBeNull();
});

test('contact full contact info attribute formats correctly', function () {
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

test('contact full contact info handles missing fields', function () {
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

test('can create contact using primary factory state', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->primary()->create(['client_id' => $client->id]);

    expect($contact->is_primary)->toBeTrue();
});

test('can find contact by email', function () {
    $client = Client::factory()->create();
    Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'contact1@example.com',
    ]);
    Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'contact2@example.com',
    ]);

    $contact = Contact::where('email', 'contact1@example.com')->first();

    expect($contact)->not->toBeNull()
        ->and($contact->email)->toBe('contact1@example.com');
});
