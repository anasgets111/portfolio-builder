<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class PortfolioInteractionsChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Top interactions — last 30 days';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '360px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $interactions = AnalyticsEvent::query()
            ->whereNotIn('name', ['section_viewed', 'section_engaged'])
            ->where('created_at', '>=', now()->subDays(30))
            ->select(['name', 'target'])
            ->selectRaw('count(*) as interaction_count')
            ->groupBy(['name', 'target'])
            ->orderByDesc('interaction_count')
            ->limit(10)
            ->get();

        $projectIds = $interactions
            ->pluck('target')
            ->map(fn (mixed $target): ?int => $this->projectId((string) $target))
            ->filter()
            ->unique()
            ->values();

        $projects = Project::query()
            ->whereKey($projectIds)
            ->pluck('title', 'id');

        return [
            'datasets' => [[
                'label' => 'Interactions',
                'data' => $interactions->pluck('interaction_count')->map(fn (mixed $value): int => (int) $value)->all(),
                'backgroundColor' => '#f43f5e',
            ]],
            'labels' => $interactions
                ->map(fn (AnalyticsEvent $event): string => $this->interactionLabel($event, $projects))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function projectId(string $target): ?int
    {
        return preg_match('/^project:(\d+)/', $target, $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    /** @param Collection<int, string> $projects */
    private function interactionLabel(AnalyticsEvent $event, Collection $projects): string
    {
        $target = $event->target ?? '';
        $projectId = $this->projectId($target);

        if ($projectId !== null) {
            $projectName = $projects->get($projectId, 'Project #'.$projectId);
            $linkType = str($target)->afterLast(':')->toString();

            return match ($event->name) {
                'project_opened' => $projectName.' opened',
                'project_hovered' => $projectName.' hovered',
                'project_link_clicked' => $projectName.' '.$linkType.' clicked',
                default => $projectName,
            };
        }

        $name = str($event->name)->headline()->toString();

        return $target === '' ? $name : $name.' · '.str($target)->headline();
    }
}
