<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectBillableHoursWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $projects = Project::with('timeEntries')
            ->whereHas('timeEntries')
            ->get();

        $stats = [];

        foreach ($projects as $project) {
            $billableHours = $project->total_billable_hours;

            $description = match ($project->rate_type) {
                'hourly' => sprintf('€%.2f/hr × %.2f hrs', $project->hourly_rate ?? 0, $billableHours),
                'fixed' => sprintf('Fixed: €%.2f', $project->fixed_price ?? 0),
                'retainer' => 'Retainer',
                default => '',
            };

            $stats[] = Stat::make($project->name, sprintf('%.2f hrs', $billableHours))
                ->description($description)
                ->descriptionIcon('heroicon-o-clock')
                ->color($project->isOverBudget() ? 'danger' : 'success')
                ->chart($this->getProjectHoursChart($project));
        }

        // If no projects with time entries, show a placeholder
        if (empty($stats)) {
            $stats[] = Stat::make('No Time Entries', '0.00 hrs')
                ->description('Start tracking time on projects')
                ->descriptionIcon('heroicon-o-information-circle')
                ->color('gray');
        }

        return $stats;
    }

    /**
     * Generate a simple chart showing daily billable hours for the project.
     */
    protected function getProjectHoursChart(Project $project): array
    {
        $chartData = $project->timeEntries()
            ->where('billable', true)
            ->selectRaw('DATE(date) as entry_date, SUM(hours) as total_hours')
            ->groupBy('entry_date')
            ->orderBy('entry_date')
            ->limit(7)
            ->get()
            ->pluck('total_hours')
            ->map(fn ($hours) => (float) $hours)
            ->toArray();

        return $chartData ?: [0];
    }
}
