<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('company'),
                Section::make([
                    TextInput::make('address_line_1'),
                    TextInput::make('address_line_2'),
                    TextInput::make('postal_code')->numeric()->maxLength(20),
                    TextInput::make('city'),
                    TextInput::make('country'),
                ])
                    ->label('Address'),
                Section::make([
                    TextInput::make('email')->email(),
                    TextInput::make('phone')->maxLength(50),
                    TextInput::make('website'),
                ])
                    ->label('Contact'),
                MarkdownEditor::make('notes')
            ]);
    }
}
