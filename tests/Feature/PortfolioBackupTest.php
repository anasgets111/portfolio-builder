<?php

use App\Actions\ExportPortfolioBackup;
use App\Actions\RestorePortfolioBackup;
use App\Filament\Pages\PortfolioBackups;
use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function putPortfolioTestImage(string $path): string
{
    $image = UploadedFile::fake()->image(basename($path), 100, 100);
    $contents = $image->getContent();

    Storage::disk('public')->put($path, $contents);

    return $contents;
}

/** @return array{path: string, profile: string, project: string, resume: string} */
function createPortfolioBackupFixture(): array
{
    $profile = putPortfolioTestImage('site/profile-images/profile.jpg');
    $projectImage = putPortfolioTestImage('projects/portfolio.jpg');
    $resume = '%PDF-1.7 portfolio resume';

    Storage::disk('public')->put('site/resumes/resume.pdf', $resume);

    SiteSetting::factory()->create([
        'name' => 'Backup Owner',
        'profile_image' => 'site/profile-images/profile.jpg',
        'resume_file' => 'site/resumes/resume.pdf',
        'og_image' => null,
    ]);

    $project = Project::factory()->published()->create([
        'title' => 'Portable Project',
        'image' => 'projects/portfolio.jpg',
        'source_url' => 'https://github.com/example/portable-project',
        'sort_order' => 10,
    ]);
    $experience = Experience::factory()->published()->create([
        'company' => 'Portable Company',
        'sort_order' => 10,
    ]);
    $experience->projects()->attach($project);

    Skill::factory()->published()->create([
        'name' => 'Portable Skill',
        'sort_order' => 10,
    ]);

    return [
        'path' => (new ExportPortfolioBackup)->handle(),
        'profile' => $profile,
        'project' => $projectImage,
        'resume' => $resume,
    ];
}

it('exports portfolio records relationships and referenced media', function () {
    $fixture = createPortfolioBackupFixture();
    $zip = new ZipArchive;

    expect($zip->open($fixture['path']))->toBeTrue();

    $manifest = json_decode(
        $zip->getFromName('portfolio-backup.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)
        ->format_version->toBe(1)
        ->site_setting->name->toBe('Backup Owner')
        ->projects->toHaveCount(1)
        ->projects->{0}->title->toBe('Portable Project')
        ->experiences->toHaveCount(1)
        ->experiences->{0}->project_keys->toBe([$manifest['projects'][0]['key']])
        ->skills->{0}->name->toBe('Portable Skill')
        ->media->toHaveCount(3)
        ->not->toHaveKey('users');

    expect($zip->getFromName('media/site/profile-images/profile.jpg'))->toBe($fixture['profile'])
        ->and($zip->getFromName('media/projects/portfolio.jpg'))->toBe($fixture['project'])
        ->and($zip->getFromName('media/site/resumes/resume.pdf'))->toBe($fixture['resume']);

    $zip->close();
});

it('restores a backup into fresh portfolio records and media paths', function () {
    $fixture = createPortfolioBackupFixture();

    Experience::query()->delete();
    Project::query()->delete();
    Skill::query()->delete();
    SiteSetting::current()?->update([
        'name' => 'Fresh Install',
        'profile_image' => null,
        'resume_file' => null,
    ]);
    Storage::disk('public')->deleteDirectory('projects');
    Storage::disk('public')->deleteDirectory('site');

    (new RestorePortfolioBackup)->handle($fixture['path']);

    $siteSetting = SiteSetting::current();
    $project = Project::query()->sole();
    $experience = Experience::query()->sole();

    expect($siteSetting)
        ->not->toBeNull()
        ->name->toBe('Backup Owner')
        ->and($siteSetting?->profile_image)->not->toBe('site/profile-images/profile.jpg')
        ->and($siteSetting?->resume_file)->not->toBe('site/resumes/resume.pdf')
        ->and($project->title)->toBe('Portable Project')
        ->and($project->source_url)->toBe('https://github.com/example/portable-project')
        ->and($project->image)->not->toBe('projects/portfolio.jpg')
        ->and($experience->company)->toBe('Portable Company')
        ->and($experience->projects()->sole()->is($project))->toBeTrue()
        ->and(Skill::query()->sole()->name)->toBe('Portable Skill');

    Storage::disk('public')->assertExists([
        $siteSetting->profile_image,
        $siteSetting->resume_file,
        $project->image,
    ]);

    expect(Storage::disk('public')->get($siteSetting->profile_image))->toBe($fixture['profile'])
        ->and(Storage::disk('public')->get($siteSetting->resume_file))->toBe($fixture['resume'])
        ->and(Storage::disk('public')->get($project->image))->toBe($fixture['project']);
});

it('rejects unsupported backup versions without changing current content', function () {
    $siteSetting = SiteSetting::factory()->create(['name' => 'Current Owner']);
    $project = Project::factory()->create(['title' => 'Current Project']);
    $archivePath = Storage::disk('local')->path('unsupported.zip');
    $zip = new ZipArchive;
    $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('portfolio-backup.json', json_encode(['format_version' => 999], JSON_THROW_ON_ERROR));
    $zip->close();

    expect(fn () => (new RestorePortfolioBackup)->handle($archivePath))
        ->toThrow(RuntimeException::class, 'not supported');

    expect($siteSetting->fresh()?->name)->toBe('Current Owner')
        ->and($project->fresh()?->title)->toBe('Current Project');
});

it('rejects unsafe archive paths without changing current content', function () {
    $project = Project::factory()->create(['title' => 'Current Project']);
    $archivePath = Storage::disk('local')->path('unsafe.zip');
    $zip = new ZipArchive;
    $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('portfolio-backup.json', json_encode(['format_version' => 1], JSON_THROW_ON_ERROR));
    $zip->addFromString('media/../../outside.txt', 'unsafe');
    $zip->close();

    expect(fn () => (new RestorePortfolioBackup)->handle($archivePath))
        ->toThrow(RuntimeException::class, 'unsafe path');

    expect($project->fresh()?->title)->toBe('Current Project');
});

it('rejects tampered media without changing current content', function () {
    $fixture = createPortfolioBackupFixture();
    $project = Project::query()->sole();
    $project->update(['title' => 'Current Project']);

    $zip = new ZipArchive;
    $zip->open($fixture['path']);
    $zip->deleteName('media/projects/portfolio.jpg');
    $zip->addFromString('media/projects/portfolio.jpg', 'tampered');
    $zip->close();

    expect(fn () => (new RestorePortfolioBackup)->handle($fixture['path']))
        ->toThrow(RuntimeException::class, 'integrity check');

    expect($project->fresh()?->title)->toBe('Current Project');
});

it('exposes backup actions only inside the authenticated admin panel', function () {
    $administrator = User::factory()->create();
    $unverifiedUser = User::factory()->unverified()->create();

    $this->actingAs($administrator)
        ->get('/admin/portfolio-backups')
        ->assertSuccessful()
        ->assertSee('Portfolio Backups');

    Livewire::actingAs($administrator)
        ->test(PortfolioBackups::class)
        ->assertActionExists('downloadBackup')
        ->assertActionExists('restoreBackup');

    $this->actingAs($unverifiedUser)
        ->get('/admin/portfolio-backups')
        ->assertForbidden();
});

it('restores an uploaded backup through the admin action', function () {
    $fixture = createPortfolioBackupFixture();
    $administrator = User::factory()->create();
    Project::query()->sole()->update(['title' => 'Changed Project']);
    $backup = UploadedFile::fake()
        ->createWithContent('portfolio-backup.zip', file_get_contents($fixture['path']))
        ->mimeType('application/zip');

    Livewire::actingAs($administrator)
        ->test(PortfolioBackups::class)
        ->callAction('restoreBackup', ['backup' => $backup])
        ->assertNotified('Portfolio restored');

    expect(Project::query()->sole()->title)->toBe('Portable Project');
});
