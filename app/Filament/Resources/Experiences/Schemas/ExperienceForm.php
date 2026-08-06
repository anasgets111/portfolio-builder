<?php

namespace App\Filament\Resources\Experiences\Schemas;

use App\Filament\TechnologySuggestions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Experience')
                    ->schema([
                        TextInput::make('company')->required()->maxLength(255),
                        TextInput::make('position')->required()->maxLength(255),
                        DatePicker::make('start_date')->required(),
                        DatePicker::make('end_date')
                            ->rule('after_or_equal:start_date'),
                        TextInput::make('location')->required()->maxLength(255),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Textarea::make('description')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        TagsInput::make('technologies')
                            ->required()
                            ->suggestions(fn (): array => TechnologySuggestions::all())
                            ->columnSpanFull(),
                        Select::make('projects')
                            ->relationship(
                                titleAttribute: 'title',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->orderBy('sort_order')
                                    ->orderBy('id'),
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        Toggle::make('is_published')
                            ->required()
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}
