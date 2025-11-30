<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a project', function () {
    $client = Client::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Website Redesign',
        'rate_type' => 'hourly',
        'hourly_rate' => 100.00,
    ]);

    expect($project->name)->toBe('Website Redesign')
        ->and($project->client_id)->toBe($client->id)
        ->and($project->rate_type)->toBe('hourly')
        ->and($project->hourly_rate)->toBe('100.00')
        ->and($project->exists)->toBeTrue();
});

test('project has fillable attributes', function () {
    $fillable = [
        'client_id',
        'name',
        'description',
        'status',
        'rate_type',
        'hourly_rate',
        'fixed_price',
        'budget_hours',
        'start_date',
        'end_date',
    ];

    $project = new Project;

    expect($project->getFillable())->toBe($fillable);
});

test('project belongs to a client', function () {
    $client = Client::factory()->create(['name' => 'Test Client']);
    $project = Project::factory()->create(['client_id' => $client->id]);

    expect($project->client)->toBeInstanceOf(Client::class)
        ->and($project->client->id)->toBe($client->id)
        ->and($project->client->name)->toBe('Test Client');
});

test('project has time entries relationship', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();
    TimeEntry::factory()->count(3)->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($project->timeEntries)->toHaveCount(3)
        ->and($project->timeEntries->first())->toBeInstanceOf(TimeEntry::class);
});

test('project status defaults to active', function () {
    $project = Project::factory()->active()->create();

    expect($project->status)->toBe('active');
});

test('project can be active, completed, or archived', function () {
    $active = Project::factory()->create(['status' => 'active']);
    $completed = Project::factory()->create(['status' => 'completed']);
    $archived = Project::factory()->create(['status' => 'archived']);

    expect($active->status)->toBe('active')
        ->and($completed->status)->toBe('completed')
        ->and($archived->status)->toBe('archived');
});

test('project can be hourly rate type', function () {
    $project = Project::factory()->hourly()->create();

    expect($project->rate_type)->toBe('hourly')
        ->and($project->hourly_rate)->not->toBeNull()
        ->and($project->fixed_price)->toBeNull();
});

test('project can be fixed price type', function () {
    $project = Project::factory()->fixedPrice()->create();

    expect($project->rate_type)->toBe('fixed')
        ->and($project->fixed_price)->not->toBeNull()
        ->and($project->hourly_rate)->toBeNull();
});

test('project calculates total billable hours', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 5.0,
        'billable' => true,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 3.5,
        'billable' => true,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 2.0,
        'billable' => false, // Non-billable
    ]);

    expect($project->total_billable_hours)->toBe(8.5);
});

test('project calculates total billable amount for hourly projects', function () {
    $project = Project::factory()->create([
        'rate_type' => 'hourly',
        'hourly_rate' => 100.00,
    ]);
    $user = User::factory()->create();

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 5.0,
        'billable' => true,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 3.0,
        'billable' => true,
    ]);

    expect($project->total_billable_amount)->toBe(800.0);
});

test('project calculates total billable amount for fixed price projects', function () {
    $project = Project::factory()->create([
        'rate_type' => 'fixed',
        'fixed_price' => 5000.00,
    ]);

    expect($project->total_billable_amount)->toBe(5000.0);
});

test('project isActive method returns true for active status', function () {
    $project = Project::factory()->create(['status' => 'active']);

    expect($project->isActive())->toBeTrue();
});

test('project isActive method returns false for non-active status', function () {
    $completed = Project::factory()->create(['status' => 'completed']);
    $archived = Project::factory()->create(['status' => 'archived']);

    expect($completed->isActive())->toBeFalse()
        ->and($archived->isActive())->toBeFalse();
});

test('project isOverBudget returns false when no budget set', function () {
    $project = Project::factory()->create(['budget_hours' => null]);
    $user = User::factory()->create();

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 100.0,
        'billable' => true,
    ]);

    expect($project->isOverBudget())->toBeFalse();
});

test('project isOverBudget returns true when hours exceed budget', function () {
    $project = Project::factory()->create(['budget_hours' => 10]);
    $user = User::factory()->create();

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 12.0,
        'billable' => true,
    ]);

    expect($project->isOverBudget())->toBeTrue();
});

test('project isOverBudget returns false when hours within budget', function () {
    $project = Project::factory()->create(['budget_hours' => 20]);
    $user = User::factory()->create();

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 15.0,
        'billable' => true,
    ]);

    expect($project->isOverBudget())->toBeFalse();
});

test('project uses soft deletes', function () {
    $project = Project::factory()->create();
    $id = $project->id;

    $project->delete();

    expect($project->trashed())->toBeTrue()
        ->and(Project::find($id))->toBeNull()
        ->and(Project::withTrashed()->find($id))->not->toBeNull();
});

test('project can be restored after soft delete', function () {
    $project = Project::factory()->create();
    $id = $project->id;

    $project->delete();
    $project->restore();

    expect(Project::find($id))->not->toBeNull()
        ->and($project->trashed())->toBeFalse();
});

test('project has timestamps', function () {
    $project = Project::factory()->create();

    expect($project->created_at)->not->toBeNull()
        ->and($project->updated_at)->not->toBeNull();
});

test('project casts dates correctly', function () {
    $project = Project::factory()->create([
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    expect($project->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($project->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
