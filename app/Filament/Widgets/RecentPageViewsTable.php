<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Kholil\FilamentAnalitik\Models\PageView;

class RecentPageViewsTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent visits')
            ->query(fn (): Builder => PageView::query())
            ->columns([
                TextColumn::make('path')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country')
                    ->placeholder('Unknown')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Visited')
                    ->dateTime()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
