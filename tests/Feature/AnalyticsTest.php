<?php

use App\Filament\Pages\Analytics;
use App\Filament\Widgets\PortfolioEngagementOverview;
use App\Filament\Widgets\PortfolioInteractionsChart;
use App\Filament\Widgets\RecentPageViewsTable;
use App\Filament\Widgets\SectionEngagementChart;
use App\Models\AnalyticsEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Kholil\FilamentAnalitik\Models\PageView;
use Kholil\FilamentAnalitik\Resources\PageViewResource;
use Livewire\Livewire;

it('records anonymous public portfolio page views without query strings', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeaders([
            'CF-IPCountry' => 'EG',
            'User-Agent' => 'Portfolio Browser',
        ])
        ->get(route('home').'?utm_source=private')
        ->assertSuccessful()
        ->assertSee('name="analytics-endpoint"', false)
        ->assertSee('data-analytics-event="navigation_clicked"', false);

    $pageView = PageView::query()->sole();

    expect($pageView->url)->toBe(route('home'))
        ->and($pageView->path)->toBe('/')
        ->and($pageView->method)->toBe('GET')
        ->and($pageView->ip)->toHaveLength(64)
        ->and($pageView->ip)->not->toContain('203.0.113.10')
        ->and($pageView->user_agent)->toBeNull()
        ->and($pageView->country)->toBe('Egypt');
});

it('does not track requests outside the public portfolio routes', function () {
    $this->get('/up')->assertSuccessful();

    expect(PageView::query()->doesntExist())->toBeTrue();
});

it('respects browser privacy signals when tracking page views', function (string $header) {
    $this->withHeader($header, '1')
        ->get(route('home'))
        ->assertSuccessful();

    expect(PageView::query()->doesntExist())->toBeTrue();
})->with(['DNT', 'Sec-GPC']);

it('can disable portfolio analytics through configuration', function () {
    config()->set('filament-analitik.enabled', false);

    $this->get('/')
        ->assertSuccessful()
        ->assertDontSee('name="analytics-endpoint"', false);

    expect(PageView::query()->doesntExist())->toBeTrue();
});

it('stores validated interaction events', function () {
    $this->postJson(route('analytics.events.store'), [
        'name' => 'section_engaged',
        'target' => 'projects',
        'value' => 12_400,
    ])->assertNoContent();

    $event = AnalyticsEvent::query()->sole();

    expect($event->name)->toBe('section_engaged')
        ->and($event->target)->toBe('projects')
        ->and($event->value)->toBe(12_400);
});

it('rejects unknown or malformed interaction events', function () {
    $this->postJson(route('analytics.events.store'), [
        'name' => 'arbitrary_event',
        'target' => 'contains personal data',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'target']);

    expect(AnalyticsEvent::query()->doesntExist())->toBeTrue();
});

it('registers a single analytics page in the admin panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());

    expect(Filament::getCurrentPanel()->getPages())
        ->toContain(Analytics::class);

    expect(Filament::getCurrentPanel()->getResources())
        ->not->toContain(PageViewResource::class);

    expect(Filament::getCurrentPanel()->getWidgets())
        ->not->toContain(PortfolioEngagementOverview::class)
        ->not->toContain(SectionEngagementChart::class)
        ->not->toContain(PortfolioInteractionsChart::class)
        ->not->toContain(RecentPageViewsTable::class);

    $this->get(Analytics::getUrl(panel: 'admin'))
        ->assertSuccessful()
        ->assertSee('Analytics')
        ->assertSee('kholil.filament-analitik.widgets.page-views-chart', false)
        ->assertSee(PortfolioEngagementOverview::class, false)
        ->assertSee(RecentPageViewsTable::class, false);

    $this->get('/admin/page-views')->assertNotFound();
    $this->get('/admin/analytics-dashboard')->assertNotFound();
});

it('renders the local engagement widgets with recorded events', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());

    AnalyticsEvent::factory()->create([
        'name' => 'cv_opened',
        'target' => 'navigation',
        'value' => null,
    ]);
    AnalyticsEvent::factory()->create([
        'name' => 'section_engaged',
        'target' => 'projects',
        'value' => 15_000,
    ]);

    Livewire::test(PortfolioEngagementOverview::class)
        ->assertOk()
        ->assertSee('CV opens');
    Livewire::test(SectionEngagementChart::class)->assertOk();
    Livewire::test(PortfolioInteractionsChart::class)->assertOk();
    Livewire::test(RecentPageViewsTable::class)
        ->assertOk()
        ->assertSee('Recent visits');
});
