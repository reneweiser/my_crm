<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                MarkdownEditor::make('description')
                    ->columnSpanFull(),
                Section::make('Project Status & Dates')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'archived' => 'Archived',
                            ])
                            ->default('active')
                            ->required(),
                        DatePicker::make('start_date'),
                        DatePicker::make('end_date'),
                    ])
                    ->columns(3),
                Section::make('Billing Information')
                    ->schema([
                        Select::make('rate_type')
                            ->options([
                                'hourly' => 'Hourly Rate',
                                'fixed' => 'Fixed Price',
                                'retainer' => 'Retainer',
                            ])
                            ->default('hourly')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state !== 'hourly') {
                                    $set('hourly_rate', null);
                                }
                                if ($state !== 'fixed') {
                                    $set('fixed_price', null);
                                }
                            }),
                        TextInput::make('hourly_rate')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->visible(fn (callable $get) => $get('rate_type') === 'hourly'),
                        TextInput::make('fixed_price')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->visible(fn (callable $get) => $get('rate_type') === 'fixed'),
                        TextInput::make('budget_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->helperText('Optional: Set a budget limit for billable hours'),
                    ])
                    ->columns(2),
            ]);
    }
}
