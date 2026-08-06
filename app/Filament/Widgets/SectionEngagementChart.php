<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use Filament\Widgets\ChartWidget;

class SectionEngagementChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Average section engagement — last 30 days';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $sections = AnalyticsEvent::query()
            ->where('name', 'section_engaged')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('target')
            ->select('target')
            ->selectRaw('round(avg(value) / 1000.0, 1) as average_seconds')
            ->groupBy('target')
            ->orderByDesc('average_seconds')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Average seconds',
                'data' => $sections->pluck('average_seconds')->map(fn (mixed $value): float => (float) $value)->all(),
                'backgroundColor' => '#f43f5e',
            ]],
            'labels' => $sections->pluck('target')->map(fn (mixed $target): string => str((string) $target)->headline()->toString())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
