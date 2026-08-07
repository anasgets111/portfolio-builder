<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use Illuminate\Database\QueryException;

it('casts structured CMS attributes to their domain types', function () {
    $siteSetting = SiteSetting::factory()->create([
        'social_links' => [[
            'platform' => 'GitHub',
            'label' => 'GitHub',
            'url' => 'https://github.com/example',
        ]],
        'appearance' => [
            ...SiteSetting::DEFAULT_APPEARANCE,
            'font' => 'system',
        ],
    ]);
    $project = Project::factory()->published()->create([
        'technologies' => ['Laravel', 'Livewire'],
    ]);
    $experience = Experience::factory()->create([
        'start_date' => '2023-12-01',
        'end_date' => null,
        'technologies' => ['Laravel', 'Filament'],
    ]);

    expect($siteSetting->social_links)->toHaveCount(1)
        ->and($siteSetting->appearance['font'])->toBe('system')
        ->and($project->technologies)->toBe(['Laravel', 'Livewire'])
        ->and($project->is_published)->toBeTrue()
        ->and($project->published_at)->not->toBeNull()
        ->and($experience->start_date->toDateString())->toBe('2023-12-01')
        ->and($experience->end_date)->toBeNull()
        ->and($experience->technologies)->toBe(['Laravel', 'Filament']);
});

it('defines complete accessible appearance themes', function () {
    foreach (SiteSetting::APPEARANCE_THEMES as $theme) {
        expect(SiteSetting::resolveAppearance($theme))->toBe($theme)
            ->and(SiteSetting::appearanceContrastFailures($theme['colors']))->toBe([]);
    }
});

it('defines accessible color-only themes', function () {
    foreach (SiteSetting::APPEARANCE_COLOR_THEMES as $colors) {
        expect(SiteSetting::appearanceContrastFailures($colors))->toBe([]);
    }
});

it('returns only published CMS records in their configured order', function () {
    Project::factory()->create(['title' => 'Draft', 'sort_order' => 0]);
    Project::factory()->published()->create(['title' => 'Second', 'sort_order' => 20]);
    Project::factory()->published()->create(['title' => 'First', 'sort_order' => 10]);

    Skill::factory()->create(['name' => 'Draft skill', 'sort_order' => 0]);
    Skill::factory()->published()->create(['name' => 'Second skill', 'sort_order' => 20]);
    Skill::factory()->published()->create(['name' => 'First skill', 'sort_order' => 10]);

    expect(Project::query()->published()->ordered()->pluck('title')->all())
        ->toBe(['First', 'Second'])
        ->and(Skill::query()->published()->ordered()->pluck('name')->all())
        ->toBe(['First skill', 'Second skill']);
});

it('enforces a single site settings record', function () {
    SiteSetting::factory()->create();

    expect(fn () => SiteSetting::factory()->create())
        ->toThrow(QueryException::class);
});

it('relates experiences and projects in both directions', function () {
    $experience = Experience::factory()->create();
    $project = Project::factory()->create();

    $experience->projects()->attach($project);

    expect($experience->projects()->sole()->is($project))->toBeTrue()
        ->and($project->experiences()->sole()->is($experience))->toBeTrue();
});

it('rejects duplicate experience and project pairs', function () {
    $experience = Experience::factory()->create();
    $project = Project::factory()->create();

    $experience->projects()->attach($project);

    expect(fn () => $experience->projects()->attach($project))
        ->toThrow(QueryException::class);
});

it('removes pivot records when either related parent is deleted', function () {
    $project = Project::factory()->create();
    $firstExperience = Experience::factory()->create();
    $secondExperience = Experience::factory()->create();

    $project->experiences()->attach([$firstExperience->id, $secondExperience->id]);
    $firstExperience->delete();

    expect($project->experiences()->get()->modelKeys())->toBe([$secondExperience->id]);

    $project->delete();

    expect($secondExperience->projects()->doesntExist())->toBeTrue();
});
