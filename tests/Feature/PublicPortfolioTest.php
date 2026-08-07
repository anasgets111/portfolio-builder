<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Support\PortfolioMark;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->withoutVite();
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

it('renders validated appearance colors and layout choices', function () {
    SiteSetting::factory()->create([
        'appearance' => [
            ...SiteSetting::DEFAULT_APPEARANCE,
            'colors' => [
                'canvas' => '#fffaf3',
                'panel' => '#f1e8dc',
                'ink' => '#231f20',
                'ink_muted' => '#5f574f',
                'brand' => '#8a2c5f',
                'brand_soft' => '#6d214d',
            ],
            'font' => 'system',
            'color_scheme' => 'light',
            'page_width' => 'wide',
            'corner_style' => 'soft',
            'hero_layout' => 'portrait_left',
            'project_layout' => 'media_left',
            'motion' => 'off',
        ],
    ]);
    Project::factory()->published()->count(2)->create();

    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('data-color-scheme="light"', false)
        ->assertSee('data-motion="off"', false)
        ->assertSee('data-hero-portrait="left"', false)
        ->assertSee('--color-canvas: #fffaf3', false)
        ->assertSee('--portfolio-page-width: 96rem', false)
        ->assertSee('--portfolio-surface-radius: 1rem', false)
        ->assertSee('data-project-media-position="left"', false)
        ->assertDontSee('data-project-media-position="right"', false);
});

it('does not render unsafe appearance values from storage', function () {
    SiteSetting::factory()->create([
        'appearance' => [
            ...SiteSetting::DEFAULT_APPEARANCE,
            'colors' => [
                ...SiteSetting::DEFAULT_APPEARANCE['colors'],
                'canvas' => '#fff";background:url(https://example.com/tracker)',
            ],
            'font' => 'url(https://example.com/font.woff2)',
        ],
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('--color-canvas: #1e1e2e', false)
        ->assertDontSee('example.com/tracker')
        ->assertDontSee('example.com/font.woff2');
});

it('renders generic placeholder images with stable dimensions', function () {
    $this->seed(DatabaseSeeder::class);

    $response = $this->get(route('home'));
    $response->assertSuccessful();

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR);
    $xpath = new DOMXPath($document);

    $portraitUrl = asset('storage/site/profile-images/profile-placeholder.svg');
    /** @var DOMElement|null $portrait */
    $portrait = $xpath->query("//img[@src='{$portraitUrl}']")?->item(0);

    expect($portrait)->toBeInstanceOf(DOMElement::class)
        ->and($portrait?->getAttribute('width'))->toBe('960')
        ->and($portrait?->getAttribute('height'))->toBe('960')
        ->and($portrait?->getAttribute('loading'))->toBe('eager')
        ->and($portrait?->getAttribute('fetchpriority'))->toBe('high');

    $projectUrl = asset('storage/projects/project-placeholder.svg');
    $projectImages = $xpath->query("//img[@src='{$projectUrl}']");

    expect($projectImages)->not->toBeFalse()
        ->and($projectImages?->length)->toBe(2);

    foreach ($projectImages ?? [] as $projectImage) {
        /** @var DOMElement $projectImage */
        expect($projectImage)->toBeInstanceOf(DOMElement::class)
            ->and($projectImage->getAttribute('width'))->toBe('1600')
            ->and($projectImage->getAttribute('height'))->toBe('900')
            ->and($projectImage->getAttribute('loading'))->toBe('lazy')
            ->and($projectImage->getAttribute('alt'))->toContain('Project Title');
    }
});

it('uses the navbar mark as a versioned favicon', function () {
    $siteSetting = SiteSetting::factory()->create([
        'name' => 'Anas Khalifa',
        'appearance' => [
            ...SiteSetting::DEFAULT_APPEARANCE,
            'colors' => [
                ...SiteSetting::DEFAULT_APPEARANCE['colors'],
                'brand' => '#ff79c6',
                'brand_soft' => '#8be9fd',
            ],
        ],
    ]);
    $portfolioMark = new PortfolioMark($siteSetting);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeText('AK')
        ->assertSee(
            'href="'.route('favicon', ['v' => $portfolioMark->fingerprint()]).'" type="image/svg+xml" sizes="any"',
            false,
        )
        ->assertDontSee('apple-touch-icon');

    $favicon = $this->get(route('favicon', ['v' => $portfolioMark->fingerprint()]));

    $favicon
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('>AK</text>', false)
        ->assertSee('fill="#ff79c6"', false)
        ->assertSee('stroke="#8be9fd"', false);
});

it('renders custom uploads from their configured storage paths', function () {
    SiteSetting::factory()->create([
        'profile_image' => 'site/profile-images/custom-profile.webp',
    ]);
    Project::factory()->published()->create([
        'image' => 'projects/custom-project.webp',
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('src="'.asset('storage/site/profile-images/custom-profile.webp').'"', false)
        ->assertSee('src="'.asset('storage/projects/custom-project.webp').'"', false);
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
        ->assertSee('tabindex="0"', false);

    Skill::query()->delete();
    Skill::factory()->published()->count(2)->create();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('data-skills-window', false)
        ->assertDontSee('data-animated', false)
        ->assertDontSee('data-skills-clone', false);
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

it('omits unavailable canonical and social image metadata', function () {
    config(['app.url' => 'not a valid URL']);

    SiteSetting::factory()->create([
        'is_indexable' => true,
        'og_image' => null,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertDontSee('rel="canonical"', false)
        ->assertDontSee('property="og:', false)
        ->assertDontSee('name="twitter:image"', false);
});

it('renders configured canonical and open graph metadata', function () {
    config(['app.url' => 'https://portfolio.example.com/']);

    SiteSetting::factory()->create([
        'name' => 'Portfolio Owner',
        'professional_title' => 'Product Engineer',
        'hero_description' => 'A fallback description from the public portfolio introduction.',
        'site_locale' => 'en-GB',
        'is_indexable' => true,
        'seo_title' => null,
        'seo_description' => null,
        'og_image' => 'site/seo/open-graph.png',
        'social_links' => [
            ['platform' => 'GitHub', 'label' => 'GitHub', 'url' => 'https://github.com/example'],
            ['platform' => 'Email', 'label' => 'Email', 'url' => 'mailto:hello@example.com'],
        ],
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('lang="en-GB"', false)
        ->assertSee('<title>Portfolio Owner</title>', false)
        ->assertSee('name="description" content="A fallback description from the public portfolio introduction."', false)
        ->assertSee('name="robots" content="index, follow"', false)
        ->assertSee('<link rel="canonical" href="https://portfolio.example.com">', false)
        ->assertSee('property="og:title" content="Portfolio Owner"', false)
        ->assertSee('property="og:url" content="https://portfolio.example.com"', false)
        ->assertSee('property="og:site_name" content="Portfolio Owner"', false)
        ->assertSee('property="og:locale" content="en_GB"', false)
        ->assertSee('property="og:image" content="https://portfolio.example.com/storage/site/seo/open-graph.png"', false)
        ->assertSee('name="twitter:image" content="https://portfolio.example.com/storage/site/seo/open-graph.png"', false)
        ->assertSee('name="twitter:image:alt" content="Portfolio Owner portfolio preview"', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('"@type":"ProfilePage"', false)
        ->assertSee('"@type":"Person"', false)
        ->assertSee('"sameAs":["https://github.com/example"]', false);
});

it('protects unfinished portfolios from indexing', function () {
    config(['app.url' => 'https://portfolio.example.com']);

    SiteSetting::factory()->create([
        'name' => 'Draft Portfolio',
        'seo_title' => null,
        'is_indexable' => false,
        'og_image' => null,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertSee('name="twitter:card" content="summary"', false);

    $this->get(route('sitemap'))->assertNotFound();
});

it('publishes a sitemap for indexable portfolios', function () {
    config(['app.url' => 'https://portfolio.example.com/']);
    SiteSetting::factory()->create(['is_indexable' => true]);

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>https://portfolio.example.com</loc>', false);
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
        ->assertSee('My Resume')
        ->assertSee('href="https://github.com/example"', false)
        ->assertSee('href="mailto:hello@example.com"', false)
        ->assertSee('<span class="text-sm font-medium">GitHub</span>', false)
        ->assertSee('<span class="text-sm font-medium">Email</span>', false)
        ->assertDontSee('Send Email')
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
