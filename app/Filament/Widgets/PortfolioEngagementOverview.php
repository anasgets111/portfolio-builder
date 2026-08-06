<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PortfolioEngagementOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 10;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $counts = AnalyticsEvent::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('name', ['cv_opened', 'contact_clicked', 'project_opened', 'social_clicked'])
            ->selectRaw('name, count(*) as interaction_count')
            ->groupBy('name')
            ->pluck('interaction_count', 'name');

        return [
            Stat::make('CV opens', (int) $counts->get('cv_opened', 0))
                ->description('Last 30 days'),
            Stat::make('Contact clicks', (int) $counts->get('contact_clicked', 0))
                ->description('Last 30 days'),
            Stat::make('Project opens', (int) $counts->get('project_opened', 0))
                ->description('Last 30 days'),
            Stat::make('Social clicks', (int) $counts->get('social_clicked', 0))
                ->description('Last 30 days'),
        ];
    }
}
