<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PortfolioEngagementOverview;
use App\Filament\Widgets\PortfolioInteractionsChart;
use App\Filament\Widgets\RecentPageViewsTable;
use App\Filament\Widgets\SectionEngagementChart;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Kholil\FilamentAnalitik\Widgets\AnalitikStatsOverview;
use Kholil\FilamentAnalitik\Widgets\PageViewsChart;
use Kholil\FilamentAnalitik\Widgets\TopCountriesTable;
use Kholil\FilamentAnalitik\Widgets\TopPagesTable;
use Kholil\FilamentAnalitik\Widgets\VisitorsCountryChart;

class Analytics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [
            AnalitikStatsOverview::class,
            PortfolioEngagementOverview::class,
            PageViewsChart::class,
            SectionEngagementChart::class,
            PortfolioInteractionsChart::class,
            VisitorsCountryChart::class,
            TopCountriesTable::class,
            TopPagesTable::class,
            RecentPageViewsTable::class,
        ];
    }
}
