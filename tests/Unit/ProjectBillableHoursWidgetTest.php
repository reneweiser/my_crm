<?php

use App\Filament\Widgets\ProjectBillableHoursWidget;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('widget displays projects with billable hours', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $project1 = Project::factory()->hourly()->create([
        'client_id' => $client->id,
        'name' => 'Project A',
        'hourly_rate' => 100.00,
    ]);

    $project2 = Project::factory()->hourly()->create([
        'client_id' => $client->id,
        'name' => 'Project B',
        'hourly_rate' => 75.00,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project1->id,
        'user_id' => $user->id,
        'hours' => 10.0,
        'billable' => true,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project2->id,
        'user_id' => $user->id,
        'hours' => 5.0,
        'billable' => true,
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('Project A')
        ->assertSee('10.00 hrs')
        ->assertSee('Project B')
        ->assertSee('5.00 hrs');
});

test('widget only shows billable hours', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->hourly()->create([
        'client_id' => $client->id,
        'name' => 'Test Project',
        'hourly_rate' => 100.00,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 8.0,
        'billable' => true,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 4.0,
        'billable' => false, // Non-billable
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('8.00 hrs') // Only billable hours
        ->assertDontSee('12.00 hrs'); // Not total of all hours
});

test('widget shows placeholder when no time entries exist', function () {
    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('No Time Entries')
        ->assertSee('0.00 hrs')
        ->assertSee('Start tracking time on projects');
});

test('widget does not show projects without time entries', function () {
    $client = Client::factory()->create();

    Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Empty Project',
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertDontSee('Empty Project')
        ->assertSee('No Time Entries');
});

test('widget displays hourly rate description for hourly projects', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Hourly Project',
        'rate_type' => 'hourly',
        'hourly_rate' => 150.00,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 10.0,
        'billable' => true,
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('€150.00/hr');
});

test('widget displays fixed price description for fixed projects', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Fixed Project',
        'rate_type' => 'fixed',
        'fixed_price' => 5000.00,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 10.0,
        'billable' => true,
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('Fixed: €5000.00');
});

test('widget renders for over budget projects', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Over Budget Project',
        'budget_hours' => 5,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 10.0,
        'billable' => true,
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('Over Budget Project')
        ->assertSee('10.00 hrs');
});

test('widget renders for projects within budget', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Within Budget Project',
        'budget_hours' => 20,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'hours' => 10.0,
        'billable' => true,
    ]);

    Livewire::test(ProjectBillableHoursWidget::class)
        ->assertOk()
        ->assertSee('Within Budget Project')
        ->assertSee('10.00 hrs');
});
