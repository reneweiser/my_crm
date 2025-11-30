<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a time entry', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 5.5,
        'description' => 'Worked on feature implementation',
    ]);

    expect($timeEntry->hours)->toBe('5.50')
        ->and($timeEntry->project_id)->toBe($project->id)
        ->and($timeEntry->user_id)->toBe($user->id)
        ->and($timeEntry->description)->toBe('Worked on feature implementation')
        ->and($timeEntry->exists)->toBeTrue();
});

test('time entry has fillable attributes', function () {
    $fillable = [
        'project_id',
        'user_id',
        'description',
        'date',
        'hours',
        'billable',
        'invoiced',
        'invoice_id',
    ];

    $timeEntry = new TimeEntry;

    expect($timeEntry->getFillable())->toBe($fillable);
});

test('time entry belongs to a project', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $user = User::factory()->create();
    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($timeEntry->project)->toBeInstanceOf(Project::class)
        ->and($timeEntry->project->id)->toBe($project->id)
        ->and($timeEntry->project->name)->toBe('Test Project');
});

test('time entry belongs to a user', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create(['name' => 'John Developer']);
    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($timeEntry->user)->toBeInstanceOf(User::class)
        ->and($timeEntry->user->id)->toBe($user->id)
        ->and($timeEntry->user->name)->toBe('John Developer');
});

test('time entry billable defaults to true', function () {
    $timeEntry = TimeEntry::factory()->billable()->create();

    expect($timeEntry->billable)->toBeTrue();
});

test('time entry invoiced defaults to false', function () {
    $timeEntry = TimeEntry::factory()->create();

    expect($timeEntry->invoiced)->toBeFalse();
});

test('time entry can be marked as non-billable', function () {
    $timeEntry = TimeEntry::factory()->nonBillable()->create();

    expect($timeEntry->billable)->toBeFalse();
});

test('time entry calculates billable amount for hourly projects', function () {
    $project = Project::factory()->create([
        'rate_type' => 'hourly',
        'hourly_rate' => 100.00,
    ]);
    $user = User::factory()->create();

    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 5.5,
        'billable' => true,
    ]);

    expect($timeEntry->billable_amount)->toBe(550.0);
});

test('time entry billable amount is zero for non-billable entries', function () {
    $project = Project::factory()->create([
        'rate_type' => 'hourly',
        'hourly_rate' => 100.00,
    ]);
    $user = User::factory()->create();

    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 5.5,
        'billable' => false,
    ]);

    expect($timeEntry->billable_amount)->toBe(0.0);
});

test('time entry billable amount is zero when project has no hourly rate', function () {
    $project = Project::factory()->create([
        'rate_type' => 'fixed',
        'hourly_rate' => null,
    ]);
    $user = User::factory()->create();

    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 5.5,
        'billable' => true,
    ]);

    expect($timeEntry->billable_amount)->toBe(0.0);
});

test('time entry can be marked as invoiced', function () {
    $timeEntry = TimeEntry::factory()->create([
        'invoiced' => false,
        'invoice_id' => null,
    ]);

    $timeEntry->markAsInvoiced(123);

    expect($timeEntry->fresh()->invoiced)->toBeTrue()
        ->and($timeEntry->fresh()->invoice_id)->toBe(123);
});

test('time entry has timestamps', function () {
    $timeEntry = TimeEntry::factory()->create();

    expect($timeEntry->created_at)->not->toBeNull()
        ->and($timeEntry->updated_at)->not->toBeNull();
});

test('time entry casts date correctly', function () {
    $timeEntry = TimeEntry::factory()->create([
        'date' => '2025-11-30',
    ]);

    expect($timeEntry->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('time entry hours are cast as decimal', function () {
    $timeEntry = TimeEntry::factory()->create([
        'hours' => 5.5,
    ]);

    expect($timeEntry->hours)->toBe('5.50');
});

test('time entry can access client through project', function () {
    $client = Client::factory()->create(['name' => 'Test Client']);
    $project = Project::factory()->create(['client_id' => $client->id]);
    $user = User::factory()->create();

    $timeEntry = TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($timeEntry->project->client)->toBeInstanceOf(Client::class)
        ->and($timeEntry->project->client->name)->toBe('Test Client');
});
