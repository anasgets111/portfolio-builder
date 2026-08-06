<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->withoutVite();
});

it('defines the Catppuccin Mocha public theme', function () {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain(
            '--color-canvas: #1e1e2e;',
            '--color-panel: #313244;',
            '--color-ink: #cdd6f4;',
            '--color-ink-muted: #a6adc8;',
            '--color-brand: #cba6f7;',
            '--color-brand-soft: #b4befe;',
            'color-scheme: dark;',
            'background: rgb(17 17 27 / 0.82);',
        );
});

it('returns the public homepage with one meaningful heading', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();

    expect(mb_substr_count($response->getContent(), '<h1'))->toBe(1);
});

it('renders the generic seeded portfolio content', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Hello, I\'m Your Name.')
        ->assertSee('Your Professional Title')
        ->assertSee('property="og:image" content="'.asset('storage/site/profile-images/profile-placeholder.svg').'"', false)
        ->assertSee('name="twitter:image" content="'.asset('storage/site/profile-images/profile-placeholder.svg').'"', false)
        ->assertSee('Project Title')
        ->assertSee('Skill One')
        ->assertSee('Invite visitors to get in touch.');
});

it('renders generic placeholder images with stable dimensions', function () {
    $this->seed(DatabaseSeeder::class);

    $response = $this->get(route('home'));
    $response->assertSuccessful();

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR);
    $xpath = new DOMXPath($document);

    $portraitUrl = asset('storage/site/profile-images/profile-placeholder.svg');
    $portrait = $xpath->query("//img[@src='{$portraitUrl}']")?->item(0);

    expect($portrait)->toBeInstanceOf(DOMElement::class)
        ->and($portrait?->getAttribute('width'))->toBe('960')
        ->and($portrait?->getAttribute('height'))->toBe('960')
        ->and($portrait?->getAttribute('loading'))->toBe('eager')
        ->and($portrait?->getAttribute('fetchpriority'))->toBe('high');

    expect($xpath->query("//picture[img[@src='{$portraitUrl}']]/source")?->length)->toBe(0);

    $projectUrl = asset('storage/projects/project-placeholder.svg');
    $projectImages = $xpath->query("//img[@src='{$projectUrl}']");

    expect($projectImages)->not->toBeFalse()
        ->and($projectImages?->length)->toBe(2);

    $projectSrcsets = [];

    foreach ($projectImages ?? [] as $projectImage) {
        expect($projectImage)->toBeInstanceOf(DOMElement::class)
            ->and($projectImage->getAttribute('width'))->toBe('1600')
            ->and($projectImage->getAttribute('height'))->toBe('900')
            ->and($projectImage->getAttribute('loading'))->toBe('lazy')
            ->and($projectImage->getAttribute('alt'))->toContain('Project Title');

        $projectSource = $projectImage->parentNode?->firstChild;

        while ($projectSource !== null && ! $projectSource instanceof DOMElement) {
            $projectSource = $projectSource->nextSibling;
        }

        $projectSrcsets[] = $projectSource?->getAttribute('srcset');
    }

    expect($projectSrcsets[0])->toBe($projectSrcsets[1])
        ->and($xpath->query('//picture')?->length)->toBe(3)
        ->and($xpath->query("//img[contains(@src, '/storage/projects/') and @loading='lazy']")?->length)->toBe(2);
});

it('renders custom uploads through their original fallback without invented variants', function () {
    SiteSetting::factory()->create([
        'profile_image' => 'site/profile-images/custom-profile.webp',
    ]);
    Project::factory()->published()->create([
        'image' => 'projects/custom-project.webp',
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('src="'.asset('storage/site/profile-images/custom-profile.webp').'"', false)
        ->assertSee('src="'.asset('storage/projects/custom-project.webp').'"', false)
        ->assertDontSee('custom-profile-500.webp')
        ->assertDontSee('custom-project-640.webp')
        ->assertDontSee('custom-project-1280.webp');
});

it('renders only published records in their configured order', function () {
    SiteSetting::factory()->create();

    Project::factory()->published()->create(['title' => 'Second Project', 'sort_order' => 20]);
    Project::factory()->published()->create(['title' => 'First Project', 'sort_order' => 10]);
    Project::factory()->create(['title' => 'Hidden Project', 'sort_order' => 0]);

    Experience::factory()->published()->create(['company' => 'Second Company', 'sort_order' => 20]);
    Experience::factory()->published()->create(['company' => 'First Company', 'sort_order' => 10]);
    Experience::factory()->create(['company' => 'Hidden Company', 'sort_order' => 0]);

    Skill::factory()->published()->create(['name' => 'Second Skill', 'sort_order' => 20]);
    Skill::factory()->published()->create(['name' => 'First Skill', 'sort_order' => 10]);
    Skill::factory()->create(['name' => 'Hidden Skill', 'sort_order' => 0]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder(['First Project', 'Second Project'])
        ->assertSeeInOrder(['First Company', 'Second Company'])
        ->assertSeeInOrder(['First Skill', 'Second Skill'])
        ->assertDontSee('Hidden Project')
        ->assertDontSee('Hidden Company')
        ->assertDontSee('Hidden Skill');
});

it('animates only skills lists that overflow the compact viewport', function () {
    SiteSetting::factory()->create();
    Skill::factory()->published()->count(15)->create();

    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('data-skills-window', false)
        ->assertSee('data-animated', false)
        ->assertSee('data-skills-clone aria-hidden="true"', false)
        ->assertSee('tabindex="0"', false)
        ->assertSee('h-50', false);

    Skill::query()->delete();
    Skill::factory()->published()->count(2)->create();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('data-skills-window', false)
        ->assertDontSee('data-animated', false)
        ->assertDontSee('data-skills-clone', false)
        ->assertDontSee('h-50', false);
});

it('renders related published projects under experiences in their configured order', function () {
    SiteSetting::factory()->create();

    $experience = Experience::factory()->published()->create(['company' => 'Related Company']);
    $secondProject = Project::factory()->published()->create(['title' => 'Second Related Project', 'sort_order' => 20]);
    $firstProject = Project::factory()->published()->create(['title' => 'First Related Project', 'sort_order' => 10]);
    $hiddenProject = Project::factory()->create(['title' => 'Hidden Related Project', 'sort_order' => 0]);

    $experience->projects()->attach([$secondProject->id, $firstProject->id, $hiddenProject->id]);

    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('Related Projects')
        ->assertSee('href="#project-'.$firstProject->id.'"', false)
        ->assertSee('href="#project-'.$secondProject->id.'"', false)
        ->assertSee('data-dialog-open="project-dialog-'.$firstProject->id.'"', false)
        ->assertSee('<dialog id="project-dialog-'.$firstProject->id.'"', false)
        ->assertSee('data-related-project', false)
        ->assertDontSee('Hidden Related Project');

    $renderedExperience = $response->viewData('experiences')->sole();

    expect($renderedExperience->relationLoaded('publishedProjects'))->toBeTrue()
        ->and($renderedExperience->publishedProjects->pluck('title')->all())
        ->toBe(['First Related Project', 'Second Related Project']);
});

it('renders related published experiences in project dialogs', function () {
    SiteSetting::factory()->create();

    $project = Project::factory()->published()->create(['title' => 'Related Project']);
    $publishedExperience = Experience::factory()->published()->create([
        'company' => 'Published Related Company',
        'sort_order' => 20,
    ]);
    $hiddenExperience = Experience::factory()->create([
        'company' => 'Hidden Related Company',
        'sort_order' => 10,
    ]);

    $project->experiences()->attach([$publishedExperience->id, $hiddenExperience->id]);

    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('Related Experience')
        ->assertSee('href="#experience-'.$publishedExperience->id.'"', false)
        ->assertSee('<article id="experience-'.$publishedExperience->id.'"', false)
        ->assertSee('data-related-experience', false)
        ->assertDontSee('Hidden Related Company');

    $renderedProject = $response->viewData('projects')->sole();

    expect($renderedProject->relationLoaded('publishedExperiences'))->toBeTrue()
        ->and($renderedProject->publishedExperiences->pluck('company')->all())
        ->toBe(['Published Related Company']);
});

it('does not render empty relationship sections', function () {
    SiteSetting::factory()->create();
    Project::factory()->published()->create();
    Experience::factory()->published()->create();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Related Projects')
        ->assertDontSee('Related Experience')
        ->assertDontSee('data-related-project', false)
        ->assertDontSee('data-related-experience', false);
});

it('omits project actions when their urls are null', function () {
    SiteSetting::factory()->create();
    Project::factory()->published()->create([
        'title' => 'Private Project',
        'source_url' => null,
        'live_url' => null,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('href="#"', false)
        ->assertDontSee('href=""', false)
        ->assertDontSee('Source code')
        ->assertDontSee('Live project');
});

it('renders configured project actions', function () {
    SiteSetting::factory()->create();
    Project::factory()->published()->create([
        'source_url' => 'https://example.com/source',
        'live_url' => 'https://example.com/live',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('href="https://example.com/source"', false)
        ->assertSee('href="https://example.com/live"', false)
        ->assertSee('Source code')
        ->assertSee('Live project');
});

it('omits unavailable canonical and open graph metadata', function () {
    SiteSetting::factory()->create([
        'site_url' => null,
        'og_image' => null,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('rel="canonical"', false)
        ->assertDontSee('property="og:', false)
        ->assertDontSee('name="twitter:image"', false);
});

it('renders configured canonical and open graph metadata', function () {
    SiteSetting::factory()->create([
        'site_url' => 'https://portfolio.example.com',
        'og_image' => 'site/seo/open-graph.png',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<link rel="canonical" href="https://portfolio.example.com">', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:url" content="https://portfolio.example.com"', false)
        ->assertSee('property="og:image" content="'.asset('storage/site/seo/open-graph.png').'"', false)
        ->assertSee('name="twitter:image" content="'.asset('storage/site/seo/open-graph.png').'"', false);
});

it('renders the configured resume and social links', function () {
    SiteSetting::factory()->create([
        'resume_file' => 'site/resumes/portfolio.pdf',
        'social_links' => [
            [
                'platform' => 'GitHub',
                'label' => 'GitHub profile',
                'url' => 'https://github.com/example',
            ],
            [
                'platform' => 'Email',
                'label' => 'Email me',
                'url' => 'mailto:hello@example.com',
            ],
        ],
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('href="'.route('cv.show').'"', false)
        ->assertDontSee('storage/site/resumes/portfolio.pdf', false)
        ->assertSee('href="https://github.com/example"', false)
        ->assertSee('href="mailto:hello@example.com"', false)
        ->assertSee('GitHub profile')
        ->assertSee('Email me');
});

it('escapes ordinary content and sanitizes approved rich text', function () {
    SiteSetting::factory()->create([
        'hero_heading' => '<Unsafe heading>',
        'about_content' => '<p>Approved about copy</p><script>alert("about")</script>',
    ]);
    Project::factory()->published()->create([
        'summary' => '<Unsafe summary>',
        'body' => '<p>Approved project copy</p><script>alert("project")</script>',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('&lt;Unsafe heading&gt;', false)
        ->assertSee('&lt;Unsafe summary&gt;', false)
        ->assertSee('<p>Approved about copy</p>', false)
        ->assertSee('<p>Approved project copy</p>', false)
        ->assertDontSee('<script>alert("about")</script>', false)
        ->assertDontSee('<script>alert("project")</script>', false);
});
