<?php

namespace App\Filament\Resources\Experiences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExperiencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company')->searchable()->sortable(),
                TextColumn::make('position')->searchable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->placeholder('Present')->sortable(),
                TextColumn::make('projects.title')->label('Projects')->badge()->placeholder('—'),
                TextColumn::make('technologies')->badge(),
                TextColumn::make('sort_order')->sortable(),
                ToggleColumn::make('is_published'),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }
}
