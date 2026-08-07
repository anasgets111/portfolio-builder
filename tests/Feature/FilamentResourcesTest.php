<?php

use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Filament\Resources\Experiences\Pages\ListExperiences;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Resources\Skills\Pages\CreateSkill;
use App\Filament\Resources\Skills\Pages\ListSkills;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TagsInput;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('allows verified users to access the panel and rejects unverified users', function () {
    $this->get('/admin')->assertSuccessful();

    $this->actingAs(User::factory()->unverified()->create());

    $this->get('/admin')->assertForbidden();
});

it('lists the project experience and skill resources', function () {
    $projects = Project::factory()->count(2)->create();
    $experiences = Experience::factory()->count(2)->create();
    $skills = Skill::factory()->count(2)->create();

    Livewire::test(ListProjects::class)
        ->assertOk()
        ->assertCanSeeTableRecords($projects);
    Livewire::test(ListExperiences::class)
        ->assertOk()
        ->assertCanSeeTableRecords($experiences);
    Livewire::test(ListSkills::class)
        ->assertOk()
        ->assertCanSeeTableRecords($skills);
});

it('creates CMS records through Filament resource forms', function () {
    Storage::fake('public');

    Livewire::test(CreateProject::class)
        ->fillForm([
            'title' => 'Portfolio CMS',
            'summary' => 'A focused portfolio content management foundation.',
            'body' => '<p>Built with Laravel and Filament.</p>',
            'image' => UploadedFile::fake()->image('project.png', 1600, 900),
            'technologies' => ['Laravel', 'Filament'],
            'sort_order' => 10,
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateExperience::class)
        ->fillForm([
            'company' => 'Example Company',
            'position' => 'Developer',
            'start_date' => '2023-12-01',
            'end_date' => null,
            'location' => 'Cairo, Egypt',
            'description' => 'Built and maintained web applications.',
            'technologies' => ['Laravel', 'Livewire'],
            'sort_order' => 10,
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateSkill::class)
        ->fillForm([
            'name' => 'Laravel',
            'sort_order' => 10,
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Project::query()->sole()->technologies)->toBe(['Laravel', 'Filament'])
        ->and(Experience::query()->sole()->technologies)->toBe(['Laravel', 'Livewire'])
        ->and(Skill::query()->sole()->name)->toBe('Laravel');
});

it('suggests previously used technologies and skills in technology fields', function () {
    Skill::factory()->create(['name' => 'Laravel']);
    Experience::factory()->create(['technologies' => ['Livewire', 'JavaScript']]);
    Project::factory()->create(['technologies' => ['Alpine.js', 'laravel']]);

    $hasExpectedSuggestions = fn (mixed $field): bool => $field instanceof TagsInput
        && $field->getSuggestions() === ['Alpine.js', 'JavaScript', 'Laravel', 'Livewire'];

    Livewire::test(CreateExperience::class)
        ->assertFormFieldExists('technologies', $hasExpectedSuggestions);

    Livewire::test(CreateProject::class)
        ->assertFormFieldExists('technologies', $hasExpectedSuggestions);
});

it('saves project relationships from the experience editor only', function () {
    $secondProject = Project::factory()->create(['title' => 'Second Project', 'sort_order' => 20]);
    $firstProject = Project::factory()->create(['title' => 'First Project', 'sort_order' => 10]);

    Livewire::test(CreateExperience::class)
        ->fillForm([
            'company' => 'Related Company',
            'position' => 'Developer',
            'start_date' => '2024-01-01',
            'end_date' => null,
            'location' => 'Cairo, Egypt',
            'description' => 'Worked on related portfolio projects.',
            'technologies' => ['Laravel'],
            'projects' => [$firstProject->id, $secondProject->id],
            'sort_order' => 10,
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $experience = Experience::query()->sole();

    expect($experience->projects()->orderBy('sort_order')->get()->modelKeys())
        ->toBe([$firstProject->id, $secondProject->id]);

    Livewire::test(CreateProject::class)
        ->assertFormFieldDoesNotExist('experiences');
});

it('edits but cannot create or delete the singleton site settings record', function () {
    $siteSetting = SiteSetting::factory()->create();

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
            'seo_keywords' => ['Laravel', 'Portfolio'],
            'social_links' => [[
                'platform' => 'GitHub',
                'label' => 'GitHub',
                'url' => 'https://github.com/example',
            ]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($siteSetting->refresh()->name)->toBe('Updated Name')
        ->and($siteSetting->seo_keywords)->toBe(['Laravel', 'Portfolio'])
        ->and(SiteSettingResource::canCreate())->toBeFalse()
        ->and(SiteSettingResource::canDelete($siteSetting))->toBeFalse()
        ->and(SiteSettingResource::canDeleteAny())->toBeFalse();
});

it('edits controlled portfolio appearance settings', function () {
    $siteSetting = SiteSetting::factory()->create();
    $appearance = [
        ...SiteSetting::DEFAULT_APPEARANCE,
        'font' => 'system',
        'color_scheme' => 'light',
        'page_width' => 'wide',
        'corner_style' => 'soft',
        'hero_layout' => 'portrait_left',
        'project_layout' => 'media_left',
        'motion' => 'off',
    ];

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm(['appearance' => $appearance])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($siteSetting->refresh()->appearance)->toBe($appearance);
});

it('previews unsaved design changes', function () {
    $siteSetting = SiteSetting::factory()->create([
        'hero_heading' => 'Published heading',
    ]);

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm([
            'hero_heading' => 'Draft heading',
            'appearance' => [
                ...SiteSetting::DEFAULT_APPEARANCE,
                'page_width' => 'wide',
                'corner_style' => 'soft',
                'hero_layout' => 'portrait_left',
                'project_layout' => 'media_left',
                'motion' => 'off',
            ],
        ])
        ->assertSee('Draft heading')
        ->assertSee('Wide canvas')
        ->assertSee('Soft corners · Motion off');

    expect($siteSetting->refresh()->hero_heading)->toBe('Published heading');
});

it('applies a curated theme as editable appearance values', function () {
    $siteSetting = SiteSetting::factory()->create();

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm(['appearance_theme' => 'terminal'])
        ->assertFormSet([
            'appearance' => SiteSetting::APPEARANCE_THEMES['terminal'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($siteSetting->refresh()->appearance)->toBe(SiteSetting::APPEARANCE_THEMES['terminal']);
});

it('applies a color theme without changing other appearance values', function () {
    $appearance = [
        ...SiteSetting::DEFAULT_APPEARANCE,
        'font' => 'inter',
        'page_width' => 'wide',
        'corner_style' => 'soft',
        'hero_layout' => 'portrait_left',
        'project_layout' => 'media_left',
        'motion' => 'off',
    ];
    $siteSetting = SiteSetting::factory()->create(['appearance' => $appearance]);
    $expectedAppearance = [
        ...$appearance,
        'colors' => SiteSetting::APPEARANCE_COLOR_THEMES['dracula'],
    ];

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm(['color_theme' => 'dracula'])
        ->assertFormSet([
            'appearance' => $expectedAppearance,
            'appearance_theme' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($siteSetting->refresh()->appearance)->toBe($expectedAppearance);
});

it('rejects unreadable or unapproved portfolio appearance settings', function () {
    $siteSetting = SiteSetting::factory()->create();
    $appearance = [
        ...SiteSetting::DEFAULT_APPEARANCE,
        'colors' => [
            ...SiteSetting::DEFAULT_APPEARANCE['colors'],
            'brand' => SiteSetting::DEFAULT_APPEARANCE['colors']['canvas'],
        ],
        'project_layout' => 'arbitrary-css-class',
    ];

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm(['appearance' => $appearance])
        ->call('save')
        ->assertHasFormErrors([
            'appearance.colors.brand',
            'appearance.project_layout',
        ]);
});

it('loads appearance defaults without publishing them immediately', function () {
    $customAppearance = [
        ...SiteSetting::DEFAULT_APPEARANCE,
        'font' => 'system',
        'page_width' => 'wide',
    ];
    $siteSetting = SiteSetting::factory()->create(['appearance' => $customAppearance]);

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm(['appearance_theme' => 'terminal'])
        ->callAction('restoreAppearanceDefaults')
        ->assertFormSet([
            'appearance' => SiteSetting::DEFAULT_APPEARANCE,
            'appearance_theme' => null,
            'color_theme' => null,
        ]);

    expect($siteSetting->refresh()->appearance)->toBe($customAppearance);
});

it('accepts supported social link schemes and rejects unsafe schemes', function () {
    $siteSetting = SiteSetting::factory()->create();

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm([
            'social_links' => [
                ['platform' => 'Website', 'label' => 'Website', 'url' => 'https://example.com'],
                ['platform' => 'Email', 'label' => 'Email', 'url' => 'mailto:hello@example.com'],
                ['platform' => 'Phone', 'label' => 'Phone', 'url' => 'tel:+201011423350'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm([
            'social_links' => [
                ['platform' => 'Unsafe', 'label' => 'Unsafe', 'url' => 'javascript:alert(1)'],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['social_links.0.url']);
});

it('replaces the configured CV while retaining the confirmed previous file', function () {
    Storage::fake('public');

    $previousResume = 'site/resumes/previous-cv.pdf';
    Storage::disk('public')->put($previousResume, '%PDF-1.7 previous CV');

    $siteSetting = SiteSetting::factory()->create([
        'resume_file' => $previousResume,
    ]);
    $replacement = UploadedFile::fake()->create('replacement-cv.pdf', 512, 'application/pdf');

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm(['resume_file' => null])
        ->fillForm(['resume_file' => [$replacement]])
        ->call('save')
        ->assertHasNoFormErrors();

    $storedResume = $siteSetting->refresh()->resume_file;

    expect($storedResume)
        ->not->toBe($previousResume)
        ->toStartWith('site/resumes/')
        ->toEndWith('.pdf');

    Storage::disk('public')->assertExists($storedResume);
    Storage::disk('public')->assertExists($previousResume);
});

it('rejects invalid CV file types and oversized PDFs', function () {
    Storage::fake('public');

    $siteSetting = SiteSetting::factory()->create([
        'resume_file' => null,
    ]);

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm([
            'resume_file' => UploadedFile::fake()->create('resume.txt', 100, 'text/plain'),
        ])
        ->call('save')
        ->assertHasFormErrors(['resume_file']);

    Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
        ->fillForm([
            'resume_file' => UploadedFile::fake()->create('resume.pdf', 10241, 'application/pdf'),
        ])
        ->call('save')
        ->assertHasFormErrors(['resume_file']);
});

it('restricts project image types sizes and dimensions', function () {
    Storage::fake('public');

    $projectData = fn (UploadedFile $image): array => [
        'title' => 'Restricted Project Image',
        'summary' => 'A project image validation test.',
        'body' => '<p>Project body.</p>',
        'image' => $image,
        'technologies' => ['Laravel'],
        'sort_order' => 10,
        'is_published' => true,
    ];

    $invalidImages = [
        UploadedFile::fake()->create('project.svg', 100, 'image/svg+xml'),
        UploadedFile::fake()->image('oversized-project.png', 1600, 900)->size(4097),
        UploadedFile::fake()->image('too-wide-project.png', 2561, 900),
    ];

    foreach ($invalidImages as $invalidImage) {
        Livewire::test(CreateProject::class)
            ->fillForm($projectData($invalidImage))
            ->call('create')
            ->assertHasFormErrors(['image']);
    }
});

it('restricts profile image types sizes and dimensions', function () {
    Storage::fake('public');

    $siteSetting = SiteSetting::factory()->create();
    $invalidImages = [
        UploadedFile::fake()->create('profile.svg', 100, 'image/svg+xml'),
        UploadedFile::fake()->image('oversized-profile.png', 1000, 1000)->size(2049),
        UploadedFile::fake()->image('too-wide-profile.png', 2001, 1000),
    ];

    foreach ($invalidImages as $invalidImage) {
        Livewire::test(EditSiteSetting::class, ['record' => $siteSetting->getRouteKey()])
            ->fillForm(['profile_image' => $invalidImage])
            ->call('save')
            ->assertHasFormErrors(['profile_image']);
    }
});
