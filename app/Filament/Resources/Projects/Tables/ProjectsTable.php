<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('client.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'archived' => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('rate_type')
                    ->label('Billing Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hourly' => 'Hourly',
                        'fixed' => 'Fixed Price',
                        'retainer' => 'Retainer',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('hourly_rate')
                    ->money('EUR')
                    ->label('Hourly Rate')
                    ->visible(fn ($record) => $record?->rate_type === 'hourly'),
                TextColumn::make('fixed_price')
                    ->money('EUR')
                    ->label('Fixed Price')
                    ->visible(fn ($record) => $record?->rate_type === 'fixed'),
                TextColumn::make('budget_hours')
                    ->suffix(' hrs')
                    ->label('Budget'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ]),
                SelectFilter::make('rate_type')
                    ->label('Billing Type')
                    ->options([
                        'hourly' => 'Hourly',
                        'fixed' => 'Fixed Price',
                        'retainer' => 'Retainer',
                    ]),
                SelectFilter::make('client_id')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
