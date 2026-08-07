<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PortfolioMediaSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Support\Facades\Storage;

it('installs the generic portfolio placeholder images', function () {
    Storage::fake('public');

    $this->seed(PortfolioMediaSeeder::class);

    expect(PortfolioMediaSeeder::ORIGINAL_IMAGES)->toBe([
        'site/profile-images/profile-placeholder.svg',
        'projects/project-placeholder.svg',
    ]);

    foreach (PortfolioMediaSeeder::ORIGINAL_IMAGES as $image) {
        Storage::disk('public')->assertExists($image);
    }
});

it('seeds generic site settings and social links', function () {
    $this->seed(DatabaseSeeder::class);

    $siteSetting = SiteSetting::query()->sole();
    expect($siteSetting->name)->toBe('Your Name')
        ->and($siteSetting->professional_title)->toBe('Your Professional Title')
        ->and($siteSetting->hero_heading)->toBe('Hello, I\'m Your Name.')
        ->and($siteSetting->hero_subheading)->toBe('Your Professional Title')
        ->and($siteSetting->seo_title)->toBe('Your Name | Portfolio')
        ->and($siteSetting->site_url)->toBeNull()
        ->and($siteSetting->og_image)->toBe($siteSetting->profile_image)
        ->and($siteSetting->resume_file)->toBeNull()
        ->and($siteSetting->social_links)->toHaveCount(3)
        ->and($siteSetting->social_links[0]['platform'])->toBe('LinkedIn')
        ->and($siteSetting->email)->toBe('hello@example.com')
        ->and($siteSetting->appearance)->toBe(SiteSetting::DEFAULT_APPEARANCE);
});

it('seeds a generic published project with no external links', function () {
    $this->seed(DatabaseSeeder::class);

    $projects = Project::query()->ordered()->get();

    expect($projects->pluck('title')->all())->toBe(['Project Title'])
        ->and($projects->every(fn (Project $project): bool => $project->is_published))->toBeTrue()
        ->and($projects->every(fn (Project $project): bool => $project->published_at === null))->toBeTrue()
        ->and($projects->sole()->source_url)->toBeNull()
        ->and($projects->sole()->live_url)->toBeNull()
        ->and($projects->sole()->image)->toBe('projects/project-placeholder.svg')
        ->and($projects->sole()->technologies)->toBe(['Technology One', 'Technology Two']);
});

it('seeds generic experience and skills placeholders', function () {
    $this->seed(DatabaseSeeder::class);

    $experience = Experience::query()->sole();

    expect($experience->company)->toBe('Company Name')
        ->and($experience->position)->toBe('Job Title')
        ->and($experience->start_date->toDateString())->toBe('2024-01-01')
        ->and($experience->end_date)->toBeNull()
        ->and($experience->technologies)->toBe(['Technology One', 'Technology Two'])
        ->and($experience->sort_order)->toBe(1)
        ->and($experience->is_published)->toBeTrue()
        ->and($experience->projects()->pluck('title')->all())->toBe(['Project Title'])
        ->and(Skill::query()->count())->toBe(3)
        ->and(Skill::query()->published()->count())->toBe(3)
        ->and(Skill::query()->ordered()->pluck('name')->all())->toBe(['Skill One', 'Skill Two', 'Skill Three']);
});

it('can run the production seeders repeatedly without duplicating content', function () {
    $this->seed(DatabaseSeeder::class);

    $originalProjectIds = Project::query()->orderBy('id')->pluck('id')->all();
    $originalExperienceIds = Experience::query()->orderBy('id')->pluck('id')->all();
    $originalRelatedProjectIds = Experience::query()->sole()->projects()->get()->modelKeys();
    $originalSkillIds = Skill::query()->orderBy('id')->pluck('id')->all();
    $originalSiteSettingId = SiteSetting::query()->sole()->getKey();

    $this->seed(DatabaseSeeder::class);

    expect(Project::query()->count())->toBe(1)
        ->and(Project::query()->orderBy('id')->pluck('id')->all())->toBe($originalProjectIds)
        ->and(Experience::query()->count())->toBe(1)
        ->and(Experience::query()->orderBy('id')->pluck('id')->all())->toBe($originalExperienceIds)
        ->and(Experience::query()->sole()->projects()->get()->modelKeys())->toBe($originalRelatedProjectIds)
        ->and($originalRelatedProjectIds)->toHaveCount(1)
        ->and(Skill::query()->count())->toBe(3)
        ->and(Skill::query()->orderBy('id')->pluck('id')->all())->toBe($originalSkillIds)
        ->and(SiteSetting::query()->count())->toBe(1)
        ->and(SiteSetting::query()->sole()->getKey())->toBe($originalSiteSettingId);
});

it('resets site settings to generic defaults when reseeded', function () {
    $this->seed(SiteSettingSeeder::class);

    $siteSetting = SiteSetting::query()->sole();
    $siteSetting->update([
        'og_image' => 'site/seo/custom-open-graph.png',
        'resume_file' => 'site/resumes/custom-cv.pdf',
        'appearance' => [
            ...SiteSetting::DEFAULT_APPEARANCE,
            'font' => 'system',
            'page_width' => 'wide',
        ],
    ]);

    $this->seed(SiteSettingSeeder::class);

    expect($siteSetting->refresh())
        ->og_image->toBe('site/profile-images/profile-placeholder.svg')
        ->resume_file->toBeNull()
        ->appearance->toBe(SiteSetting::DEFAULT_APPEARANCE);
});

it('resets profile and project images to generic placeholders when content is reseeded', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    $siteSetting = SiteSetting::query()->sole();
    $project = Project::query()->where('title', 'Project Title')->firstOrFail();
    $customProfileImage = 'site/profile-images/custom-profile.webp';
    $customProjectImage = 'projects/custom-project.webp';

    Storage::disk('public')->put($customProfileImage, 'custom profile image');
    Storage::disk('public')->put($customProjectImage, 'custom project image');
    $siteSetting->update(['profile_image' => $customProfileImage]);
    $project->update(['image' => $customProjectImage]);

    $this->seed(DatabaseSeeder::class);

    expect($siteSetting->refresh()->profile_image)->toBe('site/profile-images/profile-placeholder.svg')
        ->and($project->refresh()->image)->toBe('projects/project-placeholder.svg');

    Storage::disk('public')->assertExists($customProfileImage);
    Storage::disk('public')->assertExists($customProjectImage);
});
